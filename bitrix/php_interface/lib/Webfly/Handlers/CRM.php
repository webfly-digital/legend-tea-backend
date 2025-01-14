<?php

namespace Webfly\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Sale\Order;

Loader::includeModule('sale');
Loader::includeModule('iblock');
Loader::includeModule('crm');
Loader::includeModule('catalog');

class CRM
{

    public static function OnAfterCrmDealAddHandler(&$arFields)
    {
        $order = Order::load($arFields['ORDER_ID']);

        self::changeSelectedAddressReq($arFields);
        self::setONumber($arFields);
        self::setDelivery($arFields);
        self::setStoreMap($arFields);
        self::changeTypeCompanyAndContact($arFields, $order);
        self::addContactDeal($arFields);
    }

    protected static function changeSelectedAddressReq(&$arFields)
    {

        if ($arFields['ORDER_ID']) {
            $order = \Bitrix\Sale\Order::load($arFields['ORDER_ID']);
            $orderSite = $order->getSiteId();

            if ($order->getField("DELIVERY_ID") == 82 || $order->getField("DELIVERY_ID") == 89) {
                $propertyCollection = $order->getPropertyCollection();
                $addrProp = $propertyCollection->getAddress();
                if ($addrProp) $addrPropValue = $addrProp->getValue();

                $zipProp = $propertyCollection->getDeliveryLocationZip();
                if ($zipProp) $zipPropValue = $zipProp->getValue();

                $deliveryName = $order->getField("DELIVERY_ID") == 82 ? 'СДЭК' : "Почта России";
                if ($addrPropValue) {
                    $comment = 'ВНИМАНИЕ! Обратите внимание, при отправке заказа в службу доставки ' . $deliveryName . ' через приложение, нужно указать следующий адрес: ' . $addrPropValue;


                    if ($order->getField("DELIVERY_ID") == 89 && !empty($zipPropValue)) {
                        $comment .= ' Индекс ' . $zipPropValue;
                    }
                    $resId = \Bitrix\Crm\Timeline\CommentEntry::create(
                        array(
                            'TEXT' => $comment,
                            'SETTINGS' => array(),
                            'AUTHOR_ID' => 1,
                            'BINDINGS' => array(array('ENTITY_TYPE_ID' => \CCrmOwnerType::Deal, 'ENTITY_ID' => $arFields['ID'], 'IS_FIXED' => 'Y'))
                        ));
                }

                if ($order->getField("DELIVERY_ID") == 82) {//если ПВЗ СДЭК
                    if ($arFields['CONTACT_ID']) $contactId = $arFields['CONTACT_ID'];
                    else {
                        if ($arFields['COMPANY_ID']) {
                            $contact = $order->getContactCompanyCollection()->getPrimaryContact();
                            if ($contact) $contactId = $contact->getField('ENTITY_ID');
                        }
                    }

                    if ($contactId && $addrPropValue) {
                        $dbResult = \Bitrix\Crm\AddressTable::getList([
                            'filter' => ['REQ.ENTITY_ID' => $contactId, '%ADDRESS_1' => $addrPropValue],
                            'runtime' => [
                                new \Bitrix\Main\Entity\ReferenceField('REQ', \Bitrix\Crm\RequisiteTable::class, ['=this.ENTITY_ID' => 'ref.ID']),
                            ],
                            'select' => ['PRESET_ID' => 'REQ.PRESET_ID', 'ID' => 'REQ.ID'],
                        ]);
                        if ($dbResult) {
                            $dataReq = $dbResult->fetch();
                        }
                    }
                    if ($dataReq) {
                        $requisite = \Bitrix\Crm\EntityRequisite::getSingleInstance();
                        $settings = $requisite->loadSettings($dataReq['PRESET_ID'], $contactId);
                        $settings['REQUISITE_ID_SELECTED'] = $dataReq['ID'];
                        $res = $requisite->saveSettings($dataReq['PRESET_ID'], $contactId, $settings);
                    }
                }
            }
        }
    }

    protected static function setONumber($arFields)
    {
        if (!$arFields['ID'] || !$arFields['ORDER_ID']) return;

        $order = Order::load($arFields['ORDER_ID']);
        if ($order) {
            $oNumber = $order->getField('ACCOUNT_NUMBER');
            $deal = new \CCrmDeal(false);
            $update = [
                'UF_CRM_1706088277' => $oNumber,
                'UF_CRM_1706088935' => $arFields['ORDER_ID']
            ];
            $deal->Update($arFields['ID'], $update, true, true, array('DISABLE_USER_FIELD_CHECK' => true));
        }

    }

