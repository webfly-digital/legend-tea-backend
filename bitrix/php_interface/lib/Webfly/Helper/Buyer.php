<?php

namespace Webfly\Helper;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CSaleOrderUserProps;
use CSaleOrderUserPropsValue;
use CUser;


\Bitrix\Main\Loader::includeModule('crm');
\Bitrix\Main\Loader::includeModule('sale');

class Buyer
{


    public const PERSON_TYPE_IDS = [B2B_UR_PERSON_TYPE_ID, B2B_FIZ_PERSON_TYPE_ID];

    public const YR_FIELDS_PROFILE_REGISTER = [150, 126, 43, 44, 45, 64, 65, 96];
    public const FIZ_FIELDS_PROFILE_REGISTER = [151, 134, 133, 135, 132, 130, 131, 129, 86, 87];


    /**
     * Получение всех профилей
     *
     * @param string|null $q
     * @return array
     * @throws LoaderException
     */
    static function getAll(?string $q = null, bool $all = false): array
    {
        global $USER;

        $arFilter = ['PERSON_TYPE_ID' => self::PERSON_TYPE_IDS];
        if ($q) {
            $arFilter['~NAME'] = "%" . $q . "%";
        }
        $count = [];
        if (!$all) {
            $count = ['nTopCount' => 20];
        }
        $dbProfiles = CSaleOrderUserProps::GetList(
            array(),
            $arFilter,
            ["ID", "NAME", "USER_ID"],
            $count
        );

        $arItems = [];
        while ($profile = $dbProfiles->Fetch()) {
            $arItems[] = $profile;
        }

        return $arItems;
    }


    /**
     * Получение профилей по авторизованному пользователю
     *
     * @param bool $withProperties
     * @return array
     * @throws LoaderException
     */
    static function getByUser(bool $withProperties = false): array
    {
        global $USER;

        $dbProfiles = CSaleOrderUserProps::GetList(
            array("DATE_UPDATE" => "DESC"),
            ["USER_ID" => $USER->GetID(), 'PERSON_TYPE_ID' => self::PERSON_TYPE_IDS],
            ["ID", "NAME", "USER_ID", 'PERSON_TYPE_ID']
        );

        $arItems = [];
        while ($profile = $dbProfiles->Fetch()) {
            if ($withProperties) {
                $profile['PROPERTIES'] = self::getProperties($profile['ID']);
            }
            $arItems[] = $profile;
        }


        return $arItems;
    }


    /**
     * Установка профиля по умолчанию
     *
     * @param int $id
     * @return bool
     * @throws LoaderException
     */
    static function setDefault(int $id = 0, int $userId = 0): bool
    {
        global $USER;

        if ($userId == 0) $userId = $USER->GetID();


        if (!self::getById($id, false)) {
            return false;
        }


        $res = (new \CUser)->Update($userId, ["UF_PROFILE_ID" => $id]);
        return $res;
    }


    static function getLastProfile(int $userId = 0): int
    {
        global $USER;
        Loader::includeModule('sale');

        if ($userId == 0) $userId = $USER->GetID();

        $profileId = 0;
        $dbProfiles = CSaleOrderUserProps::GetList(
            ["ID" => "ASC"],
            ["USER_ID" => $userId, 'PERSON_TYPE_ID' => self::PERSON_TYPE_IDS],
            ["ID"]
        );
        while ($profile = $dbProfiles->Fetch()) {
            $profileId = $profile['ID'];
        }

        return $profileId;
    }


    /**
     * Получение профиля по умолчанию
     *
     * @param bool $withProperties
     * @return array|false
     * @throws LoaderException
     */
    static function getDefault(bool $withProperties = false): array|false
    {
        global $USER;

        $userProperties = CUser::GetList(
            array(),
            array(),
            array("ID" => $USER->GetID()),
            array("SELECT" => array("UF_PROFILE_ID"))
        );

        $profileId = $userProperties->Fetch()['UF_PROFILE_ID'];
        if (!$profileId) {
            $profiles = self::getByUser();

            if (empty($profiles)) {
                return false;
            }

            self::setDefault($profiles[0]['ID']);
            $profileId = $profiles[0]['ID'];
        }


        $profile = self::getById($profileId, false);

        if ($withProperties) {
            $profile['PROPERTIES'] = self::getProperties($profileId);
        }
        return $profile;
    }

