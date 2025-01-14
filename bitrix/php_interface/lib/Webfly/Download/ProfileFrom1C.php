<?php

namespace Webfly\Download;

class ProfileFrom1C
{


    const FILE_PATH = '/bitrix/php_interface/lib/Webfly/Download/dataContact1C_22.xml'; // miniDataContact1C.xml'; dataContact1C_14.xml
    const FILE_PATH_NEW = '/bitrix/php_interface/lib/Webfly/Download/dataContact1C_UPD_BX_PROFILE_22_TEST.xml'; //miniDataContact1C_UPD_BX.xml'; dataContact1C_UPD_BX_14.xml
    // const FILE_PATH = '/bitrix/php_interface/lib/Webfly/Download/miniDataContact1C.xml'; // miniDataContact1C.xml'; dataContact1C_14.xml
    //  const FILE_PATH_NEW = '/bitrix/php_interface/lib/Webfly/Download/miniDataContact1C_UPD_BX_PROFILE.xml'; //miniDataContact1C_UPD_BX.xml'; dataContact1C_UPD_BX_14.xml
    const FILE_PATH_ERROR = '/bitrix/php_interface/lib/Webfly/Download/dataErrorsProfile.csv';

    public $arErrors = [];
    public $arNewIDs = [];
    public $arInfo = [];
    public $arProfile1C = [];
    public $arProps = [
        5 => [
            'TYPE' => 'YR',
            'PROPS_CODE' => [
                "ВидКонтрагента" => 'COMPANY_UF_CRM_1724146192',
                'GUID_Контрагент' => 'COMPANY_UF_CRM_1724146140',
                'Наименование' => 'COMPANY_TITLE',
                'Телефон' => 'COMPANY_PHONE',
                'Почта' => 'COMPANY_EMAIL',
                'ИНН' => 'INN',
            ]
        ],
        6 => [
            'TYPE' => 'FIZ',
            'PROPS_CODE' => [
                "ВидКонтрагента" => 'COMPANY_UF_CRM_1724146192',
                'GUID_Контрагент' => 'COMPANY_UF_CRM_1724146140',
                'Наименование' => 'COMPANY_TITLE',
                'Телефон' => 'COMPANY_PHONE',
                'Почта' => 'COMPANY_EMAIL',
                'Имя' => 'COMPANY_UF_COMPANY_NAME',
                'Фамилия' => 'COMPANY_UF_COMPANY_LAST_NAME',
                'Отчество' => 'COMPANY_UF_COMPANY_SECOND_NAME',
            ]
        ],
    ];
    public $log = true;


    function __construct()
    {
    }


    function executeUpdateProfile()
    {

        die;
        \Bitrix\Main\Loader::includeModule('main');
        \Bitrix\Main\Loader::includeModule('sale');

        $this->parseXmlUserAndProfile();

        if (!empty($this->arProfile1C)) {
            $this->getUserProfileGuid();
            $this->fullIdUserXml();
        }

        //   $this->writeError();
    }

