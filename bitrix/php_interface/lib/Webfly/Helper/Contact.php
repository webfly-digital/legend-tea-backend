<?php

namespace Webfly\Helper;


use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;
use Bitrix\Crm\Service;

\Bitrix\Main\Loader::includeModule('crm');
\Bitrix\Main\Loader::includeModule('sale');
\Bitrix\Main\Loader::IncludeModule('im');

class Contact
{

    public static function fullLastAuthorize(&$fields = [])
    {
        $objDateTime = \Bitrix\Main\Type\DateTime::createFromPhp(new \DateTime());
        $fields['UF_CRM_1681474825685'] = $objDateTime;
    }

    public static function fullUserID($arContactID, &$fields = [])
    {
        $result = \Bitrix\Crm\ContactTable::getList([
            'select' => ['ID', 'UF_ID_USER'],
            'filter' => ['ID' => $arContactID],
        ]);
        while ($ob = $result->fetch()) {
            if (!empty($ob['UF_ID_USER'])) {
                $arStr = explode(',', $ob['UF_ID_USER']);

                if (array_search($fields['ID'], $arStr) === false) {
                    $arStr[] = $fields['ID'];
                    $str = implode(',', $arStr);
                    $fields['UF_ID_USER'] = $str;
                }
            } else {
                $fields['UF_ID_USER'] = $fields['ID'];
            }
        }
    }


    public static function getPhonesForSearch($phone)
    {
        $phonesForSearch = [];
        $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $phone);
        $firstSymbol = mb_substr($phoneClear, 0, 1);
        $phonesForSearch[] = $phoneClear;
        $phoneTrim = mb_substr($phoneClear, 1);

