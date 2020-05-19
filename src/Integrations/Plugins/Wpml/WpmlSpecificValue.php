<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\SpecificValue;
use jtl\Connector\Model\SpecificValueI18n as SpecificValueI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class WpmlSpecificValue
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlSpecificValue extends AbstractComponent
{
    /**
     * @param SpecificValue $specificValue
     * @param int $mainSpecificValueId
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function getTranslations(SpecificValue $specificValue, int $mainSpecificValueId)
    {
        $elementType = 'tax_pa_size';
        $trid = (int)$this->getPlugin()->getElementTrid($mainSpecificValueId, $elementType);

        $translations = $this->getPlugin()->getComponent(WpmlTermTranslation::class)->getTranslations($trid, $elementType, true);

        foreach($translations as $languageCode=>$translation){
            $specificValue->addI18n((new SpecificValueI18nModel)
                ->setLanguageISO(Language::convert($languageCode))
                ->setSpecificValueId($specificValue->getId())
                ->setValue($translation->name));
        }
    }
}