    /**
     * Получение профиля по ID
     *
     * @param int $id
     * @param bool $checkUser
     * @param bool $withProperties
     * @return array|false
     * @throws LoaderException
     */
    public static function getById(int $id, bool $checkUser = true, bool $withProperties = false): array|false
    {
        global $USER;

        $arFields = array("ID" => $id);
        if ($checkUser) $arFields['USER_ID'] = $USER->GetID();


        $dbProfiles = CSaleOrderUserProps::GetList(
            array(),
            $arFields,
            ["ID", "NAME", "USER_ID", "PERSON_TYPE_ID"]
        );
        $profile = $dbProfiles->Fetch();
        if ($withProperties) $profile['PROPERTIES'] = self::getProperties($id);


        return $profile;
    }

    /**
     * Получение свойств профиля
     *
     * @param string $profile_id
     * @return array
     */
    public static function getProperties(string $profile_id): array
    {
        $items = [];
        $db_propVals = \CSaleOrderUserPropsValue::GetList(array(), array('USER_PROPS_ID' => $profile_id));
        while ($arPropVals = $db_propVals->Fetch()) {
            $items[$arPropVals['PROP_ID']] = [
                'ID' => $arPropVals['ID'],
                'PROP_ID' => $arPropVals['PROP_ID'],
                'NAME' => $arPropVals['NAME'],
                'VALUE' => $arPropVals['VALUE'],
            ];
        }
        return $items;
    }

    /**
     * Получение свойств для создания профиля
     *
     * @param string $profile_id
     * @return array
     */
    public static function getPropertiesByIds($type, $prop, $select = '')
    {
        $arFilter = ['ID' => $prop, 'PERSON_TYPE_ID' => $type];
        $dbProps = \CSaleOrderProps::GetList([], $arFilter, false, false, $select);
        while ($arProp = $dbProps->Fetch()) {
            $resProp[] = $arProp;
        }

        return $resProp;
    }

    public static function getProperty(string $profile_id, int $propertyId): array
    {
        $db_propVals = \CSaleOrderUserPropsValue::GetList(array(), array('USER_PROPS_ID' => $profile_id, 'ORDER_PROPS_ID' => $propertyId));
        while ($arPropVals = $db_propVals->Fetch()) {
            return $arPropVals;
        }
        return [];
    }

    /**
     * Проверка возврата к своей учетной записи
     *
     * @return bool
     */
    public static function canBuyerReturn(): bool
    {
        $id = Application::getInstance()->getContext()->getRequest()->getCookie(COOKIE_NAME_ACCOUNT);
        if (!$id) {
            return false;
        }
        $user = CUser::GetByID($id)->fetch();
        if (!$user) {
            return false;
        }
        return true;
    }