    function parseXmlUserAndProfile()
    {
        $files[] = $_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH;
        if (!empty($files)) {

            foreach ($files as $file) {
                if (file_exists($file)) {
                    $xml1 = simplexml_load_file($file);
                    $xml = json_decode(json_encode($xml1), true);

                    foreach ($xml["Партнер"] as $key => $partner) {
                        $guidPartner = $partner["@attributes"]["GUID_Партнера"];

                        foreach ($partner["КонтактныеЛицаПартнера"] as $key1 => $contFacePartner) {

                            if (key_exists('@attributes', $contFacePartner)) {

                                if ($contFacePartner["@attributes"]["Тип"] == 'Пользователь' && !empty($contFacePartner["ID"])) {
                                    if (!empty($contFacePartner["Телефон"]) || !empty($contFacePartner["Почта"])) {
                                        $info = $contFacePartner;
                                        $info["ФИО"] = $contFacePartner["ФИО"];
                                        if (is_array($contFacePartner["Телефон"])) {
                                            $info["Телефон"] = current($contFacePartner["Телефон"]);
                                        }
                                        $info["Телефон"] = preg_replace('/[^0-9]/', '', $info["Телефон"]);
                                        $info["GUID_Партнера"] = $guidPartner;
                                        unset($info["@attributes"]);
                                        $this->arUsers1C[] = $info;


                                        $this->arProfile1C[$guidPartner]['USERS'][] = [
                                            'ID' => $info['ID'],
                                            'GUID_Контакт' => $info['GUID_Контакт'],
                                        ];
                                    } else {
                                        $this->arInfo[$contFacePartner['GUID_Контакт']] .= 'NO_PHONE_OR_EMAIL ';
                                    }
                                } else {
                                    $this->arInfo[$contFacePartner['GUID_Контакт']] .= 'NO_ID ';
                                }

                            } else {

                                foreach ($contFacePartner as $key2 => $contFace) {

                                    if ($contFace["@attributes"]["Тип"] == 'Пользователь' && !empty($contFace["ID"])) {
                                        if (!empty($contFace["Телефон"]) || !empty($contFace["Почта"])) {
                                            $info = $contFace;
                                            $info["ФИО"] = $contFace["ФИО"];
                                            if (is_array($contFace["Телефон"])) {
                                                $info["Телефон"] = current($contFace["Телефон"]);
                                            }
                                            $info["Телефон"] = preg_replace('/[^0-9]/', '', $info["Телефон"]);
                                            $info["GUID_Партнера"] = $guidPartner;
                                            unset($info["@attributes"]);
                                            $this->arUsers1C[] = $info;


                                            $this->arProfile1C[$guidPartner]['USERS'][] = [
                                                'ID' => $info['ID'],
                                                'GUID_Контакт' => $info['GUID_Контакт'],
                                            ];
                                        } else {
                                            $this->arInfo[$contFace['GUID_Контакт']] .= 'NO_PHONE_OR_EMAIL ';
                                        }
                                    } else {
                                        $this->arInfo[$contFace['GUID_Контакт']] .= 'NO_ID ';
                                    }

                                }

                            }

                        }

                        foreach ($partner["КонтрагентыПартнера"] as $key1 => $contragentPartner) {
                            if (key_exists('@attributes', $contragentPartner)) {

                                if ($contragentPartner["@attributes"]["Тип"] == 'ПрофильДоставки' && $this->arProfile1C[$guidPartner]) {
                                    // if (!empty($contragentPartner["Телефон"]) || !empty($contragentPartner["Почта"])) {
                                    $info = $contragentPartner;
                                    if (is_array($contragentPartner["Телефон"])) {
                                        $info["Телефон"] = current($contragentPartner["Телефон"]);
                                    }
                                    $info["Телефон"] = preg_replace('/[^0-9]/', '', $info["Телефон"]);
                                    $info["ID"] = !empty($info['ID']) ? $info['ID'] : "";
                                    $info["GUID_Партнера"] = $guidPartner;
                                    unset($info["@attributes"]);
                                    $this->arProfile1C[$guidPartner]['PROFILES'][] = $info;
                                    // }
                                }

                            } else {

                                foreach ($contragentPartner as $key2 => $contragent) {
                                    if ($contragent["@attributes"]["Тип"] == 'ПрофильДоставки' && $this->arProfile1C[$guidPartner]) {
                                        //if (!empty($contragent["Телефон"]) || !empty($contragent["Почта"])) {
                                        $info = $contragent;
                                        if (is_array($contragent["Телефон"])) {
                                            $info["Телефон"] = current($contragent["Телефон"]);
                                        }
                                        $info["Телефон"] = preg_replace('/[^0-9]/', '', $info["Телефон"]);
                                        $info["ID"] = !empty($info['ID']) ? $info['ID'] : "";
                                        $info["GUID_Партнера"] = $guidPartner;
                                        unset($info["@attributes"]);
                                        $this->arProfile1C[$guidPartner]['PROFILES'][] = $info;
                                    }
                                    // }
                                }

                            }

                        }

                    }
                }
            }
        }
    }


