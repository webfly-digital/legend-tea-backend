<?php

namespace Webfly\Helper;

use CSaleOrderUserProps;

\Bitrix\Main\Loader::IncludeModule('sale');
\Bitrix\Main\Loader::IncludeModule('catalog');
\Bitrix\Main\Loader::IncludeModule('main');
\Bitrix\Main\Loader::IncludeModule('iblock');

class Functions
{

    static function getFirstProfileBuyer(int $userId)
    {
        \Bitrix\Main\Loader::includeModule('sale');

        global $USER;
        $dbProfiles = CSaleOrderUserProps::GetList(
            ["ID" => "ASC"],
            ["USER_ID" => $userId,], false, ['nTopCount' => 1],
            ["ID", "NAME", "USER_ID"]
        );

        $arItems = [];
        while ($profile = $dbProfiles->Fetch()) {
            $arItems = $profile;
        }

        return $arItems;
    }


    public static function sendMailNewOrder($orderID) //https://webfly.bitrix24.ru/workgroups/group/127/tasks/task/view/27035/
    {
        if (empty($orderID)) return 'empty OrderId';

        $order = \Bitrix\Sale\Order::load($orderID);

        $propertyCollection = $order->getPropertyCollection();
        $emailPropValue = $propertyCollection->getUserEmail();
        if ($emailPropValue) $email = $emailPropValue->getValue();
        $namePropValue = $propertyCollection->getPayerName();
        if ($namePropValue) $name = $namePropValue->getValue();

        $basket = $order->getBasket();
        if ($basket) $basketItems = $basket->getListOfFormatText();

        if (!empty($email)) {
            $saleEmail = \Bitrix\Main\Config\Option::get("sale", "order_email");
            $rsSites = \CSite::GetByID($order->getSiteId());
            $arSite = $rsSites->Fetch();


            $res = \Bitrix\Main\Mail\Event::send(array(
                "EVENT_NAME" => "SALE_NEW_ORDER",
                "LID" => $order->getSiteId(),
                "C_FIELDS" => array(
                    "ORDER_ID" => $order->getField("ACCOUNT_NUMBER"),
                    "ORDER_REAL_ID" => $orderID,
                    'ORDER_ACCOUNT_NUMBER_ENCODE' => $orderID,
                    'ORDER_DATE' => $order->getField("DATE_INSERT"),
                    'ORDER_USER' => $name,
                    'EMAIL' => $email,
                    'ORDER_LIST' => $basketItems,
                    'BCC' => $saleEmail,
                    'SALE_EMAIL' => $saleEmail,
                    'PRICE' => \SaleFormatCurrency($order->getPrice(), $order->getCurrency()),
                    'DELIVERY_PRICE' => $order->getDeliveryPrice(),
                    'ORDER_PUBLIC_URL' => '',
                    'SITE_NAME' => $arSite['SITE_NAME'],
                    'SERVER_NAME' => $arSite['SERVER_NAME'],
                    'DEFAULT_EMAIL_FROM' => $arSite['EMAIL']
                ),));
            if ($res->isSuccess()) return 'Success';
        }

        return 'fail';
    }

    public static function countElementRoz(&$arSections)
    {
        foreach ($arSections as $key => $sect) {
            if ($sect['DEPTH_LEVEL'] == 1) $arMainSectId[] = $key;
            $arSections[$key]['CNT'] = '0';
        }

        $mainFilter = [
            'IBLOCK_ID' => 93,
            'SECTION_ACTIVE' => 'Y',
            'SECTION_GLOBAL_ACTIVE' => 'Y',
            'ACTIVE' => 'Y',
            'PROPERTY_ROZNICHNYY_SEGMENT_VALUE' => 'Да',
        ];


        $resSect = \CIBlockElement::getList([], $mainFilter, ['IBLOCK_SECTION_ID'], false);
        while ($obSect = $resSect->fetch()) {
            $arSections[$obSect['IBLOCK_SECTION_ID']]['CNT'] = $obSect['CNT'];
        }

        foreach ($arMainSectId as $sectId) {
            unset($resSect);
            unset($obSect);

            $filter = [
                'INCLUDE_SUBSECTIONS' => 'Y',
                'SECTION_ID' => [$sectId],
            ];

            $resSect = \CIBlockElement::getList(['sort' => 'asc'], array_merge($mainFilter, $filter), ['SECTION_ID'], false);
            while ($obSect = $resSect->fetch()) {
                $arSections[$sectId]['CNT'] = $obSect['CNT'];
            }
        }
    }

