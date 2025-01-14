<?php

namespace Webfly\Handlers;

use Bitrix\Crm\FieldMultiTable,
    Bitrix\Main\Loader,
    Bitrix\Main\Entity\ReferenceField,
    Bitrix\Main\Entity\ExpressionField;

Loader::includeModule('form');
Loader::includeModule('crm');

class Form
{
    /**
     * onAfterResultAdd
     * После добавлениф новых результатов форм
     * добавляем/обновляем лид на портале
     * @param $WEB_FORM_ID
     * @param $RESULT_ID
     */
    public static function addCrmLead($WEB_FORM_ID, $RESULT_ID)
    {
        //Веб-формы, по которым нужно создавать лиды
        $dealForms = [6, 10];

        if (in_array($WEB_FORM_ID, $dealForms)) {
            $phonesForSearch = [];
            $email = '';

            \CFormResult::GetDataByID($RESULT_ID, [], $arResult, $arAnswer);
            $form = \CForm::GetByID($WEB_FORM_ID)->fetch();
            if ($arAnswer) {
                $name = reset($arAnswer['CLIENT_NAME'])["USER_TEXT"];
                $phone = reset($arAnswer['PHONE'])["USER_TEXT"];
                $email = reset($arAnswer['EMAIL'])["USER_TEXT"];
                $question = reset($arAnswer['QUESTION'])["USER_TEXT"];
            }
            $updateFields = [];

            if ($phone){
                $phonesForSearch = self::getPhonesArray($phone);
            }

            if ($phonesForSearch)
                $contactData = self::getContactByPhone($phonesForSearch);

            if (!$contactData && $email)
                $contactData = self::getContactByEmail($email);


            $oContact = new \CCrmContact(false);
            $fields = [];
            $contactId = $contactData['ID']?:'';

            if($contactData['FIND_BY']=='PHONE' && !empty($email) && !in_array($email, $contactData['EMAIL'])){
                //дописать email в контакт
                $fields['FM']['EMAIL'] = array(
                    'n0' => array(
                        'VALUE_TYPE' => 'WORK',
                        'VALUE' => $email,
                    )
                );
                $oContact->update($contactData['ID'],$fields);
            }elseif($contactData['FIND_BY']=='EMAIL' && !empty($phonesForSearch) && !array_intersect($phonesForSearch, $contactData['PHONE'])){
                //дописать phone в контакт
                $fields['FM']['PHONE'] = array(
                    'n0' => array(
                        'VALUE_TYPE' => 'WORK',
                        'VALUE' => reset($phonesForSearch),
                    )
                );
                $oContact->update($contactData['ID'],$fields);
            }


            //создание лида
            $leadTitle = $form['NAME'];
            if ($phone)
                $leadTitle .= " {$phone}";

            if ($email)
                $leadTitle .= " ({$email})";
            $leadFields = [
                "TITLE" => $leadTitle,
                'COMMENTS' => $question,
                "STATUS_ID" => "NEW",
                "OPENED" => "Y",
                "ASSIGNED_BY_ID" => 1,
                "SOURCE_ID" => 'WEB',
            ];

            if ($phonesForSearch){
                $leadFields['FM']['PHONE'] = array(
                    'n0' => array(
                        'VALUE_TYPE' => 'WORK',
                        'VALUE' => reset($phonesForSearch),
                    )
                );
            }

            if ($email){
                $leadFields['FM']['EMAIL'] = array(
                    'n0' => array(
                        'VALUE_TYPE' => 'WORK',
                        'VALUE' => $email,
                    )
                );
            }

            if ($contactId) {//повторынй лид
                $leadFields['CONTACT_ID'] = $contactId;
            } else {//новый лид
                if ($name) $updateFields['NAME'] = $name;
                $leadFields = array_merge($leadFields, $updateFields);
            }

            $oLead = new \CCrmLead(false);
            $oLead->add($leadFields);
        }
    }

