<?php

namespace Webfly\Download;

class UserFrom1C
{


    //   const FILE_PATH = '/bitrix/php_interface/lib/Webfly/Download/dataContact1C_14.xml'; // miniDataContact1C.xml'; dataContact1C_14.xml
    // const FILE_PATH_NEW = '/bitrix/php_interface/lib/Webfly/Download/dataContact1C_UPD_BX_19.xml'; //miniDataContact1C_UPD_BX.xml'; dataContact1C_UPD_BX_14.xml
    const FILE_PATH = '/bitrix/php_interface/lib/Webfly/Download/miniDataContact1C.xml'; // miniDataContact1C.xml'; dataContact1C_14.xml
    const FILE_PATH_NEW = '/bitrix/php_interface/lib/Webfly/Download/miniDataContact1C_UPD_BX.xml'; //miniDataContact1C_UPD_BX.xml'; dataContact1C_UPD_BX_14.xml
    const FILE_PATH_ERROR = '/bitrix/php_interface/lib/Webfly/Download/dataErrors.csv';
    const FILE_PATH_OPEN_LINE = '/bitrix/php_interface/lib/Webfly/Download/dataOpenLine.csv';
    const FILE_PATH_OPEN_LINE_NEW = '/bitrix/php_interface/lib/Webfly/Download/dataOpenLineNew.csv';

    public $arErrors = [];
    public $arUsers1C = [];
    public $arNewIDs = [];
    public $arInfo = [];
    public $arProfile1C = [];


    function __construct()
    {
    }

    function execute()
    {
        \Bitrix\Main\Loader::includeModule('main');
        die;
        $this->parseXml();
        if (!empty($this->arUsers1C)) {
            $this->getUser();
            $this->getContact();

            $this->fullIdUserXml();
        }

        $this->writeError();
    }

    function executeUpdateUserAndContact()
    {
        die;
        \Bitrix\Main\Loader::includeModule('main');
        $this->parseXml();

        if (!empty($this->arUsers1C)) {
            $this->getUserGuidAndData();
            $this->getContactGuid();
            $this->fullIdUserXml();
        }
        $this->writeError();
    }

    function getUserOpenLines()
    {
        die;
        //  $fp = fopen($_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH_OPEN_LINE_NEW, 'w');
        //$ar = ['ID'];
        //     fputcsv($fp, $ar);

        \Bitrix\Main\Loader::includeModule('main');
        \Bitrix\Main\Loader::includeModule('imopenlines');

        $resOpenUser = \Bitrix\Imopenlines\Model\UserRelationTable::GetList([]);

        while ($arRes = $resOpenUser->fetch()) {
            $dbrUser = \Bitrix\Main\UserTable::getList([
                'select' => ['ID', 'LOGIN', 'PERSONAL_PHONE'],
                'filter' => ['ID' => $arRes['USER_ID'],'!PERSONAL_PHONE' => false]
            ]);

            while ($user = $dbrUser->fetch()) {

                $arGroup = \Bitrix\Main\UserTable::getUserGroupIds($user['ID']);
                if (count($arGroup) == 1 && current($arGroup) == 2) {

                    $arFields['PERSONAL_PHONE'] = '';
                    $userUpd = new \CUser;
                    echo '<pre>';
                    var_dump($user);
                    echo '</pre>';
                   // $res = $userUpd->Update($user['ID'], $arFields);
                } else {


                }


            }
            //       $ar = [];
            //     $ar[] = $arRes['USER_ID'];
            //    fputcsv($fp, $ar);

        }


        // fclose($fp);
    }

    function getAndWriteUserOpenLine()
    {
        $fp = fopen($_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH_OPEN_LINE, 'w');

        $dbrUser = \Bitrix\Main\UserTable::getList([
            'order' => ['ID' => 'ASC'],
            'select' => ['ID', 'LOGIN'],
        ]);
        $ar = ['ID', 'LOGIN'];
        fputcsv($fp, $ar);
        while ($arRes = $dbrUser->fetch()) {
            $arGroup = \Bitrix\Main\UserTable::getUserGroupIds($arRes['ID']);
            if (count($arGroup) == 1 && current($arGroup) == 2) {
                $ar = [];
                $ar[] = $arRes['ID'];
                $ar[] = $arRes['LOGIN'];
                fputcsv($fp, $ar);
            }
            if ($arGroup == [2, 3, 4]) {
                $ar = [];
                $ar[] = $arRes['ID'];
                $ar[] = $arRes['LOGIN'];
                fputcsv($fp, $ar);
            }


        }

        fclose($fp);
    }

