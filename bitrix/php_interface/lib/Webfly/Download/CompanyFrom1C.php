<?php

namespace Webfly\Download;

class CompanyFrom1C
{

    const DEFAULT_ASSIGNED_ID = 1;

    const FILE_PATH = '/bitrix/php_interface/lib/Webfly/Download/dataContact03.xml'; // miniDataContact1C.xml'; dataContact1C_14.xml
    //   const FILE_PATH_NEW = '/bitrix/php_interface/lib/Webfly/Download/dataContact03.xml'; //miniDataContact1C_UPD_BX.xml'; dataContact1C_UPD_BX_14.xml
    // const FILE_PATH = '/bitrix/php_interface/lib/Webfly/Download/miniDataContact03.xml'; // miniDataContact1C.xml'; dataContact1C_14.xml
    // const FILE_PATH_NEW = '/bitrix/php_interface/lib/Webfly/Download/miniDataContact1C_Company_UPD_LINK_BX.xml'; //miniDataContact1C_UPD_BX.xml'; dataContact1C_UPD_BX_14.xml
    const FILE_PATH_ERROR = '/bitrix/php_interface/lib/Webfly/Download/dataErrors.csv';
    const FILE_PATH_OPEN_LINE = '/bitrix/php_interface/lib/Webfly/Download/dataOpenLine.csv';

    public $arErrors = [];
    public $arNewIDs = [];
    public $arInfo = [];
    public $arProfile1C = [];

    public $arManagerBX24 = [];
    public $log = true;


    function __construct()
    {
    }


    function executeUpdateProfile()
    {

        die;
        \Bitrix\Main\Loader::includeModule('iblock');
        \Bitrix\Main\Loader::includeModule('main');
        \Bitrix\Main\Loader::includeModule('sale');


        $this->parseXmlContactAndCompany();
        $this->getLinkAssigned();


        if (!empty($this->arProfile1C)) {
            $this->getAssignedContacts1c();

        }
        die;
        if (!empty($this->arProfile1C)) {
            //   $this->getUserProfileGuid();
            //   $this->fullIdUserXml();
        }
    }

    function getLinkAssigned()
    {
        $resManager = \CIBlockElement::GetList([], ['ACTIVE' => 'Y', 'IBLOCK_ID' => '110'], false, false, ['NAME', 'PROPERTY_MANAGER_CRM']);
        while ($ob = $resManager->fetch()) {
            $this->arManagerBX24[$ob['NAME']] = $ob["PROPERTY_MANAGER_CRM_VALUE"];
        }

    }

    function updateAssignedContacts1c($obContact)
    {
        foreach ($this->arProfile1C as $data) {
            foreach ($data['CONTACT'] as $contact1C) {
                if ($contact1C['GUID_Контакт'] == $obContact['UF_CRM_1723022437']) {
                    $companyIDs = [];
                    $arFields = [];

                    if (!empty($contact1C['Менеджер']))
                        $assignedById1C = key_exists($contact1C['Менеджер'], $this->arManagerBX24) ? $this->arManagerBX24[$contact1C['Менеджер']] : self::DEFAULT_ASSIGNED_ID;
                    else
                        $assignedById1C = self::DEFAULT_ASSIGNED_ID;

                    $companyIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($obContact['ID']);


                    $arFields['ASSIGNED_BY_ID'] = $assignedById1C;


                    if (!empty($arFields)) {

                        if (!empty($companyIDs)) {
                            echo '<pre>';
                            var_dump($companyIDs);
                            echo '</pre>';
                            foreach ($companyIDs as $company) {
                                $obCompany = \Bitrix\Crm\CompanyTable::getList([
                                    'order' => ['ID' => 'desc'],
                                    'filter' => ['ID' => $company],
                                    'select' => ['ID', 'ASSIGNED_BY_ID',],
                                ])->fetch();

                                if ($assignedById1C != $obCompany["ASSIGNED_BY_ID"]) {
                                    var_dump('COMPANY UPD');

                                    $companyUpd = new \CCrmCompany(false);
                                    $res = $companyUpd->Update($company, $arFields);
                                }
                            }
                        }
                        echo '<pre>';
                        var_dump($obContact['ID']);
                        echo '</pre>';
                        if ($assignedById1C != $obContact["ASSIGNED_BY_ID"]) {
                            echo '<pre>';
                            var_dump('CONTACT UPD');
                            echo '</pre>';


                            $contactUpd = new \CCrmContact(false);
                            $res = $contactUpd->Update($obContact['ID'], $arFields);
                        }

                    }
                }
            }
        }
    }

