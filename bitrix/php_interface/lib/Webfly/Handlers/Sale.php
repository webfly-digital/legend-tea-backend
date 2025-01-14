<?

namespace Webfly\Handlers;

use \Bitrix\Main\Loader,
    \Bitrix\Sale\Order,
    \Bitrix\Main\Mail\Event;


Loader::includeModule('sale');
Loader::includeModule('crm');


class Sale
{
    public static function OnSaleOrderEntitySavedHandler(\Bitrix\Main\Event $event)
    {

    }


    public static function OnSaleComponentOrderCreatedHandler($order)
    {
        //AddMessage2Log($order);
    }

    public
    static function OnSaleComponentOrderPropertiesHandler(&$arUserResult, $arRequest, $arParams, $arResult)
    {
        self::changeNameProfileNewOrder($arUserResult, $arRequest, $arParams, $arResult);
    }

    static function OnSaleOrderBeforeSavedHandler(\Bitrix\Main\Event $event)
    {
        $order = $event->getParameter("ENTITY");
        $isNewOrder = !$order->getId();

        if ($isNewOrder) {
            $orderPropertyCollection = $order->getPropertyCollection();


            $companyPhoneProp = $orderPropertyCollection->getItemByOrderPropertyCode("COMPANY_PHONE");
            $contactPhoneProp = $orderPropertyCollection->getItemByOrderPropertyCode("CONTACT_PHONE");
            if ($contactPhoneProp) $contactPhoneValue = $contactPhoneProp->getValue();
            if ($contactPhoneValue && $companyPhoneProp) $companyPhoneProp->setValue($contactPhoneValue);

            $companyEmailProp = $orderPropertyCollection->getItemByOrderPropertyCode("COMPANY_EMAIL");
            $contactEmailProp = $orderPropertyCollection->getItemByOrderPropertyCode("CONTACT_EMAIL");
            if ($contactEmailProp) $contactEmailValue = $contactEmailProp->getValue();
            if ($contactEmailValue && $companyEmailProp) $companyEmailProp->setValue($contactEmailValue);


        }
    }

    /**
     * некоторые поля (IS_PAYER,IS_EMAIL... ) в новом профиле польователя авторматически заоплняется инфой из данных пользователя SaleOrderAjax getValueFromCUser
     * для нового физ лица Название профиля заполнено ФИО пользователя (свойтсво IS_PAYER данные берутся из юзера), на сохранение заказа, изменяем его ФИО на свойства, заполеннеые в заказе
     * так жн для остальных профилй физ лиц делаем название профиля  из ФИО, чтобы были красивые навание компаний
     * **/
    public
    static function changeNameProfileNewOrder(&$arUserResult, $arRequest, $arParams, $arResult)
    {
        $postList = $arRequest->getPostList();
        if ($postList) $postListAr = $postList->toArray();
        if ($postListAr['action']) $actionSave = $postListAr['action'] == 'saveOrderAjax' ? true : false;

        $profileFiz = $arUserResult['PERSON_TYPE_ID'] == B2B_FIZ_PERSON_TYPE_ID ? true : false;

        if ($arUserResult['ORDER_PROP'] && $actionSave && $profileFiz) {
            $filter = ["PERSON_TYPE_ID" => $arUserResult['PERSON_TYPE_ID'], "USER_PROPS" => "Y", "ACTIVE" => "Y", "UTIL" => "N"];
            $orderPropertiesList = \CSaleOrderProps::GetList([], $filter, false, false,
                array("ID", "NAME", "TYPE", "REQUIED", "MULTIPLE", "IS_LOCATION", "PROPS_GROUP_ID", "IS_EMAIL", "IS_PROFILE_NAME", "IS_PAYER", "IS_LOCATION4TAX", "CODE", "SORT")
            );
            $profileNamePropID = false;
            $nameProfileAr = [];
            while ($orderProperty = $orderPropertiesList->GetNext()) {
                if ($orderProperty['IS_PROFILE_NAME'] == 'Y') {
                    $profileNamePropID = $orderProperty['ID'];
                }
                if (in_array($orderProperty['CODE'], B2B_FIZ_FIO_PROP) && !empty($arUserResult['ORDER_PROP'][$orderProperty['ID']])) {
                    $nameProfileAr[] = $arUserResult['ORDER_PROP'][$orderProperty['ID']];
                }
            }
            if ($profileNamePropID > 0 && !empty($nameProfileAr)) {
                $arUserResult['ORDER_PROP'][$profileNamePropID] = implode(' ', $nameProfileAr);
            }
        }
//        if ($arUserResult['ORDER_PROP'] && $actionSave && !$profileFiz) {
//            AddMessage2Log($arUserResult);
//            AddMessage2Log($arRequest);
//        }
    }

//изменяем статус в сделке Б24, если изменён статус заказа
    public
    static function ChangeStatusDealBX24($orderID = false, $newStatus = false)
    {
        if ($newStatus) {
            $dbStatusOrder = \Bitrix\Sale\Internals\StatusTable::GetList([
                'filter' => ['TYPE' => 'O', 'STATUS.LID' => 'ru', 'ID' => $newStatus],
                'runtime' => [
                    new  \Bitrix\Main\Entity\ReferenceField('STATUS', \Bitrix\Sale\StatusLangTable::getEntity(),
                        ['=this.ID' => 'ref.STATUS_ID'],
                        ['join_type' => 'LEFT']),
                ],
                'select' => ['STATUS.DESCRIPTION'],
            ]);
            if ($dbStatusOrder) while ($rsStatusOrder = $dbStatusOrder->fetch()) $dealStatusId = $rsStatusOrder['SALE_INTERNALS_STATUS_STATUS_DESCRIPTION'];
        }


        if ($orderID) {
            $resDeal = \Bitrix\Crm\Binding\OrderEntityTable::getOwnerByOrderId($orderID);
            $dealID = $resDeal["OWNER_ID"];
        }

        if ($dealID && $dealStatusId) {
            $container = \Bitrix\Crm\Service\Container::getInstance();
            $factory = $container->getFactory(\CCrmOwnerType::Deal);
            $item = $factory->getItem($dealID);
            $item->setStageId($dealStatusId);
            $operation = $factory->getUpdateOperation($item)
                ->disableCheckAccess()
                ->disableCheckFields();
            $operation->launch();
        }

    }