        if ($firstSymbol == '+') {
            $phonesForSearch[] = "8{$phoneTrim}";
            $phonesForSearch[] = "7{$phoneTrim}";
            $phonesForSearch[] = "{$phoneTrim}";
        } elseif ($firstSymbol == 7) {
            $phonesForSearch[] = "8{$phoneTrim}";
            $phonesForSearch[] = "+7{$phoneTrim}";
            $phonesForSearch[] = "{$phoneTrim}";
        } elseif ($firstSymbol == 8) {
            $phonesForSearch[] = "7{$phoneTrim}";
            $phonesForSearch[] = "+7{$phoneTrim}";
            $phonesForSearch[] = "{$phoneTrim}";
        } elseif ($firstSymbol == 9) {
            $phonesForSearch[] = "7{$phoneTrim}";
            $phonesForSearch[] = "+7{$phoneTrim}";
            $phonesForSearch[] = "8{$phoneTrim}";
        }
        return $phonesForSearch;
    }

    //Проверяем есть ли контакт с таикми данными, если нет создаём
    public static function checkExistContact(&$arFields)
    {
        $arContact = self::searchContactByPhoneAndEmail($arFields);

        if (empty($arContact)) self::addContact($arFields);
        else {
            if (count($arContact) == 1) {
                $contactId = current($arContact)['ELEMENT_ID'];
                self::fullInfoContact($contactId, $arFields);
            }
        }
    }

    public static function searchContactByPhoneAndEmail($arFields)
    {
        if ($arFields['PERSONAL_PHONE'] || $arFields['EMAIL']) {
            $arPhones = [];
            $arEmail = [];


            if (!empty($arFields['PERSONAL_PHONE'])) {
                $arFilter['ENTITY_ID'] = 'CONTACT';
                $arFilter['TYPE_ID'] = 'PHONE';
                $arPhones = self::getPhonesForSearch($arFields['PERSONAL_PHONE']);

                if (!empty($arPhones)) {
                    $arFilter['PHONE_CLEAR'] = $arPhones;
                    $runtime = [new \Bitrix\Main\Entity\ExpressionField('PHONE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['VALUE'])];

                    $obPhones = \Bitrix\Crm\FieldMultiTable::getList([
                        'filter' => $arFilter,
                        'select' => ['ELEMENT_ID'],
                        'runtime' => $runtime
                    ]);

                    while ($resPhone = $obPhones->fetch()) {
                        $arContact[$resPhone['ELEMENT_ID']] = $resPhone;
                    }
                }
            }

            if (!empty($arFields['EMAIL'])) {
                $arFilter = [];
                $arFilter['ENTITY_ID'] = 'CONTACT';
                $arFilter['TYPE_ID'] = 'PHONE';
                $arEmail[] = $arFields['EMAIL'];

                if (!empty($arEmail)) {
                    $arFilter['VALUE'] = $arEmail;
                    $obPhones = \Bitrix\Crm\FieldMultiTable::getList([
                        'filter' => $arFilter,
                        'select' => ['ELEMENT_ID'],
                    ]);
                    while ($resPhone = $obPhones->fetch()) {
                        $arContact[$resPhone['ELEMENT_ID']] = $resPhone;
                    }
                }
            }


        }

        return $arContact;
    }

    public static function addContact($arFields)
    {
        $source = 'WEB';
        $typeID = 'CLIENT';
        if ($arFields['SITE_ID'] == 's3') {//б2б
            $source = 'UC_NXQ5YR';
            $typeID = '1';
        }
        $arNewFields = array(
            "NAME" => $arFields['NAME'],
            "LAST_NAME" => $arFields['LAST_NAME'],
            "SECOND_NAME" => $arFields['SECOND_NAME'],
            'FM' => [
                'EMAIL' => array(
                    'n0' => array('VALUE' => $arFields["EMAIL"], 'VALUE_TYPE' => \Bitrix\Crm\Multifield\Type\Email::VALUE_TYPE_HOME)
                ),
                'PHONE' => array(
                    'n0' => array('VALUE' => $arFields["PERSONAL_PHONE"], 'VALUE_TYPE' => \Bitrix\Crm\Multifield\Type\Phone::VALUE_TYPE_PAGER)
                )
            ],
            "ASSIGNED_BY_ID" => 1,
            "SOURCE_ID" => $source,
            'TYPE_ID' => $typeID,
        );
        if ($arFields['UF_CONTACT_GUID']) {
            $arNewFields['UF_CRM_1723022437'] = $arFields['UF_CONTACT_GUID'];
            $arNewFields['UF_ID_USER'] = $arFields['ID'];
        }

        $oContact = new \CCrmContact(false);
        $ID = $oContact->add($arNewFields, true, ['DISABLE_USER_FIELD_CHECK' => true, 'DISABLE_REQUIRED_USER_FIELD_CHECK' => true]);
    }

    public static function updateContact($arContactID, $arFieldsUpd = [])
    {
        $oContact = new \CCrmContact(false);
        $updateContact = $oContact->Update($arContactID, $arFieldsUpd);
    }

    public static function fullInfoContact($arContactID, $arFields)
    {
        $arFieldsUpd = [];
        if (!empty($arFields['UF_CONTACT_GUID'])) {
            $arFieldsUpd['UF_CRM_1723022437'] = $arFields['UF_CONTACT_GUID'];
            self::fullUserID($arContactID, $arFieldsUpd);
        }
        self::fullLastAuthorize($arFieldsUpd);
        self::updateContact($arContactID, $arFieldsUpd);
    }


    public static function updateContactWhenChangeUser($arFields)
    {
        $record = new  \Webfly\Upload\RequestQueueIn1c();
        $record->createRecord($arFields['ID'], 'UPDATE_USER');


        $arContact = self::searchContactByPhoneAndEmail($arFields);

        if (!empty($arContact) && count($arContact) == 1) {
            $contactId = current($arContact)['ELEMENT_ID'];
            $oldContact = self::getContactById($contactId, ['NAME', 'LAST_NAME', 'SECOND_NAME', 'MULTI_' => 'MULTI'], true);
            $arFieldsUpd = self::dataPreparationBeforeUpdate($oldContact, $arFields);

            if (!empty($arFieldsUpd)) {
                self::writeHistoryOldContact($contactId, $oldContact, $arFieldsUpd);
                self::updateContact($contactId, $arFieldsUpd);
            }
        } else {
            if (!empty($arContact)) self::sendMessageFindContact($arContact, $arFields['ID']);
        }
    }

    public static function getContactById($contactId, $select = [], $multi = false)
    {
        $resContact = [];

        $params['filter'] = ['ID' => $contactId];
        $params['select'] = array_merge($select, ['ID']);

        if ($multi) {
            $params['filter'] = array_merge($params['filter'], ['MULTI.ENTITY_ID' => 'CONTACT']);
            $params['runtime'] = [
                new \Bitrix\Main\Entity\ReferenceField('MULTI', \Bitrix\Crm\FieldMultiTable::class, ['=this.ID' => 'ref.ELEMENT_ID']),
            ];
        }

        $dbContact = \Bitrix\Crm\ContactTable::getList($params);
        while ($obContact = $dbContact->fetch()) {
            if (empty($resContact)) $resContact = $obContact;
            else $resContact = array_merge($resContact, $obContact);
            if ($multi) {
                if ($obContact['MULTI_TYPE_ID'] == 'EMAIL') {
                    $resContact['EMAIL'][$obContact['MULTI_ID']]['VALUE'] = $obContact['MULTI_VALUE'];
                    $resContact['EMAIL'][$obContact['MULTI_ID']]['TYPE'] = $obContact['MULTI_VALUE_TYPE'];
                }
                if ($obContact['MULTI_TYPE_ID'] == 'PHONE') {
                    $resContact['PHONE'][$obContact['MULTI_ID']]['VALUE'] = $obContact['MULTI_VALUE'];
                    $resContact['PHONE'][$obContact['MULTI_ID']]['TYPE'] = $obContact['MULTI_VALUE_TYPE'];
                }
                unset($resContact['MULTI_ID']);
                unset($resContact['MULTI_ENTITY_ID']);
                unset($resContact['MULTI_ELEMENT_ID']);
                unset($resContact['MULTI_TYPE_ID']);
                unset($resContact['MULTI_VALUE_TYPE']);
                unset($resContact['MULTI_COMPLEX_ID']);
                unset($resContact['MULTI_VALUE']);
                unset($resContact['ID']);
            }
        }
        return $resContact;
    }

    public static function dataPreparationBeforeUpdate($oldContact, $arFields)
    {
        $arFieldsUpd = [];
        foreach ($oldContact as $codeField => $valueField) {
            switch ($codeField) {
                case 'PHONE':

                    $addPhone = true;
                    if (!empty($valueField)) {
                        foreach ($valueField as $key => $value) {//перебираем все теелфоны, что есть у контактов
                            if ($arFields["PERSONAL_PHONE"] == $value['VALUE']) { //если телефон совпал с теелфоном контакта, убираем телефон
                                unset($valueField[$key]);
                                $addPhone = false;
                            }
                        }
                    }

                    if (!empty($valueField)) foreach ($valueField as $key => $value) $arFieldsUpd['FM'][$codeField][$key] = ['VALUE' => ''];

                    if ($addPhone) $arFieldsUpd['FM'][$codeField]['n0'] = ['VALUE' => $arFields["PERSONAL_PHONE"], 'VALUE_TYPE' => \Bitrix\Crm\Multifield\Type\Phone::VALUE_TYPE_HOME];

                    break;
                case'EMAIL':
                    $addEmail = true;
                    if (!empty($valueField)) {
                        foreach ($valueField as $key => $value) {//перебираем все теелфоны, что есть у контактов
                            if ($arFields["EMAIL"] == $value['VALUE']) { //если телефон совпал с теелфоном контакта, убираем телефон
                                unset($valueField[$key]);
                                $addEmail = false;
                            }
                        }
                    }

                    if (!empty($valueField)) foreach ($valueField as $key => $value) $arFieldsUpd['FM'][$codeField][$key] = ['VALUE' => ''];

                    if ($addEmail) $arFieldsUpd['FM'][$codeField]['n0'] = ['VALUE' => $arFields["EMAIL"], 'VALUE_TYPE' => \Bitrix\Crm\Multifield\Type\Phone::VALUE_TYPE_HOME];

                    break;
                default:
                    if ($arFields[$codeField] != $valueField) $arFieldsUpd[$codeField] = $arFields[$codeField];

            }
        }

        return $arFieldsUpd;

    }

    public static function writeHistoryOldContact($contactId, $oldContact, $arFieldsUpd)
    {
        $history = $historyFM = [];
        $history = array_intersect_key($oldContact, $arFieldsUpd);
        if ($arFieldsUpd['FM']) $historyFM = array_intersect_key($oldContact, $arFieldsUpd['FM']);
        $arHistory = array_merge($history, $historyFM);
        $arHistory['DESCRIPTION'] = "old data contact";

        \Webfly\Helper\Helper::writeHistory($contactId, print_r($arHistory, true), 'CONTACT');
    }

    public static function sendMessageFindContact($arContact, $userId)
    {
        foreach ($arContact as $key => $value) $arLink[] = 'https://crm.legend-tea.ru/crm/contact/details/' . $key . '/';

        $ids = implode(' , ', $arLink);
        $str = 'Пользователь [https://crm.legend-tea.ru/bitrix/admin/user_edit.php?lang=ru&ID=' . $userId . '] изменил личную информацию. Было найдено несколько контактов в срм с актуальной информацией пользователя ' . $ids;

        \Webfly\Helper\Helper::sendMessageGroupChat(ID_CHAT_CHANGE_CONTACT, $str);
    }

}