    public static function checkElementRoz($idItem)
    {
        if (strpos($idItem, 'S') !== false) {//  при поиске раздел содержит S
            $sectionId = str_replace('S', '', $idItem);

            $mainFilter = [
                'SECTION_ACTIVE' => 'Y',
                'SECTION_GLOBAL_ACTIVE' => 'Y',
                'ACTIVE' => 'Y',
                'IBLOCK_SECTION_ID' => $sectionId,
                'PROPERTY_ROZNICHNYY_SEGMENT_VALUE' => 'Да',
            ];

            $resSect = \CIBlockElement::getList([], $mainFilter, ['PROPERTY_ROZNICHNYY_SEGMENT'], false);
            while ($obSect = $resSect->fetch()) {
                if ($obSect['CNT'] > 0) return true;
            }
            return false;

        } else {

            $mainFilter = [
                'SECTION_ACTIVE' => 'Y',
                'SECTION_GLOBAL_ACTIVE' => 'Y',
                'ACTIVE' => 'Y',
                'ID' => $idItem
            ];

            $resElem = \CIBlockElement::getList([], $mainFilter, false, false, ['PROPERTY_ROZNICHNYY_SEGMENT', 'IBLOCK_ID']);
            while ($obElem = $resElem->fetch()) {
                if ($obElem['PROPERTY_ROZNICHNYY_SEGMENT_VALUE'] == 'Да') return true;
                else return false;

            }
        }

        return true;
    }

    public static function checkListElementsSite($ids)
    {

        $resProduct = \CCatalogSKU::getProductList($ids, 94);
        if (!empty($resProduct)) {
            foreach ($ids as $keyBasket => $productID) {
                if ($resProduct[$productID]) {
                    $ids[$keyBasket] = $resProduct[$productID]['ID'];
                }
            }
        }


        $mainFilter = [
            'ACTIVE' => 'Y',
            'ID' => $ids,
        ];
        $arDelete = [];
        $resElem = \CIBlockElement::getList([], $mainFilter, false, false, ['PROPERTY_ROZNICHNYY_SEGMENT', 'PROPERTY_OPTOVYY_SEGMENT', 'ID']);
        while ($obElem = $resElem->fetch()) {
            if (SITE_ID == 's1' && $obElem['PROPERTY_ROZNICHNYY_SEGMENT_VALUE'] != 'Да') {
                $keyBasket = array_search($obElem['ID'], $ids);
                $arDelete[$keyBasket] = $obElem['ID'];
            }
            if (SITE_ID == 's3' && $obElem['PROPERTY_OPTOVYY_SEGMENT_VALUE'] != 'Да') {
                $keyBasket = array_search($obElem['ID'], $ids);
                $arDelete[$keyBasket] = $obElem['ID'];
            }
        }

        return $arDelete;
    }


    public
    static function changeFilterRoz(&$filter)
    {
        $filter['PROPERTY_ROZNICHNYY_SEGMENT_VALUE'] = 'да';
    }

    public
    static function catalogOffersRoz($arItem)
    {
        if ($arItem['OFFERS']) {
            foreach ($arItem['OFFERS'] as $keyOffer => $arOffer) {
                if ($arOffer['PROPERTIES']['ROZNICHNYY_SEGMENT']['VALUE'] != "Да") {
                    unset($arItem['OFFERS'][$keyOffer]);
                }
            }
        }
        if ($arItem['JS_OFFERS']) {
            foreach ($arItem['JS_OFFERS'] as $keyOffer => $arOffer) {

                if ($arOffer['DISPLAY_PROPERTIES']) {
                    foreach ($arOffer['DISPLAY_PROPERTIES'] as $offer) {
                        if ($offer['CODE'] == 'ROZNICHNYY_SEGMENT' && $offer['VALUE'] != "Да") {
                            unset($arItem['JS_OFFERS'][$keyOffer]);
                        }
                    }
                } else {
                    unset($arItem['JS_OFFERS'][$keyOffer]);
                }

            }
        }
        return $arItem;
    }

