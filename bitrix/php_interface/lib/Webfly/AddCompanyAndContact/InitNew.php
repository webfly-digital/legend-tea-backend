<?php

namespace Webfly\AddCompanyAndContact;

use Vendor\CRest;

class InitNew
{
    const FILE_PATH = '/bitrix/php_interface/lib/Webfly/AddCompanyAndContact/all.XML';
    const COMPANY_CODE_PROP = 'UF_CRM_1656324443';
    const COMPANY_WORKING_PROP = 'UF_CRM_1656315466';
    const COMPANY_REGION_PROP = 'UF_CRM_1656315172';
    const COMPANY_INFO_PROP = 'UF_CRM_1658911322';
    const COMPANY_ROLE_CONTRACTOR_PROP = 'UF_CRM_1659349383';
    const CONTACT_CODE_PROP = 'UF_CRM_1656324467';
    const CONTACT_WORKING_PROP = 'UF_CRM_1656315517';
    const CONTACT_REGION_PROP = 'UF_CRM_1650217890547';
    const CONTACT_ROLE_CONTRACTOR_PROP = 'UF_CRM_1659342574';
    const CONTACT_ROLE_FACE_PROP = 'UF_CRM_1659342544';
    const CONTACT_JOB_PROP = 'UF_CRM_1659342595';
    const CONTACT_GENDER_PROP = 'UF_CRM_1659344713';
    const CONTACT_INFO_PROP = 'UF_CRM_1659344742';
    const CONTACT_BIRTHDATE_PROP = 'BIRTHDATE';
    const REGION_IBLOCK = 106;
    const WORKING_IBLOCK = 107;
    const ROLE_CONTRACTOR_IBLOCK = 108;
    const ROLE_FACE_IBLOCK = 109;
    const ASSIGNED_IBLOCK = 110;
    const ASSIGNED_ID = 1;


    function __construct()
    {
    }

    public function onlyChangeXMLtoCRM()
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . '/crm-1c-contragent';
        $files = [];
        foreach (glob($dir . '/*.XML') as $file) {
            if (is_file($file)) {
                $files[] = $file;
            }
        }
        \Bitrix\Main\Loader::includeModule('crm');
        // $files[] = $_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH;
        if (!empty($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    $xml1 = simplexml_load_file($file);
                    $xml = json_decode(json_encode($xml1), true);

                    if (!array_key_exists('0', $xml["Контрагент"])) {
                        $newItem = $xml["Контрагент"];
                        unset($xml["Контрагент"]);
                        $xml["Контрагент"][0] = $newItem;
                    }
                    $listCompany = [];
                    $listCompanyСode = [];
                    $listContact = [];
                    foreach ($xml["Контрагент"] as $key => $item) { //всегда создаём компании, убираем функционал контактов
                        $existCompany = $item["ИНН"];
                        $listCompany[$item["@attributes"]['ГУИД']]['CODE'] = $item["@attributes"]['ГУИД'];
                        $listCompany[$item["@attributes"]['ГУИД']]['NAME'] = $item["Наименование"];
                        $listCompany[$item["@attributes"]['ГУИД']]['INN'] = $existCompany;
                        $listCompany[$item["@attributes"]['ГУИД']]['PHONE'] = $item["Телефон"];
                        $listCompany[$item["@attributes"]['ГУИД']]['EMAIL'] = $item["ЭлектроннаяПочта"];
                        $listCompany[$item["@attributes"]['ГУИД']]['REGION'] = $item["БизнесРегион"];
                        $listCompany[$item["@attributes"]['ГУИД']]['WORKING'] = $item["ВидДеятельности"];
                        $listCompany[$item["@attributes"]['ГУИД']]['YR_ADDRESS'] = $item["ЮридическийАдрес"];
                        $listCompany[$item["@attributes"]['ГУИД']]['FIZ_ADDRESS'] = $item["ФактическийАдрес"];
                        $listCompany[$item["@attributes"]['ГУИД']]['INFO'] = $item["ДополнительнаяИнформацияКонтрагента"];
                        $listCompany[$item["@attributes"]['ГУИД']]['MANAGER'] = $item["ОсновнойМенеджер"]['ФИОМенеджера'];
                        $listCompanyСode[$item["@attributes"]['ГУИД']] = $item["@attributes"]['ГУИД'];
                        foreach ($item["РолиКонтрагента"] as $itemRole) {
                            $listCompany[$item["@attributes"]['ГУИД']]['ROLE_CONTRACTOR'][] = $itemRole;
                        }
                        if (!empty($item["Телефон"])) {
                            $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $item["Телефон"]);
                            $firstSymbol = mb_substr($phoneClear, 0, 1);
                            $phoneTrim = mb_substr($phoneClear, 1);
                            $phonesForSearchComp[] = $phoneClear;
                            if ($firstSymbol == 7) {
                                $phonesForSearchComp[] = "8{$phoneTrim}";
                            } elseif ($firstSymbol == 8) {
                                $phonesForSearchComp[] = "7{$phoneTrim}";
                            }
                            $listCompanyPhone[$item["@attributes"]['ГУИД']] = $phoneClear;
                        }
                        if (!empty($item["ЭлектроннаяПочта"]))
                            $listCompanyEmail[$item["@attributes"]['ГУИД']] = $item["ЭлектроннаяПочта"];

                        foreach ($item["КонтактныеЛица"] as $itemFace) {
                            if (!array_key_exists('0', $itemFace)) {
                                $newItem = $itemFace;
                                unset($itemFace);
                                $itemFace[0] = $newItem;
                            }
                            foreach ($itemFace as $itemContact) {
                                if (!empty($itemContact["Телефон"])) {
                                    $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $itemContact["Телефон"]);
                                    $firstSymbol = mb_substr($phoneClear, 0, 1);
                                    $phonesForSearch[] = $phoneClear;
                                    $phoneTrim = mb_substr($phoneClear, 1);
                                    if ($firstSymbol == 7) {
                                        $phonesForSearch[] = "8{$phoneTrim}";
                                    } elseif ($firstSymbol == 8) {
                                        $phonesForSearch[] = "7{$phoneTrim}";
                                    }
                                    $listContactPhone[$itemContact["@attributes"]['ГУИД']] = $phoneClear;
                                }
                                if (!empty($itemContact["ЭлектроннаяПочта"]))
                                    $listContactEmail[$itemContact["@attributes"]['ГУИД']] = $itemContact["ЭлектроннаяПочта"];

                                $listContact[$itemContact["@attributes"]['ГУИД']] = [
                                    'CODE' => $itemContact["@attributes"]['ГУИД'],
                                    'NAME' => $itemContact["Имя"],
                                    'LAST_NAME' => $itemContact["Фамилия"],
                                    'SECOND_NAME' => $itemContact["Отчетсво"],
                                    'JOB' => $itemContact["ДолжностьПоВизитке"],
                                    'BIRTHDATE' => $itemContact["ДатаРождения"],
                                    'GENDER' => $itemContact["Пол"],
                                    'INFO' => $itemContact["ДополнительнаяИнформацияКЛ"],
                                    'COMPANY_GIUID' => $item["@attributes"]['ГУИД'],
                                    'ROLE_CONTRACTOR' => $listCompany[$item["@attributes"]['ГУИД']]['ROLE_CONTRACTOR'],
                                    'MANAGER' => $listCompany[$item["@attributes"]['ГУИД']]['MANAGER'],
                                ];
                                foreach ($itemContact['РолиКонтактногоЛица'] as $roleFace) {
                                    $listContact[$itemContact["@attributes"]['ГУИД']]['ROLE_FACE'] = $roleFace;
                                }

                                if (!empty($itemContact["@attributes"]['ГУИД']))
                                    $listContactСode[$itemContact["@attributes"]['ГУИД']] = $itemContact["@attributes"]['ГУИД'];

                                if (!empty($itemContact["@attributes"]['ГУИД']))
                                    $listСompanyContact[$item["@attributes"]['ГУИД']][$itemContact["@attributes"]['ГУИД']] = $itemContact["@attributes"]['ГУИД'];
                            }
                        }
                    }