    protected static function getContactByPhone($phonesForSearch){
        $result = [];

        if (!$phonesForSearch) return $result;

        $params = [
            'filter' => [
                'PHONE_CLEAR' => $phonesForSearch,
                'ENTITY_ID' => 'CONTACT',
                'TYPE_ID' => 'PHONE',
                'MULTI.ENTITY_ID' => 'CONTACT',
                'MULTI.TYPE_ID' => ['PHONE','EMAIL']
            ],
            'select' => ['ELEMENT_ID', 'MULTI'],
            'group' => ['CRM_FIELD_MULTI_MULTI_TYPE_ID'],
            'runtime' => [
                new ExpressionField('PHONE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['VALUE']),
                new ReferenceField('MULTI', FieldMultiTable::class, ['=this.ELEMENT_ID' => 'ref.ELEMENT_ID']),
            ]];
        $resultPhone = FieldMultiTable::getList($params);


        while ($ob_item = $resultPhone->fetch()) {
            $result[$ob_item['ELEMENT_ID']]['ID'] = $ob_item['ELEMENT_ID'];
            $result[$ob_item['ELEMENT_ID']]['FIND_BY'] = 'PHONE';
            $type = $ob_item["CRM_FIELD_MULTI_MULTI_TYPE_ID"];
            if ($type == 'EMAIL') {
                if (!in_array($ob_item["CRM_FIELD_MULTI_MULTI_VALUE"], $result[$ob_item['ELEMENT_ID']][$type]))
                    $result[$ob_item['ELEMENT_ID']][$type][] = strtoupper($ob_item["CRM_FIELD_MULTI_MULTI_VALUE"]);
            }
        }
        return reset($result);
    }

    protected static function getContactByEmail($email){
        $result = [];
        if (!$email) return $result;

        $resultEmail = \Bitrix\Crm\FieldMultiTable::getList([
            'filter' => [
                'VALUE' => $email,
                'ENTITY_ID' => 'CONTACT',
                'TYPE_ID' => 'EMAIL',
                'MULTI.ENTITY_ID' => 'CONTACT',
                'MULTI.TYPE_ID' => ['PHONE','EMAIL']
            ],
            'select' => ['ELEMENT_ID', 'MULTI'],
            'group' => ['CRM_FIELD_MULTI_MULTI_TYPE_ID'],
            'runtime' => [
                new ReferenceField('MULTI', FieldMultiTable::class, ['=this.ELEMENT_ID' => 'ref.ELEMENT_ID']),
            ]
        ]);
        while ($ob_item = $resultEmail->fetch()) {
            $result[$ob_item['ELEMENT_ID']]['ID'] = $ob_item['ELEMENT_ID'];
            $result[$ob_item['ELEMENT_ID']]['FIND_BY'] = 'EMAIL';
            $type = $ob_item["CRM_FIELD_MULTI_MULTI_TYPE_ID"];
            if ($type == 'PHONE') {
                $phones = self::getPhonesArray($ob_item["CRM_FIELD_MULTI_MULTI_VALUE"]);
                if ($phones) {
                    foreach ($phones as $phone) {
                        if (!in_array($phone, $result[$ob_item['ELEMENT_ID']][$type]))
                            $result[$ob_item['ELEMENT_ID']][$type][] = $phone;
                    }
                }
            }
        }

        return reset($result);
    }

    protected static function getPhonesArray($phone)
    {
        $phonesForSearch = [];
        if (!$phone) return $phonesForSearch;

        $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $phone);
        $phonesForSearch[] = $phoneClear;

        $firstSymbol = mb_substr($phoneClear, 0, 1);
        if ($firstSymbol == 7) {
            $phoneTrim = mb_substr($phoneClear, 1);
            $phonesForSearch[] = "8{$phoneTrim}";
        } elseif ($firstSymbol == 8) {
            $phoneTrim = mb_substr($phoneClear, 1);
            $phonesForSearch[] = "7{$phoneTrim}";
        }
        return $phonesForSearch;
    }
}