    protected static function setDelivery($arFields)
    {
        if (!$arFields['ID'] || !$arFields['ORDER_ID']) return;

        $order = Order::load($arFields['ORDER_ID']);
        if (!$order) return;

        $deliveryId = $order->getField('DELIVERY_ID');
        if (!$deliveryId) return;

        $iblockItemId = 0;
        $item = \CIBlockElement::getList([], ['IBLOCK_ID' => DELIVERY_IBLOCK_ID, 'PROPERTY_DELIVERY' => $deliveryId], false, false, ['ID'])->fetch();
        if ($item['ID']) {
            $iblockItemId = $item['ID'];
        } else {
            $el = new \CIBlockElement();
            $iblockItemId = $el->Add(['NAME' => 'Delivery', 'IBLOCK_ID' => DELIVERY_IBLOCK_ID, 'PROPERTY_VALUES' => ['DELIVERY' => $deliveryId]]);
        }

        if ($iblockItemId) {
            $deal = new \CCrmDeal(false);
            $update = [
                'UF_CRM_1706097219' => $iblockItemId
            ];
            $deal->Update($arFields['ID'], $update, true, true, array('DISABLE_USER_FIELD_CHECK' => true));
        }
    }

    protected static function setStoreMap($arFields)
    {
        if (!$arFields['ID'] || !$arFields['ORDER_ID']) return;

        $order = Order::load($arFields['ORDER_ID']);
        if (!$order) return;

        $deliveryId = $order->getField('DELIVERY_ID');
        if ($deliveryId != PICKUP_DELIVERY_ID) return;

        $shipmentCollection = $order->getShipmentCollection();
        if (!$shipmentCollection) return;

        foreach ($shipmentCollection as $shipment) {
            if ($shipment->getDeliveryId() == PICKUP_DELIVERY_ID) {
                $storeId = $shipment->getStoreId();
                break;
            }
        }
        if (!$storeId) return;

        $link = '';
        $store = \Bitrix\Catalog\StoreTable::getById($storeId)->fetch();
        if ($store["GPS_N"] && $store["GPS_S"])
            $link = "https://yandex.ru/maps/?pt=" . $store["GPS_S"] . "," . $store["GPS_N"] . "&z=17";

        if (!$link) return;

        $deal = new \CCrmDeal(false);
        $update = ['UF_CRM_1706164389' => $link];
        $deal->Update($arFields['ID'], $update, true, true, array('DISABLE_USER_FIELD_CHECK' => true));

    }