    public static function getInfoUser($userID)
    {
        $filter = ['ID' => $userID, 'ACTIVE' => 'Y'];
        $arParams["FIELDS"] = array("EMAIL", "ACTIVE", "NAME", "LAST_NAME");
        $rsUsers = \CUser::GetList(array('sort' => 'asc'), 'sort', $filter, $arParams);
        while ($arUser = $rsUsers->Fetch()) {
            return $arUser;
        }
        return [];
    }


//    public static function addProfile(&$arFields, $request)
//    {
//        $dataUserTo1C = $arFields; // массив отвечает за данные для 1с
//        $individual = !empty($request->get('COMPANY')) ? false : true;
//
//        if ($individual && !empty($request->get('INDIVIDUAL'))) {
//            $type = B2B_FIZ_PERSON_TYPE_ID;
//            $profileName = trim(implode(' ', [$arFields['LAST_NAME'], $arFields['NAME'], $arFields['SECOND_NAME']]));
//            $idsProp = self::FIZ_FIELDS_PROFILE_REGISTER;
//
//        } else {
//            $type = B2B_UR_PERSON_TYPE_ID;
//            $profileName = $request->get('COMPANY');
//            $idsProp = self::YR_FIELDS_PROFILE_REGISTER;
//        }
//
//        if ($type > 0 && !empty($profileName)) {
//            $arProfileFields = [
//                "NAME" => $profileName,
//                "USER_ID" => $arFields['USER_ID'],
//                "PERSON_TYPE_ID" => $type
//            ];
//
//
//            $arProps = self::getPropertiesByIds($type, $idsProp, ['ID', 'NAME', 'CODE']);
//            $PROFILE_ID = \CSaleOrderUserProps::Add($arProfileFields);
//            if ($PROFILE_ID) {
//                $PROPS = [];
//                $dataUserTo1C['PROFILE_ID'] = $PROFILE_ID;
//                $dataUserTo1C['PROFILE_TYPE_ID'] = $type;
//
//                foreach ($arProps as $prop) {
//
//                    $PROPS[$prop['ID']] = [
//                        "USER_PROPS_ID" => $PROFILE_ID,
//                        "ORDER_PROPS_ID" => $prop['ID'],
//                        "NAME" => $prop['NAME'],
//                        "CODE" => $prop['CODE'],
//                    ];
//
//                    if ($prop['CODE'] == 'PROFILE_ID') $PROPS[$prop['ID']]['VALUE'] = $PROFILE_ID;
//                    if ($prop['CODE'] == 'COMPANY_UF_CRM_1724146140') $PROPS[$prop['ID']]['VALUE'] = $request->get('COMPANY_UF_CRM_1724146140');
//
//
//                    if ($prop['CODE'] == 'COMPANY_TITLE') $PROPS[$prop['ID']]['VALUE'] = $dataUserTo1C['COMPANY'] = $profileName;
//                    if ($prop['CODE'] == 'COMPANY_ADR') $PROPS[$prop['ID']]['VALUE'] = $dataUserTo1C['COMPANY_ADR'] = $request->get('COMPANY_ADR');
//                    if ($prop['CODE'] == 'INN') $PROPS[$prop['ID']]['VALUE'] = $dataUserTo1C['INN'] = $request->get('INN');
//
//
//                    if ($prop['CODE'] == 'EDO_USE') $PROPS[$prop['ID']]['VALUE'] = $request->get('USE_EDO') ? 'Y' : '';
//                    if ($prop['CODE'] == 'EDO') $PROPS[$prop['ID']]['VALUE'] = $request->get('EDO');
//
//                    if ($prop['CODE'] == 'ADDRESS') $PROPS[$prop['ID']]['VALUE'] = $request->get('ADDRESS');
//                    if ($prop['CODE'] == 'LOCATION') {
//                        if ($arFields['UF_LOCATION_ID']) $PROPS[$prop['ID']]['VALUE'] = $arFields['UF_LOCATION_ID'];
//                        if ($arFields['UF_LOCATION_CODE']) $PROPS[$prop['ID']]['VALUE'] = $arFields['UF_LOCATION_CODE'];
//                    }
//
//                    if ($prop['CODE'] == 'COMPANY_UF_COMPANY_LAST_NAME') $PROPS[$prop['ID']]['VALUE'] = $dataUserTo1C['COMPANY_UF_COMPANY_LAST_NAME'] = $arFields['LAST_NAME'];
//                    if ($prop['CODE'] == 'COMPANY_UF_COMPANY_NAME') $PROPS[$prop['ID']]['VALUE'] = $dataUserTo1C['COMPANY_UF_COMPANY_NAME'] = $arFields['NAME'];
//                    if ($prop['CODE'] == 'COMPANY_UF_COMPANY_SECOND_NAME') $PROPS[$prop['ID']]['VALUE'] = $arFields['SECOND_NAME'];
//                    if ($prop['CODE'] == 'COMPANY_PHONE') $PROPS[$prop['ID']]['VALUE'] = $arFields['PERSONAL_PHONE'];
//                    if ($prop['CODE'] == 'COMPANY_EMAIL') $PROPS[$prop['ID']]['VALUE'] = $arFields['EMAIL'];
//
//                }
//
//                foreach ($PROPS as &$prop) {
//                    $prop['ID'] = \CSaleOrderUserPropsValue::Add($prop);
//                    if ($prop['CODE'] == 'COMPANY_UF_CRM_1724146140') $idPropGuid = $prop['ID'];
//                }
//
//                $res1C = \Webfly\Helper\User::uploadUserAndProfile($dataUserTo1C);
//
//                $guidProfile = self::getGuidFrom1C($res1C);
//                if ($idPropGuid && $guidProfile) self::updateProfile($idPropGuid, ['VALUE' => $guidProfile]);
//
//                $guidUser = \Webfly\Helper\User::getGuidFrom1C($res1C);
//                \Webfly\Helper\User::updateFieldsProfileAndGuid($arFields['USER_ID'], $PROFILE_ID, $guidUser, $arFields); //протягивает $arFields, чтобы заполнить его гуид, и прис оздании контактв передать данный гуид
//            }
//        }
//    }