    function updateProfile($arProfileID, $profile1C, &$user)
    {
        $arUpdProps = [];
        $typeID = $arProfileID[$profile1C['ID']]['PERSON_TYPE_ID'];
        $profileID = $arProfileID[$profile1C['ID']]['ID'];
        $userId = $arProfileID[$profile1C['ID']]['USER_ID'];

        if (!$this->log && $userId) \CSaleOrderUserProps::Update($profileID, ['NAME' => $profile1C['Наименование'], 'USER_ID' => $userId]);


        $db_props = \CSaleOrderProps::GetList([], ["PERSON_TYPE_ID" => $typeID, "CODE" => $this->arProps[$typeID]['PROPS_CODE']], false, false, ['ID', 'CODE', 'NAME']);
        while ($arProps = $db_props->Fetch()) {
            $arProps['ORDER_PROPS_ID'] = $arProps['ID'];
            $arProps['USER_PROPS_ID'] = $profileID;
            unset($arProps['ID']);
            $arUpdProps[$arProps['CODE']] = $arProps;
        }

        $db_propVals = \CSaleOrderUserPropsValue::GetList([], ['USER_PROPS_ID' => $profileID, 'CODE' => array_keys($arUpdProps)]); // ищу значения свойств конкретного профиля / то есть информацию профиля
        while ($arPropVals = $db_propVals->Fetch()) { //существующие свойства заполняем id
            $arUpdProps[$arPropVals['PROP_CODE']]['ID'] = $arPropVals['ID'];
        }

        foreach ($arUpdProps as $keyProp => $prop) { //все свойства заполняем информацией из 1С
            $arUpdProps[$keyProp]['VALUE'] = $this->fullPropsValue1C($keyProp, $typeID, $profile1C);
        }


        foreach ($arUpdProps as $prop) {
            if ($prop['ID']) {
                if (!$this->log) \CSaleOrderUserPropsValue::Update($prop["ID"], $prop);
            } else {
                if (!$this->log) $res = \CSaleOrderUserPropsValue::Add($prop);
            }
        }
        $this->arInfo[$profile1C['GUID_Контрагент']] .= 'UPD_PROFILE ';

        $arrGuidProfile = array_column($user['PROFILES'], 'GUID_Контрагент'); //если у польователя в 1С несколько профилей с одинаковыми гуид, убираем их из массива
        foreach ($arrGuidProfile as $keyGuid => $guid) {
            if ($guid == $profile1C['GUID_Контрагент']) unset($user['PROFILES'][$keyGuid]);
        }

    }

    function addProfile($arExistUserID, $profile1C)
    {
        $arAddProps = [];
        $typeID = $profile1C['ВидКонтрагента'] == 'Физическое лицо' ? '6' : "5";
        $userId = current($arExistUserID);

        $arFieldsProfile = [
            "NAME" => $profile1C['Наименование'],
            "USER_ID" => $userId,
            "PERSON_TYPE_ID" => $typeID
        ];


        if (!$this->log) $profileID = \CSaleOrderUserProps::Add($arFieldsProfile);
        else   $profileID = rand(10000, 15000);

        $db_props = \CSaleOrderProps::GetList([], ["PERSON_TYPE_ID" => $typeID, "CODE" => $this->arProps[$typeID]['PROPS_CODE']], false, false, ['ID', 'CODE', 'NAME']);
        while ($arProps = $db_props->Fetch()) {
            $arNewProps['ORDER_PROPS_ID'] = $arProps['ID'];
            $arNewProps['NAME'] = $arProps['NAME'];
            $arNewProps['USER_PROPS_ID'] = $profileID;
            $arNewProps['VALUE'] = $this->fullPropsValue1C($arProps['CODE'], $typeID, $profile1C);
            $arAddProps[$arProps['CODE']] = $arNewProps;
        }


        foreach ($arAddProps as $prop) {
            if (!$this->log) {
                $resId = \CSaleOrderUserPropsValue::Add($prop);
            }
        }
        $this->arInfo[$profile1C['GUID_Контрагент']] .= 'ADD_PROFILE ';

        return $profileID;
    }