    /**
     * перевод заказа в нужный статус,
     * Отправка письма юзеру, что можно оплачивать, синхронизация статусов с Б24
     * @param \Bitrix\Main\Event $event
     */
    public
    static function OnSaleOrderBeforeSaved(\Bitrix\Main\Event $event)
    {
        $order = $event->getParameter("ENTITY");
        $values = $event->getParameter("VALUES");
        if (!$order) return;

        $orderId = $order->getId();
        $statusId = $order->getField('STATUS_ID');

        if ($orderId == 0) {
            if (!empty($order->getField("COMMENTS")) && empty($values['COMMENTS'])) {
                $idProfile = $order->getField("COMMENTS");
                $propertyCollection = $order->getPropertyCollection();

                $db_sales = \CSaleOrderUserPropsValue::GetList([], ["USER_PROPS_ID" => $idProfile]);
                while ($ar_sales = $db_sales->Fetch()) {
                    if (!empty($ar_sales['VALUE'])) {
                        $somePropValue = $propertyCollection->getItemByOrderPropertyId($ar_sales["ORDER_PROPS_ID"]);
                        if (!empty($somePropValue)) {
                            if ($ar_sales['PROP_IS_LOCATION'] == 'Y') {
                                $loc = \Bitrix\Sale\Location\LocationTable::getById($ar_sales['VALUE'])->fetch();
                                if ($loc) $ar_sales['VALUE'] = $loc['CODE'];
                            }
                            $res = $somePropValue->setValue($ar_sales['VALUE']);
                        }
                    }
                }
            }
        }


        if ($orderId) {
            $curOrder = \Bitrix\Sale\Order::load($orderId);
            $curOrderStatusId = $curOrder->getField('STATUS_ID');
        } else {
            $curOrderStatusId = '';
        }

        $siteId = $order->getSiteId();

//        $badStatuses = ['P', 'OO', 'L', 'O', 'I', 'Z'];
//
//        if (in_array($statusId, $badStatuses)){
//            return new \Bitrix\Main\EventResult(
//                \Bitrix\Main\EventResult::ERROR,
//                \Bitrix\Sale\ResultError::create(new \Bitrix\Main\Error("Нельзя перевести заказ в статус {$statusId}"))
//            );
//        }


        if ($statusId != $curOrderStatusId) {//статус заказа изменился
            /**
             * Меняем статус в Б24
             */
            self::ChangeStatusDealBX24($orderId, $statusId);
            /**
             * Отправляем письмо в b2b, что можно оплатить заказ
             */
            if ($statusId == B2B_PAY_STATUS && $siteId == 's3')
                self::SendB2bPay($order);
            /**
             * Меняем статус заказа на нужный
             */
//            $map = [
//                's3' => [
//                    'N' => 'N',//Согласование
//                    'PO' => 'N',//Предоплата до обеспечения
//                    'S' => 'N',//Готов к обеспечению
//
//                    'VO' => 'VR',//В процессе обеспечения
//
//                    'PS' => 'N',//Предоплата до сборки
//
//                    'GS' => 'VR',//Готов к сборке
//
//                    'VS' => 'VS',//В процессе сборки
//                    'OP' => 'VS',//Ожидается проверка
//                    'PT' => 'VS',//Проверяется
//                    'PR' => 'VS',//Проверен, ожидается оплата
//                    'PG' => 'VS',//Проверен готов к отгрузке
//
//                    'VD' => 'VD',//В доставке
//                    'DO' => 'VD',//В доставке, оплата при получении
//                    'OG' => 'VD',//Оплата после отгрузки
//
//                    'F' => 'F',//Выполнен
//                    'D' => 'D',//Отменен
//
//                ],
//                's1' => [
//                    'N' => 'J',//Согласование
//                    'PO' => 'J',//Предоплата до обеспечения
//                    'S' => 'VS',//Готов к обеспечению
//
//                    'VO' => 'VS',//В процессе обеспечения
//
//                    'PS' => 'J',//Предоплата до сборки
//
//                    'GS' => 'VS',//Готов к сборке
//
//                    'VS' => 'VS',//В процессе сборки
//                    'OP' => 'VS',//Ожидается проверка
//                    'PT' => 'VS',//Проверяется
//                    'PR' => 'VS',//Проверен, ожидается оплата
//                    'PG' => 'VS',//Проверен готов к отгрузке
//
//                    'VD' => 'VD',//В доставке
//                    'DO' => 'VD',//В доставке, оплата при получении
//                    'OG' => 'VD',//Оплата после отгрузки
//
//                    'F' => 'F',//Выполнен
//                    'D' => 'D',//Отменен
//                ]
//            ];
//            $statuses = $map[$siteId];
//            if ($statuses) {
//                $newStatus = $statuses[$statusId];
//                if ($newStatus && $statusId && $statusId != $newStatus) {
//                    $order->setField("STATUS_ID", $newStatus);
//                    $event->addResult(
//                        new \Bitrix\Main\EventResult(
//                            \Bitrix\Main\EventResult::SUCCESS, $order
//                        )
//                    );
//                }
//            }
        }
    }

