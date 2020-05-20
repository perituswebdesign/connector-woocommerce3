<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\WooCommerce;

use jtl\Connector\Model\SpecificValue;
use jtl\Connector\Model\SpecificValueI18n;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\SqlHelper;

/**
 * Class WooCommerceSpecificValue
 * @package JtlWooCommerceConnector\Integrations\Plugins\WooCommerce
 */
class WooCommerceSpecificValue extends AbstractComponent
{
    /**
     * @param string $taxonomy
     * @param SpecificValue $specificValue
     * @param SpecificValueI18n $specificValueI18n
     * @return SpecificValue|null
     */
    public function saveTranslation(string $taxonomy, SpecificValue $specificValue, SpecificValueI18n $specificValueI18n): ?SpecificValue
    {
        $endpointValue = [
            'name' => $specificValueI18n->getValue(),
            'slug' => wc_sanitize_taxonomy_name($specificValueI18n->getValue()),
        ];

        $exValId = $this->getPlugin()->getPluginsManager()->getDatabase()->query(
            SqlHelper::getSpecificValueId(
                $taxonomy,
                $endpointValue['name']
            )
        );

        if (count($exValId) >= 1) {
            if (isset($exValId[0]['term_id'])) {
                $exValId = $exValId[0]['term_id'];
            } else {
                $exValId = null;
            }
        } else {
            $exValId = null;
        }

        $endValId = (int)$specificValue->getId()->getEndpoint();

        if (is_null($exValId) && $endValId === 0) {
            $newTerm = \wp_insert_term(
                $endpointValue['name'],
                $taxonomy
            );

            if ($newTerm instanceof \WP_Error) {
                WpErrorLogger::getInstance()->logError($newTerm);
                return null;
            }

            $termId = $newTerm['term_id'];
        } elseif (is_null($exValId) === false && $endValId !== 0) {
            $termId = \wp_update_term($endValId, $taxonomy, $endpointValue);
        } else {
            $termId = $exValId;
        }

        if ($termId instanceof \WP_Error) {
            WpErrorLogger::getInstance()->logError($termId);
            return null;
        }

        if (is_array($termId)) {
            $termId = $termId['term_id'];
        }

        $specificValue->getId()->setEndpoint($termId);

        return $specificValue;
    }
}