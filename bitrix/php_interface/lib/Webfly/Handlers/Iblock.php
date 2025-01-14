<?php

namespace Webfly\Handlers;

use \Bitrix\Main\Loader,
    \Bitrix\Sale\Order,
    \Bitrix\Main\Mail\Event;


Loader::includeModule('sale');
Loader::includeModule('crm');
Loader::includeModule('iblock');

class Iblock
{
    public static function setArtNumberAsCode(&$arFields)
    {
        if ($arFields['IBLOCK_ID'] != CATALOG_IBLOCK_ID) return;

        $artNumberToCode = '';
        $property = $arFields['PROPERTY_VALUES']['1751'];
        if (is_array($property))
            $artNumber = current($property);
        if ($artNumber['VALUE']) {
            $artNumberToCode = $artNumber['VALUE'];
        }
        if ($artNumberToCode) $arFields['CODE'] = $artNumberToCode;
    }


    static function OnAfterIBlockElementAddHandler(&$arFields)
    {
        self::conformityElementAndOrderProp($arFields);
    }

    static function OnAfterIBlockElementUpdateHandler(&$arFields)
    {
        self::conformityElementAndOrderProp($arFields);
    }

    static function conformityElementAndOrderProp($arFields)
    {
        if ($arFields["RESULT"]) {
            if ($arFields['IBLOCK_ID'] == B2B_INFO_ORDER_PROP_GROUP_DELIVERY[$arFields['IBLOCK_ID']]['IBLOCK_ID']) {
                $propId = B2B_INFO_ORDER_PROP_GROUP_DELIVERY[$arFields['IBLOCK_ID']]['IBLOCK_PROP_ID'];
                $value = current($arFields["PROPERTY_VALUES"][$propId])['VALUE'];
                if ($value) {
                    $arUpdate = false;
                    $name = $arFields['NAME'];

                    $db_propVals = \CSaleOrderPropsVariant::GetList([], array("VALUE" => $value));
                    while ($item = $db_propVals->fetch()) {
                        $arUpdate[] = $item;
                    }
                    if (!empty($arUpdate)) {
                        foreach ($arUpdate as $itemUpdate) {
                            $itemUpdate['NAME'] = $name;
                            \CSaleOrderPropsVariant::Update($itemUpdate['ID'], $itemUpdate);
                        }
                    } else {
                        foreach (B2B_INFO_ORDER_PROP_GROUP_DELIVERY[$arFields['IBLOCK_ID']]['PROP_IDS'] as $prop) {
                            $addFields = array(
                                'ORDER_PROPS_ID' => $prop,
                                'NAME' => $name,
                                'VALUE' => $value,
                            );
                            \CSaleOrderPropsVariant::Add($addFields);
                        }
                    }
                }
            }
        }
    }

    public static function setDeliveryName(&$arFields)
    {

        if ($arFields['IBLOCK_ID'] != DELIVERY_IBLOCK_ID) return;

        $property = $arFields['PROPERTY_VALUES']['2498'];
        if (!$property)
            $property = $arFields['PROPERTY_VALUES']['DELIVERY'];

        if (is_array($property))
            $delivery = current($property);


        if ($delivery['VALUE'])
            $deliveryID = $delivery['VALUE'];
        else
            $deliveryID = $property;

        if ($deliveryID) {
            $deliveryName = '';
            $delivery = \Bitrix\Sale\Delivery\Services\Table::getList([
                'filter' => ['ID' => $deliveryID],
                'limit' => 1,
                'select' => ['ID', 'NAME', 'PARENT_ID', 'PARENT_NAME' => 'PARENT.NAME'],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PARENT',
                        '\Bitrix\Sale\Delivery\Services\Table',
                        array(
                            '=this.PARENT_ID' => 'ref.ID',
                        )
                    ),
                ]
            ])->fetch();


            if ($delivery['PARENT_NAME'])
                $deliveryName = $delivery['PARENT_NAME'] . ": " . $delivery['NAME'];
            else
                $deliveryName = $delivery['NAME'];


            if ($deliveryName)
                $arFields['NAME'] = $deliveryName;

        }

    }

}

