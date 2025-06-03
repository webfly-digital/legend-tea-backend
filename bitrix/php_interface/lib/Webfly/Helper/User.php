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
\Bitrix\Main\Loader::includeModule('main');

class User
{


    static public function uploadUserAndProfile($user)
    {
        if ($user['UF_CONTACT_GUID']) return false;

        if ($user['RESULT_MESSAGE']) unset($user['RESULT_MESSAGE']);
        if ($user['LOGIN']) unset($user['LOGIN']);
        if ($user['CHECKWORD']) unset($user['CHECKWORD']);
        if ($user['~CHECKWORD_TIME']) unset($user['~CHECKWORD_TIME']);
        if ($user['PASSWORD']) unset($user['PASSWORD']);
        if ($user['CONFIRM_PASSWORD']) unset($user['CONFIRM_PASSWORD']);
        if ($user['CONFIRM_CODE']) unset($user['CONFIRM_CODE']);
        if ($user['USER_IP']) unset($user['USER_IP']);
        if ($user['USER_HOST']) unset($user['USER_HOST']);
        if ($user['AUTO_TIME_ZONE']) unset($user['AUTO_TIME_ZONE']);
        if ($user['GROUP_ID']) unset($user['GROUP_ID']);


        $newReq = new \Webfly\Upload\RequestTo1C();
        $res = $newReq->executePostRequest($user, 'user_add');
        return $res;
    }


    public static function identicalPhonesInNewUser(&$arFields)
    {
        if ($arFields["PERSONAL_PHONE"]) {
            $phone = UserPhoneAuthTable::normalizePhoneNumber($arFields["PERSONAL_PHONE"]);
            $arFields['PHONE_NUMBER'] = $arFields["PERSONAL_PHONE"] = $phone;
        }
    }

    public static function changeFieldsNewUser(&$arFields)
    {
        self::identicalPhonesInNewUser($arFields);

        $IdLocation = $arFields["UF_LOCATION_ID"];
        $codeLocation = $arFields["UF_LOCATION_CODE"];
        if ($IdLocation || $codeLocation) {

            $arFields["PERSONAL_COUNTRY"] = '';
            $arFields["UF_FEDERAL_DISTRICT"] = '';
            $arFields["PERSONAL_STATE"] = '';
            $arFields["UF_DISTRICT"] = '';
            $arFields["PERSONAL_CITY"] = '';

            if ($IdLocation) $filter = ['=ID' => $IdLocation];
            if ($codeLocation) $filter = ['=CODE' => $codeLocation];

            $arFilter = array_merge($filter, ['=PARENTS.NAME.LANGUAGE_ID' => LANGUAGE_ID, '=PARENTS.TYPE.NAME.LANGUAGE_ID' => LANGUAGE_ID,]);
            $obTreeLocation = \Bitrix\Sale\Location\LocationTable::getList(array(
                'filter' => $arFilter,
                'select' => [
                    'ID',
                    'CODE',
                    'I_ID' => 'PARENTS.ID',
                    'I_NAME_RU' => 'PARENTS.NAME.NAME',
                    'I_TYPE_CODE' => 'PARENTS.TYPE.CODE',
                    'I_TYPE_NAME_RU' => 'PARENTS.TYPE.NAME.NAME',
                ],
            ));

            while ($resTreeLocation = $obTreeLocation->fetch()) {
                if ($resTreeLocation['CODE']) $arFields["UF_LOCATION_CODE"] = $resTreeLocation['CODE'];
                if ($resTreeLocation['ID']) $arFields["UF_LOCATION_ID"] = $resTreeLocation['ID'];
                if ($resTreeLocation['I_TYPE_CODE'] == 'COUNTRY') $arFields["PERSONAL_COUNTRY"] = $resTreeLocation['I_ID'];
                $name = $resTreeLocation['I_NAME_RU'] ?: '';
                if ($resTreeLocation['I_TYPE_CODE'] == 'COUNTRY_DISTRICT') $arFields["UF_FEDERAL_DISTRICT"] = $name;
                if ($resTreeLocation['I_TYPE_CODE'] == 'REGION') $arFields["PERSONAL_STATE"] = $name;
                if ($resTreeLocation['I_TYPE_CODE'] == 'SUBREGION') $arFields["UF_DISTRICT"] = $name;
                if ($resTreeLocation['I_TYPE_CODE'] == 'CITY') $arFields["PERSONAL_CITY"] = $name;
            }

        }
    }

    public static function validFieldsRegister(&$arFields, $request)
    {
        if ($request->get('USER_PERSONAL_PHONE')) $phone = UserPhoneAuthTable::normalizePhoneNumber($request->get('USER_PERSONAL_PHONE'));
        if (!empty($arFields['PERSONAL_PHONE'])) $phone = UserPhoneAuthTable::normalizePhoneNumber($arFields['PERSONAL_PHONE']);

        $arFields['PERSONAL_PHONE'] = $arFields['PHONE_NUMBER'] = $phone;

        $errors = [];

        if (empty($arFields['LAST_NAME'])) $errors[] = "Поле \"Фамилия\" не заполнено";
        if (empty($arFields['NAME'])) $errors[] = "Поле \"Имя\" не заполнено";
        if (empty($arFields['PERSONAL_PHONE'])) $errors[] = "Поле \"Телефон\" не заполнено";

        $individual = $request->get('INDIVIDUAL');
        if (!$individual) {//юр лицо
            $inn = $request->get('INN');
            $company = $request->get('COMPANY');

            if (empty($inn)) $errors[] = "Поле \"ИНН\" не заполнено";
            if (empty($company)) $errors[] = "Поле \"Юридическое название организации\" не заполнено";
            if (!empty($inn)) {
                $res = \Webfly\Helper\Helper::getExistUsersInn($inn);
                if (!empty($res)) $errors[] = json_encode(\Webfly\Helper\Helper::getExistUsersInn($inn));
            }
        }
        return $errors;
    }