    public
    static function catalogOffersOpt($arItem)
    {
        if ($arItem['OFFERS']) {
            foreach ($arItem['OFFERS'] as $keyOffer => $arOffer) {
                if ($arOffer['PROPERTIES']['OPTOVYY_SEGMENT']['VALUE'] != "Да") {
                    unset($arItem['OFFERS'][$keyOffer]);
                }
            }
        }
        if ($arItem['JS_OFFERS']) {
            foreach ($arItem['JS_OFFERS'] as $keyOffer => $arOffer) {

                if ($arOffer['DISPLAY_PROPERTIES']) {
                    foreach ($arOffer['DISPLAY_PROPERTIES'] as $offer) {
                        if ($offer['CODE'] == 'OPTOVYY_SEGMENT' && $offer['VALUE'] != "Да") {
                            unset($arItem['JS_OFFERS'][$keyOffer]);
                        }
                    }
                } else {
                    unset($arItem['JS_OFFERS'][$keyOffer]);
                }

            }
        }
        return $arItem;
    }


    public
    static function changeFilterOpt(&$filter)
    {
        $filter['PROPERTY_OPTOVYY_SEGMENT_VALUE'] = 'Да';
    }

    public static function countElementOpt(&$arSections)
    {
        foreach ($arSections as $key => $sect) {
            if ($sect['DEPTH_LEVEL'] == 1) $arMainSectId[] = $key;
            $arSections[$key]['CNT'] = '0';
        }
        $mainFilter = [
            'IBLOCK_ID' => 93,
            'SECTION_ACTIVE' => 'Y',
            'SECTION_GLOBAL_ACTIVE' => 'Y',
            'ACTIVE' => 'Y',
            'PROPERTY_OPTOVYY_SEGMENT_VALUE' => 'Да',
        ];

        $resSect = \CIBlockElement::getList([], $mainFilter, ['IBLOCK_SECTION_ID'], false);
        while ($obSect = $resSect->fetch()) {
            if ($arSections[$obSect['IBLOCK_SECTION_ID']]) $arSections[$obSect['IBLOCK_SECTION_ID']]['CNT'] = $obSect['CNT'];
        }

        foreach ($arMainSectId as $sectId) {
            unset($resSect);
            unset($obSect);

            $filter = [
                'INCLUDE_SUBSECTIONS' => 'Y',
                'SECTION_ID' => [$sectId],
            ];

            $resSect = \CIBlockElement::getList(['sort' => 'asc'], array_merge($mainFilter, $filter), ['SECTION_ID'], false);
            while ($obSect = $resSect->fetch()) {
                $arSections[$sectId]['CNT'] = $obSect['CNT'];
            }
        }
    }

    public static function checkPurchasedProduct($idProduct) // для отзывов проверяем что товар данный юзер купил
    {
        if (empty($idProduct)) return false;
        global $USER;
        $userID = $USER->GetID();

        \Bitrix\Main\Loader::includeModule('sale');


        $arOffers = \CCatalogSKU::getOffersList($idProduct);
        if (!empty($arOffers)) $idProduct = array_keys($arOffers[$idProduct]);

        $dbResItems = \Bitrix\Sale\Basket::getList([
            'select' => [
                'PRODUCT_ID', 'ORDER-' => 'ORDER'
            ],
            'filter' => [
                'PRODUCT_ID' => $idProduct, 'ORDER-USER_ID' => $userID, 'ORDER-LID' => 's1', 'ORDER-STATUS_ID' => 'F'
            ],
            'runtime' => [new \Bitrix\Main\Entity\ReferenceField('ORDER', '\Bitrix\Sale\OrderTable', array('=this.ORDER_ID' => 'ref.ID'), array('join_type' => 'LEFT')),]
        ]);

        while ($item = $dbResItems->fetch()) {
            return true;
        }

        return false;

    }


}