    public
    static function SendB2bPay($order)
    {
        $orderAccountNumber = $order->getField('ACCOUNT_NUMBER');

        $propertyCollection = $order->getPropertyCollection();
        $emailProperty = $propertyCollection->getUserEmail();
        if ($emailProperty) {
            $email = $emailProperty->getValue();
        }
        if ($email && $orderAccountNumber) {
            Event::send(array(
                "EVENT_NAME" => "PAY_ALLOWED",
                "LID" => "s3",
                'MESSAGE_ID' => 257,
                "C_FIELDS" => array(
                    "EMAIL" => $email,
                    "ORDER_ID" => $orderAccountNumber,
                ),));
        }
    }

    public
    static function OnOrderNewSendEmailHandler($orderID, &$eventName, &$arFields)
    {

        if (self::getCreateUserID($orderID))
            self::AddScheduleInfo($orderID, $arFields);
        else
            return false;
    }

    protected
    static function getCreateUserID($orderID)
    {
        $order = Order::load($orderID);
        $userId = $order->getField('CREATED_BY');

        if ($userId == ID_USER_1C) return false;
        return true;
    }

    protected
    static function AddScheduleInfo($orderID, &$arFields)
    {
        $order = Order::load($orderID);
        $shipmentCollection = $order->getShipmentCollection();
        if ($shipmentCollection) {
            foreach ($shipmentCollection as $shipment) {
                if ($shipment->isSystem())
                    continue;
                $deliveryID = $shipment->getDeliveryId();
                if ($deliveryID == OFFICE_PICKUP_DELIVERY) {
                    $arFields['SCHEDULE_INFO'] = "<p><b>Внимание!</b> Время работы склада самовывоза: Вт-Вск, 08:00-16:00. <a href='https://legend-tea.ru/contacts/stores/62570/'>Посмотреть на карте</a></p>";
                }
                if ($deliveryID == RETAIL_PICKUP_DELIVERY) {
                    $arFields['SCHEDULE_INFO'] = "<p><b>Внимание!</b> Время работы точки самовывоза: Вт-Вск, 08:00-16:00. <a href='https://legend-tea.ru/contacts/stores/1011/'>Посмотреть на карте</a></p>";
                }
            }
        }
        if (!$arFields['SCHEDULE_INFO']) $arFields['SCHEDULE_INFO'] = '';
    }