    function getAssignedContacts1c()
    {
        $arContactGuid = [];

        foreach ($this->arProfile1C as $data) {
            $arContactGuid = array_merge($arContactGuid, array_column($data['CONTACT'], 'GUID_Контакт'));
        }

        $arContactGuidUniq = array_unique($arContactGuid);

        if (!empty($arContactGuidUniq)) {
            $resGuid = \Bitrix\Crm\ContactTable::getList([
                'order' => ['ID' => 'desc'],
                'filter' => ['UF_CRM_1723022437' => $arContactGuidUniq],
                'select' => ['ID', 'NAME', 'ASSIGNED_BY_ID', 'UF_CRM_1723022437'],
            ]);
            while ($ob = $resGuid->fetch()) {
                $this->updateAssignedContacts1c($ob);
            }
        }


        die;
        $propGuid = 'UF_CRM_1723022437';
        $resGuid = \Bitrix\Crm\ContactTable::getList([
            'order' => ['ID' => 'desc'],
            'select' => ['ID', 'UF_CRM_1724832101', $propGuid, 'NAME'],
            'limit' => 8000
        ]);
        while ($ob = $resGuid->fetch()) {

            $arFields ['UF_CRM_1724832101'] = 1;
            if (!empty($ob[$propGuid])) $arFields ['UF_CRM_1724832101'] = 0;

            if ($ob['UF_CRM_1724832101'] != $arFields ['UF_CRM_1724832101']) {
                $contactUpd = new \CCrmContact(false);
                $res = $contactUpd->Update($ob['ID'], $arFields);
            }
        }
    }


    function deleteDoublePhoneAndEmail()
    {
        $date = new \Bitrix\Main\Type\DateTime('29.08.2024 00:00:00');

        $entityID = 'CONTACT'; //'COMPANY';
        $resCompany = \Bitrix\Crm\CompanyTable::getList([
            'filter' => ['!UF_CRM_1724146140' => false, '>=DATE_MODIFY' => $date],
            'limit' => 500,
            'select' => ['ID', 'TITLE',],
        ]);

        $resContact = \Bitrix\Crm\ContactTable::getList([
            'filter' => ['!UF_CRM_1723022437' => false,
                //     '>=DATE_MODIFY' => $date,
            ],
            'limit' => 2000,
            'select' => ['ID', 'NAME',],
        ]);

        while ($ob_company = $resContact->fetch()) {

            $arPhone = [];
            $arEmail = [];
            $resultMulti = \Bitrix\Crm\FieldMultiTable::getList([
                'filter' => ['ELEMENT_ID' => $ob_company['ID'], 'ENTITY_ID' => $entityID, 'TYPE_ID' => ['EMAIL', 'PHONE']],
            ]);

            while ($ob_item = $resultMulti->fetch()) {
                if ($ob_item["TYPE_ID"] == "PHONE") {
                    $phoneTrim = $this->getPhoneFormat($ob_item["VALUE"]);
                    $arPhone[$phoneTrim][] = $ob_item['ID'];
                }

                if ($ob_item["TYPE_ID"] == "EMAIL") {
                    $phoneTrim = mb_strtolower($ob_item["VALUE"]);
                    $arEmail[$phoneTrim][] = $ob_item['ID'];
                }

            }


            echo '<pre>';
            var_dump($ob_company);
            var_dump($arEmail);
            echo '</pre>';
            if ($arEmail) {
                foreach ($arEmail as $list) {
                    $arFields = [];
                    if (count($list) > 1) {

                        foreach ($list as $key => $item) {
                            if ($key != 0) $arFields[$item] = [
                                "VALUE" => '',
                                "VALUE_TYPE" => "WORK",
                            ];
                        }
                        if (!empty($arFields)) {
                            var_dump('UPD');
                            $contactFields = [
                                "FM" => [
                                    "EMAIL" => $arFields
                                ],
                            ];

                            echo '<pre>';
                            var_dump($contactFields);
                            echo '</pre>';

                            $contactEntity = new \CCrmContact(false);
                            $isUpdateSuccess = $contactEntity->Update($ob_company['ID'], $contactFields);
                            var_dump('res');
                            var_dump($isUpdateSuccess);
                        }


                    }

                }
            }

            if ($arPhone) {
                foreach ($arPhone as $listPhone) {
                    $arFields = [];
                    if (count($listPhone) > 1) {

                        foreach ($listPhone as $key => $item) {
                            if ($key != 0) $arFields[$item] = [
                                "VALUE" => '',
                                "VALUE_TYPE" => "WORK",
                            ];
                        }
                        if (!empty($arFields)) {
                            var_dump('UPD');
                            $contactFields = [
                                "FM" => [
                                    "PHONE" => $arFields
                                ],
                            ];

                            echo '<pre>';
                            var_dump($contactFields);
                            echo '</pre>';

                            $contactEntity = new \CCrmContact(false);
                            $isUpdateSuccess = $contactEntity->Update($ob_company['ID'], $contactFields);
                            var_dump('res');
                            var_dump($isUpdateSuccess);
                        }


                    }

                }
            }


        }

    }


