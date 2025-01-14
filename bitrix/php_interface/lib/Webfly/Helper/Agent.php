<?php

namespace Webfly\Helper;

use Bitrix\Main\Loader;


Loader::includeModule('iblock');
Loader::includeModule('sender');

class Agent
{

    public static function createAutoTemplateLetterPrice()
    {
        $letterID = 87;

        $letter = new  \Bitrix\Sender\Entity\Letter($letterID);
        $configurationId = $letter->getMessage()->getConfiguration()->getId();
        if (!$configurationId) return null;
        $result = $letter->getMessage()->copyConfiguration($configurationId);
        if (!$result->isSuccess() || !$result->getId()) return null;

        $data = array(
            'CAMPAIGN_ID' => $letter->get('CAMPAIGN_ID'),
            'MESSAGE_CODE' => $letter->get('MESSAGE_CODE'),
            'MESSAGE_ID' => $result->getId(),
            'REITERATE' => $letter->get('REITERATE'),
            'TEMPLATE_TYPE' => $letter->get('TEMPLATE_TYPE'),
            'TEMPLATE_ID' => $letter->get('TEMPLATE_ID'),
            'CREATED_BY' => $letter->getUser()->getId(),
            'UPDATED_BY' => $letter->getUser()->getId(),
            'IS_TRIGGER' => $letter->get('IS_TRIGGER'),
            'TITLE' => 'Прайс. Автоматическая рассылка. Оптовый отдел. Создана ' . date("d.m.y"),
            'SEGMENTS_INCLUDE' => $letter->get('SEGMENTS_INCLUDE'),
            'SEGMENTS_EXCLUDE' => $letter->get('SEGMENTS_EXCLUDE'),
        );

        $instance = \Bitrix\Sender\Entity\Letter::create()->mergeData($data);
        $instance->save();
        $ID = $instance->getId();
        $instance->getState()->send();//отправляем созданный шаблон

        $letter->getErrorCollection()->add($instance->getErrors());
        if (!is_null($letter->getMessage()->getConfiguration()->get('MESSAGE'))) {
            \Bitrix\Sender\FileTable::syncFiles(
                $instance->getId(),
                0,
                $letter->getMessage()->getConfiguration()->get('MESSAGE')
            );
        }

        return '\Webfly\Helper\Agent::createAutoTemplateLetterPrice();';
    }

    public static function createAutoTemplateLetter()
    {
        $letterID = 26;

        $letter = new  \Bitrix\Sender\Entity\Letter($letterID);
        $configurationId = $letter->getMessage()->getConfiguration()->getId();
        if (!$configurationId) return null;
        $result = $letter->getMessage()->copyConfiguration($configurationId);
        if (!$result->isSuccess() || !$result->getId()) return null;

        $data = array(
            'CAMPAIGN_ID' => $letter->get('CAMPAIGN_ID'),
            'MESSAGE_CODE' => $letter->get('MESSAGE_CODE'),
            'MESSAGE_ID' => $result->getId(),
            'REITERATE' => $letter->get('REITERATE'),
            'TEMPLATE_TYPE' => $letter->get('TEMPLATE_TYPE'),
            'TEMPLATE_ID' => $letter->get('TEMPLATE_ID'),
            'CREATED_BY' => $letter->getUser()->getId(),
            'UPDATED_BY' => $letter->getUser()->getId(),
            'IS_TRIGGER' => $letter->get('IS_TRIGGER'),
            'TITLE' => 'Автоматическая рассылка. Оптовый отдел. Создана ' . date("d.m.y"),
            'SEGMENTS_INCLUDE' => $letter->get('SEGMENTS_INCLUDE'),
            'SEGMENTS_EXCLUDE' => $letter->get('SEGMENTS_EXCLUDE'),
        );

        $instance = \Bitrix\Sender\Entity\Letter::create()->mergeData($data);
        $instance->save();
        $letter->getErrorCollection()->add($instance->getErrors());

        if (!is_null($letter->getMessage()->getConfiguration()->get('MESSAGE'))) {
            \Bitrix\Sender\FileTable::syncFiles(
                $instance->getId(),
                0,
                $letter->getMessage()->getConfiguration()->get('MESSAGE')
            );
        }

        return '\Webfly\Helper\Agent::createAutoTemplateLetter();';
    }


    public static function fullMinPrice()
    {
        $arProductSKU = [];
        $arProduct = [];
        $resElemSKU = \CIBlockElement::GetList(array('CATALOG_PRICE_' . CATALOG_PRICE_ID => 'DESC'), array("IBLOCK_ID" => SKU_IBLOCK_ID, "ACTIVE" => "Y", '!CATALOG_PRICE_' . CATALOG_PRICE_ID => false), false, false, array("ID", "IBLOCK_ID", 'CATALOG_PRICE_' . CATALOG_PRICE_ID, 'PROPERTY_CML2_LINK'));
        while ($obElemSKU = $resElemSKU->Fetch()) {
            $arProductSKU[$obElemSKU['PROPERTY_CML2_LINK_VALUE']] = $obElemSKU['CATALOG_PRICE_' . CATALOG_PRICE_ID];
        }
        $resElem = \CIBlockElement::GetList([], array("IBLOCK_ID" => CATALOG_IBLOCK_ID, "ACTIVE" => "Y", '!CATALOG_PRICE_' . CATALOG_PRICE_ID => false), false, false, array("ID", "IBLOCK_ID", 'CATALOG_PRICE_' . CATALOG_PRICE_ID,));
        while ($obElem = $resElem->Fetch()) {
            $arProduct[$obElem['ID']] = $obElem['CATALOG_PRICE_' . CATALOG_PRICE_ID];
        }
        $allProduct = $arProductSKU + $arProduct;
        if ($allProduct) {
            foreach ($allProduct as $key => $product) {
                \CIBlockElement::SetPropertyValuesEx($key, false, ["MIN_PRICE" => $product]);
            }
        }
        return '\Webfly\Helper\Agent::fullMinPrice();';
    }

    public static function agentGeneratePriceList($type)
    {
        $example = new \Webfly\Generate\GeneratePriceList();
        $example = $example->agent($type);

        return '\Webfly\Helper\Agent::agentGeneratePriceList("' . $type . '");';
    }

}

?>