    public static function updateUser($userId, $fields)
    {
        $user = new \CUser;
        $user->Update($userId, $fields);
    }


    static function getGuidFrom1C($res1C)
    {
        $guidUser = '';
        if ($res1C['result']['result'] == 'OK') $guidUser = $res1C['result']['guid_contact'];

        return $guidUser;
    }


    public static function getInfo($userID, $select =[])
    {
        $filter = ['ID' => $userID, 'ACTIVE' => 'Y'];
        $arParams["FIELDS"] = $select;
        $rsUsers = \CUser::GetList(array('sort' => 'asc'), 'sort', $filter, $arParams);
        while ($arUser = $rsUsers->Fetch()) {
            return $arUser;
        }
        return [];
    }



    static function updateFieldsProfileAndGuid($userId, $PROFILE_ID, $guidUser, &$arFields)
    {
        $arUpdUser = [];
        if ($guidUser) $arUpdUser['UF_CONTACT_GUID'] = $arFields['UF_CONTACT_GUID'] = $guidUser;
        if ($PROFILE_ID) $arUpdUser['UF_PROFILE_ID'] = $PROFILE_ID;

        if (!empty($arUpdUser)) self::updateUser($userId, $arUpdUser);
    }

    static function searchUserPhone($userId, $phone)
    {
        $findUser = false;
        if ($phone) {
            $phoneTrim = self::getPhoneFormat($phone);

            $filter[] = ['%PHONE_NUMBER' => $phoneTrim,];
            $filter[] = ['%PERSONAL_PHONE' => $phoneTrim];
            $filter[] = ['%PERSONAL_MOBILE' => $phoneTrim];

            $filter[] = ['%PHONE_NUMBER_CLEAR' => $phoneTrim];
            $filter[] = ['%PERSONAL_PHONE_CLEAR' => $phoneTrim];
            $filter[] = ['%PERSONAL_MOBILE_CLEAR' => $phoneTrim];
        }

        if (!empty($filter)) {
            $filterOr = ['LOGIC' => 'OR'] + $filter;

            $dbrUser = \Bitrix\Main\UserTable::getList([
                'order' => ['ID' => 'ASC'],
                'filter' => ['!ID' => $userId] + [$filterOr],
                'select' => ['PERSONAL_PHONE_CLEAR', 'PERSONAL_MOBILE_CLEAR', 'PHONE_NUMBER_CLEAR', 'ID', 'PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'EMAIL', 'UF_CONTACT_GUID', "UF_PARTNER_GUID", 'LAST_NAME', 'NAME', 'SECOND_NAME'],
                'runtime' => [
                    new \Bitrix\Main\Entity\ExpressionField('PERSONAL_PHONE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['PERSONAL_PHONE']),
                    new \Bitrix\Main\Entity\ExpressionField('PERSONAL_MOBILE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['PERSONAL_MOBILE']),
                    new \Bitrix\Main\Entity\ExpressionField('PHONE_NUMBER_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['PHONE_AUTH.PHONE_NUMBER']),
                ]
            ]);
            while ($resUser = $dbrUser->fetch()) {
                $findUser[] = $resUser;
            }
        }

        return $findUser;
    }

    static function checkUniqPhone(&$arFields)
    {
        $resSearch = self::searchUserPhone($arFields['ID'], $arFields['PERSONAL_PHONE']);
        if (is_array($resSearch)) {
            $res = self::formatErrorUpdateUser($resSearch);
            return $res;
        } else {
            $arFields['PHONE_NUMBER'] = $arFields['PERSONAL_PHONE'] = UserPhoneAuthTable::normalizePhoneNumber($arFields['PERSONAL_PHONE']);
        }
    }

    static function getPhoneFormat($phoneUser)//+79030303, 8(903)0303, 999450
    {
        $phone = $phoneUser;
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneUser);//79030303, 89030303, 999450

        if (!empty($phoneNumber)) {
            $firstSymbol = mb_substr($phoneNumber, 0, 1); //7, 8, 9

            if ($firstSymbol == 9) // 999450
                $phone = $phoneNumber; // 999450
            else
                $phone = mb_substr($phoneNumber, 1); // 9030303
        }
        return $phone;
    }

    static function hideEmail($myname)
    {
        $myname = substr_replace($myname, str_pad("", strlen($myname) - 4, "*"), 2, -2);
        return $myname;
    }

    static function formatErrorUpdateUser($resSearch)
    {
        $strError = 'Указаные данные принадлежат другому пользователю';
        foreach ($resSearch as $item) {
            if ($item['EMAIL']) $arEmail[] = self::hideEmail($item['EMAIL']);
        }

        if (!empty($arEmail)) {
            $strEmail = implode(',', $arEmail);
        }

        if ($strEmail) {
            $strError = 'Укаазнный телефон принадлежит пользователям: ' . $strEmail;
        }

        return $strError;
    }


}