    function parseXml()
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

                                if ($contFacePartner["@attributes"]["Тип"] == 'Пользователь') {
                                    if (!empty($contFacePartner["Телефон"]) || !empty($contFacePartner["Почта"])) {
                                        $info = $contFacePartner;
                                        $info["ФИО"] = $contFacePartner["ФИО"];
                                        if (is_array($contFacePartner["Телефон"])) {
                                            $info["Телефон"] = current($contFacePartner["Телефон"]);
                                        }
                                        $info["GUID_Партнера"] = $guidPartner;
                                        unset($info["@attributes"]);
                                        $this->arUsers1C[] = $info;
                                    }
                                }

                            } else {

                                foreach ($contFacePartner as $key2 => $contFace) {
                                    if ($contFace["@attributes"]["Тип"] == 'Пользователь') {
                                        if (!empty($contFace["Телефон"]) || !empty($contFace["Почта"])) {
                                            $info = $contFace;
                                            $info["ФИО"] = $contFace["ФИО"];
                                            if (is_array($contFace["Телефон"])) {
                                                $info["Телефон"] = current($contFace["Телефон"]);
                                            }
                                            $info["GUID_Партнера"] = $guidPartner;
                                            unset($info["@attributes"]);
                                            $this->arUsers1C[] = $info;
                                        }
                                    }
                                }

                            }

                        }
                    }
                }
            }
        }
    }

    function getPhoneFormat($phoneUser, $addPlus = false)//+79030303, 8(903)0303, 999450
    {
        $phone = $phoneUser;
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneUser);//79030303, 89030303, 999450

        if (!empty($phoneNumber)) {
            $firstSymbol = mb_substr($phoneNumber, 0, 1); //7, 8, 9

            if ($firstSymbol == 9) // 999450
                $phone = $phoneNumber; // 999450
            else
                $phone = mb_substr($phoneNumber, 1); // 9030303

            if ($addPlus) {
                if ($firstSymbol == 9) // 999450
                    $phone = '+' . $phone;// +999450
                else // 9030303
                    $phone = '+7' . $phone;//  +79030303
            }
        }
        return $phone;
    }

    function getPhonesArraySearch($phone)
    {
        $phonesForSearch = [];
        $phoneClear = preg_replace('/[^0-9]/', '', $phone);
        $firstSymbol = mb_substr($phoneClear, 0, 1);
        $phoneTrim = mb_substr($phoneClear, 1);
        $phonesForSearch[] = $phoneClear;
        if ($firstSymbol == 7) {
            $phonesForSearch[] = "8{$phoneTrim}";
        } elseif ($firstSymbol == 8) {
            $phonesForSearch[] = "7{$phoneTrim}";
        }
        return $phonesForSearch;
    }

    function getUser()
    {
        $this->arErrors['USER'] = [];

        foreach ($this->arUsers1C as $key => $user) {
            $filter = [];

            if ($user['Почта']) $filter[] = ['%EMAIL' => $user['Почта']];
            if ($user['Телефон']) {
                $phoneTrim = $this->getPhoneFormat($user['Телефон']);

                $filter[] = ['%PHONE_NUMBER' => $phoneTrim,];
                $filter[] = ['%PERSONAL_PHONE' => $phoneTrim];
                $filter[] = ['%PERSONAL_MOBILE' => $phoneTrim];

                $filter[] = ['%PHONE_NUMBER_CLEAR' => $phoneTrim];
                $filter[] = ['%PERSONAL_PHONE_CLEAR' => $phoneTrim];
                $filter[] = ['%PERSONAL_MOBILE_CLEAR' => $phoneTrim];
            }

            if (!empty($filter)) {
                $arGetUser = [];
                $filterOr = ['LOGIC' => 'OR'] + $filter;

                $dbrUser = \Bitrix\Main\UserTable::getList([
                    'order' => ['ID' => 'ASC'],
                    'filter' => $filterOr,
                    'select' => ['PERSONAL_PHONE_CLEAR', 'PERSONAL_MOBILE_CLEAR', 'PHONE_NUMBER_CLEAR', 'ID', 'PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'EMAIL', 'UF_CONTACT_GUID', "UF_PARTNER_GUID", 'LAST_NAME', 'NAME', 'SECOND_NAME'],
                    'runtime' => [
                        new \Bitrix\Main\Entity\ExpressionField('PERSONAL_PHONE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['PERSONAL_PHONE']),
                        new \Bitrix\Main\Entity\ExpressionField('PERSONAL_MOBILE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['PERSONAL_MOBILE']),
                        new \Bitrix\Main\Entity\ExpressionField('PHONE_NUMBER_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['PHONE_AUTH.PHONE_NUMBER']),
                    ]
                ]);

                while ($resUser = $dbrUser->fetch()) $arGetUser[] = $resUser;
                if (!empty($arGetUser)) {
                    if (count($arGetUser) == 1) {

                        if ($user['ID'] != current($arGetUser)['ID'] || empty($user['ID'])) {   //если в 1С есть ИД и оно отличаетс от ИД битркиса
                            $this->arNewIDs[$user['GUID_Контакт']] = current($arGetUser)['ID'];
                        }

                        $this->arInfo[$user['GUID_Контакт']] = 'EXIST';
                        $this->updateUser(current($arGetUser), $user);

                    } else {
                        $this->arInfo[$user['GUID_Контакт']] .= ' USER_DOUBLE';
                        $this->arErrors['USER'] = array_merge($this->arErrors['USER'], array_column($arGetUser, 'ID'));
                    }
                } else {
                    $this->addUser($user);
                }
            }
        }
    }

    function getContactGuid()
    {

        $this->arErrors['CONTACT'] = [];

        foreach ($this->arUsers1C as $key => $user) {
            $findGuid = false;
            $resContact = \Bitrix\Crm\ContactTable::getList([
                    'filter' => ['UF_CRM_1723022437' => $user['GUID_Контакт']],
                    'select' => ['ID', 'UF_CRM_1723022437'],
                ]
            );
            while ($ob_contact = $resContact->fetch()) {
                $findGuid = true;
                if (strpos($this->arInfo[$user['GUID_Контакт']], 'OPEN_LINE') !== false) $this->arInfo[$user['GUID_Контакт']] .= ' OPEN_LINE_CONTACT';
                else $this->updateContactFio($ob_contact, $user);
            }

            if (!$findGuid) {
                $arContact = [];
                if ($user['Почта']) {
                    $filter = [
                        'VALUE' => $user['Почта'],
                        'TYPE_ID' => 'EMAIL',
                        'MULTI.TYPE_ID' => ['EMAIL', 'PHONE'],
                        'ENTITY_ID' => 'CONTACT',
                        'MULTI.ENTITY_ID' => 'CONTACT',
                    ];

                    $resultEmail = \Bitrix\Crm\FieldMultiTable::getList([
                        'filter' => $filter,
                        'select' => ['ELEMENT_ID', 'MULTI'],
                        'group' => ['CRM_FIELD_MULTI_MULTI_TYPE_ID'],
                        'runtime' => [
                            new \Bitrix\Main\Entity\ReferenceField('MULTI', \Bitrix\Crm\FieldMultiTable::class, ['=this.ELEMENT_ID' => 'ref.ELEMENT_ID']),
                        ]
                    ]);
                    while ($ob_item = $resultEmail->fetch()) {
                        $arContact[$ob_item["ELEMENT_ID"]][$ob_item["CRM_FIELD_MULTI_MULTI_ID"]] = $ob_item;
                    }
                }
                if ($user['Телефон']) {
                    $phonesForSearch = self::getPhonesArraySearch($user['Телефон']);

                    $filter = [
                        'PHONE_CLEAR' => $phonesForSearch,
                        'TYPE_ID' => 'PHONE',
                        'MULTI.TYPE_ID' => ['PHONE', 'EMAIL'],
                        'ENTITY_ID' => 'CONTACT',
                        'MULTI.ENTITY_ID' => 'CONTACT',
                    ];

                    $resultPhone = \Bitrix\Crm\FieldMultiTable::getList([
                        'filter' => $filter,
                        'select' => ['ELEMENT_ID', 'MULTI'],
                        'group' => ['CRM_FIELD_MULTI_MULTI_TYPE_ID'],
                        'runtime' => [
                            new \Bitrix\Main\Entity\ReferenceField('MULTI', \Bitrix\Crm\FieldMultiTable::class, ['=this.ELEMENT_ID' => 'ref.ELEMENT_ID']),
                            new \Bitrix\Main\Entity\ExpressionField('PHONE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['VALUE']),
                        ]
                    ]);
                    while ($ob_item = $resultPhone->fetch()) {
                        $arContact[$ob_item["ELEMENT_ID"]][$ob_item["CRM_FIELD_MULTI_MULTI_ID"]] = $ob_item;
                    }
                }

                if (!empty($arContact)) {
                    if (count($arContact) == 1) { //найден 1 контакт
                        $arPhone = [];
                        $arEmail = [];
                        foreach (current($arContact) as $data) {
                            if ($data["CRM_FIELD_MULTI_MULTI_TYPE_ID"] == "PHONE") {
                                $arPhone[] = preg_replace('/[^0-9]/', '', $data["CRM_FIELD_MULTI_MULTI_VALUE"]);
                            }
                            if ($data["CRM_FIELD_MULTI_MULTI_TYPE_ID"] == "EMAIL") {
                                $arEmail[] = $data["CRM_FIELD_MULTI_MULTI_VALUE"];
                            }
                        }

                        $resContact = \Bitrix\Crm\ContactTable::getList([
                                'filter' => ['ID' => key($arContact)],
                                'select' => ['ID', 'UF_CRM_1723022437'],
                            ]
                        );
                        while ($ob_contact = $resContact->fetch()) {
                            if ($this->arInfo[$user['GUID_Контакт']] == 'OPEN_LINE') $this->arInfo[$user['GUID_Контакт']] .= ' OPEN_LINE_CONTACT';
                            else  $this->updateContact($ob_contact, $user, $arEmail, $arPhone);
                        }
                    } else {
                        $this->arInfo[$user['GUID_Контакт']] .= ' DOUBLE_CONTACT';
                        $this->arErrors['CONTACT'] = array_merge(array_keys($arContact), $this->arErrors['CONTACT']);
                    }
                } else {//контакт не найден
                    if ($this->arInfo[$user['GUID_Контакт']] != 'OPEN_LINE') $this->addContact($user);
                }
            }
        }
    }

    function getContact()
    {
        $this->arErrors['CONTACT'] = [];

        foreach ($this->arUsers1C as $key => $user) {
            $arContact = [];

            if ($user['Почта']) {
                $filter = [
                    'VALUE' => $user['Почта'],
                    'TYPE_ID' => 'EMAIL',
                    'MULTI.TYPE_ID' => ['EMAIL', 'PHONE'],
                    'ENTITY_ID' => 'CONTACT',
                    'MULTI.ENTITY_ID' => 'CONTACT',
                ];

                $resultEmail = \Bitrix\Crm\FieldMultiTable::getList([
                    'filter' => $filter,
                    'select' => ['ELEMENT_ID', 'MULTI'],
                    'group' => ['CRM_FIELD_MULTI_MULTI_TYPE_ID'],
                    'runtime' => [
                        new \Bitrix\Main\Entity\ReferenceField('MULTI', \Bitrix\Crm\FieldMultiTable::class, ['=this.ELEMENT_ID' => 'ref.ELEMENT_ID']),
                    ]
                ]);
                while ($ob_item = $resultEmail->fetch()) {
                    $arContact[$ob_item["ELEMENT_ID"]][$ob_item["CRM_FIELD_MULTI_MULTI_ID"]] = $ob_item;
                }
            }
            if ($user['Телефон']) {
                $phonesForSearch = self::getPhonesArraySearch($user['Телефон']);

                $filter = [
                    'PHONE_CLEAR' => $phonesForSearch,
                    'TYPE_ID' => 'PHONE',
                    'MULTI.TYPE_ID' => ['PHONE', 'EMAIL'],
                    'ENTITY_ID' => 'CONTACT',
                    'MULTI.ENTITY_ID' => 'CONTACT',
                ];

                $resultPhone = \Bitrix\Crm\FieldMultiTable::getList([
                    'filter' => $filter,
                    'select' => ['ELEMENT_ID', 'MULTI'],
                    'group' => ['CRM_FIELD_MULTI_MULTI_TYPE_ID'],
                    'runtime' => [
                        new \Bitrix\Main\Entity\ReferenceField('MULTI', \Bitrix\Crm\FieldMultiTable::class, ['=this.ELEMENT_ID' => 'ref.ELEMENT_ID']),
                        new \Bitrix\Main\Entity\ExpressionField('PHONE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['VALUE']),
                    ]
                ]);
                while ($ob_item = $resultPhone->fetch()) {
                    $arContact[$ob_item["ELEMENT_ID"]][$ob_item["CRM_FIELD_MULTI_MULTI_ID"]] = $ob_item;
                }
            }


            if (!empty($arContact)) {
                if (count($arContact) == 1) { //найден 1 контакт
                    $arPhone = [];
                    $arEmail = [];
                    foreach (current($arContact) as $data) {
                        if ($data["CRM_FIELD_MULTI_MULTI_TYPE_ID"] == "PHONE") {
                            $arPhone[] = preg_replace('/[^0-9]/', '', $data["CRM_FIELD_MULTI_MULTI_VALUE"]);
                        }
                        if ($data["CRM_FIELD_MULTI_MULTI_TYPE_ID"] == "EMAIL") {
                            $arEmail[] = $data["CRM_FIELD_MULTI_MULTI_VALUE"];
                        }
                    }

                    $resContact = \Bitrix\Crm\ContactTable::getList([
                            'filter' => ['ID' => key($arContact)],
                            'select' => ['ID', 'UF_CRM_1723022437'],
                        ]
                    );
                    while ($ob_contact = $resContact->fetch()) {
                        $this->updateContact($ob_contact, $user, $arEmail, $arPhone);
                    }
                } else $this->arErrors['CONTACT'] = $arContact;
            } else {//контакт не найден
                $this->addContact($user);
            }
        }
    }

    function updateContact($ob_contact, $user, $arEmail, $arPhone)
    {
        $arFields = [];


        $arFio = explode(' ', $user['ФИО']);
        if ($arFio[0]) $arFields['LAST_NAME'] = $arFio[0];
        if ($arFio[1]) $arFields['NAME'] = $arFio[1];
        if ($arFio[2]) $arFields['SECOND_NAME'] = $arFio[2];


        if (!empty($user['Почта']) && !in_array($user['Почта'], $arEmail)) {
            $arFields["FM"]['EMAIL']["n0"]['VALUE'] = $user['Почта'];
            $arFields["FM"]['EMAIL']["n0"]['VALUE_TYPE'] = 'WORK';
        }

        if (!empty($user['Телефон'])) {
            $phoneTrim = $this->getPhoneFormat($user['Телефон']);

            $existPhone = true;
            foreach ($arPhone as $itemPhone) {//идем по всем номерам и ищем подстроку телефона из 1с, если подстрока-телееофн не найденв, то добавим телеон
                if (strpos($itemPhone, $phoneTrim) === false) $existPhone = false;
            }

            if (!$existPhone) {
                $arFields["FM"]['PHONE']["n0"]['VALUE'] = $this->getPhoneFormat($user['Телефон'], true);
                $arFields["FM"]['PHONE']["n0"]['VALUE_TYPE'] = 'WORK';
            }
        }

        if ($ob_contact["UF_CRM_1723022437"] != $user['GUID_Контакт']) {
            $arFields["UF_CRM_1723022437"] = $user['GUID_Контакт'];
        }

        if (!empty($arFields)) {
            $contactUpd = new \CCrmContact(false);
            if ($contactUpd->Update($ob_contact['ID'], $arFields)) {
                $this->arInfo[$user['GUID_Контакт']] .= ' UPD_CONTACT';
            } else {
                $this->arInfo[$user['GUID_Контакт']] .= ' ERROR_UPD_CONTACT';
            }
        }
    }

    function updateContactFio($ob_contact, $user)
    {
        $arFields = [];

        $arFio = explode(' ', $user['ФИО']);
        if ($arFio[0]) $arFields['LAST_NAME'] = $arFio[0];
        if ($arFio[1]) $arFields['NAME'] = $arFio[1];
        if ($arFio[2]) $arFields['SECOND_NAME'] = $arFio[2];


        if (!empty($arFields)) {
            $contactUpd = new \CCrmContact(false);
            if ($contactUpd->Update($ob_contact['ID'], $arFields)) {
                $this->arInfo[$user['GUID_Контакт']] .= ' UPD_FIO_CONTACT';
            } else {
                $this->arInfo[$user['GUID_Контакт']] .= ' ERROR_UPD_FIO_CONTACT';
            }
        }
    }

    function addContact($user)
    {
        $problemUser = false;

        if (strpos($this->arInfo[$user['GUID_Контакт']], 'ERROR ') !== false) {
            $problemUser = true;
        }

        if (!$problemUser) {
            $arFields = [
                'TYPE_ID' => '1',
                'SOURCE_ID' => '4',
                "ASSIGNED_BY_ID" => 1,
                'UF_CRM_1723022437' => $user['GUID_Контакт'],
            ];

            $arFio = explode(' ', $user['ФИО']);
            if ($arFio[0]) $arFields['LAST_NAME'] = $arFio[0];
            if ($arFio[1]) $arFields['NAME'] = $arFio[1];
            if ($arFio[2]) $arFields['SECOND_NAME'] = $arFio[2];

            if (!empty($user['Телефон'])) {
                $phone = $this->getPhoneFormat($user['Телефон'], true);

                $arFields["FM"]['PHONE']["n0"]['VALUE'] = $phone;
                $arFields["FM"]['PHONE']["n0"]['VALUE_TYPE'] = 'WORK';
            }

            if (!empty($user['Почта'])) {
                $arFields['EMAIL'] = $user['Почта'];
            }

            $contactAdd = new \CCrmContact(false);

            $ID = rand(15000, 20000);
            $ID = $contactAdd->Add($arFields);

            if (intval($ID) > 0) {
                $this->arInfo[$user['GUID_Контакт']] .= ' ADD_CONTACT';
            } else {
                $this->arInfo[$user['GUID_Контакт']] .= ' ERROR_ADD_CONTACT ' . $contactAdd->LAST_ERROR;
            }

            return $ID;
        }
    }

    function addUser($user)
    {
        $arFields = [
            "LID" => "s3",
            "ACTIVE" => "Y",
            "GROUP_ID" => [3, 6, 4, 10, 23],
            "PASSWORD" => "123456",
            "CONFIRM_PASSWORD" => "123456",
            'UF_CONTACT_GUID' => $user['GUID_Контакт'],
            'UF_PARTNER_GUID' => $user['GUID_Партнера'],
            'UF_1C' => 'да',
        ];

        $arFio = explode(' ', $user['ФИО']);
        if ($arFio[0]) $arFields['LAST_NAME'] = $arFio[0];
        if ($arFio[1]) $arFields['NAME'] = $arFio[1];
        if ($arFio[2]) $arFields['SECOND_NAME'] = $arFio[2];

        if (!empty($user['Телефон'])) {
            $phone = $this->getPhoneFormat($user['Телефон'], true);

            $arFields['PHONE_NUMBER'] = $phone;
            $arFields['PERSONAL_PHONE'] = $phone;
            $arFields['PERSONAL_MOBILE'] = $phone;
            $arFields['LOGIN'] = $phone;
        }
        if (!empty($user['Почта'])) {
            $arFields['EMAIL'] = $user['Почта'];
            if (!array_key_exists('LOGIN', $arFields))
                $arFields['LOGIN'] = $user['Почта'];
        }

        $userAdd = new \CUser;

        $ID = rand(15000, 20000);

        $ID = $userAdd->Add($arFields);
        if (intval($ID) > 0) {
            $this->arInfo[$user['GUID_Контакт']] = 'ADD';
            $this->arNewIDs[$user['GUID_Контакт']] = $ID;
        } else {
            $this->arInfo[$user['GUID_Контакт']] = 'ERROR ' . $userAdd->LAST_ERROR;
        }
    }

    function updateUser($arGetUser, $user)
    {
        $arFields = [];

        $arFio = explode(' ', $user['ФИО']);
        if ($arFio[0]) $arFields['LAST_NAME'] = $arFio[0];
        if ($arFio[1]) $arFields['NAME'] = $arFio[1];
        if ($arFio[2]) $arFields['SECOND_NAME'] = $arFio[2];

        if (!empty($user['Телефон'])) {
            $phone = $this->getPhoneFormat($user['Телефон'], true);

            if (empty($arGetUser['PHONE_NUMBER']) && $phone != $arGetUser['PHONE_NUMBER']) $arFields['PHONE_NUMBER'] = $phone;
            if (empty($arGetUser['PERSONAL_PHONE']) && $phone != $arGetUser['PERSONAL_PHONE']) $arFields['PERSONAL_PHONE'] = $phone;
            if (empty($arGetUser['PERSONAL_MOBILE']) && $phone != $arGetUser['PERSONAL_MOBILE']) $arFields['PERSONAL_MOBILE'] = $phone;
        }
        if (!empty($user['Почта']) && empty($arGetUser['EMAIL']) && $arGetUser['EMAIL'] != $user['Почта']) $arFields['EMAIL'] = $user['Почта'];

        if ($arGetUser['UF_CONTACT_GUID'] != $user['GUID_Контакт']) $arFields['UF_CONTACT_GUID'] = $user['GUID_Контакт'];
        if ($arGetUser['UF_PARTNER_GUID'] != $user['GUID_Партнера']) $arFields['UF_PARTNER_GUID'] = $user['GUID_Партнера'];


        if (!empty($arFields)) {
            $userUpd = new \CUser;
            if ($userUpd->Update($arGetUser['ID'], $arFields)) {
                $this->arInfo[$user['GUID_Контакт']] = 'UPD';
            } else {
                $this->arInfo[$user['GUID_Контакт']] = $userUpd->LAST_ERROR;
            }
        }
    }


    function updateUserFio($arGetUser, $user)
    {
        $arFields = [];

        $arFio = explode(' ', $user['ФИО']);
        if ($arFio[0]) $arFields['LAST_NAME'] = $arFio[0];
        if ($arFio[1]) $arFields['NAME'] = $arFio[1];
        if ($arFio[2]) $arFields['SECOND_NAME'] = $arFio[2];

        if (!empty($arFields)) {
            $userUpd = new \CUser;
            if ($userUpd->Update($arGetUser['ID'], $arFields)) {
                $this->arInfo[$user['GUID_Контакт']] = 'UPD_FIO';
            } else {
                $this->arInfo[$user['GUID_Контакт']] = $userUpd->LAST_ERROR;
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
                                if ($this->arNewIDs[(string)$contFacePartner->КонтактноеЛицо->GUID_Контакт]) {
                                    $contFacePartner->КонтактноеЛицо->addChild('BX_ID', $this->arNewIDs[(string)$contFacePartner->КонтактноеЛицо->GUID_Контакт]);
                                }
                                if ($this->arInfo[(string)$contFacePartner->КонтактноеЛицо->GUID_Контакт]) {
                                    $contFacePartner->КонтактноеЛицо->addChild('INFO', $this->arInfo[(string)$contFacePartner->КонтактноеЛицо->GUID_Контакт]);
                                }
                            } else {

                                foreach ($contFacePartner->КонтактноеЛицо as $key2 => $contFace) {
                                    if ($this->arNewIDs[(string)$contFace->GUID_Контакт]) {
                                        $contFace->addChild('BX_ID', $this->arNewIDs[(string)$contFace->GUID_Контакт]);
                                    }
                                    if ($this->arInfo[(string)$contFace->GUID_Контакт]) {
                                        $contFace->addChild('INFO', $this->arInfo[(string)$contFace->GUID_Контакт]);
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