    function updateContacts1c()
    {

        die;
        $propGuid = 'UF_CRM_1723022437';
        $resGuid = \Bitrix\Crm\ContactTable::getList([
            'order' => ['ID' => 'desc'],
            'select' => ['ID', 'UF_CRM_1724832101', $propGuid, 'NAME'],
            'limit' => 8000
        ]);
        while ($ob = $resGuid->fetch()) {

            $arFields ['UF_CRM_1724832101'] = 1;
            if (!empty($ob[$propGuid])) $arFields ['UF_CRM_1724832101'] = 0;

            if ($ob['UF_CRM_1724832101'] != $arFields ['UF_CRM_1724832101']) {
                $contactUpd = new \CCrmContact(false);
                $res = $contactUpd->Update($ob['ID'], $arFields);
            }
        }
    }


    function linkCompanyContact()
    {
        die;
        foreach ($this->arProfile1C as $keyUser => $user) {
            if ($user['PROFILES']) {
                $arExistUserGUID = [];
                $arExistUserID = [];
                $arUserIDs = array_column($user['USERS'], 'ID');

                $dbrUser = \Bitrix\Main\UserTable::getList(['filter' => ['ID' => $arUserIDs], 'select' => ['ID', 'UF_CONTACT_GUID']]); //находим всех пользователей, оставляем только тех не из ОЛ
                while ($resUser = $dbrUser->fetch()) {
                    $arGroup = \Bitrix\Main\UserTable::getUserGroupIds($resUser['ID']);
                    if (count($arGroup) == 1 && current($arGroup) == 2) {
                        $keySearchUser = array_search($resUser['ID'], $arUserIDs);
                        $this->arInfo[$user['USERS'][$keySearchUser]['GUID_Контакт']] .= 'OPEN_LINE ';
                    } else {
                        $arExistUserID[] = $resUser['ID'];
                        $arExistUserGUID[$resUser['ID']] = $resUser['UF_CONTACT_GUID'];
                    }
                }

                if (!empty($arExistUserID)) {
                    $arIDsContact = [];
                    $arIDsCompany = [];
                    $arContactGuid = array_column($user['USERS'], 'GUID_Контакт');

                    $resContactGuid = \Bitrix\Crm\ContactTable::getList([
                        'filter' => ['UF_CRM_1723022437' => $arContactGuid],
                        'select' => ['ID', 'UF_CRM_1723022437',],
                    ]);
                    while ($ob_contact = $resContactGuid->fetch()) {
                        $arIDsContact[] = $ob_contact['ID'];
                    }
                    echo '<pre>';
                    var_dump($arIDsContact);
                    echo '</pre>';

                    if (!empty($arIDsContact)) {
                        $arCompanyGuid = array_column($user['PROFILES'], 'GUID_Контрагент');
                        if ($arCompanyGuid) {

                            $resCompanyGuid = \Bitrix\Crm\CompanyTable::getList([
                                'filter' => ['UF_CRM_1724146140' => $arCompanyGuid],
                                'select' => ['ID', 'UF_CRM_1724146140',],
                            ]);
                            while ($ob_company = $resCompanyGuid->fetch()) {
                                $arIDsCompany[] = $ob_company['ID'];
                            }

                            if (!empty($arIDsCompany)) {
                                foreach ($arIDsContact as $idContact) {
                                    $arFields = [];
                                    $arFields ['COMPANY_IDS'] = $arIDsCompany;
                                    echo '<pre>';
                                    var_dump($arFields);
                                    echo '</pre>';
                                    $contactUpd = new \CCrmContact(false);
                                    $res = $contactUpd->Update($idContact, $arFields);
                                    if ($res) {

                                    } else {
                                        echo '<pre>';
                                        var_dump($res);
                                        echo '</pre>';

                                    }

                                }
                            }

                            var_dump($arIDsContact);
                        }
                    }
                }

            }
        }
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
                                if (!empty($contFacePartner["ID"])) {
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
                                    $this->arInfo[$contFacePartner['GUID_Контакт']] .= 'NO_ID ';
                                }

                            } else {

                                foreach ($contFacePartner as $key2 => $contFace) {

                                    if ($contFace["@attributes"]["Тип"] == 'Пользователь' && !empty($contFace["ID"])) {
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

                                    if (!empty($contragent["Почта"])) {
                                        if (is_array($contragentPartner["Почта"])) {
                                            $info["Почта"] = current($contragentPartner["Почта"]);
                                        }
                                        $info["Почта"] = mb_strtolower($info["Почта"]);
                                    }

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

                                        if (!empty($contragent["Почта"])) {
                                            if (is_array($contragent["Почта"])) {
                                                $info["Почта"] = current($contragent["Почта"]);
                                            }
                                            $info["Почта"] = mb_strtolower($contragent["Почта"]);
                                        }

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

    function parseXmlContactAndCompany()
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
                                if (!empty($contFacePartner["ID"])) {
                                    $info = $contFacePartner;

                                    $this->arProfile1C[$guidPartner]['CONTACT'][] = [
                                        'ID' => $info['ID'],
                                        'GUID_Контакт' => $info['GUID_Контакт'],
                                        'Менеджер' => $info['ОсновнойМенеджер'],
                                    ];
                                } else {
                                    $this->arInfo[$contFacePartner['GUID_Контакт']] .= 'NO_ID ';
                                }

                            } else {

                                foreach ($contFacePartner as $key2 => $contFace) {

                                    if ($contFace["@attributes"]["Тип"] == 'Пользователь' && !empty($contFace["ID"])) {
                                        $info = $contFace;

                                        $this->arProfile1C[$guidPartner]['CONTACT'][] = [
                                            'ID' => $info['ID'],
                                            'GUID_Контакт' => $info['GUID_Контакт'],
                                            'Менеджер' => $info['ОсновнойМенеджер'],
                                        ];
                                    } else {
                                        $this->arInfo[$contFace['GUID_Контакт']] .= 'NO_ID ';
                                    }

                                }

                            }

                        }

                        foreach ($partner["КонтрагентыПартнера"] as $key1 => $contragentPartner) {
                            if (key_exists('@attributes', $contragentPartner)) {

                                if ($contragentPartner["@attributes"]["Тип"] == 'ПрофильДоставки' && $this->arProfile1C[$guidPartner]) {
                                    $info = $contragentPartner;

                                    $infoDaa = [
                                        'ID' => $info['ID'],
                                        'GUID_Контрагент' => $info['GUID_Контакт'],
                                        'Менеджер' => $info['ОсновнойМенеджер'],
                                    ];
                                    $this->arProfile1C[$guidPartner]['COMPANY'][] = $infoDaa;
                                }

                            } else {

                                foreach ($contragentPartner as $key2 => $contragent) {
                                    if ($contragent["@attributes"]["Тип"] == 'ПрофильДоставки' && $this->arProfile1C[$guidPartner]) {
                                        $info = $contragent;

                                        $infoDaa = [
                                            'ID' => $info['ID'],
                                            'GUID_Контрагент' => $info['GUID_Контакт'],
                                            'Менеджер' => $info['ОсновнойМенеджер'],
                                        ];

                                        $this->arProfile1C[$guidPartner]['COMPANY'][] = $infoDaa;
                                    }
                                }

                            }

                        }

                    }
                }
            }

        }
    }


    function addCompany($profile1C)
    {
        $arFields = [];
        $arFields['TITLE'] = $profile1C['Наименование'];
        $arFields['ASSIGNED_BY_ID'] = '1';
        $arFields['UF_CRM_1659349383'] = 83671;
        $arFields["UF_CRM_1724146140"] = $profile1C['GUID_Контрагент'];

        if ($profile1C['Фамилия']) $arFields['UF_COMPANY_LAST_NAME'] = $profile1C ['Фамилия'];
        if ($profile1C['Имя']) $arFields['UF_COMPANY_NAME'] = $profile1C ['Имя'];
        if ($profile1C['Отчество']) $arFields['UF_COMPANY_SECOND_NAME'] = $profile1C ['Отчество'];

        if (!empty($profile1C['Телефон'])) {
            $phone = $this->getPhoneFormat($profile1C['Телефон'], true);

            $arFields["FM"]['PHONE']["n0"]['VALUE'] = $phone;
            $arFields["FM"]['PHONE']["n0"]['VALUE_TYPE'] = 'WORK';
        }
        if (!empty($profile1C['Почта'])) {
            $arFields["FM"]['EMAIL']["n0"]['VALUE'] = $profile1C['Почта'];
            $arFields["FM"]['EMAIL']["n0"]['VALUE_TYPE'] = 'WORK';
        }


        if ($profile1C['ВидКонтрагента'] == 'Физическое лицо') $arFields['UF_CRM_1724146192'] = '309';
        else  $arFields['UF_CRM_1724146192'] = '308';


        echo '<pre>';
        var_dump('ADD');
        var_dump($arFields);
        echo '</pre>';


        $ID = rand(15000, 20000);
        if (!$this->log) {
            $companyAdd = new \CCrmCompany(false);
            $ID = $companyAdd->Add($arFields);

            if (intval($ID) > 0) {
                $this->arInfo[$profile1C['GUID_Контрагент']] .= ' ADD_COMPANY-' . $ID;

                if ($profile1C['ВидКонтрагента'] != 'Физическое лицо' && !empty($profile1C['ИНН'])) {
                    $requisiteFields = array(
                        "ENTITY_TYPE_ID" => 4, /*реквизит для компании*/
                        "ENTITY_ID" => $ID, /* ид нашей созданной компании*/
                        "PRESET_ID" => 1, // тип реквизитов
                        "NAME" => $arFields['TITLE'],
                        "RQ_INN" => $profile1C['ИНН'],
                    );
                    $requisite = new \Bitrix\Crm\EntityRequisite();
                    $resultRequisit = $requisite->add($requisiteFields);
                    if ($resultRequisit->isSuccess()) {
                        $this->arInfo[$profile1C['GUID_Контрагент']] .= ' ADD_REQUISITE';
                    } else {
                        $this->arInfo[$profile1C['GUID_Контрагент']] .= ' ERROR_ADD_REQUISITE';
                    }
                }

            } else {
                $this->arInfo[$profile1C['GUID_Контрагент']] .= ' ERROR_ADD_COMPANY ' . $companyAdd->LAST_ERROR;
            }
        } else {
            $this->arInfo[$profile1C['GUID_Контрагент']] .= ' ADD_COMPANY-' . $ID;
        }


        return $ID;
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


    function getUserProfileGuid()
    {
        foreach ($this->arProfile1C as $keyUser => $user) {
            if ($user['PROFILES']) {
                $arExistUserGUID = [];
                $arExistUserID = [];
                $arUserIDs = array_column($user['USERS'], 'ID');

                $dbrUser = \Bitrix\Main\UserTable::getList(['filter' => ['ID' => $arUserIDs], 'select' => ['ID', 'UF_CONTACT_GUID']]); //находим всех пользователей, оставляем только тех не из ОЛ
                while ($resUser = $dbrUser->fetch()) {
                    $arGroup = \Bitrix\Main\UserTable::getUserGroupIds($resUser['ID']);
                    if (count($arGroup) == 1 && current($arGroup) == 2) {
                        $keySearchUser = array_search($resUser['ID'], $arUserIDs);
                        $this->arInfo[$user['USERS'][$keySearchUser]['GUID_Контакт']] .= 'OPEN_LINE ';
                    } else {
                        $arExistUserID[] = $resUser['ID'];
                        $arExistUserGUID[$resUser['ID']] = $resUser['UF_CONTACT_GUID'];
                    }
                }

                if (!empty($arExistUserID)) {
                    $arProfileID = [];

                    foreach ($arExistUserID as $userID) {
                        $arProfileIDs = array_column($user['PROFILES'], 'ID');
                        $db_sales = \CSaleOrderUserProps::GetList([], ["USER_ID" => $userID, 'PERSON_TYPE_ID' => [5, 6]]); //получаем существующие профили покупателя пользователя
                        while ($ar_sales = $db_sales->Fetch()) {
                            if (in_array($ar_sales['ID'], $arProfileIDs)) {
                                $ar_sales ['GUID_USER'] = $arExistUserGUID[$ar_sales['USER_ID']];
                                $ar_sales ['EXIST_1C'] = in_array($ar_sales['ID'], $arProfileIDs);
                                $arProfileID[$ar_sales['ID']] = $ar_sales;
                            }
                        }
                    }
                    if (empty($arProfileID)) {
                        foreach ($user['USERS'] as $us) {
                            if (empty($this->arInfo[$us['GUID_Контакт']])) $this->arInfo[$us['GUID_Контакт']] .= 'NO_FIND_PROFILES ';
                        }
                    } else {
                        foreach ($user['PROFILES'] as $key => $profile1C) {
                            if ($arProfileID[$profile1C['ID']]) {

                                $findGuid = false;
                                $resCompanyGuid = \Bitrix\Crm\CompanyTable::getList([
                                    'filter' => ['UF_CRM_1724146140' => $profile1C['GUID_Контрагент']],
                                    'select' => ['ID', 'UF_CRM_1724146140', 'TITLE'],
                                ]);
                                while ($ob_companyGuid = $resCompanyGuid->fetch()) {

                                    $arPhone = [];
                                    $arEmail = [];
                                    if ($profile1C['Телефон']) $arPhone[] = $profile1C['Телефон'];
                                    if ($profile1C['Почта']) $arEmail[] = $profile1C['Почта'];
                                    $resultMulti = \Bitrix\Crm\FieldMultiTable::getList([
                                        'filter' => ['ELEMENT_ID' => $ob_companyGuid['ID'], 'ENTITY_ID' => 'COMPANY', 'TYPE_ID' => ['EMAIL', 'PHONE']],
                                    ]);
                                    while ($ob_item = $resultMulti->fetch()) {
                                        if ($ob_item['VALUE']) {
                                            if ($ob_item['TYPE_ID'] == 'PHONE') $arPhone[] = preg_replace('/[^0-9]/', '', $ob_item['VALUE']);
                                            if ($ob_item['TYPE_ID'] == 'EMAIL') $arEmail[] = mb_strtolower($ob_item['VALUE']);
                                        }
                                    }


                                    if ($profile1C['ИНН'] && $profile1C['ВидКонтрагента'] != 'Физическое лицо') {
                                        $requisite = new \Bitrix\Crm\EntityRequisite();
                                        $rs = $requisite->getList([
                                            "filter" => ["RQ_INN" => $profile1C['ИНН'], "PRESET_ID" => [1, 2], 'ENTITY_TYPE_ID' => 4]
                                        ]);
                                        if ($reqData = $rs->fetch()) {
                                            $arCompany[$reqData['ENTITY_ID']]['ID_REQ'] = $reqData['ID'];
                                            $arCompany[$reqData['ENTITY_ID']]['ID_COMPANY'] = $reqData['ENTITY_ID'];
                                        }
                                    }

                                    echo '<pre>';
                                    var_dump('FIND_GUID ' . $ob_companyGuid['ID']);
                                    echo '</pre>';

                                    $this->updateCompany($ob_companyGuid, $profile1C, $arEmail, $arPhone, $arCompany[$ob_companyGuid['ID']]);

                                    $findGuid = true;
                                }

                                if (!$findGuid) {
                                    $arCompany = [];
                                    if ($profile1C['ВидКонтрагента'] == 'Физическое лицо') {

                                        if ($profile1C['Почта']) {
                                            $filter = [
                                                'VALUE' => $profile1C['Почта'],
                                                'TYPE_ID' => 'EMAIL',
                                                'MULTI.TYPE_ID' => ['EMAIL', 'PHONE'],
                                                'ENTITY_ID' => 'COMPANY',
                                                'MULTI.ENTITY_ID' => 'COMPANY',
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
                                                $arCompany[$ob_item["ELEMENT_ID"]][$ob_item["CRM_FIELD_MULTI_MULTI_ID"]] = $ob_item;
                                            }
                                        }
                                        if ($profile1C['Телефон']) {
                                            $phonesForSearch = self::getPhonesArraySearch($profile1C['Телефон']);
                                            $filter = [
                                                'PHONE_CLEAR' => $phonesForSearch,
                                                'TYPE_ID' => 'PHONE',
                                                'MULTI.TYPE_ID' => ['PHONE', 'EMAIL'],
                                                'ENTITY_ID' => 'COMPANY',
                                                'MULTI.ENTITY_ID' => 'COMPANY',
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
                                                $arCompany[$ob_item["ELEMENT_ID"]][$ob_item["CRM_FIELD_MULTI_MULTI_ID"]] = $ob_item;
                                            }
                                        }


                                    } else {
                                        if ($profile1C['ИНН']) {
                                            $requisite = new \Bitrix\Crm\EntityRequisite();
                                            $rs = $requisite->getList([
                                                "filter" => ["RQ_INN" => $profile1C['ИНН'], "PRESET_ID" => [1, 2], 'ENTITY_TYPE_ID' => 4]
                                            ]);
                                            if ($reqData = $rs->fetch()) {
                                                $arCompany[$reqData['ENTITY_ID']]['ID_REQ'] = $reqData['ID'];
                                                $arCompany[$reqData['ENTITY_ID']]['ID_COMPANY'] = $reqData['ENTITY_ID'];
                                            }
                                        }
                                    }

                                    if (!empty($arCompany)) {
                                        if (count($arCompany) == 1) { //найден 1 company
                                            $arPhone = [];
                                            $arEmail = [];
                                            foreach (current($arCompany) as $data) {
                                                if (is_array($data) && $data["CRM_FIELD_MULTI_MULTI_TYPE_ID"] == "PHONE") $arPhone[] = preg_replace('/[^0-9]/', '', $data["CRM_FIELD_MULTI_MULTI_VALUE"]);
                                                if (is_array($data) && $data["CRM_FIELD_MULTI_MULTI_TYPE_ID"] == "EMAIL") $arEmail[] = mb_strtolower($data["CRM_FIELD_MULTI_MULTI_VALUE"]);
                                            }

                                            $resCompany = \Bitrix\Crm\CompanyTable::getList([
                                                'filter' => ['ID' => key($arCompany)],
                                                'select' => ['ID', 'UF_CRM_1724146140', 'TITLE'],
                                            ]);
                                            while ($ob_company = $resCompany->fetch()) {
                                                $existReqArr = [];
                                                if ($arCompany[$ob_company['ID']]['ID_REQ']) $existReqArr = $arCompany[$ob_company['ID']];
                                                $this->updateCompany($ob_company, $profile1C, $arEmail, $arPhone, $existReqArr);
                                            }
                                        } else {
                                            $this->arInfo[$profile1C['GUID_Контрагент']] .= ' DOUBLE_COMPANY';
                                        }
                                    } else {//compay не найден
                                        $this->addCompany($profile1C);
                                    }


                                }
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
                    if (empty($this->arInfo[$us['GUID_Контакт']])) $this->arInfo[$us['GUID_Контакт']] .= 'NO_PROFILES_USER ';
                }
            }
        }
    }


    function updateCompany($ob_company, $profile1C, $arEmail, $arPhone, $infoInn = [])
    {
        $arFields = [];
        $arFields['TITLE'] = $profile1C['Наименование'];
        $arFields['UF_CRM_1659349383'] = 83671;
        $arFields["UF_CRM_1724146140"] = $profile1C['GUID_Контрагент'];

        if ($profile1C['Фамилия']) $arFields['UF_COMPANY_LAST_NAME'] = $profile1C ['Фамилия'];
        if ($profile1C['Имя']) $arFields['UF_COMPANY_NAME'] = $profile1C ['Имя'];
        if ($profile1C['Отчество']) $arFields['UF_COMPANY_SECOND_NAME'] = $profile1C ['Отчество'];;

        if (!empty($profile1C['Почта']) && !in_array($profile1C['Почта'], $arEmail)) {
            $this->arInfo[$profile1C['GUID_Контрагент']] .= ' EMAIL';
            $arFields["FM"]['EMAIL']["n0"]['VALUE'] = $profile1C['Почта'];
            $arFields["FM"]['EMAIL']["n0"]['VALUE_TYPE'] = 'WORK';
        }

        if (!empty($profile1C['Телефон'])) {
            $phoneTrim = $this->getPhoneFormat($profile1C['Телефон']);

            $existPhone = true;
            foreach ($arPhone as $itemPhone) {//идем по всем номерам и ищем подстроку телефона из 1с, если подстрока-телееофн не найденв, то добавим телеон
                if (strpos($itemPhone, $phoneTrim) === false) {
                    $existPhone = false;
                    break;
                } else {
                    break;
                }
            }

            if (!$existPhone) {
                $this->arInfo[$profile1C['GUID_Контрагент']] .= ' PHONE';
                $arFields["FM"]['PHONE']["n0"]['VALUE'] = $this->getPhoneFormat($profile1C['Телефон'], true);
                $arFields["FM"]['PHONE']["n0"]['VALUE_TYPE'] = 'WORK';
            }
        }


        if ($profile1C['ВидКонтрагента'] == 'Физическое лицо') $arFields['UF_CRM_1724146192'] = '309';
        else  $arFields['UF_CRM_1724146192'] = '308';


        echo '<pre>';
        var_dump('UPD ' . $ob_company['ID']);
        echo '</pre>';


        if (!$this->log) {
            $contactUpd = new \CCrmCompany(false);
            if ($contactUpd->Update($ob_company['ID'], $arFields)) {
                $this->arInfo[$profile1C['GUID_Контрагент']] .= ' UPD_COMPANY-' . $ob_company['ID'];

            } else {
                $this->arInfo[$profile1C['GUID_Контрагент']] .= ' ERROR_UPD_COMPANY';
            }
        }

        if (!empty($infoInn) && $infoInn['ID_REQ']) {
            $requisiteFields = array(
                "ENTITY_ID" => $ob_company['ID'], /* ид нашей  компании*/
                "NAME" => $arFields['TITLE'],
                "RQ_INN" => $profile1C['ИНН'],
            );
            $requisite = new \Bitrix\Crm\EntityRequisite();
            if (!$this->log) {
                $resultRequisit = $requisite->update($infoInn['ID_REQ'], $requisiteFields);
                if ($resultRequisit->isSuccess()) {
                    $this->arInfo[$profile1C['GUID_Контрагент']] .= ' UPD_REQUISITE-' . $infoInn['ID_REQ'];
                } else {
                    $this->arInfo[$profile1C['GUID_Контрагент']] .= ' ERROR_UPD_REQUISITE-' . $infoInn['ID_REQ'];
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

}