    public
    static function OnSaleBasketBeforeSavedHandler(\Bitrix\Main\Event $event)
    {
        $basket = $event->getParameter("ENTITY");
        if ($basket) {
            foreach ($basket as $basketItem) {
                $parentItem = \CCatalogSku::GetProductInfo($basketItem->getProductId(), SKU_IBLOCK_ID);//у ТП есть товар
                if ($parentItem["ID"]) {
                    $arProduct['ELEM_ID'][$basketItem->getProductId()] = $parentItem["ID"];
                } else {
                    $arProduct['ELEM_ID'][$basketItem->getProductId()] = $basketItem->getProductId();
                }
                $arProduct['PRODUCT_ID'][$basketItem->getProductId()] = $basketItem->getProductId();
                $arProduct['QUANTITY'][$basketItem->getProductId()] = $basketItem->getQuantity();
                $arProduct['PRICE'][$basketItem->getProductId()] = $basketItem->getPrice();
                $arProduct['BASE_PRICE'][$basketItem->getProductId()] = $basketItem->getBasePrice();
                $arProduct['CUSTOM_PRICE'][$basketItem->getProductId()] = $basketItem->getField('CUSTOM_PRICE');
                $arProduct['PRICE_TYPE_ID'][$basketItem->getProductId()] = $basketItem->getField('PRICE_TYPE_ID');
                $arProduct['NAME'][$basketItem->getProductId()] = $basketItem->getField('NAME');
            }
            if ($arProduct) {
                $brendLegendaTea = [];
                $res = \CIBlockElement::getList([], ['ID' => $arProduct['ELEM_ID']], false, false, ['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'PROPERTY_BREND_PROIZVODITELYA']);
                while ($ob = $res->fetch()) {
                    if ($ob['PROPERTY_BREND_PROIZVODITELYA_ENUM_ID'] == '3812') $brendLegendaTea[$ob['ID']] = $ob['ID'];
                    $arSections[$ob['IBLOCK_SECTION_ID']]['ELEM_ID'][$ob['ID']] = $ob['ID'];
                }
            }
            if ($arSections) {
                $dataProductCoffeeMontis = [];
                $dataProductLegendaTea = [];
                foreach ($arSections as $sect => $elem) {
                    $sParents = \CIBlockSection::GetNavChain(CATALOG_IBLOCK_ID, $sect, ['ID', 'NAME', 'DEPTH_LEVEL'], true);
                    $parentSection = current(array_filter($sParents, function ($v) {
                        return $v['DEPTH_LEVEL'] == 1;
                    }));
                    if ($parentSection['ID'] == ID_SECTION_MONTIS) {
                        $dataProductCoffeeMontis[$sect] = $elem['ELEM_ID'];
                    }
                    if ($parentSection['ID'] == ID_SECTION_CHAY) {
                        $similarElem = array_intersect_key($elem['ELEM_ID'], $brendLegendaTea); // сравниваем массив id элементов бренда LegendaTea с массивом элементов из раздела Чай
                        if (!empty($similarElem)) $dataProductLegendaTea[$sect] = $similarElem;
                    }
                }
                $arElem = array_merge($dataProductCoffeeMontis, $dataProductLegendaTea);
                $arElemIDs = [];
                array_walk_recursive($arElem, function ($v, $k) use (&$arElemIDs) {
                    $arElemIDs[$v] = $v;
                });
            }

            $result = \Webfly\Handlers\Sale::getPrice($arProduct['ELEM_ID'], $arProduct['PRICE'], $arProduct['QUANTITY'], $arElemIDs, $basket->getPrice());
            if ($result) {
                $idPrice = $result['PRICE_ID'];
                $arProductUpdatePrice = $result['PRODUCT_NEW_PRICE'];
            }

            foreach ($basket as $basketItem) {
                if (!empty($arProductUpdatePrice[$basketItem->getProductId()]) && $idPrice) {
                    $basketItem->setFields(array(
                        'PRICE' => $arProductUpdatePrice[$basketItem->getProductId()][$idPrice],
                        'PRICE_TYPE_ID' => $idPrice,
                        'CUSTOM_PRICE' => 'Y',
                    ));
                } else {
                    $basketItem->setFields(array(
                        'PRICE' => $basketItem->getBasePrice(),
                        'PRICE_TYPE_ID' => ID_BASE_PRICE_B2B,
                        'CUSTOM_PRICE' => 'N',
                    ));
                }
            }
        }
    }

    public
    static function getPrice($arID, $arPrice, $arQuantity, $arIDNewPrice = [], $basketPrice = 0)
    {
        if (empty($arID) || empty($arPrice) || empty($arQuantity)) return false;

        $sumProductOldPrice = 0;
        foreach ($arID as $key => $elem) {
            if (in_array($elem, $arIDNewPrice)) $arProductNewPrice[] = $key;
            else $sumProductOldPrice += $arPrice[$key] * $arQuantity[$key]; //здесь сумма элементов, у которых не мееняется цена
        }
        $result['SUM_PRODUCT_OLD_PRICE'] = $sumProductOldPrice;

        $idPrice = ID_BASE_PRICE_B2B;
        if ($arProductNewPrice) {
            $arProductPrice = false;
            $arProductUpadatePrice = false;
            $arTypePrice = [ID_BASE_PRICE_B2B, ID_TYPE1_PRICE_B2B, ID_TYPE2_PRICE_B2B, ID_TYPE3_PRICE_B2B];
            $obProductPrices = \Bitrix\Catalog\PriceTable::getList([
                "select" => ["PRODUCT_ID", 'PRICE', 'CATALOG_GROUP_ID'],
                "filter" => ["PRODUCT_ID" => $arProductNewPrice, 'CATALOG_GROUP_ID' => $arTypePrice],
            ]);
            while ($resProductPrices = $obProductPrices->fetch()) {
                $arProductUpadatePrice[$resProductPrices['PRODUCT_ID']][$resProductPrices['CATALOG_GROUP_ID']] = $resProductPrices['PRICE'];
                $arProductPrice[$resProductPrices['CATALOG_GROUP_ID']] += $resProductPrices['PRICE'] * $arQuantity[$resProductPrices['PRODUCT_ID']] + $sumProductOldPrice;
            }
            $arIdPrice = false;
            foreach ($arProductPrice as $key => $item) {
                if ($item >= 0 && $item < 15000) {
                    $arIdPrice[$key] = ID_BASE_PRICE_B2B;
                } else if ($item >= 15000 && $item < 50000) {
                    $arIdPrice[$key] = ID_TYPE1_PRICE_B2B;
                } else if ($item >= 50000 && $item < 100000) {
                    $arIdPrice[$key] = ID_TYPE2_PRICE_B2B;
                } else if ($item >= 100000) $arIdPrice[$key] = ID_TYPE3_PRICE_B2B;
            }
            if($arIdPrice)  $idPrice = $arIdPrice[max($arIdPrice)];

            $result['PRODUCT_PRICE'] = $arProductPrice;
            $result['PRODUCT_NEW_PRICE'] = $arProductUpadatePrice;
        } else {
            if ($basketPrice >= 15000) $idPrice = ID_TYPE1_PRICE_B2B;
            if ($basketPrice >= 50000) $idPrice = ID_TYPE2_PRICE_B2B;
            if ($basketPrice >= 100000) $idPrice = ID_TYPE3_PRICE_B2B;
            $result['PRODUCT_PRICE'][$idPrice + 1] = $basketPrice;
        }
        $result['PRICE_ID'] = $idPrice;
        return $result;
    }


}
