<?php

namespace Webfly\Handlers;

class Catalog
{

    public static function OnSuccessCatalogImport1CHandler($arParams, $arFields)
    {
        \CBitrixComponent::clearComponentCache('bitrix:catalog');
        \CBitrixComponent::clearComponentCache('bitrix:catalog.section');
        \CBitrixComponent::clearComponentCache('bitrix:catalog.item');
    }

    /**
     * @param $productID
     * @param int $quantity
     * @param array $arUserGroups
     * @param string $renewal
     * @param array $arPrices
     * @param false $siteID
     * @param false $arDiscountCoupons
     * @return array[]|bool
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function SetCatalogGroupId($productID, $quantity = 1, $arUserGroups = array(), $renewal = "N", $arPrices = array(), $siteID = false, $arDiscountCoupons = false)
    {
        if (SITE_ID == 's1')
            $priceID = CATALOG_PRICE_ID;
        elseif (SITE_ID == 's3')
            $priceID = B2B_PRICE_ID;

        if (!$priceID) return;

        $baseProductPrice = \Bitrix\Catalog\PriceTable::getList([
            "select" => ["*"],
            "filter" => [
                "=PRODUCT_ID" => $productID,
                "=CATALOG_GROUP_ID" => $priceID
            ],
            'limit' => 1,
        ])->fetch();

        return array(
            'PRICE' => array(
                "ID" => $baseProductPrice['ID'],
                'CATALOG_GROUP_ID' => $baseProductPrice['CATALOG_GROUP_ID'],
                'PRICE' => $baseProductPrice['PRICE'],
                'CURRENCY' => $baseProductPrice['CURRENCY'],
                'ELEMENT_IBLOCK_ID' => $productID,
                'VAT_INCLUDED' => "Y",
            ),
        );
    }
}
