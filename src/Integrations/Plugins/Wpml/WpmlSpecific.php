<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Specific;
use jtl\Connector\Model\SpecificI18n as SpecificI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;

/**
 * Class WpmlSpecific
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlSpecific extends AbstractComponent
{
    /**
     * @return int
     */
    public function getStats(): int
    {
        $wpdb = $this->getPlugin()->getWpDb();
        $wat = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
        $jcls = $wpdb->prefix . 'jtl_connector_link_specific';

        $sql = sprintf("
            SELECT COUNT(at.attribute_id)
            FROM {$wat} at
            LEFT JOIN {$jcls} l ON at.attribute_id = l.endpoint_id
            WHERE l.host_id IS NULL;"
        );

        return (int)$this->getPlugin()->getPluginsManager()->getDatabase()->queryOne($sql);
    }


    /**
     * @param Specific $specific
     * @param string $name
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function getTranslations(Specific $specific,string $name)
    {
        $languages = $this->getPlugin()->getActiveLanguages();

        foreach ($languages as $languageCode => $language) {
            $translatedName = apply_filters('wpml_translate_single_string', $name,
                'WordPress', $name, $languageCode);
            if ($translatedName !== $name) {
                $specific->addI18n(
                    (new SpecificI18nModel)
                        ->setSpecificId($specific->getId())
                        ->setLanguageISO(Language::convert($languageCode))
                        ->setName($translatedName)
                );
            }
        }
    }
}