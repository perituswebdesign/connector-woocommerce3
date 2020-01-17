<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Model\CrossSelling as CrossSellingModel;
use jtl\Connector\Model\CrossSellingItem;
use jtl\Connector\Model\Identity;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Models\CrossSellingGroup;
use JtlWooCommerceConnector\Utilities\SqlHelper;

/**
 * Class CrossSelling
 * @package JtlWooCommerceConnector\Controllers
 */
class CrossSelling extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    /**
     * @param $limit
     * @return array
     */
    protected function pullData($limit)
    {
        $crossSelling = [];

        $result = $this->database->query(SqlHelper::crossSellingPull($limit));

        foreach ($result as $row) {
            if (!isset($row['meta_value']) || !isset($row['meta_key'])) {
                continue;
            }

            $relatedProducts = unserialize($row['meta_value']);
            $type = $row['meta_key'];

            $crossSellingGroup = CrossSellingGroup::getByWooCommerceName($type);

            if (!empty($relatedProducts)) {

                if(!isset($crossSelling[$row['post_id']])){
                    $crossSelling[$row['post_id']] = (new CrossSellingModel());
                }

                $crossSelling[$row['post_id']]
                    ->setId(new Identity($row['post_id']))
                    ->setProductId(new Identity($row['post_id']));

                foreach ($relatedProducts as $product) {
                    $crossSelling[$row['post_id']]->addItem((new CrossSellingItem())
                        ->setCrossSellingGroupId($crossSellingGroup->getId())
                        ->addProductId(new Identity($product)));
                }
            }

            reset($crossSelling);
        }

        return $crossSelling;
    }

    /**
     * @param CrossSellingModel $crossSelling
     * @return CrossSellingModel
     */
    protected function pushData(CrossSellingModel $crossSelling)
    {
        $product = \wc_get_product((int)$crossSelling->getProductId()->getEndpoint());

        if (!$product instanceof \WC_Product) {
            return $crossSelling;
        }

        $crossSelling->getId()->setEndpoint($crossSelling->getProductId()->getEndpoint());

        $crossSellingProducts = $this->getProductIds($crossSelling, CrossSellingGroup::TYPE_CROSS_SELL);
        $upSellProducts = $this->getProductIds($crossSelling, CrossSellingGroup::TYPE_UP_SELL);

        \update_post_meta(
            $product->get_id(),
            '_crosssell_ids',
            $crossSellingProducts,
            \get_post_meta($product->get_id(), '_crosssell_ids', true)
        );

        \update_post_meta(
            $product->get_id(),
            '_upsell_ids',
            $upSellProducts,
            \get_post_meta($product->get_id(), '_upsell_ids', true)
        );

        return $crossSelling;
    }

    /**
     * @param CrossSellingModel $crossSelling
     * @return CrossSellingModel
     */
    protected function deleteData(CrossSellingModel $crossSelling)
    {
        $product = \wc_get_product((int)$crossSelling->getProductId()->getEndpoint());

        if (!$product instanceof \WC_Product) {
            return $crossSelling;
        }

        $crossSellingProducts = $this->getProductIds($crossSelling, CrossSellingGroup::TYPE_CROSS_SELL);
        $upSellProducts = $this->getProductIds($crossSelling, CrossSellingGroup::TYPE_UP_SELL);

        $crossSellIds = !empty($crossSellingProducts) ? array_diff($product->get_cross_sell_ids(), $crossSellingProducts) : [];
        $upSellIds = !empty($upSellProducts) ? array_diff($product->get_upsell_ids(), $upSellProducts) : [];

        \update_post_meta(
            $product->get_id(),
            '_crosssell_ids',
            $crossSellIds,
            \get_post_meta($product->get_id(), '_crosssell_ids', true)
        );

        \update_post_meta(
            $product->get_id(),
            '_upsell_ids',
            $upSellIds,
            \get_post_meta($product->get_id(), '_upsell_ids', true)
        );

        return $crossSelling;
    }

    /**
     * @return int
     */
    protected function getStats()
    {
        $count = 0;

        $result = $this->database->query(SqlHelper::crossSellingPull());

        foreach ($result as $row) {
            if (!isset($row['meta_value'])) {
                continue;
            }

            $relatedProducts = unserialize($row['meta_value']);

            if (!empty($relatedProducts)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param CrossSellingModel $crossSelling
     * @param $crossSellingGroupEndpointId
     * @return array
     */
    private function getProductIds(CrossSellingModel $crossSelling, $crossSellingGroupEndpointId)
    {
        $products = [];

        foreach ($crossSelling->getItems() as $item) {
            foreach ($item->getProductIds() as $productId) {
                if ($crossSellingGroupEndpointId === $item->getCrossSellingGroupId()->getEndpoint()) {
                    $products[] = (int)$productId->getEndpoint();
                }
            }
        }

        return array_unique($products);
    }
}
