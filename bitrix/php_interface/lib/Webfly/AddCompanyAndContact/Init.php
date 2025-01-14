<?php

namespace Webfly\AddCompanyAndContact;

use   \Vendor\CRest;

class Init
{
    const FILE_PATH = '/bitrix/php_interface/lib/Webfly/AddCompanyAndContact/12072022154005_exemple.XML';
    const COMPANY_CODE_PROP = 'UF_CRM_1656324443';
    const COMPANY_WORKING_PROP = 'UF_CRM_1656315466';
    const COMPANY_REGION_PROP = 'UF_CRM_1656315172';
    const COMPANY_INFO_PROP = 'UF_CRM_1658911322';
    const COMPANY_ROLE_CONTRACTOR_PROP = 'UF_CRM_1659349383';
    const COMPANY_ASSIGNED_ID_PROP = '';
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
    const ASSIGNED_ID = 2233;


    function __construct()
    {
    }

    public function onlyChangeXMLtoCRM()
    {
        /* $dir = $_SERVER['DOCUMENT_ROOT'] . '/crm-1c-contragent';
         $files = [];
         foreach (glob($dir . '/*.XML') as $file) {
             if (is_file($file)) {
                 $files[] = $file;
             }
         }*/
        \Bitrix\Main\Loader::includeModule('crm');
        $files[] = $_SERVER['DOCUMENT_ROOT'] . self::FILE_PATH;
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
                    $listContactСode = [];
                    foreach ($xml["Контрагент"] as $key => $item) { //всегда создаём компании, убираем функционал контактов
                        $existCompany = $item["ИНН"];
//                        if (!empty($existCompany)) {
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
                            if ($firstSymbol == 7) {
                                $phonesForSearch[] = $phoneClear;
                                $phoneTrim = mb_substr($phoneClear, 1);
                                $phonesForSearch[] = "8{$phoneTrim}";
                            } elseif ($firstSymbol == 8) {
                                $phoneTrim = mb_substr($phoneClear, 1);
                                $phonesForSearch[] = "7{$phoneTrim}";
                            }
                            $listCompanyPhone[$item["@attributes"]['ГУИД']] = $phoneClear;
                        }
                        if (!empty($item["ЭлектроннаяПочта"]))
                            $listCompanyEmail[$item["@attributes"]['ГУИД']] = $item["ЭлектроннаяПочта"];

                        foreach ($item["КонтактныеЛица"] as $itemFace) {
//                            if (!array_key_exists('0', $item["КонтактныеЛица"])) {
//                                $newItem =$item["КонтактныеЛица"];
//                                unset($item["КонтактныеЛица"]);
//                                $item["КонтактныеЛица"][0] = $newItem;
//                            }
//                            foreach ($itemFace as $itemContact) {
//                                if (!empty($itemContact["Телефон"])) {
//                                    $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $itemContact["Телефон"]);
//                                    $firstSymbol = mb_substr($phoneClear, 0, 1);
//                                    if ($firstSymbol == 7) {
//                                        $phonesForSearch[] = $phoneClear;
//                                        $phoneTrim = mb_substr($phoneClear, 1);
//                                        $phonesForSearch[] = "8{$phoneTrim}";
//                                    } elseif ($firstSymbol == 8) {
//                                        $phoneTrim = mb_substr($phoneClear, 1);
//                                        $phonesForSearch[] = "7{$phoneTrim}";
//                                    }
//                                    $listContactPhone[$itemContact["@attributes"]['ГУИД']] = $phoneClear;
//                                }
//                                if (!empty($itemContact["ЭлектроннаяПочта"]))
//                                    $listContactEmail[$itemContact["@attributes"]['ГУИД']] = $itemContact["ЭлектроннаяПочта"];
//
//                                $listContact[$item["@attributes"]['ГУИД']]['CONTACTS'][] = [
//                                    'CODE' => $itemContact["@attributes"]['ГУИД'],
//                                    'NAME' => $itemContact["Имя"],
//                                    'LAST_NAME' => $itemContact["Фамилия"],
//                                    'SECOND_NAME' => $itemContact["Отчетсво"],
//                                    'JOB' => $itemContact["ДолжностьПоВизитке"],
//                                    'BIRTHDATE' => $itemContact["ДатаРождения"],
//                                    'GENDER' => $itemContact["Пол"],
//                                    'INFO' => $itemContact["ДополнительнаяИнформацияКЛ"],
//                                    'ROLE_CONTRACTOR' => $listCompany[$item["@attributes"]['ГУИД']]['ROLE_CONTRACTOR'],
//                                ];
//                            }
                        }
//                        }                       else {
//                            $listContact[$item["@attributes"]['ГУИД']]['CODE'] = $item["@attributes"]['ГУИД'];
//                            $listContact[$item["@attributes"]['ГУИД']]['NAME'] = $item["НаименованиеПолное"];
//                            $listContact[$item["@attributes"]['ГУИД']]['PHONE'] = $item["Телефон"];
//                            $listContact[$item["@attributes"]['ГУИД']]['EMAIL'] = $item["ЭлектроннаяПочта"];
//                            $listContact[$item["@attributes"]['ГУИД']]['REGION'] = $item["БизнесРегион"];
//                            $listContact[$item["@attributes"]['ГУИД']]['WORKING'] = $item["ВидДеятельности"];
//                            $listContact[$item["@attributes"]['ГУИД']]['YR_ADDRESS'] = $item["ЮридическийАдрес"];
//                            $listContact[$item["@attributes"]['ГУИД']]['FIZ_ADDRESS'] = $item["ФактическийАдрес"];
//                            $listContactСode[$item["@attributes"]['ГУИД']] = $item["@attributes"]['ГУИД'];
//                            if (!empty($item["Телефон"])) {
//                                $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $item["Телефон"]);
//                                $firstSymbol = mb_substr($phoneClear, 0, 1);
//                                if ($firstSymbol == 7) {
//                                    $phonesForSearch[] = $phoneClear;
//                                    $phoneTrim = mb_substr($phoneClear, 1);
//                                    $phonesForSearch[] = "8{$phoneTrim}";
//                                } elseif ($firstSymbol == 8) {
//                                    $phoneTrim = mb_substr($phoneClear, 1);
//                                    $phonesForSearch[] = "7{$phoneTrim}";
//                                }
//                                $listContactPhone[$item["@attributes"]['ГУИД']] = $phoneClear;
//                            }
//                            if (!empty($item["ЭлектроннаяПочта"]))
//                                $listContactEmail[$item["@attributes"]['ГУИД']] = $item["ЭлектроннаяПочта"];
//                        }
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

                    if ($listCompany) {
                        $allExistCompanyCRM = [];
                        $resultCompanyAddCRM = [];
                        $resultExistCompany = self::getExistCode($listCompanyСode, 'COMPANY');
                        if ($resultExistCompany['res_id']) $allExistCompanyCRM = $resultExistCompany['res_id'];
                        $resultCompanyAddCRM = array_diff_key($listCompany, $allExistCompanyCRM);
                        self::updateDate($resultExistCompany, $listCompany, 'COMPANY', $res_region, $res_working, $res_role_contractor);
                        //self::addCompanyAndContact($resultCompanyAddCRM, 'COMPANY', $res_region, $res_working);
                    }
//                    if ($listContact) {
//                        $allExistContactCRM = [];
//                        $resultContactAddCRM = [];
//                        $resultExistContact = self::getExistCode($listContactСode, 'CONTACT');
//                        if ($resultExistContact['res_id']) $allExistContactCRM = $resultExistContact['res_id'];
//                        $resultContactAddCRM = array_diff_key($listContact, $allExistContactCRM);
//                        self::updateDate($resultExistContact, $listContact, 'CONTACT', $res_region, $res_working);
//                        self::addCompanyAndContact($resultContactAddCRM, 'CONTACT', $res_region, $res_working);
//                    }
                    //$resDel = unlink($file);
                }
            }
        }

        return '\Webfly\AddCompanyAndContact\Init::onlyChangeXMLtoCRM();';
    }

    public
    function updateXMLtoCRM()
    {
        \Bitrix\Main\Loader::includeModule('crm');
        libxml_use_internal_errors(TRUE);
        if (file_exists(self::FILE_PATH)) {
            $xml1 = simplexml_load_file(self::FILE_PATH);
            $xml = json_decode(json_encode($xml1), true);
            $phonesForSearch = [];
            $listCompany = [];
            $listContact = [];
            $listCompanyСode = [];
            $listContactСode = [];
            foreach ($xml["Контрагент"] as $key => $item) {
                $existCompany = $item["ИНН"];
                if (!empty($existCompany)) {
                    $listCompany[$item["@attributes"]['ГУИД']]['CODE'] = $item["@attributes"]['ГУИД'];
                    $listCompany[$item["@attributes"]['ГУИД']]['NAME'] = $item["Наименование"];
                    $listCompany[$item["@attributes"]['ГУИД']]['INN'] = $existCompany;
                    $listCompany[$item["@attributes"]['ГУИД']]['PHONE'] = $item["Телефон"];
                    $listCompany[$item["@attributes"]['ГУИД']]['EMAIL'] = $item["ЭлектроннаяПочта"];
                    $listCompany[$item["@attributes"]['ГУИД']]['REGION'] = $item["БизнесРегион"];
                    $listCompany[$item["@attributes"]['ГУИД']]['WORKING'] = $item["ВидДеятельности"];
                    $listCompany[$item["@attributes"]['ГУИД']]['YR_ADDRESS'] = $item["ЮридическийАдрес"];
                    $listCompany[$item["@attributes"]['ГУИД']]['FIZ_ADDRESS'] = $item["ФактическийАдрес"];
                    $listCompanyСode[$item["@attributes"]['ГУИД']] = $item["@attributes"]['ГУИД'];
                    if (!empty($item["Телефон"])) {
                        $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $item["Телефон"]);
                        $firstSymbol = mb_substr($phoneClear, 0, 1);
                        if ($firstSymbol == 7) {
                            $phonesForSearch[] = $phoneClear;
                            $phoneTrim = mb_substr($phoneClear, 1);
                            $phonesForSearch[] = "8{$phoneTrim}";
                        } elseif ($firstSymbol == 8) {
                            $phoneTrim = mb_substr($phoneClear, 1);
                            $phonesForSearch[] = "7{$phoneTrim}";
                        }
                        $listCompanyPhone[$item["@attributes"]['ГУИД']] = $phoneClear;
                    }
                    if (!empty($item["ЭлектроннаяПочта"]))
                        $listCompanyEmail[$item["@attributes"]['ГУИД']] = $item["ЭлектроннаяПочта"];
                } else {
                    $listContact[$item["@attributes"]['ГУИД']]['CODE'] = $item["@attributes"]['ГУИД'];
                    $listContact[$item["@attributes"]['ГУИД']]['NAME'] = $item["НаименованиеПолное"];
                    $listContact[$item["@attributes"]['ГУИД']]['PHONE'] = $item["Телефон"];
                    $listContact[$item["@attributes"]['ГУИД']]['EMAIL'] = $item["ЭлектроннаяПочта"];
                    $listContact[$item["@attributes"]['ГУИД']]['REGION'] = $item["БизнесРегион"];
                    $listContact[$item["@attributes"]['ГУИД']]['WORKING'] = $item["ВидДеятельности"];
                    $listContact[$item["@attributes"]['ГУИД']]['YR_ADDRESS'] = $item["ЮридическийАдрес"];
                    $listContact[$item["@attributes"]['ГУИД']]['FIZ_ADDRESS'] = $item["ФактическийАдрес"];
                    $listContactСode[$item["@attributes"]['ГУИД']] = $item["@attributes"]['ГУИД'];
                    if (!empty($item["Телефон"])) {
                        $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $item["Телефон"]);
                        $firstSymbol = mb_substr($phoneClear, 0, 1);
                        if ($firstSymbol == 7) {
                            $phonesForSearch[] = $phoneClear;
                            $phoneTrim = mb_substr($phoneClear, 1);
                            $phonesForSearch[] = "8{$phoneTrim}";
                        } elseif ($firstSymbol == 8) {
                            $phoneTrim = mb_substr($phoneClear, 1);
                            $phonesForSearch[] = "7{$phoneTrim}";
                        }
                        $listContactPhone[$item["@attributes"]['ГУИД']] = $phoneClear;
                    }
                    if (!empty($item["ЭлектроннаяПочта"]))
                        $listContactEmail[$item["@attributes"]['ГУИД']] = $item["ЭлектроннаяПочта"];
                }
            }

            $res_region = [];
            $db_region = \CIBlockElement::getList([], ['IBLOCK_ID' => self::REGION_IBLOCK], false, false, ['ID', 'NAME']);
            while ($ob_region = $db_region->fetch()) $res_region[$ob_region['NAME']] = $ob_region['ID'];
            $res_working = [];
            $db_working = \CIBlockElement::getList([], ['IBLOCK_ID' => self::WORKING_IBLOCK], false, false, ['ID', 'NAME']);
            while ($ob_working = $db_working->fetch()) $res_working[$ob_working['NAME']] = $ob_working['ID'];

            if ($listCompany) {
                $allExistCompanyCRM = [];
                $resultCompanyAddCRM = [];
                $resultExistCompany = self::getExistCode($listCompanyСode, 'COMPANY');
                if ($resultExistCompany['res_id']) $allExistCompanyCRM = $resultExistCompany['res_id'];
                self::updateDate($resultExistCompany, $listCompany, 'COMPANY', $res_region, $res_working);
            }
            if ($listContact) {
                $allExistContactCRM = [];
                $resultContactAddCRM = [];
                $resultExistContact = self::getExistCode($listContactСode, 'CONTACT');
                if ($resultExistContact['res_id']) $allExistContactCRM = $resultExistContact['res_id'];
                self::updateDate($resultExistContact, $listContact, 'CONTACT', $res_region, $res_working);
            }

        }
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
        if ($firstSymbol == 7) {
            $phonesForSearch[] = $phoneClear;
            $phoneTrim = mb_substr($phoneClear, 1);
            $phonesForSearch[] = "8{$phoneTrim}";
        } elseif ($firstSymbol == 8) {
            $phoneTrim = mb_substr($phoneClear, 1);
            $phonesForSearch[] = "7{$phoneTrim}";
        }
        return $phonesForSearch;
    }

    protected
    function updateDate($listUpdateCRM, $listDateXML, $typeOrg, $res_region, $res_working, $res_role_contractor)
    {
        if ($listUpdateCRM && $listDateXML) {
            foreach ($listDateXML as $key => $item) {
                $fields = [];
                $item['ID'] = $listUpdateCRM['res_id'][$key];
                $fields['TITLE'] = $item['NAME'];
//                $fields['ASSIGNED_BY_ID'] = self::ASSIGNED_ID;
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
                    $fields  [self::COMPANY_CODE_PROP] = $item['CODE'];
                    $fields  [self::COMPANY_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::COMPANY_REGION_PROP] = $res_region[$item['REGION']];
                    $fields  [self::COMPANY_INFO_PROP] = $res_region[$item['INFO']];
                    if(!empty($item['ROLE_CONTRACTOR'])) {
                        foreach ($item['ROLE_CONTRACTOR'] as $role) {
                            $fields  [self::COMPANY_ROLE_CONTRACTOR_PROP][] = $res_role_contractor[$role];
                        }
                    }

                    $oCompany = new \CCrmCompany(false);
                    //$updateCompany = $oCompany->Update($item['ID'], $fields);

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
                       // $resultRequisit = $requisite->update($reqData['ID'], $requisiteFields);
                        $rsDel = $requisite->getList([
                            "filter" => ["RQ_INN" => $item['INN'], "ENTITY_ID" => $item['ID'], "PRESET_ID" => 3],//удаляем реквизиты с физ лицом
                            'select' => ['ID']
                        ]);
                        if ($rsDel) {
                            $reqData2 = $rsDel->fetch();
                          //  if ($reqData2['ID']) $resultRequisit2 = $requisite->delete($reqData2['ID']);
                        }
                    } else {
                       // $resultRequisit = $requisite->add($requisiteFields);
                    }

                } else if ($typeOrg == 'CONTACT') {
                    $fields['NAME'] = $item['NAME'];
                    $fields['TYPE_ID'] = 'CLIENT';
                    $fields['SOURCE_ID'] = '4';
                    $fields  [self::CONTACT_CODE_PROP] = $item['CODE'];
                    $fields  [self::CONTACT_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::CONTACT_REGION_PROP] = $res_region[$item['REGION']];
                    $oContact = new \CCrmContact(false);
                  //  $updateLead = $oContact->Update($item['ID'], $fields);
                }
            }
        }
    }

    public
    function generalUnloadingXMLtoCRM()
    {
        \Bitrix\Main\Loader::includeModule('crm');
        libxml_use_internal_errors(TRUE);
        if (file_exists(self::FILE_PATH)) {
            var_dump('file');
            $xml1 = simplexml_load_file(self::FILE_PATH);
            $xml = json_decode(json_encode($xml1), true);
            $phonesForSearch = [];
            foreach ($xml["Контрагент"] as $key => $item) {
                $existCompany = $item["ИНН"];
                if (!empty($existCompany)) {
                    $listCompany[$item["@attributes"]['ГУИД']]['CODE'] = $item["@attributes"]['ГУИД'];
                    $listCompany[$item["@attributes"]['ГУИД']]['NAME'] = $item["Наименование"];
                    $listCompany[$item["@attributes"]['ГУИД']]['INN'] = $existCompany;
                    $listCompany[$item["@attributes"]['ГУИД']]['PHONE'] = $item["Телефон"];
                    $listCompany[$item["@attributes"]['ГУИД']]['EMAIL'] = $item["ЭлектроннаяПочта"];
                    $listCompany[$item["@attributes"]['ГУИД']]['REGION'] = $item["БизнесРегион"];
                    $listCompany[$item["@attributes"]['ГУИД']]['WORKING'] = $item["ВидДеятельности"];
                    $listCompany[$item["@attributes"]['ГУИД']]['YR_ADDRESS'] = $item["ЮридическийАдрес"];
                    $listCompany[$item["@attributes"]['ГУИД']]['FIZ_ADDRESS'] = $item["ФактическийАдрес"];
                    if (!empty($item["Телефон"])) {
                        $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $item["Телефон"]);
                        $firstSymbol = mb_substr($phoneClear, 0, 1);
                        if ($firstSymbol == 7) {
                            $phonesForSearch[] = $phoneClear;
                            $phoneTrim = mb_substr($phoneClear, 1);
                            $phonesForSearch[] = "8{$phoneTrim}";
                        } elseif ($firstSymbol == 8) {
                            $phoneTrim = mb_substr($phoneClear, 1);
                            $phonesForSearch[] = "7{$phoneTrim}";
                        }
                        $listCompanyPhone[$item["@attributes"]['ГУИД']] = $phoneClear;
                    }
                    if (!empty($item["ЭлектроннаяПочта"]))
                        $listCompanyEmail[$item["@attributes"]['ГУИД']] = $item["ЭлектроннаяПочта"];
                } else {
                    $listContact[$item["@attributes"]['ГУИД']]['CODE'] = $item["@attributes"]['ГУИД'];
                    $listContact[$item["@attributes"]['ГУИД']]['NAME'] = $item["НаименованиеПолное"];
                    $listContact[$item["@attributes"]['ГУИД']]['PHONE'] = $item["Телефон"];
                    $listContact[$item["@attributes"]['ГУИД']]['EMAIL'] = $item["ЭлектроннаяПочта"];
                    $listContact[$item["@attributes"]['ГУИД']]['REGION'] = $item["БизнесРегион"];
                    $listContact[$item["@attributes"]['ГУИД']]['WORKING'] = $item["ВидДеятельности"];
                    $listContact[$item["@attributes"]['ГУИД']]['YR_ADDRESS'] = $item["ЮридическийАдрес"];
                    $listContact[$item["@attributes"]['ГУИД']]['FIZ_ADDRESS'] = $item["ФактическийАдрес"];
                    if (!empty($item["Телефон"])) {
                        $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $item["Телефон"]);
                        $firstSymbol = mb_substr($phoneClear, 0, 1);
                        if ($firstSymbol == 7) {
                            $phonesForSearch[] = $phoneClear;
                            $phoneTrim = mb_substr($phoneClear, 1);
                            $phonesForSearch[] = "8{$phoneTrim}";
                        } elseif ($firstSymbol == 8) {
                            $phoneTrim = mb_substr($phoneClear, 1);
                            $phonesForSearch[] = "7{$phoneTrim}";
                        }
                        $listContactPhone[$item["@attributes"]['ГУИД']] = $phoneClear;
                    }
                    if (!empty($item["ЭлектроннаяПочта"]))
                        $listContactEmail[$item["@attributes"]['ГУИД']] = $item["ЭлектроннаяПочта"];
                }
            }

            $res_region = [];
            $db_region = \CIBlockElement::getList([], ['IBLOCK_ID' => self::REGION_IBLOCK], false, false, ['ID', 'NAME']);
            while ($ob_region = $db_region->fetch()) $res_region[$ob_region['NAME']] = $ob_region['ID'];
            $res_working = [];
            $db_working = \CIBlockElement::getList([], ['IBLOCK_ID' => self::WORKING_IBLOCK], false, false, ['ID', 'NAME']);
            while ($ob_working = $db_working->fetch()) $res_working[$ob_working['NAME']] = $ob_working['ID'];

            if ($listCompany) {
                $resultCompanyPhoneUpdateID = [];
                $resultCompanyEmailUpdateID = [];

                $resultCompanyPhone = self::getAllPhone($listCompanyPhone, $phonesForSearch, 'COMPANY');
                if ($resultCompanyPhone['PhoneUpdateID']) $resultCompanyPhoneUpdateID = $resultCompanyPhone['PhoneUpdateID'];
                $resultCompanyEmail = self::getAllEmail($listCompanyEmail, $resultCompanyPhoneUpdateID, 'COMPANY');
                if ($resultCompanyEmail['EmailUpdateID']) $resultCompanyEmailUpdateID = $resultCompanyEmail['EmailUpdateID'];

                $allExistCompanyCRM = array_merge($resultCompanyPhoneUpdateID, $resultCompanyEmailUpdateID);
                $resultCompanyAddCRM = array_diff_key($listCompany, $allExistCompanyCRM);

                $listCompanyPhoneUpdateCRM = array_intersect_key($listCompany, $resultCompanyPhoneUpdateID);
                $listCompanyEmailUpdateCRM = array_intersect_key($listCompany, $resultCompanyEmailUpdateID);

                if ($resultCompanyPhoneUpdateID) self::updatePhone($listCompanyPhoneUpdateCRM, $resultCompanyPhoneUpdateID, $resultCompanyPhone['RES'], 'COMPANY', $res_region, $res_working);
                if ($resultCompanyEmailUpdateID) self::updateEmail($listCompanyEmailUpdateCRM, $resultCompanyEmailUpdateID, $resultCompanyEmail['RES'], 'COMPANY', $res_region, $res_working);
                self::addCompanyAndContact($resultCompanyAddCRM, 'COMPANY', $res_region, $res_working);
            }
            if ($listContact) {
                $resultContactPhoneUpdateID = [];
                $resultContactEmailUpdateID = [];

                $resultContactPhone = self::getAllPhone($listContactPhone, $phonesForSearch, 'CONTACT');
                if ($resultContactPhone['PhoneUpdateID']) $resultContactPhoneUpdateID = $resultContactPhone['PhoneUpdateID'];
                $resultContactEmail = self::getAllEmail($listContactEmail, $resultContactPhoneUpdateID, 'CONTACT');
                if ($resultContactEmail['EmailUpdateID']) $resultContactEmailUpdateID = $resultContactEmail['EmailUpdateID'];

                $allExistContactCRM = array_merge($resultContactPhoneUpdateID, $resultContactEmailUpdateID);
                $resultContactAddCRM = array_diff_key($listContact, $allExistContactCRM);

                $listContactPhoneUpdateCRM = array_intersect_key($listContact, $resultContactPhoneUpdateID);
                $listContactEmailUpdateCRM = array_intersect_key($listContact, $resultContactEmailUpdateID);

                if ($resultContactPhoneUpdateID) self::updatePhone($listContactPhoneUpdateCRM, $resultContactPhoneUpdateID, $resultContactPhone['RES'], 'CONTACT', $res_region, $res_working);
                if ($resultContactEmailUpdateID) self::updateEmail($listContactEmailUpdateCRM, $resultContactEmailUpdateID, $resultContactEmail['RES'], 'CONTACT', $res_region, $res_working);
                self::addCompanyAndContact($resultContactAddCRM, 'CONTACT', $res_region, $res_working);
            }
        }
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
    function updatePhone($listPhoneUpdateCRM, $resultPhoneUpdateID, $res, $typeOrg, $res_region, $res_working)
    {
        if ($listPhoneUpdateCRM) {
            foreach ($listPhoneUpdateCRM as $key => $item) {
                $fields = [];
                $item['ID'] = $resultPhoneUpdateID[$key];
                $fields['TITLE'] = $item['NAME'];
//                $fields['ASSIGNED_BY_ID'] = self::ASSIGNED_ID;
                if ($item['EMAIL']) {
                    if (!in_array(strtoupper($item['EMAIL']), $res[$item['ID']]['EMAIL'])) {
                        $fields['FM']['EMAIL'] = array(
                            'n0' => array(
                                'VALUE_TYPE' => 'WORK',
                                'VALUE' => $item['EMAIL'],
                            )
                        );
                    }
                }

                if ($typeOrg == 'COMPANY') {
                    $fields  [self::COMPANY_CODE_PROP] = $item['CODE'];
                    $fields  [self::COMPANY_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::COMPANY_REGION_PROP] = $res_region[$item['REGION']];

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
                        "filter" => ["RQ_INN" => $item['INN'], "ENTITY_ID" => $item['ID'], "PRESET_ID" => $typeReq,]
                    ]);
                    $reqData = $rs->fetch();
                    if ($reqData) {
                        $resultRequisit = $requisite->update($reqData['ID'], $requisiteFields);
                    } else {
                        $resultRequisit = $requisite->add($requisiteFields);
                    }

                } else if ($typeOrg == 'CONTACT') {
                    $fields  [self::CONTACT_CODE_PROP] = $item['CODE'];
                    $fields  [self::CONTACT_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::CONTACT_REGION_PROP] = $res_region[$item['REGION']];
                    $oContact = new \CCrmContact(false);
                    $updateLead = $oContact->Update($item['ID'], $fields);
                }
            }
        }
    }

    protected
    function updateEmail($listEmailUpdateCRM, $resultEmailUpdateCRM, $res, $typeOrg, $res_region, $res_working)
    {
        if ($listEmailUpdateCRM) {
            foreach ($listEmailUpdateCRM as $key => $item) {
                $item['ID'] = $resultEmailUpdateCRM[$key];
                $fields = [];
                $fields  ['TITLE'] = $item['NAME'];
//                $fields  ['ASSIGNED_BY_ID'] = self::ASSIGNED_ID;
                if ($item['PHONE']) {
                    if (!in_array(str_replace(['+', ' ', '(', ')', '-'], "", $item['PHONE']), $res[$item['ID']]['PHONE'])) {
                        $fields['FM']['PHONE'] = array(
                            'n0' => array(
                                'VALUE_TYPE' => 'WORK',
                                'VALUE' => $item['PHONE'],
                            )
                        );
                    }
                }

                if ($typeOrg == 'COMPANY') {
                    $fields  [self::COMPANY_CODE_PROP] = $item['CODE'];
                    $fields  [self::COMPANY_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::COMPANY_REGION_PROP] = $res_region[$item['REGION']];

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
                        "filter" => ["RQ_INN" => $item['INN'], "ENTITY_ID" => $item['ID'], "PRESET_ID" => $typeReq,]
                    ]);
                    $reqData = $rs->fetch();
                    if ($reqData) {
                        $resultRequisit = $requisite->update($reqData['ID'], $requisiteFields);
                    } else {
                        $resultRequisit = $requisite->add($requisiteFields);
                    }

                } else if ($typeOrg == 'CONTACT') {
                    $fields  [self::CONTACT_CODE_PROP] = $item['CODE'];
                    $fields  [self::CONTACT_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::CONTACT_REGION_PROP] = $res_region[$item['REGION']];
                    $oContact = new \CCrmContact(false);
                    $updateLead = $oContact->Update($item['ID'], $fields);
                }
            }
        }
    }

    protected
    function addCompanyAndContact($resultAddCRM, $typeOrg, $res_region, $res_working)
    {
        if ($resultAddCRM) {
            foreach ($resultAddCRM as $key => $item) {
                $fields = [];
//                $fields['ASSIGNED_BY_ID'] = self::ASSIGNED_ID;
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
                    $fields  [self::COMPANY_CODE_PROP] = $item['CODE'];
                    $fields  [self::COMPANY_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::COMPANY_REGION_PROP] = $res_region[$item['REGION']];
                    $oCompany = new \CCrmCompany(false);
                    $idCompany = $oCompany->Add($fields);
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
                    $fields['NAME'] = $item['NAME'];
                    $fields['TYPE_ID'] = 'CLIENT';
                    $fields['SOURCE_ID'] = '4';
                    $fields  [self::CONTACT_CODE_PROP] = $item['CODE'];
                    $fields  [self::CONTACT_WORKING_PROP] = $res_working[$item['WORKING']];
                    $fields  [self::CONTACT_REGION_PROP] = $res_region[$item['REGION']];
                    $oContact = new \CCrmContact(false);
                    $idContact = $oContact->Add($fields);
                    if (!$idContact) print $oContact->LAST_ERROR;
                }
            }
        }
    }
}