                    $res_region = [];
                    $db_region = \CIBlockElement::getList([], ['IBLOCK_ID' => self::REGION_IBLOCK], false, false, ['ID', 'NAME']);
                    while ($ob_region = $db_region->fetch()) $res_region[$ob_region['NAME']] = $ob_region['ID'];
                    $res_working = [];
                    $db_working = \CIBlockElement::getList([], ['IBLOCK_ID' => self::WORKING_IBLOCK], false, false, ['ID', 'NAME']);
                    while ($ob_working = $db_working->fetch()) $res_working[$ob_working['NAME']] = $ob_working['ID'];
                    $res_role_contractor = [];
                    $db_role_contractor = \CIBlockElement::getList([], ['IBLOCK_ID' => self::ROLE_CONTRACTOR_IBLOCK], false, false, ['ID', 'NAME']);
                    while ($ob_role_contractor = $db_role_contractor->fetch()) $res_role_contractor[$ob_role_contractor['NAME']] = $ob_role_contractor['ID'];
                    $res_role_face = [];
                    $db_role_face = \CIBlockElement::getList([], ['IBLOCK_ID' => self::ROLE_FACE_IBLOCK], false, false, ['ID', 'NAME']);
                    while ($ob_role_face = $db_role_face->fetch()) $res_role_face[$ob_role_face['NAME']] = $ob_role_face['ID'];
                    $res_manager = [];
                    $db_manager = \CIBlockElement::getList([], ['IBLOCK_ID' => self::ASSIGNED_IBLOCK], false, false, ['ID', 'NAME', 'PROPERTY_MANAGER_CRM']);
                    while ($ob_manager = $db_manager->fetch()) $res_manager[$ob_manager['NAME']] = $ob_manager['PROPERTY_MANAGER_CRM_VALUE'];
                    $res_gender = [];
                    $db_gender = \CUserFieldEnum::getList([], ['USER_FIELD_ID' => 315]);
                    while ($ob_gender = $db_gender->fetch()) $res_gender[$ob_gender['VALUE']] = $ob_gender['ID'];