    function fullPropsValue1C($keyProp, $typeID, $profile1C)
    {
        $value = '';
        if ($keyProp == 'COMPANY_UF_CRM_1724146192') { // если свойтсво ВидКонтрагента просталяем значение
            $value = $profile1C['ВидКонтрагента'] == 'Физическое лицо' ? '309' : "308";
        } else {
            $key = array_search($keyProp, $this->arProps[$typeID]['PROPS_CODE']);
            if ($profile1C[$key]) $value = $profile1C[$key];
        }

        return $value;
    }

    function getUserProfileGuid()
    {
        foreach ($this->arProfile1C as $keyUser => $user) {
            if ($user['PROFILES']) {
                $arExistUserID = [];
                $arUserIDs = array_column($user['USERS'], 'ID');

                $dbrUser = \Bitrix\Main\UserTable::getList(['filter' => ['ID' => $arUserIDs], 'select' => ['ID']]); //находим всех пользователей, оставляем только тех не из ОЛ
                while ($resUser = $dbrUser->fetch()) {
                    $arGroup = \Bitrix\Main\UserTable::getUserGroupIds($resUser['ID']);
                    if (count($arGroup) == 1 && current($arGroup) == 2) {
                        $keySearchUser = array_search($resUser['ID'], $arUserIDs);
                        $this->arInfo[$user['USERS'][$keySearchUser]['GUID_Контакт']] .= 'OPEN_LINE ';
                    } else $arExistUserID[] = $resUser['ID'];
                }

                if (!empty($arExistUserID)) {
                    $arProfileGuid = [];
                    $arProfileID = [];
                    $arExistProfileGuid = [];

                    foreach ($arExistUserID as $userID) {
                        $db_sales = \CSaleOrderUserProps::GetList([], ["USER_ID" => $userID, 'PERSON_TYPE_ID' => array_keys($this->arProps)]); //получаем существующие профили покупателя пользователя
                        while ($ar_sales = $db_sales->Fetch()) {
                            $arProfileID[$ar_sales['ID']] = $ar_sales;
                        }
                    }


                    $arProfileGuid = array_column($user['PROFILES'], 'GUID_Контрагент');
                    $db_propVals = \CSaleOrderUserPropsValue::GetList([], ['CODE' => ['COMPANY_UF_CRM_1724146140'], 'VALUE' => $arProfileGuid],);//получаем профили покупателя у которых усавновлен гуид профиля, добавляем профили в список на обновление
                    while ($arPropVals = $db_propVals->Fetch()) {

                        foreach ($user['PROFILES'] as $key => $profile1C) {
                            if ($arPropVals['VALUE'] == $profile1C['GUID_Контрагент']) {
                                if (empty($user['PROFILES'][$key]['ID'])) {
                                    $this->arNewIDs[$profile1C['GUID_Контрагент']] = $arPropVals['USER_PROPS_ID'];
                                }
                                $user['PROFILES'][$key]['ID'] = $arPropVals['USER_PROPS_ID'];
                            }
                        }

                    }


                    foreach ($user['PROFILES'] as $profile1C) {
                        if ($arProfileID[$profile1C['ID']]) {  //среди профилей пользователя в 1С есть существующий профиль на сайте
                            $this->updateProfile($arProfileID, $profile1C, $user);
                        }
                    }


                    if (!empty($user['PROFILES'])) {  //оставляем только новые профили
                        $guid = [];
                        foreach ($user['PROFILES'] as $keyProfile1C => $profile1C) {
                            $guid[$profile1C['GUID_Контрагент']] += 1;
                            if ($guid[$profile1C['GUID_Контрагент']] > 1) {
                                unset($user['PROFILES'][$keyProfile1C]);
                            }
                        }

                        if (!empty($user['PROFILES'])) {
                            foreach ($user['PROFILES'] as $keyProfile1C => $profile1C) {
                                $resID = $this->addProfile($arExistUserID, $profile1C);
                                $this->arNewIDs[$profile1C['GUID_Контрагент']] = $resID;
                            }
                        }
                    }
                } else {
                    foreach ($user['USERS'] as $us) {
                        if (empty($this->arInfo[$us['GUID_Контакт']])) $this->arInfo[$us['GUID_Контакт']] .= 'NO_FIND_OR_DOUBLE_CONTACT ';
                    }
                }
            } else {
                foreach ($user['USERS'] as $us) {
                    if (empty($this->arInfo[$us['GUID_Контакт']])) $this->arInfo[$us['GUID_Контакт']] .= 'NO_PROFILE ';
                }
            }
        }
    }