    public static function changeTypeCompanyAndContact(&$arFields, $order) //изменяем тип комапнии и контакта на Оптовый клиент
    {
        if ($arFields['SOURCE_ID'] == 'UC_NXQ5YR') { // источник сделки  Б2Б
            if ($arFields['CONTACT_ID']) {
                $contactTypeB2B = 1;
                $contactResult = \CCrmContact::GetListEx([], ['ID' => $arFields['CONTACT_ID'], 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'TYPE_ID']);
                while ($contact = $contactResult->fetch()) {
                    if ($contact['TYPE_ID'] != $contactTypeB2B) {
                        $arUpdFields = ["TYPE_ID" => $contactTypeB2B];
                        $oContact = new \CCrmContact(false);
                        $res = $oContact->Update($arFields['CONTACT_ID'], $arUpdFields, true, true, []);
                    }
                }
            }
            if ($arFields['COMPANY_ID']) {
                $orderPersonType = $order->getPersonTypeId() == B2B_UR_PERSON_TYPE_ID ? 308 : 309;
                $companyTypeB2B = 'CUSTOMER';
                $companyResult = \CCrmCompany::GetListEx([], ['ID' => $arFields['COMPANY_ID'], 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID', 'COMPANY_TYPE', 'UF_CRM_1724146192']);
                while ($company = $companyResult->fetch()) {
                    if ($company['COMPANY_TYPE'] != $companyTypeB2B || $orderPersonType != $company['UF_CRM_1724146192']) {
                        $arUpdFields = ["COMPANY_TYPE" => $companyTypeB2B, 'UF_CRM_1724146192' => $orderPersonType];
                        $oCompany = new \CCrmCompany(false);
                        $oCompany->Update($arFields['COMPANY_ID'], $arUpdFields, true, true, []);
                    }
                }
            }
        }
    }

    public static function OnBeforeCrmDealAddHandler(&$arFields)
    {
        self::changeDealTitle($arFields);
        self::changePropOrder($arFields);
    }

    protected static function addContactDeal(&$arFields) //добавляем контакт в сделку, который в заказе = пользователь. добавляем также к контакту компании
    {
        if ($arFields['ORDER_ID'] && $arFields['COMPANY_ID']) {
            $order = \Bitrix\Sale\Order::getList(['select' => ['ID', 'LID', 'EXTERNAL_ORDER', 'PERSON_TYPE_ID', 'ACCOUNT_NUMBER', 'USER_ID', 'UF_CONTACT_GUID' => 'USER.UF_CONTACT_GUID'], 'limit' => 1, 'filter' => ['=ID' => $arFields['ORDER_ID']], 'runtime' => [
                new \Bitrix\Main\Entity\ReferenceField('USER', '\Bitrix\Main\UserTable', array('=this.USER_ID' => 'ref.ID'), array('join_type' => 'LEFT')),
            ]])->fetch();

            if ($order['UF_CONTACT_GUID']) {
                $resGuidContact = \Bitrix\Crm\ContactTable::getList([
                    'filter' => ['UF_CRM_1723022437' => $order['UF_CONTACT_GUID']],
                    'select' => ['ID'],
                    'limit' => 1
                ]);

                $allCompanyIDs = [];
                while ($obContact = $resGuidContact->fetch()) {
                    $contactID = $obContact['ID'];
                    $allCompanyIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($obContact['ID']);
                }

                if ($contactID) {
                    \Bitrix\Crm\Binding\DealContactTable::bindContacts($arFields['ID'], [$contactID]);

                    if (!in_array($arFields['COMPANY_ID'], $allCompanyIDs)) {
                        $contactUpd = new \CCrmContact(false);
                        $allCompanyIDs[] = $arFields['COMPANY_ID'];
                        $arFieldsUpd = ['COMPANY_IDS' => $allCompanyIDs];
                        $res = $contactUpd->Update($contactID, $arFieldsUpd);
                    }
                }

            }
        }
    }

    protected static function changeDealTitle(&$arFields)
    {
        if ($arFields['ORDER_ID']) {
            $order = Order::getList(['select' => ['ID', 'LID', 'EXTERNAL_ORDER', 'PERSON_TYPE_ID', 'ACCOUNT_NUMBER'], 'limit' => 1, 'filter' => ['=ID' => $arFields['ORDER_ID']]])->fetch();

            if ($order['EXTERNAL_ORDER'] == 'Y') {
                $arFields['TITLE'] = "Заказ из 1С №" . $order['ACCOUNT_NUMBER'];
            } else {
                if ($order['LID'] == 's1') {
                    $arFields['TITLE'] = "Заказ интернет-магазина №" . $order['ACCOUNT_NUMBER'];
                    $arFields['SOURCE_ID'] = 'STORE';
                } elseif ($order['LID'] == 's3') {
                    $arFields['TITLE'] = "Заказ оптовый №" . $order['ACCOUNT_NUMBER'];
                    $arFields['SOURCE_ID'] = 'UC_NXQ5YR';

                    if ($order['PERSON_TYPE_ID'] == B2B_UR_PERSON_TYPE_ID) $type = 162;
                    elseif ($order['PERSON_TYPE_ID'] == B2B_FIZ_PERSON_TYPE_ID) $type = 161;
                    if ($type) $arFields['UF_CRM_1675855723'] = $type;
                }
            }

        }
    }

    protected static function changePropOrder(&$arFields)
    {
        if ($arFields['ORDER_ID']) {
            $order = \Bitrix\Sale\Order::load($arFields['ORDER_ID']);
            $propertyCollection = $order->getPropertyCollection();

            foreach (B2B_INFO_ORDER_PROP_GROUP_DELIVERY as $prop) {
                $propValue = $propertyCollection->getItemByOrderPropertyCode($prop['CODE_PROP']);
                if ($propValue) {
                    $value = $propValue->getValue();
                    if ($value) {
                        if ($prop['IBLOCK_ID']) {
                            $resProp = \CIBlockElement::GetList([], ['IBLOCK_ID' => $prop['IBLOCK_ID'], 'PROPERTY_GUID_1C' => $value], false, false, ['ID']);
                            while ($idProp = $resProp->fetch()) {
                                $arFields[$prop['CODE_CRM']] = $idProp['ID'];
                            }
                        } else {
                            $arFields[$prop['CODE_CRM']] = $value;
                        }
                    }
                }
            }
            $propValueAddress = $propertyCollection->getItemByOrderPropertyCode('ADDRESS_COMMENT');
            if ($propValueAddress) $valueAddress = $propValueAddress->getValue();
            if ($valueAddress) $arFields['UF_CRM_1705302648341'] = $valueAddress;
            $valueComment = $order->getField("USER_DESCRIPTION");
            if ($valueComment) $arFields['COMMENTS'] = $valueComment;
        }
    }




    public static function OnBeforeCrmDealUpdateHandler(&$arFields)
    {
        self::getStatusDeliveryAndStartBP($arFields);

    }

    public static function getStatusDeliveryAndStartBP(&$arFields)
    {
        if ($arFields['ID'] && (!empty($arFields['UF_CRM_OT_STATUS']) || !empty($arFields['UF_CRM_1669901379679']))) {
            $idBp = false;
            $deliveryId = false;
            $codeProp = '';
            $arStatusBP = [];

            $idOrder = current(\Bitrix\Crm\Binding\OrderEntityTable::getOrderIdsByOwner($arFields['ID'], 2));
            $order = \Bitrix\Sale\Order::load($idOrder);

            if ($order) $deliveryId = $order->getField('DELIVERY_ID');
            if ($deliveryId == 82) { //СДЭК
                $codeProp = 'UF_CRM_1669901379679';
                $arStatusBP = [
                    146 => 'Принят на склад до востребования',
                    147 => 'Вручен'
                ];
            }
            if ($deliveryId == 35) { //Почта
                $codeProp = 'UF_CRM_OT_STATUS';
                $arStatusBP = [
                    146 => 'Прибыло в место вручения',
                    147 => 'Вручение адресату'
                ];
            }


            $fieldsOld = \Bitrix\Crm\DealTable::getList([
                'filter' => ['ID' => $arFields['ID']],
                'select' => ['STAGE_ID', $codeProp],
            ])->fetch();
            if ($fieldsOld['STAGE_ID'] == 'WON' && $fieldsOld[$codeProp] !== $arFields[$codeProp]) {
                foreach ($arStatusBP as $status) {
                    if (strpos($arFields[$codeProp], $status) !== false) {
                        $idBp = array_search($arFields[$codeProp], $arStatusBP);
                    }
                }
            }

            if ($idBp) {
                $arErrorsTmp = [];
                $wfId = \CBPDocument::StartWorkflow(
                    $idBp,
                    array("crm", "CCrmDocumentDeal", 'DEAL_' . $arFields['ID']),
                    [],
                    $arErrorsTmp
                );
            }
        }
    }

    public static function OnAfterCrmDealUpdateHandler(&$arFields)
    {
        $idOrder = current(\Bitrix\Crm\Binding\OrderEntityTable::getOrderIdsByOwner($arFields['ID'], 2));
        if ($idOrder) {
            $arPropInfo = B2B_INFO_ORDER_PROP_GROUP_DELIVERY;
            $propCommentAddress['CODE_CRM'] = 'UF_CRM_1705302648341';
            $propCommentAddress['CODE_PROP'] = 'ADDRESS_COMMENT';
            $arPropInfo[] = $propCommentAddress;
            foreach ($arPropInfo as $prop) {
                if ($arFields[$prop['CODE_CRM']]) {
                    if ($prop['IBLOCK_ID']) {
                        $resProp = \CIBlockElement::GetList([], ['IBLOCK_ID' => $prop['IBLOCK_ID'], 'ID' => $arFields[$prop['CODE_CRM']]], false, false, ['PROPERTY_GUID_1C']);
                        while ($idProp = $resProp->fetch()) {
                            $arNewFields[$prop['CODE_PROP']] = $idProp['PROPERTY_GUID_1C_VALUE'];
                        }
                    } else {
                        $arNewFields[$prop['CODE_PROP']] = $arFields[$prop['CODE_CRM']];
                    }
                }
            }
            if (!empty($arNewFields)) {
                $order = \Bitrix\Sale\Order::load($idOrder);
                $propertyCollection = $order->getPropertyCollection();
                foreach ($arNewFields as $key => $value) {
                    $propValue = $propertyCollection->getItemByOrderPropertyCode($key);
                    $propValue->setValue($value);
                    $res = $propValue->save();
                }

            }
        }
    }

    public static function OnAfterCrmContactAddHandler(&$arFields) //https://webfly.bitrix24.ru/company/personal/user/298/tasks/task/view/25093/
    {
        if ($arFields['SOURCE_ID'] == 'WEBFORM' && $arFields['WEBFORM_ID'] == '27') {
            $newUser['LOGIN'] = $newUser['PHONE_NUMBER'] = $newUser['PERSONAL_PHONE'] = current($arFields['FM']['PHONE']) ['VALUE'];
            $newUser['NAME'] = $arFields['NAME'];
            $newUser['LAST_NAME'] = $arFields['LAST_NAME'];
            $newUser['SECOND_NAME'] = $arFields['SECOND_NAME'];
            $newUser['EMAIL'] = current($arFields['FM']['EMAIL']) ['VALUE'];
            $newUser['PERSONAL_CITY'] = $arFields['ADDRESS'];

            if (!empty($newUser)) {
                $user = new \CUser;
                $arFields = array(
                    "LID" => "s2",
                    "ACTIVE" => "Y",
                    "GROUP_ID" => [3, 4, 6, 10, B2B_GROUP],
                    "PASSWORD" => "123456",
                    "CONFIRM_PASSWORD" => "123456",
                );
                $arFields = array_merge($newUser, $arFields);
                $ID = $user->Add($arFields);

            }
        }
    }

    public static function OnBeforeCrmContactUpdateHandler(&$arFields)
    {
        self::changeEmailAndPhoneInUser($arFields);
    }

    public static function changeEmailAndPhoneInUser(&$arFields)
    {
        $typeAuthEmail = \Bitrix\Crm\Multifield\Type\Email::VALUE_TYPE_HOME;
        $keyAuth = '';
        $updEmail = '';
        $arEmail = [];
        if ($arFields['FM']['EMAIL']) {
            foreach ($arFields['FM']['EMAIL'] as $key => $email) {
                if ($key == 'n0' && $email['VALUE_TYPE'] != $typeAuthEmail) $keyAuth = $key;
                else if ($email['VALUE_TYPE'] == $typeAuthEmail) $keyAuth = $key;
            }
            if (empty($keyAuth)) $keyAuth = array_key_last($arFields['FM']['EMAIL']);

            foreach ($arFields['FM']['EMAIL'] as $key => &$email) {
                if (!empty($keyAuth) && $keyAuth == $key && $email['VALUE'] != $typeAuthEmail) {
                    $email['VALUE_TYPE'] = $typeAuthEmail;
                    $updEmail = $email['VALUE'];
                } elseif ($email['VALUE_TYPE'] == $typeAuthEmail) $email['VALUE_TYPE'] = 'WORK';
                $arEmail[] = $email['VALUE'];
            }
        }

        $typeAuthPhone = \Bitrix\Crm\Multifield\Type\Phone::VALUE_TYPE_PAGER;
        $keyAuth = '';
        $arPhone = [];
        $updPhone = '';
        if ($arFields['FM']['PHONE']) {
            foreach ($arFields['FM']['PHONE'] as $key => $phone) {
                if ($key == 'n0' && $phone['VALUE_TYPE'] != $typeAuthPhone) $keyAuth = $key;
                else if ($phone['VALUE_TYPE'] == $typeAuthPhone) $keyAuth = $key;
            }
            if (empty($keyAuth)) $keyAuth = array_key_last($arFields['FM']['PHONE']);

            foreach ($arFields['FM']['PHONE'] as $key => &$phone) {
                if (!empty($keyAuth) && $keyAuth == $key && $phone['VALUE'] != $typeAuthPhone) {
                    $phone['VALUE_TYPE'] = $typeAuthPhone;
                    $updPhone = $phone['VALUE'];
                } elseif ($phone['VALUE_TYPE'] == $typeAuthPhone) $phone['VALUE_TYPE'] = 'WORK';
                $arPhone[] = $phone['VALUE'];
            }
        }

        $userId = false;
        $actualIds = [];

        $arFiler = [];
        if (!empty($arPhone)) $arFiler['PERSONAL_PHONE'] = $arPhone;
        if (!empty($arEmail)) $arFiler['EMAIL'] = $arEmail;

        if (!empty($arFiler)) {
            $userData = \Bitrix\Main\UserTable::getList([
                'select' => ['ID', 'PERSONAL_PHONE', 'EMAIL'],
                'filter' => $arFiler,
            ]);
            while ($obUser = $userData->Fetch()) {
                $actualIds[$obUser['ID']] = $obUser['ID'];
                $actualInfo[$obUser['ID']] = $obUser;
            }
            if (count($actualIds) == 1) $userId = current($actualIds);
            else if (count($actualIds) > 1) $userId = end($actualIds);
        }

        if ($userId && !empty($actualInfo[$userId])) {
            $arNewField = [];
            if ($arFields['FM']['PHONE'] && $actualInfo[$userId]['PERSONAL_PHONE'] != $updPhone) $arNewField['LOGIN'] = $arNewField['PHONE_NUMBER'] = $arNewField['PERSONAL_PHONE'] = $updPhone;
            if ($arFields['FM']['EMAIL'] && $actualInfo[$userId]['EMAIL'] != $updEmail) $arNewField['EMAIL'] = $updEmail;

            if (!empty($arNewField)) {
                $oUser = new \CUser;
                $res = $oUser->Update($userId, $arNewField);
            }

        }

    }

}