                    if ($listContact) {
                        $allExistContactCRM = [];
                        $resultContactAddCRM = [];
                        $newContactIds = [];
                        $resultExistContact = self::getExistCode($listContactСode, 'CONTACT');
                        if ($resultExistContact['res_id']) $allExistContactCRM = $resultExistContact['res_id'];
                        $resultContactAddCRM = array_diff_key($listContact, $allExistContactCRM);
                        self::updateDate($resultExistContact, $listContact, 'CONTACT', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager, $res_gender);
                        if (!empty($resultContactAddCRM)) $newContactIds = self::addCompanyAndContact($resultContactAddCRM, 'CONTACT', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager, $res_gender);

                        $allContactCRM = array_merge($allExistContactCRM, $newContactIds);//существую контакты в CRM + созданные контакты в CRM
                        foreach ($listСompanyContact as $key => $itemCompany) {
                            foreach ($itemCompany as $itemContact) {
                                if (!empty($allContactCRM[$itemContact])) {
                                    $listCompany[$key]['CONTACT_ID'][$itemContact] = $allContactCRM[$itemContact];
                                }
                            }
                        }
                    }
                    if ($listCompany) {
                        $allExistCompanyCRM = [];
                        $resultCompanyAddCRM = [];
                        $newCompanyIds = [];
                        $resultExistCompany = self::getExistCode($listCompanyСode, 'COMPANY');
                        if ($resultExistCompany['res_id']) $allExistCompanyCRM = $resultExistCompany['res_id'];
                        $resultCompanyAddCRM = array_diff_key($listCompany, $allExistCompanyCRM);
                        self::updateDate($resultExistCompany, $listCompany, 'COMPANY', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager);
                        if (!empty($resultCompanyAddCRM)) $newCompanyIds = self::addCompanyAndContact($resultCompanyAddCRM, 'COMPANY', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager, $res_gender);
                    }
                    $resDel = unlink($file);
                }
            }
        }

        return '\Webfly\AddCompanyAndContact\InitNew::onlyChangeXMLtoCRM();';
    }

    protected
    function getExistCode($listCode, $typeOrg)
    {
        if ($listCode) {
            if ($typeOrg == 'COMPANY') {
                $listCompany = \Bitrix\Crm\CompanyTable::getList(
                    [
                        'filter' => [self::COMPANY_CODE_PROP => $listCode],
                        'select' => ['ID', self::COMPANY_CODE_PROP],
                    ]

                )->fetchAll();
                foreach ($listCompany as $item) {
                    $listID[$item[self::COMPANY_CODE_PROP]] = $item['ID'];
                }

            } else if ($typeOrg == 'CONTACT') {
                $listContact = \Bitrix\Crm\ContactTable::getList(
                    [
                        'filter' => [self::CONTACT_CODE_PROP => $listCode],
                        'select' => ['ID', self::CONTACT_CODE_PROP],
                    ]

                )->fetchAll();
                foreach ($listContact as $item) {
                    $listID[$item[self::CONTACT_CODE_PROP]] = $item['ID'];
                }

            }
            $resultDate = \Bitrix\Crm\FieldMultiTable::getList([
                'filter' => [
                    'ELEMENT_ID' => $listID,
                    'ENTITY_ID' => $typeOrg,
                    'TYPE_ID' => ['PHONE', 'EMAIL']],
                'select' => ['ELEMENT_ID', 'VALUE', "TYPE_ID"],
            ]);
            $res_phone = [];
            $res_mail = [];
            while ($ob_item = $resultDate->fetch()) {
                $type = $ob_item["TYPE_ID"];
                if ($type == 'PHONE') {
                    $phones = self::getPhonesArray($ob_item["VALUE"]);
                    if ($phones) {
                        foreach ($phones as $phone) {
                            if (!in_array($phone, $res_phone[$ob_item['ELEMENT_ID']][$type]))
                                $res_phone[$ob_item['ELEMENT_ID']][$type][] = $phone;
                        }
                    }
                } else {
                    if (!in_array($ob_item["VALUE"], $res_phone[$ob_item['ELEMENT_ID']][$type]))
                        $res_mail[$ob_item['ELEMENT_ID']][$type][] = strtoupper($ob_item["VALUE"]);
                }
            }

            $array = [];
            $array['res_id'] = $listID;
            $array['res_phone'] = $res_phone;
            $array['res_mail'] = $res_mail;
            return $array;
        }
    }

    protected
    function getPhonesArray($phone)
    {
        $phonesForSearch = [];
        $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $phone);

        $firstSymbol = mb_substr($phoneClear, 0, 1);
        $phonesForSearch[] = $phoneClear;
        $phoneTrim = mb_substr($phoneClear, 1);
        if ($firstSymbol == 7) {
            $phonesForSearch[] = "8{$phoneTrim}";
        } elseif ($firstSymbol == 8) {
            $phonesForSearch[] = "7{$phoneTrim}";
        }
        return $phonesForSearch;
    }

    protected
    function updateDate($listUpdateCRM, $listDateXML, $typeOrg, $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager, $res_gender = [])
    {
        if ($listUpdateCRM && $listDateXML) {
            foreach ($listDateXML as $key => $item) {
                $fields = [];
                $item['ID'] = $listUpdateCRM['res_id'][$key];
                $fields['ASSIGNED_BY_ID'] = $res_manager[$item['MANAGER']] ?: self::ASSIGNED_ID;
                if ($item['EMAIL']) {
                    if (!in_array(strtoupper($item['EMAIL']), $listUpdateCRM['res_mail'][$item['ID']]['EMAIL'])) {
                        $fields['FM']['EMAIL'] = array(
                            'n0' => array(
                                'VALUE_TYPE' => 'WORK',
                                'VALUE' => $item['EMAIL'],
                            )
                        );
                    }
                }
                if ($item['PHONE']) {
                    if (!in_array(str_replace(['+', ' ', '(', ')', '-'], "", $item['PHONE']), $listUpdateCRM['res_phone'][$item['ID']]['PHONE'])) {
                        $fields['FM']['PHONE'] = array(
                            'n0' => array(
                                'VALUE_TYPE' => 'WORK',
                                'VALUE' => $item['PHONE'],
                            )
                        );
                    }
                }

                if ($typeOrg == 'COMPANY') {
                    $fields['TITLE'] = $item['NAME'];
                    if ($item['CONTACT_ID']) $fields['CONTACT_ID'] = $item['CONTACT_ID'];
                    else  $fields['CONTACT_IDS'] = [];
                    $fields  [self::COMPANY_CODE_PROP] = $item['CODE'];
                    $fields  [self::COMPANY_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::COMPANY_REGION_PROP] = $res_region[$item['REGION']];
                    $fields  [self::COMPANY_INFO_PROP] = $item['INFO'];
                    if (!empty($item['ROLE_CONTRACTOR'])) {
                        if (!array_key_exists('0', $item['ROLE_CONTRACTOR'])) {
                            $newItem = $item['ROLE_CONTRACTOR'];
                            unset($item['ROLE_CONTRACTOR']);
                            $item['ROLE_CONTRACTOR'][0] = $newItem;
                        }
                        foreach ($item['ROLE_CONTRACTOR'] as $role) {
                            $fields  [self::COMPANY_ROLE_CONTRACTOR_PROP][] = $res_role_contractor[$role];
                        }
                    } else {
                        $fields  [self::COMPANY_ROLE_CONTRACTOR_PROP] = [];
                    }

                    $oCompany = new \CCrmCompany(false);
                    $updateCompany = $oCompany->Update($item['ID'], $fields);

                    $typeReq = mb_strpos($item['NAME'], "ИП") ? '2' : false;
                    if (!$typeReq)
                        $typeReq = 1;

                    $requisiteFields = array(
                        "ENTITY_TYPE_ID" => 4, /*реквизит для компании*/
                        "ENTITY_ID" => $item['ID'], /* ид нашей созданной компании*/
                        "PRESET_ID" => $typeReq, // тип реквизитов
                        "NAME" => $item['NAME'],
                        "RQ_INN" => $item['INN'],
                    );
                    $requisiteFields["RQ_ADDR"]["1"] = array(
                        "ADDRESS_1" => $item['FIZ_ADDRESS'],
                    );
                    $requisiteFields["RQ_ADDR"]["6"] = array(
                        "ADDRESS_1" => $item['YR_ADDRESS'],
                    );

                    //ищем существующий реквизит у компании
                    $requisite = new \Bitrix\Crm\EntityRequisite();
                    $rs = $requisite->getList([
                        "filter" => ["RQ_INN" => $item['INN'], "ENTITY_ID" => $item['ID'], "PRESET_ID" => $typeReq],
                        'select' => ['ID']
                    ]);
                    $reqData = $rs->fetch();

                    if ($reqData) {
                        $resultRequisit = $requisite->update($reqData['ID'], $requisiteFields);
                        $rsDel = $requisite->getList([
                            "filter" => ["RQ_INN" => $item['INN'], "ENTITY_ID" => $item['ID'], "PRESET_ID" => 3],//удаляем реквизиты с физ лицом
                            'select' => ['ID']
                        ]);
                        if ($rsDel) {
                            $reqData2 = $rsDel->fetch();
                            if ($reqData2['ID']) $resultRequisit2 = $requisite->delete($reqData2['ID']);
                        }
                    } else {
                        $resultRequisit = $requisite->add($requisiteFields);
                    }
                } else if ($typeOrg == 'CONTACT') {
                    if (!empty($item['NAME'])) {
                        $fields ['NAME'] = $item['NAME'];
                        if ($item['LAST_NAME']) $fields  ['LAST_NAME'] = $item['LAST_NAME'];
                    } else {
                        $fields ['NAME'] = $item['LAST_NAME'];
                    }
                    if ($item['SECOND_NAME']) $fields  ['SECOND_NAME'] = $item['SECOND_NAME'];
                    $fields  [self::CONTACT_BIRTHDATE_PROP] = !empty($item['BIRTHDATE']) ? $item['BIRTHDATE'] : '';
                    $fields  [self::CONTACT_CODE_PROP] = $item['CODE'];
                    $fields  [self::CONTACT_JOB_PROP] = $item['JOB'];
                    $fields  [self::CONTACT_INFO_PROP] = $item['INFO'];
                    $fields  [self::CONTACT_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::CONTACT_REGION_PROP] = $res_region[$item['REGION']];
                    $fields  [self::CONTACT_GENDER_PROP] = $res_gender[$item['GENDER']];

                    if (!empty($item['ROLE_FACE'])) {
                        if (!array_key_exists('0', $item['ROLE_FACE'])) {
                            $newItem = $item['ROLE_FACE'];
                            unset($item['ROLE_FACE']);
                            $item['ROLE_FACE'][0] = $newItem;
                        }
                        foreach ($item['ROLE_FACE'] as $roleFace) {
                            $fields  [self::CONTACT_ROLE_FACE_PROP][] = $res_role_face[$roleFace];
                        }
                    } else {
                        $fields  [self::CONTACT_ROLE_FACE_PROP] = [];
                    }
                    if (!empty($item['ROLE_CONTRACTOR'])) {
                        if (!array_key_exists('0', $item['ROLE_CONTRACTOR'])) {
                            $newItem = $item['ROLE_CONTRACTOR'];
                            unset($item['ROLE_CONTRACTOR']);
                            $item['ROLE_CONTRACTOR'][0] = $newItem;
                        }
                        foreach ($item['ROLE_CONTRACTOR'] as $role) {
                            $fields  [self::CONTACT_ROLE_CONTRACTOR_PROP][] = $res_role_contractor[$role];
                        }
                    } else {
                        $fields  [self::CONTACT_ROLE_CONTRACTOR_PROP] = [];
                    }
                    $oContact = new \CCrmContact(false);
                    $updateLead = $oContact->Update($item['ID'], $fields);
                }
            }
        }
    }

    protected
    function addCompanyAndContact($resultAddCRM, $typeOrg, $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager, $res_gender = [])
    {
        if ($resultAddCRM) {
            foreach ($resultAddCRM as $key => $item) {
                $fields = [];
                $fields['ASSIGNED_BY_ID'] = $res_manager[$item['MANAGER']] ?: self::ASSIGNED_ID;
                if ($item['PHONE']) {
                    $fields['FM']['PHONE'] = array(
                        'n0' => array(
                            'VALUE_TYPE' => 'WORK',
                            'VALUE' => $item['PHONE'],
                        )
                    );
                }
                if ($item['EMAIL']) {
                    $fields['FM']['EMAIL'] = array(
                        'n0' => array(
                            'VALUE_TYPE' => 'WORK',
                            'VALUE' => $item['EMAIL'],
                        )
                    );
                }
                if ($typeOrg == 'COMPANY') {
                    $fields['TITLE'] = $item['NAME'];
                    if ($item['CONTACT_ID']) $fields['CONTACT_ID'] = $item['CONTACT_ID'];
                    else  $fields['CONTACT_IDS'] = [];
                    $fields  [self::COMPANY_CODE_PROP] = $item['CODE'];
                    $fields  [self::COMPANY_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::COMPANY_REGION_PROP] = $res_region[$item['REGION']];
                    $fields  [self::COMPANY_INFO_PROP] = $item['INFO'];
                    if (!empty($item['ROLE_CONTRACTOR'])) {
                        if (!array_key_exists('0', $item['ROLE_CONTRACTOR'])) {
                            $newItem = $item['ROLE_CONTRACTOR'];
                            unset($item['ROLE_CONTRACTOR']);
                            $item['ROLE_CONTRACTOR'][] = $newItem;
                        }
                        foreach ($item['ROLE_CONTRACTOR'] as $role) {
                            $fields  [self::COMPANY_ROLE_CONTRACTOR_PROP][] = $res_role_contractor[$role];
                        }
                    }

                    $oCompany = new \CCrmCompany(false);
                    $idCompany = $oCompany->Add($fields);
                    $newIds[] = $idCompany;
                    if (!$idCompany) $oCompany->LAST_ERROR;

                    if ($idCompany) {
                        $typeReq = mb_strpos($item['NAME'], "ИП") ? '2' : false;
                        if (!$typeReq)
                            $typeReq = 1;

                        $requisiteFields = array(
                            "ENTITY_TYPE_ID" => 4, /*реквизит для компании*/
                            "ENTITY_ID" => $idCompany, /* ид нашей созданной компании*/
                            "PRESET_ID" => $typeReq, // тип реквизитов
                            "NAME" => $item['NAME'],
                            "RQ_INN" => $item['INN'],
                        );
                        $requisiteFields["RQ_ADDR"]["1"] = array(
                            "ADDRESS_1" => $item['FIZ_ADDRESS'],
                        );
                        $requisiteFields["RQ_ADDR"]["6"] = array(
                            "ADDRESS_1" => $item['YR_ADDRESS'],
                        );
                        $requisite = new \Bitrix\Crm\EntityRequisite();
                        if ($requisiteFields) $resultRequisit = $requisite->add($requisiteFields);
                    }
                } else if ($typeOrg == 'CONTACT') {
                    $fields['TYPE_ID'] = 'CLIENT';
                    $fields['SOURCE_ID'] = '4';
                    if (!empty($item['NAME'])) {
                        $fields ['NAME'] = $item['NAME'];
                        if ($item['LAST_NAME']) $fields  ['LAST_NAME'] = $item['LAST_NAME'];
                    } else {
                        $fields ['NAME'] = $item['LAST_NAME'];
                    }
                    if ($item['SECOND_NAME']) $fields  ['SECOND_NAME'] = $item['SECOND_NAME'];
                    $$fields  [self::CONTACT_BIRTHDATE_PROP] = !empty($item['BIRTHDATE']) ? $item['BIRTHDATE'] : '';
                    $fields  [self::CONTACT_CODE_PROP] = $item['CODE'];
                    $fields  [self::CONTACT_JOB_PROP] = $item['JOB'];
                    $fields  [self::CONTACT_INFO_PROP] = $item['INFO'];
                    $fields  [self::CONTACT_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::CONTACT_REGION_PROP] = $res_region[$item['REGION']];
                    $fields  [self::CONTACT_GENDER_PROP] = $res_gender[$item['GENDER']];

                    if (!empty($item['ROLE_FACE'])) {
                        if (!array_key_exists('0', $item['ROLE_FACE'])) {
                            $newItem = $item['ROLE_FACE'];
                            unset($item['ROLE_FACE']);
                            $item['ROLE_FACE'][] = $newItem;
                        }
                        foreach ($item['ROLE_FACE'] as $roleFace) {
                            $fields  [self::CONTACT_ROLE_FACE_PROP][] = $res_role_face[$roleFace];
                        }
                    }
                    if (!empty($item['ROLE_CONTRACTOR'])) {
                        if (!array_key_exists('0', $item['ROLE_CONTRACTOR'])) {
                            $newItem = $item['ROLE_CONTRACTOR'];
                            unset($item['ROLE_CONTRACTOR']);
                            $item['ROLE_CONTRACTOR'][] = $newItem;
                        }
                        foreach ($item['ROLE_CONTRACTOR'] as $role) {
                            $fields  [self::CONTACT_ROLE_CONTRACTOR_PROP][] = $res_role_contractor[$role];
                        }
                    }
                    $oContact = new \CCrmContact(false);
                    $idContact = $oContact->Add($fields);
                    $newIds[$item['CODE']] = $idContact;
                    if (!$idContact) print $oContact->LAST_ERROR;
                }
            }
            return $newIds;
        }
    }

    public
    function generalUnloadingXMLtoCRM() // тут берутся все данные из файла, и если находится контрагент по телефону или имейлу, до дописываем по нему инфу
    {
        \Bitrix\Main\Loader::includeModule('crm');
        libxml_use_internal_errors(TRUE);
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH)) {
            $xml1 = simplexml_load_file($_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH);
            $xml = json_decode(json_encode($xml1), true);
            $phonesForSearchComp = [];
            $phonesForSearch = [];
            if (!array_key_exists('0', $xml["Контрагент"])) {
                $newItem = $xml["Контрагент"];
                unset($xml["Контрагент"]);
                $xml["Контрагент"][0] = $newItem;
            }
            foreach ($xml["Контрагент"] as $key => $item) { //всегда создаём компании, убираем функционал контактов
                $existCompany = $item["ИНН"];
                $listCompany[$item["@attributes"]['ГУИД']]['CODE'] = $item["@attributes"]['ГУИД'];
                $listCompany[$item["@attributes"]['ГУИД']]['NAME'] = $item["Наименование"];
                $listCompany[$item["@attributes"]['ГУИД']]['INN'] = $existCompany;
                $listCompany[$item["@attributes"]['ГУИД']]['PHONE'] = $item["Телефон"];
                $listCompany[$item["@attributes"]['ГУИД']]['EMAIL'] = $item["ЭлектроннаяПочта"];
                $listCompany[$item["@attributes"]['ГУИД']]['REGION'] = $item["БизнесРегион"];
                $listCompany[$item["@attributes"]['ГУИД']]['WORKING'] = $item["ВидДеятельности"];
                $listCompany[$item["@attributes"]['ГУИД']]['YR_ADDRESS'] = $item["ЮридическийАдрес"];
                $listCompany[$item["@attributes"]['ГУИД']]['FIZ_ADDRESS'] = $item["ФактическийАдрес"];
                $listCompany[$item["@attributes"]['ГУИД']]['INFO'] = $item["ДополнительнаяИнформацияКонтрагента"];
                $listCompany[$item["@attributes"]['ГУИД']]['MANAGER'] = $item["ОсновнойМенеджер"]['ФИОМенеджера'];
                $listCompanyСode[$item["@attributes"]['ГУИД']] = $item["@attributes"]['ГУИД'];
                foreach ($item["РолиКонтрагента"] as $itemRole) {
                    $listCompany[$item["@attributes"]['ГУИД']]['ROLE_CONTRACTOR'] = $itemRole;
                }
                if (!empty($item["Телефон"])) {
                    $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $item["Телефон"]);
                    $firstSymbol = mb_substr($phoneClear, 0, 1);
                    $phoneTrim = mb_substr($phoneClear, 1);
                    $phonesForSearchComp[] = $phoneClear;
                    if ($firstSymbol == 7) {
                        $phonesForSearchComp[] = "8{$phoneTrim}";
                    } elseif ($firstSymbol == 8) {
                        $phonesForSearchComp[] = "7{$phoneTrim}";
                    }
                    $listCompanyPhone[$item["@attributes"]['ГУИД']] = $phoneClear;
                }
                if (!empty($item["ЭлектроннаяПочта"]))
                    $listCompanyEmail[$item["@attributes"]['ГУИД']] = $item["ЭлектроннаяПочта"];

                if ($item["КонтактныеЛица"]) {
                    foreach ($item["КонтактныеЛица"] as $itemFace) {
                        if (!array_key_exists('0', $itemFace)) {
                            $newItem = $itemFace;
                            unset($itemFace);
                            $itemFace[0] = $newItem;
                        }
                        foreach ($itemFace as $itemContact) {
                            if (!empty($itemContact["Телефон"])) {
                                $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $itemContact["Телефон"]);
                                $firstSymbol = mb_substr($phoneClear, 0, 1);
                                $phonesForSearch[] = $phoneClear;
                                $phoneTrim = mb_substr($phoneClear, 1);
                                if ($firstSymbol == 7) {
                                    $phonesForSearch[] = "8{$phoneTrim}";
                                } elseif ($firstSymbol == 8) {
                                    $phonesForSearch[] = "7{$phoneTrim}";
                                }
                                $listContactPhone[$itemContact["@attributes"]['ГУИД']] = $phoneClear;
                            }
                            if (!empty($itemContact["ЭлектроннаяПочта"]))
                                $listContactEmail[$itemContact["@attributes"]['ГУИД']] = $itemContact["ЭлектроннаяПочта"];

                            $listContact[$itemContact["@attributes"]['ГУИД']] = [
                                'CODE' => $itemContact["@attributes"]['ГУИД'],
                                'NAME' => $itemContact["Имя"],
                                'LAST_NAME' => $itemContact["Фамилия"],
                                'SECOND_NAME' => $itemContact["Отчетсво"],
                                'JOB' => $itemContact["ДолжностьПоВизитке"],
                                'BIRTHDATE' => $itemContact["ДатаРождения"],
                                'GENDER' => $itemContact["Пол"],
                                'INFO' => $itemContact["ДополнительнаяИнформацияКЛ"],
                                'COMPANY_GIUID' => $item["@attributes"]['ГУИД'],
                                'ROLE_CONTRACTOR' => $listCompany[$item["@attributes"]['ГУИД']]['ROLE_CONTRACTOR'],
                                'MANAGER' => $listCompany[$item["@attributes"]['ГУИД']]['MANAGER'],
                            ];
                            foreach ($itemContact['РолиКонтактногоЛица'] as $roleFace) {
                                $listContact[$itemContact["@attributes"]['ГУИД']]['ROLE_FACE'] = $roleFace;
                            }

                            if (!empty($itemContact["@attributes"]['ГУИД']))
                                $listContactСode[$itemContact["@attributes"]['ГУИД']] = $itemContact["@attributes"]['ГУИД'];

                            if (!empty($itemContact["@attributes"]['ГУИД']))
                                $listСompanyContact[$item["@attributes"]['ГУИД']][$itemContact["@attributes"]['ГУИД']] = $itemContact["@attributes"]['ГУИД'];
                        }
                    }
                }
            }

            $res_region = [];
            $db_region = \CIBlockElement::getList([], ['IBLOCK_ID' => self::REGION_IBLOCK], false, false, ['ID', 'NAME']);
            while ($ob_region = $db_region->fetch()) $res_region[$ob_region['NAME']] = $ob_region['ID'];
            $res_working = [];
            $db_working = \CIBlockElement::getList([], ['IBLOCK_ID' => self::WORKING_IBLOCK], false, false, ['ID', 'NAME']);
            while ($ob_working = $db_working->fetch()) $res_working[$ob_working['NAME']] = $ob_working['ID'];
            $res_role_contractor = [];
            $db_role_contractor = \CIBlockElement::getList([], ['IBLOCK_ID' => self::ROLE_CONTRACTOR_IBLOCK], false, false, ['ID', 'NAME']);
            while ($ob_role_contractor = $db_role_contractor->fetch()) $res_role_contractor[$ob_role_contractor['NAME']] = $ob_role_contractor['ID'];
            $res_role_face = [];
            $db_role_face = \CIBlockElement::getList([], ['IBLOCK_ID' => self::ROLE_FACE_IBLOCK], false, false, ['ID', 'NAME']);
            while ($ob_role_face = $db_role_face->fetch()) $res_role_face[$ob_role_face['NAME']] = $ob_role_face['ID'];
            $res_manager = [];
            $db_manager = \CIBlockElement::getList([], ['IBLOCK_ID' => self::ASSIGNED_IBLOCK], false, false, ['ID', 'NAME', 'PROPERTY_MANAGER_CRM']);
            while ($ob_manager = $db_manager->fetch()) $res_manager[$ob_manager['NAME']] = $ob_manager['PROPERTY_MANAGER_CRM_VALUE'];
            $res_gender = [];
            $db_gender = \CUserFieldEnum::getList([], ['USER_FIELD_ID' => 315]);
            while ($ob_gender = $db_gender->fetch()) $res_gender[$ob_gender['VALUE']] = $ob_gender['ID'];

            if ($listContact) {
                $resultContactPhoneUpdateID = [];
                $resultContactEmailUpdateID = [];
                $newContactIds = [];
                $resultContactPhone = self::getAllPhone($listContactPhone, $phonesForSearch, 'CONTACT');
                if ($resultContactPhone['PhoneUpdateID']) $resultContactPhoneUpdateID = $resultContactPhone['PhoneUpdateID'];
                $resultContactEmail = self::getAllEmail($listContactEmail, $resultContactPhoneUpdateID, 'CONTACT');
                if ($resultContactEmail['EmailUpdateID']) $resultContactEmailUpdateID = $resultContactEmail['EmailUpdateID'];

                $allExistContactCRM = array_merge($resultContactPhoneUpdateID, $resultContactEmailUpdateID);//существую контакты в CRM
                $resultContactAddCRM = array_diff_key($listContact, $allExistContactCRM);

                $listContactPhoneUpdateCRM = array_intersect_key($listContact, $resultContactPhoneUpdateID);
                $listContactEmailUpdateCRM = array_intersect_key($listContact, $resultContactEmailUpdateID);


                if ($resultContactPhoneUpdateID) self::updateCompanyAndContact($listContactPhoneUpdateCRM, $resultContactPhoneUpdateID, $resultContactPhone['RES'], 'CONTACT', 'PHONE', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager, $res_gender);
                if ($resultContactEmailUpdateID) self::updateCompanyAndContact($listContactEmailUpdateCRM, $resultContactEmailUpdateID, $resultContactEmail['RES'], 'CONTACT', 'EMAIL', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager, $res_gender);
                if (!empty($resultContactAddCRM)) $newContactIds = self::addCompanyAndContact($resultContactAddCRM, 'CONTACT', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager, $res_gender);

                $allContactCRM = array_merge($allExistContactCRM, $newContactIds);//существую контакты в CRM + созданные контакты в CRM
                foreach ($listСompanyContact as $key => $itemCompany) {
                    foreach ($itemCompany as $itemContact) {
                        if (!empty($allContactCRM[$itemContact])) {
                            $listCompany[$key]['CONTACT_ID'][$itemContact] = $allContactCRM[$itemContact];
                        }
                    }
                }
            }
            if ($listCompany) {
                $resultCompanyPhoneUpdateID = [];
                $resultCompanyEmailUpdateID = [];

                $resultCompanyPhone = self::getAllPhone($listCompanyPhone, $phonesForSearchComp, 'COMPANY');
                if ($resultCompanyPhone['PhoneUpdateID']) $resultCompanyPhoneUpdateID = $resultCompanyPhone['PhoneUpdateID'];
                $resultCompanyEmail = self::getAllEmail($listCompanyEmail, $resultCompanyPhoneUpdateID, 'COMPANY');
                if ($resultCompanyEmail['EmailUpdateID']) $resultCompanyEmailUpdateID = $resultCompanyEmail['EmailUpdateID'];

                $allExistCompanyCRM = array_merge($resultCompanyPhoneUpdateID, $resultCompanyEmailUpdateID);
                $resultCompanyAddCRM = array_diff_key($listCompany, $allExistCompanyCRM);

                $listCompanyPhoneUpdateCRM = array_intersect_key($listCompany, $resultCompanyPhoneUpdateID);
                $listCompanyEmailUpdateCRM = array_intersect_key($listCompany, $resultCompanyEmailUpdateID);

                if ($resultCompanyPhoneUpdateID) self::updateCompanyAndContact($listCompanyPhoneUpdateCRM, $resultCompanyPhoneUpdateID, $resultCompanyPhone['RES'], 'COMPANY', 'PHONE', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager);
                if ($resultCompanyEmailUpdateID) self::updateCompanyAndContact($listCompanyEmailUpdateCRM, $resultCompanyEmailUpdateID, $resultCompanyEmail['RES'], 'COMPANY', 'EMAIL', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager);
                if (!empty($resultCompanyAddCRM)) $newCompanyIds = self::addCompanyAndContact($resultCompanyAddCRM, 'COMPANY', $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager);
            }
        }
        return '\Webfly\AddCompanyAndContact\InitNew::generalUnloadingXMLtoCRM();';
    }

    protected
    function getAllPhone($listPhone, $phonesForSearch, $typeOrg)
    {
        if ($listPhone && $phonesForSearch) {
            $res_phone = [];
            $resultPhone = \Bitrix\Crm\FieldMultiTable::getList([
                'filter' => [
                    'PHONE_CLEAR' => $phonesForSearch,
                    'ENTITY_ID' => $typeOrg,
                    'TYPE_ID' => 'PHONE',
                    'MULTI.ENTITY_ID' => $typeOrg,
                    'MULTI.TYPE_ID' => ['PHONE', 'EMAIL']],
                'select' => ['ELEMENT_ID', 'MULTI'],
                'group' => ['CRM_FIELD_MULTI_MULTI_TYPE_ID'],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField('MULTI', \Bitrix\Crm\FieldMultiTable::class, ['=this.ELEMENT_ID' => 'ref.ELEMENT_ID']),
                    new \Bitrix\Main\Entity\ExpressionField('PHONE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['VALUE']),
                ]]);
            while ($ob_item = $resultPhone->fetch()) {
                $type = $ob_item["CRM_FIELD_MULTI_MULTI_TYPE_ID"];
                if ($type == 'PHONE') {
                    $phones = self::getPhonesArray($ob_item["CRM_FIELD_MULTI_MULTI_VALUE"]);
                    if ($phones) {
                        foreach ($phones as $phone) {
                            if (!in_array($phone, $res_phone[$ob_item['ELEMENT_ID']][$type]))
                                $res_phone[$ob_item['ELEMENT_ID']][$type][] = $phone;
                        }
                    }
                } else {
                    if (!in_array($ob_item["CRM_FIELD_MULTI_MULTI_VALUE"], $res_phone[$ob_item['ELEMENT_ID']][$type]))
                        $res_phone[$ob_item['ELEMENT_ID']][$type][] = strtoupper($ob_item["CRM_FIELD_MULTI_MULTI_VALUE"]);
                }
            }
            foreach ($listPhone as $key => $item) {
                foreach ($res_phone as $key2 => $item2) {
                    if (in_array($item, $item2["PHONE"])) {
                        $resultPhoneUpdateID[$key] = $key2;
                    }
                }
            }
            $array['RES'] = $res_phone;
            $array['PhoneUpdateID'] = $resultPhoneUpdateID;
            return $array;
        }
    }

    protected
    function getAllEmail($listEmail, $resultPhoneUpdateID, $typeOrg)
    {
        if ($listEmail) {
            $res_email = [];
            $resultEmail = \Bitrix\Crm\FieldMultiTable::getList([
                'filter' => [
                    'VALUE' => $listEmail,
                    'ENTITY_ID' => $typeOrg,
                    'TYPE_ID' => 'EMAIL',
                    'MULTI.ENTITY_ID' => $typeOrg,
                    'MULTI.TYPE_ID' => ['EMAIL', 'PHONE'],
                    '!ELEMENT_ID' => $resultPhoneUpdateID
                ],
                'select' => ['ELEMENT_ID', 'MULTI'],
                'group' => ['CRM_FIELD_MULTI_MULTI_TYPE_ID'],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField('MULTI', \Bitrix\Crm\FieldMultiTable::class, ['=this.ELEMENT_ID' => 'ref.ELEMENT_ID']),
                ]
            ]);
            while ($ob_item = $resultEmail->fetch()) {
                $type = $ob_item["CRM_FIELD_MULTI_MULTI_TYPE_ID"];
                if ($type == 'PHONE') {
                    $phones = self::getPhonesArray($ob_item["CRM_FIELD_MULTI_MULTI_VALUE"]);
                    if ($phones) {
                        foreach ($phones as $phone) {
                            if (!in_array($phone, $res_email[$ob_item['ELEMENT_ID']][$type]))
                                $res_email[$ob_item['ELEMENT_ID']][$type][] = $phone;
                        }
                    }
                } else {
                    if (!in_array($ob_item["CRM_FIELD_MULTI_MULTI_VALUE"], $res_email[$ob_item['ELEMENT_ID']][$type]))
                        $res_email[$ob_item['ELEMENT_ID']][$type][] = strtoupper($ob_item["CRM_FIELD_MULTI_MULTI_VALUE"]);
                }
            }
            foreach ($listEmail as $key => $item) {
                foreach ($res_email as $key2 => $item2) {
                    if (in_array(strtoupper($item), $item2["EMAIL"])) {
                        $resultEmailUpdateID[$key] = $key2;
                    }
                }
            }
            $array['RES'] = $res_email;
            $array['EmailUpdateID'] = $resultEmailUpdateID;
            return $array;
        }

    }

    protected
    function updateCompanyAndContact($listUpdateCRM, $updateExistID, $res, $typeOrg, $typeUpdate, $res_region, $res_working, $res_role_face, $res_role_contractor, $res_manager, $res_gender = [])
    {
        if ($listUpdateCRM) {
            foreach ($listUpdateCRM as $key => $item) {
                $fields = [];
                $item['ID'] = $updateExistID[$key];
                $fields['ASSIGNED_BY_ID'] = $res_manager[$item['MANAGER']] ?: self::ASSIGNED_ID;
                if ($typeUpdate == 'PHONE') {
                    if ($item['EMAIL']) {//если нашли по номеру телефона, то дописываем имейл
                        if (!in_array(strtoupper($item['EMAIL']), $res[$item['ID']]['EMAIL'])) {
                            $fields['FM']['EMAIL'] = array(
                                'n0' => array(
                                    'VALUE_TYPE' => 'WORK',
                                    'VALUE' => $item['EMAIL'],
                                )
                            );
                        }
                    }
                } else if ($typeUpdate == 'EMAIL') {
                    if ($item['PHONE']) {//если нашли по имейлу, то дописываем телефон
                        if (!in_array(str_replace(['+', ' ', '(', ')', '-'], "", $item['PHONE']), $res[$item['ID']]['PHONE'])) {
                            $fields['FM']['PHONE'] = array(
                                'n0' => array(
                                    'VALUE_TYPE' => 'WORK',
                                    'VALUE' => $item['PHONE'],
                                )
                            );
                        }
                    }
                }
                if ($typeOrg == 'COMPANY') {
                    $fields['TITLE'] = $item['NAME'];
                    if ($item['CONTACT_ID']) $fields['CONTACT_ID'] = $item['CONTACT_ID'];
                    else  $fields['CONTACT_IDS'] = [];
                    $fields  [self::COMPANY_CODE_PROP] = $item['CODE'];
                    $fields  [self::COMPANY_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::COMPANY_REGION_PROP] = $res_region[$item['REGION']];
                    $fields  [self::COMPANY_INFO_PROP] = $item['INFO'];
                    if (!empty($item['ROLE_CONTRACTOR'])) {
                        if (!array_key_exists('0', $item['ROLE_CONTRACTOR'])) {
                            $newItem = $item['ROLE_CONTRACTOR'];
                            unset($item['ROLE_CONTRACTOR']);
                            $item['ROLE_CONTRACTOR'][0] = $newItem;
                        }
                        foreach ($item['ROLE_CONTRACTOR'] as $role) {
                            $fields  [self::COMPANY_ROLE_CONTRACTOR_PROP][] = $res_role_contractor[$role];
                        }
                    }

                    $oCompany = new \CCrmCompany(false);
                    $updateCompany = $oCompany->Update($item['ID'], $fields);

                    $typeReq = mb_strpos($item['NAME'], "ИП") ? '2' : false;
                    if (!$typeReq)
                        $typeReq = 1;

                    $requisiteFields = array(
                        "ENTITY_TYPE_ID" => 4, /*реквизит для компании*/
                        "ENTITY_ID" => $item['ID'], /* ид нашей созданной компании*/
                        "PRESET_ID" => $typeReq, // тип реквизитов
                        "NAME" => $item['NAME'],
                        "RQ_INN" => $item['INN'],
                    );
                    $requisiteFields["RQ_ADDR"]["1"] = array(
                        "ADDRESS_1" => $item['FIZ_ADDRESS'],
                    );
                    $requisiteFields["RQ_ADDR"]["6"] = array(
                        "ADDRESS_1" => $item['YR_ADDRESS'],
                    );

                    $requisite = new \Bitrix\Crm\EntityRequisite(); //ищем существующий реквизит у компании
                    $rs = $requisite->getList([
                        "filter" => ["RQ_INN" => $item['INN'], "ENTITY_ID" => $item['ID'], "PRESET_ID" => $typeReq,]
                    ]);
                    $reqData = $rs->fetch();
                    if ($reqData) {
                        $resultRequisit = $requisite->update($reqData['ID'], $requisiteFields);
                    } else {
                        $resultRequisit = $requisite->add($requisiteFields);
                    }
                } else if ($typeOrg == 'CONTACT') {
                    if ($item['NAME']) $fields ['NAME'] = $item['NAME'];
                    if ($item['LAST_NAME']) $fields  ['LAST_NAME'] = $item['LAST_NAME'];
                    if ($item['SECOND_NAME']) $fields  ['SECOND_NAME'] = $item['SECOND_NAME'];
                    $fields  [self::CONTACT_BIRTHDATE_PROP] = !empty($item['BIRTHDATE']) ? $item['BIRTHDATE'] : '';
                    $fields  [self::CONTACT_CODE_PROP] = $item['CODE'];
                    $fields  [self::CONTACT_JOB_PROP] = $item['JOB'];
                    $fields  [self::CONTACT_INFO_PROP] = $item['INFO'];
                    $fields  [self::CONTACT_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::CONTACT_REGION_PROP] = $res_region[$item['REGION']];
                    $fields  [self::CONTACT_GENDER_PROP] = $res_gender[$item['GENDER']];

                    if (!empty($item['ROLE_FACE'])) {
                        if (!array_key_exists('0', $item['ROLE_FACE'])) {
                            $newItem = $item['ROLE_FACE'];
                            unset($item['ROLE_FACE']);
                            $item['ROLE_FACE'][0] = $newItem;
                        }
                        foreach ($item['ROLE_FACE'] as $roleFace) {
                            $fields  [self::CONTACT_ROLE_FACE_PROP][] = $res_role_face[$roleFace];
                        }
                    }
                    if (!empty($item['ROLE_CONTRACTOR'])) {
                        if (!array_key_exists('0', $item['ROLE_CONTRACTOR'])) {
                            $newItem = $item['ROLE_CONTRACTOR'];
                            unset($item['ROLE_CONTRACTOR']);
                            $item['ROLE_CONTRACTOR'][0] = $newItem;
                        }
                        foreach ($item['ROLE_CONTRACTOR'] as $role) {
                            $fields  [self::CONTACT_ROLE_CONTRACTOR_PROP][] = $res_role_contractor[$role];
                        }
                    }
                    $oContact = new \CCrmContact(false);
                    $updateLead = $oContact->Update($item['ID'], $fields);
                }
            }
        }
    }
}