    function fullIdUserXml()
    {
        $files[] = $_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH;
        if (!empty($files)) {

            foreach ($files as $file) {
                if (file_exists($file)) {
                    $xml1 = simplexml_load_file($file);

                    foreach ($xml1->Партнер as $partner) {
                        foreach ($partner->КонтактныеЛицаПартнера as $key1 => $contFacePartner) {

                            if (count($contFacePartner->КонтактноеЛицо) == 1) {
                                if ($this->arInfo[(string)$contFacePartner->КонтактноеЛицо->GUID_Контакт]) {
                                    $contFacePartner->КонтактноеЛицо->addChild('INFO', $this->arInfo[(string)$contFacePartner->КонтактноеЛицо->GUID_Контакт]);
                                }
                            } else {
                                foreach ($contFacePartner->КонтактноеЛицо as $key2 => $contFace) {
                                    if ($this->arInfo[(string)$contFace->GUID_Контакт]) {
                                        $contFace->addChild('INFO', $this->arInfo[(string)$contFace->GUID_Контакт]);
                                    }
                                }

                            }
                        }
                        foreach ($partner->КонтрагентыПартнера as $key1 => $contFacePartner) {

                            if (count($contFacePartner->Контрагент) == 1) {
                                if ($this->arNewIDs[(string)$contFacePartner->Контрагент->GUID_Контрагент]) {
                                    $contFacePartner->Контрагент->addChild('BX_ID', $this->arNewIDs[(string)$contFacePartner->Контрагент->GUID_Контрагент]);
                                }
                                if ($this->arInfo[(string)$contFacePartner->Контрагент->GUID_Контрагент]) {
                                    $contFacePartner->Контрагент->addChild('INFO', $this->arInfo[(string)$contFacePartner->Контрагент->GUID_Контрагент]);
                                }
                            } else {
                                foreach ($contFacePartner->Контрагент as $key2 => $contFace) {
                                    if ($this->arNewIDs[(string)$contFace->GUID_Контрагент]) {
                                        $contFace->addChild('BX_ID', $this->arNewIDs[(string)$contFace->GUID_Контрагент]);
                                    }
                                    if ($this->arInfo[(string)$contFace->GUID_Контрагент]) {
                                        $contFace->addChild('INFO', $this->arInfo[(string)$contFace->GUID_Контрагент]);
                                    }
                                }

                            }
                        }
                        $xml1->asXML($_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH_NEW);
                    }
                }
            }
        }
    }

    function writeError()
    {

        $fp = fopen($_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH_ERROR, 'w');

        if (!empty($this->arErrors)) {
            if (!empty($this->arErrors['USER'])) {
                $this->arErrors['USER'] = array_unique($this->arErrors['USER']);
                foreach ($this->arErrors['USER'] as $error) {
                    $ar = [];
                    $ar[] = 'USER';
                    $ar[] = $error;
                    fputcsv($fp, $ar);
                }
            }

            if (!empty($this->arErrors['CONTACT'])) {
                foreach ($this->arErrors['CONTACT'] as $key => $error) {
                    $ar = [];
                    $ar[] = 'CONTACT';
                    $ar[] = $error;
                    fputcsv($fp, $ar);
                }
            }
        } else {
            fputcsv($fp, []);
        }
        fclose($fp);
    }
}