    public static function addProfileRegister(&$arFieldsUser, $request)
    {
        $dataUserTo1C = $arFieldsUser; // массив отвечает за данные для 1с
        $dataRequest = $request->getPostList()->toArray(); //пост запрос с формы
        $individual = empty($dataRequest['COMPANY']) && !empty($dataRequest['INDIVIDUAL']) ? true : false;

        $objProfile = new Profile($individual);
        $objProfile->setNameProfile($arFieldsUser, $dataRequest['COMPANY']);
        $objProfile->addProfileBuyer($arFieldsUser['USER_ID']);

        if ($dataRequest[Profile::PROP_ORDER_CODE_GUID['CODE']]) $objProfile->setCodeGuidValue($dataRequest[Profile::PROP_ORDER_CODE_GUID['CODE']]);

        if ($dataRequest[Profile::PROP_ORDER_COMPANY_ADR['CODE']]) $objProfile->setCompanyAdrValue($dataRequest[Profile::PROP_ORDER_COMPANY_ADR['CODE']]);
        if ($dataRequest[Profile::PROP_ORDER_COMPANY_INN['CODE']]) $objProfile->setCompanyInnValue($dataRequest[Profile::PROP_ORDER_COMPANY_INN['CODE']]);
        if ($dataRequest[Profile::PROP_ORDER_EDO_USE['CODE']]) $objProfile->setEdoUseValue($dataRequest[Profile::PROP_ORDER_EDO_USE['CODE']]);
        if ($dataRequest[Profile::PROP_ORDER_EDO['CODE']]) $objProfile->setEdoValue($dataRequest[Profile::PROP_ORDER_EDO['CODE']]);
        if ($dataRequest[Profile::PROP_ORDER_ADDRESS['CODE']]) $objProfile->setAddressValue($dataRequest[Profile::PROP_ORDER_ADDRESS['CODE']]);

        if ($arFieldsUser['UF_LOCATION_ID']) $objProfile->setLocationValue($arFieldsUser['UF_LOCATION_ID']);
        else if ($arFieldsUser['UF_LOCATION_CODE']) $objProfile->setLocationValue($arFieldsUser['UF_LOCATION_CODE']);

        if ($arFieldsUser['LAST_NAME']) $objProfile->setBuyerLastNameValue($arFieldsUser['LAST_NAME']);
        if ($arFieldsUser['NAME']) $objProfile->setBuyerNameValue($arFieldsUser['NAME']);
        if ($arFieldsUser['SECOND_NAME']) $objProfile->setBuyerSecondNameValue($arFieldsUser['SECOND_NAME']);
        if ($arFieldsUser['PERSONAL_PHONE']) $objProfile->setEmailValue($arFieldsUser['PERSONAL_PHONE']);
        if ($arFieldsUser['EMAIL']) $objProfile->setPhoneValue($arFieldsUser['EMAIL']);

        $objProfile->addPropsProfileBuyer();
        $objProfile->arrayTo1C($dataUserTo1C);

        $res1C = \Webfly\Helper\User::uploadUserAndProfile($dataUserTo1C);

        $guidProfile = self::getGuidFrom1C($res1C);
        if ($guidProfile) $objProfile->updatePropCodeGuid($guidProfile);

        $guidUser = \Webfly\Helper\User::getGuidFrom1C($res1C);

        \Webfly\Helper\User::updateFieldsProfileAndGuid($arFieldsUser['USER_ID'], $objProfile->idProfile, $guidUser, $arFieldsUser); //протягивает $arFields, чтобы заполнить его гуид, и прис оздании контактв передать данный гуид
    }


    static function getGuidFrom1C($res1C)
    {
        $guidProfile = '';
        if ($res1C['result']['result'] == 'OK') $guidProfile = $res1C['result']['guid_kontragent'];

        return $guidProfile;
    }

    static function updateProfile($propID, $updProp)
    {
        \CSaleOrderUserPropsValue::update($propID, $updProp);
    }


}
