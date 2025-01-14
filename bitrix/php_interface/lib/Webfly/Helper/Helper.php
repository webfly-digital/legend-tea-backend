<?php

namespace Webfly\Helper;

use Bitrix\Main\Loader;


Loader::includeModule('iblock');

class Helper
{

    public static function getSectionView($sectionId)
    {
        $enum = [];
        $viewType = \CIBlockSection::getList([], ['IBLOCK_ID' => CATALOG_IBLOCK_ID, 'ID' => $sectionId], false, ['ID', 'UF_VIEW_TYPE'], false)->fetch();
        if ($viewType['UF_VIEW_TYPE']) {
            $enum = \CUserFieldEnum::GetList(array("DEF" => "DESC", "SORT" => "ASC"), array("USER_FIELD_NAME" => 'UF_VIEW_TYPE', 'ID' => $viewType['UF_VIEW_TYPE']))->fetch();
        }
        return $enum["XML_ID"] ?: 'SIMPLE';
    }

    public static function getCatalogSections($filter = [])
    {
        $sects = [];
        $sections = [];
        $res = \CIBlockElement::getList([], $filter, ['IBLOCK_SECTION_ID'], false, ['IBLOCK_SECTION_ID']);
        while ($ob = $res->fetch()) {
            if ($ob['CNT'] > 0)
                $sects[] = $ob;
        }
        unset ($ob);
        unset ($res);

        $sectIDs = [];
        if ($sects) {
            $sectIDs = array_column($sects, 'IBLOCK_SECTION_ID');
            $res = \CIBlockSection::getList(['NAME' => 'asc'], ['ID' => $sectIDs, 'IBLOCK_ID' => CATALOG_IBLOCK_ID, '>DEPTH_LEVEL' => 1], false, ['ID', 'NAME', 'SORT', 'CODE'], false);
            while ($ob = $res->fetch()) {
                $sections[$ob['ID']] = $ob;
            }
            unset ($ob);
            unset ($res);
        }


        $firstLvlSections = array_diff($sectIDs, array_column($sections, 'ID'));
        //если товары лежат в основной категории (какао)
        if ($firstLvlSections) {
            $res = \CIBlockSection::getList(['UF_B2B_SORT' => 'asc', 'SORT' => 'asc'], ['ID' => $firstLvlSections, 'IBLOCK_ID' => CATALOG_IBLOCK_ID, '=DEPTH_LEVEL' => 1], false, ['ID', 'NAME', 'SORT', 'CODE'], false);
            while ($ob = $res->fetch()) {
                $sections[$ob['ID']] = $ob;
            }
            unset ($ob);
            unset ($res);
        }
        if ($sections) {
            uasort($sections, function ($a, $b) {
                if ($a['NAME'] == $b['NAME']) {
                    return 0;
                }
                return ($a['NAME'] < $b['NAME']) ? -1 : 1;
            });
        }
        return $sections;
    }

    public static function getDetailInfo($productIDs)
    {
        \Bitrix\Main\Loader::includeModule('iblock');
        $resProps = [];
        $propCodes = ['PROPERTY_MORE_PHOTO', 'PROPERTY_CML2_ARTICLE', 'PROPERTY_HIT', 'PROPERTY_OPISANIE_DLYA_SAYTA'];
        $fields = ['IBLOCK_ID', 'ID', 'DETAIL_PICTURE', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'NAME', 'CATALOG_QUANTITY'];
        $props = \CIBlockElement::getList([], ['IBLOCK_ID' => [CATALOG_IBLOCK_ID, SKU_IBLOCK_ID], 'ID' => $productIDs], false, false, array_merge($fields, $propCodes));
        while ($ob = $props->fetch()) {

            foreach ($propCodes as $propCode) {

                $propValue = $ob[$propCode . "_VALUE"];

                if (!$propValue) continue;

                if ($propCode == 'PROPERTY_MORE_PHOTO') {
                    if (is_array($resProps[$ob['ID']]['PROPERTIES'][$propCode])) {
                        if ($propValue && !in_array($propValue, $resProps[$ob['ID']]['PROPERTIES'][$propCode]))
                            $resProps[$ob['ID']]['PROPERTIES'][$propCode][] = $propValue;
                    } else {
                        $resProps[$ob['ID']]['PROPERTIES'][$propCode][] = $propValue;
                    }
                } elseif ($propCode == 'PROPERTY_HIT') {
                    switch (mb_strtolower($propValue)) {
                        case 'новинка':
                            $label = ['CLASS' => 'green', 'ICON' => 'new', 'TEXT' => 'Новинка'];
                            break;
                        case 'хит':
                            $label = ['CLASS' => 'red', 'ICON' => 'fire', 'TEXT' => 'Хит'];
                            break;
                        case 'рекомендуем':
                            $label = ['CLASS' => 'yellow', 'ICON' => 'thumb-up', 'TEXT' => 'Советуем'];
                            break;
                        default:
                            $label = '';
                            break;
                    }
                    if ($label)
                        $resProps[$ob['ID']]['LABEL'] = $label;
                } else {
                    $resProps[$ob['ID']]['PROPERTIES'][$propCode] = $propValue;
                }
            }

            foreach ($fields as $fCode) {
                $fieldValue = $ob[$fCode];
                if ($fieldValue || $fCode == 'CATALOG_QUANTITY') {
                    $resProps[$ob['ID']]['FIELDS'][$fCode] = $fieldValue;
                }
            }

        }
        unset($ob);
        unset($props);
        unset($propCode);
        unset($fCode);

        if ($resProps) {
            $propertyList = array(
                1 => "VID_KOFE",
                2 => "OBRABOTKA",
                3 => "RAZNOVIDNOST",
                4 => "REGION",
                6 => "CML2_BASE_UNIT",
                7 => "ZAMETKI_KAPPINGA",
                8 => "CML2_MANUFACTURER",
                9 => "CML2_BAR_CODE",
                10 => "VYSOTA_PROIZRASTANIYA",
                11 => "OTSENKA_Q_GRADER",
                12 => "STEPEN_OBZHARKI",
                13 => "POMOL",
                14 => "FORMA_VYPUSKA",
                15 => "MATERIAL",
                16 => "VID_CHAYA",
                17 => "OBRABOTKA_1",
                18 => "UPAKOVKA_1",
                19 => "ARTIKUL_NOMENKLATURY_DLYA_BITRIKS24",
                21 => "BRAND",
                23 => "EXPANDABLES_FILTER",
                24 => "LINK_SALE",
                25 => "POPUP_VIDEO",
                26 => "PODBORKI",
                27 => "ASSOCIATED_FILTER",
                28 => "LINK_REGION",
                29 => "STRANA_PROISKHOZHDENIYA_OSNOVA",
                30 => "VID_KOFE2",
                31 => "OBEM_ML_",
                32 => "OTDEL",
                33 => "PECHAT_ETIKETKI",
                34 => "PECHAT_ETIKETKI_2",
                35 => "EXPANDABLES",
                36 => "BIG_BLOCK",
                37 => "LINK_VACANCY",
                38 => "VIDEO_YOUTUBE",
                39 => "PROP_2104",
                40 => "COL_GROUP",
                41 => "LINK_NEWS",
                42 => "ASSOCIATED",
                43 => "HELP_TEXT",
                44 => "skorost_vrash",
                45 => "LINK_STAFF",
                46 => "LINK_BLOG",
                47 => "BCOUNTRY",
                48 => "SALE_TEXT",
                49 => "PROP_2033",
                50 => "FAVORIT_ITEM",
                51 => "SERVICES",
                52 => "PROP_2065",
                53 => "PROP_2052",
                54 => "PROP_2053",
                55 => "PROP_2089",
                56 => "PROP_2085",
                57 => "PROP_284",
                58 => "PROP_25",
                59 => "PROP_2101",
                60 => "PROP_2067",
                61 => "PROP_2054",
                62 => "PROP_283",
                63 => "PROP_2066",
                64 => "PROP_2084",
                65 => "TIP_KOFEMOLKI",
                66 => "PROP_2017",
                67 => "PROP_206",
                68 => "PROP_2100",
                69 => "PROP_315",
                70 => "PROP_2091",
                71 => "PROP_2026",
                72 => "PROP_307",
                73 => "TIP_KOFEMASHINY",
                74 => "GOD_RETSEPTA",
                75 => "GOD_IZGOTOVLENIYA",
                76 => "OBEM_BOYLERA",
                77 => "KOLICHESTVO_GRUPP",
                78 => "MOSHCHNOST",
                79 => "PROIZVODITELNOST_CHASHEK_V_DEN",
                80 => "VID_NOMENKLATURY_DLYA_BITRIKS24",
                81 => "POL_DLYA_BITRIKS24",
                82 => "DIAMETR_ZHERNOVOV_MM_",
                83 => "NAPRYAZHENIE",
                84 => "VMESTIMOST_BUNKERA",
                85 => "PROIZVODITELNOST_KG_DEN",
                86 => "SKOROST_POMOLA_SEK_DOZA",
                87 => "REKOMENDATSIYA",
                88 => "NAKLEYKA",
                89 => "OSTATOK_NOMENKLATURY_DLYA_BITRIKS24",
                90 => "DOLG_NAM_MY_PO_DANNYM_1S_DLYA_BITRIKS24",
                91 => "PROP_159",
                92 => "PROP_2083",
                93 => "COLOR_REF2",
                94 => "PROP_305",
                95 => "PROP_352",
                96 => "PROP_317",
                97 => "PROP_357",
                98 => "PROP_2102",
                99 => "PROP_318",
                100 => "PROP_349",
                101 => "PROP_327",
                102 => "PROP_370",
                103 => "PROP_336",
                104 => "PROP_2115",
                105 => "PROP_346",
                106 => "PROP_2120",
                107 => "PROP_363",
                108 => "PROP_320",
                109 => "PROP_325",
                110 => "PROP_2103",
                111 => "PROP_300",
                112 => "PROP_322",
                113 => "PROP_362",
                114 => "PROP_365",
                115 => "PROP_359",
                116 => "PROP_364",
                117 => "PROP_356",
                118 => "PROP_343",
                119 => "PROP_314",
                120 => "PROP_348",
                121 => "PROP_316",
                122 => "PROP_350",
                123 => "PROP_333",
                124 => "PROP_332",
                125 => "PROP_360",
                126 => "PROP_353",
                127 => "PROP_347",
                128 => "PROP_2114",
                129 => "PROP_301",
                130 => "PROP_323",
                131 => "PROP_324",
                132 => "PROP_355",
                133 => "PROP_304",
                134 => "PROP_358",
                135 => "PROP_319",
                136 => "PROP_344",
                137 => "PROP_328",
                138 => "PROP_338",
                139 => "PROP_2113",
                140 => "PROP_366",
                141 => "PROP_302",
                142 => "PROP_303",
                143 => "PROP_341",
                144 => "PROP_223",
                145 => "PROP_354",
                146 => "PROP_313",
                147 => "PROP_329",
                148 => "PROP_342",
                149 => "PROP_367",
                150 => "PROP_340",
                151 => "PROP_351",
                152 => "PROP_368",
                153 => "PROP_369",
                154 => "PROP_331",
                155 => "PROP_337",
                156 => "PROP_345",
                157 => "PROP_339",
                158 => "PROP_310",
                159 => "PROP_309",
                160 => "PROP_330",
                161 => "PROP_335",
                162 => "PROP_321",
                163 => "PROP_308",
                164 => "PROP_334",
                165 => "PROP_311",
                166 => "PROP_2132",
                167 => "SHUM",
                168 => "PROP_361",
                169 => "PROP_326",
                170 => "PROP_2090",
                171 => "PROP_2027",
                172 => "PROP_2098",
                173 => "PROP_2112",
                174 => "PROP_2122",
                175 => "PROP_221",
                176 => "PROP_24",
                177 => "PROP_2134",
                178 => "PROP_23",
                179 => "PROP_2049",
                180 => "PROP_22",
                181 => "PROP_2095",
                182 => "PROP_2044",
                183 => "PROP_162",
                184 => "PROP_207",
                185 => "PROP_220",
                186 => "PROP_2094",
                187 => "PROP_2092",
                188 => "PROP_2111",
                189 => "PROP_2133",
                190 => "PROP_2096",
                191 => "PROP_2086",
                192 => "PROP_285",
                193 => "PROP_2130",
                194 => "PROP_286",
                195 => "PROP_222",
                196 => "PROP_2121",
                197 => "PROP_2123",
                198 => "PROP_2124",
                199 => "PROP_2093",
                200 => "LINK_REVIEWS",
                201 => "PROP_312",
                202 => "PROP_3083",
                203 => "PROP_2055",
                204 => "PROP_2069",
                205 => "PROP_2062",
                206 => "PROP_2061",
                207 => "RECOMMEND",
                208 => "NEW",
                209 => "STOCK",
                210 => "VIDEO",
                211 => "MORE_PHOTO",
                212 => "OSNOVA",
                213 => "KOLICHESTVO_OSADKOV",
                214 => "POCHVA",
                215 => "POD_ZAPAYKU_1",
                216 => "VYSOTA_MM",
                217 => "VYSOTA_MM_1",
                218 => "GLUBINA_MM_1",
                219 => "SHIRINA_MM_1",
                220 => "TSVET_1",
                221 => "TIP_PAKETA_1",
                222 => "VMESTIMOST_PRIMERNO_1",
                223 => "KLAPAN_1",
                224 => "PODKHODIT_DLYA",
                225 => "OKOSHKO",
                226 => "FORMA_VYPUSKA",
                227 => "VID_CHAYA",
                228 => "OSNOVA",
                229 => "ZAMETKI_KAPPINGA",
                230 => "VYSOTA_PROIZRASTANIYA",
                231 => "REGION",
                232 => "KOLICHESTVO_OSADKOV",
                233 => "POCHVA",
                234 => "VES_UPAKOVKI",
                235 => "OTSENKA_Q_GRADER",
                236 => "RAZNOVIDNOST",
                237 => "OBRABOTKA_1",
                238 => "ORIDZHIN",
                239 => "KATEGORIYA_CHAYA",
                240 => "TIP_SLADOSTI",
                241 => "DATA_PROIZVODSTVA",
                242 => "FABRIKA",
                243 => "TIP_POSUDY",
                244 => "MATERIAL",
                245 => "OBEM",
                248 => "OTSENKA_Q_GRADER",
                249 => "RAZMER_ZERNA",
                250 => "ROSTER_DLYA_OBZHARKI",
                251 => "OBRABOTKA",
                252 => "OBRABOTKA_1",
                253 => "KISLOTNOST",
                254 => "PLOTNOST",
            );
            foreach ($resProps as &$resData) {
                $pictures = [];
                if ($resData['FIELDS']['DETAIL_PICTURE'])
                    $pictures[] = $resData['FIELDS']['DETAIL_PICTURE'];

                if ($resData["PROPERTIES"]['PROPERTY_MORE_PHOTO'])
                    $pictures = array_merge($pictures, $resData["PROPERTIES"]['PROPERTY_MORE_PHOTO']);

                if ($pictures) {
                    foreach ($pictures as $pictureID) {
                        $resData['PICTURES'][] = \CFile::ResizeImageGet($pictureID, ['width' => 352, 'height' => 352], BX_RESIZE_IMAGE_PROPORTIONAL, false, false, false, 100);
                        $resData['PICTURES_ORIGINAL'][] = \CFile::GetPath($pictureID);
                    }
                }

                foreach ($propertyList as $pid) {
                    $prop = \CIBlockElement::GetProperty($resData['FIELDS']['IBLOCK_ID'], $resData['FIELDS']['ID'], "sort", "asc", array("CODE" => $pid))->fetch();
                    $values = \CIBlockFormatProperties::GetDisplayValue($resData['FIELDS'], $prop);
                    if ($values ["PROPERTY_TYPE"] == 'F') continue;
                    $values['DISPLAY_VALUE'] = $values['VALUE_ENUM'] ?: $values['DISPLAY_VALUE'];

                    if ($values['DISPLAY_VALUE']) {
                        $ranges = ['KISLOTNOST', 'PLOTNOST'];

                        if (in_array($pid, $ranges)) {
                            $percent = \Webfly\Helper\Helper::getRangePercent($values['DISPLAY_VALUE']);
                            if ($percent > 0)
                                $resData['RANGES'][$pid] = ['NAME' => $values['NAME'], 'VALUE' => $percent];
                        } else {
                            $resData['DISPLAY_PROPERTIES'][$pid] = $values;
                        }

                    }

                }

            }
            unset($pictures);
            unset($pictureID);
        }
        return $resProps;
    }

    public static function getRangePercent($propValue)
    {
        $percent = 0;
        if (strstr($propValue, '/')) {
            $value = explode('/', $propValue);
            if (is_numeric($value[0])) $percent = $value[0] * 10;
        }
        return $percent;
    }


    public static function getProfileLogo($userID)
    {
        Loader::includeModule('sale');
        Loader::includeModule('iblock');

        $arCompany = [];
        if ($userID) {
            $propUser = \CSaleOrderUserProps::GetList([], ["PERSON_TYPE_ID" => B2B_UR_PERSON_TYPE_ID, 'USER_ID' => $userID]);
            while ($itemUser = $propUser->fetch()) {
                $propValsLogo = \CSaleOrderUserPropsValue::GetList([], array("!VALUE" => false, 'PERSON_TYPE_ID' => B2B_UR_PERSON_TYPE_ID, 'CODE' => 'LOGO', 'USER_PROPS_ID' => $itemUser['ID']));
                while ($itemLogo = $propValsLogo->fetch()) {
                    $arCompany[] = ['ID' => $itemUser['ID'], 'NAME' => $itemUser['NAME']];
                }
            }

        }
        return $arCompany;
    }


    public static function getExistUsersInn($inn, $userID = false)
    {
        $resultEmail = [];
        if (empty($inn)) return $resultEmail;
        Loader::includeModule('iblock');
        Loader::includeModule('main');
        $arExistInn = [];
        $arId = [];
        $arName = [];

        $propValsInn = \CSaleOrderUserPropsValue::GetList([], array("VALUE" => $inn, 'PERSON_TYPE_ID' => B2B_UR_PERSON_TYPE_ID, 'CODE' => 'INN'));
        while ($itemInn = $propValsInn->fetch()) {
            $arExistInn[$itemInn['USER_PROPS_ID']] = $itemInn;
        }

        if (!empty($arExistInn)) {
            $propUser = \CSaleOrderUserProps::GetList([], array("ID" => array_keys($arExistInn)));
            while ($itemUser = $propUser->fetch()) {
                $arId[$itemUser['USER_ID']] = $itemUser['USER_ID'];
                $arName[$itemUser['USER_ID']] = $itemUser['NAME'];
            }
            if ($arId) {
                $rsUsers = \Bitrix\Main\UserTable::getList([
                    "select" => ["ID", "EMAIL"],
                    "filter" => ["ID" => $arId],
                ]);
                while ($arUser = $rsUsers->Fetch()) {
                    if ($arUser['EMAIL']) {
                        $strEmail = explode('@', $arUser['EMAIL']);
                        if ($strEmail[0]) {
                            if (mb_strlen($strEmail[0]) > 3) {
                                $newAr = array_fill(0, mb_strlen($strEmail[0]) - 3, '*');
                                $newStr = implode('', $newAr);
                                $email = mb_substr($strEmail[0], 0, 3) . $newStr . '@' . $strEmail[1];
                            }
                        }
                        $arEmail[$arUser['ID']] = $email ?: $arUser['EMAIL'];
                    }
                };

            }

        }
        if (!empty($arEmail)) {
            $resultEmail['LIST'] = $arEmail;
            $resultEmail['STRING'] = implode(', ', $arEmail);
            if ($userID) {
                if ($arEmail[$userID] && $arName[$userID]) {
                    if (count($arEmail) == 1) $resultEmail['MESSAGE'] = 'Компания с таким ИНН ' . $inn . ' (' . $arName[$userID] . ') уже создана в вашем оптовом кабинете.';
                    else {
                        unset($arEmail[$userID]);
                        $resultEmail['STRING'] = implode(', ', $arEmail);
                        $resultEmail['MESSAGE'] = $resultEmail['MESSAGE'] . 'Компания с таким ИНН ' . $inn . '  уже создана в вашем оптовом кабинет. Также с этим ИНН уже создана компания другим пользователем ' . $resultEmail['STRING'] . '. Если вам незнаком этот пользователь, Вы можете обратиться в <span style="text-decoration: underline" id="CALL_FORM">техническую поддержку</span>.';
                    }
                } else {
                    $resultEmail['MESSAGE'] = 'Компания с таким ИНН ' . $inn . ' уже создана другим пользователем ' . $resultEmail['STRING'] . '. Если вам незнаком этот пользователь, Вы можете обратиться в <span style="text-decoration: underline"  id="CALL_FORM">техническую поддержку</span>.';
                }
            } else {
                $resultEmail['MESSAGE'] = 'Компания с таким ИНН уже зарегистрирована в оптовом кабинете с почтовым адресом ' . $resultEmail['STRING'] . '.';
            }
        }

        return $resultEmail;
    }


    public static function getExistProfile($value, $propertyCode, $userID = false)
    {
        $result = [];
        if (empty($value) || empty($propertyCode)) return $result;
        Loader::includeModule('sale');
        $resultEmail = [];
        $arExistValue = [];
        $arId = [];
        $type = $propertyCode == 'COMPANY_PHONE' ? 'телефоном' : 'почтой';


        $propVals = \CSaleOrderUserPropsValue::GetList([], array("VALUE" => $value, 'PERSON_TYPE_ID' => B2B_FIZ_PERSON_TYPE_ID, 'CODE' => $propertyCode));
        while ($item = $propVals->fetch()) {
            $arExistValue[$item['USER_PROPS_ID']] = $item;
        }

        if (!empty($arExistValue)) {
            $propUser = \CSaleOrderUserProps::GetList([], array("ID" => array_keys($arExistValue)));
            while ($itemUser = $propUser->fetch()) {
                $arId[$itemUser['USER_ID']] = $itemUser['USER_ID'];
            }
            if ($arId) {
                $rsUsers = \Bitrix\Main\UserTable::getList([
                    "select" => ["ID", "EMAIL"],
                    "filter" => ["ID" => $arId],
                ]);
                while ($arUser = $rsUsers->Fetch()) {
                    if ($arUser['EMAIL']) {
                        $strEmail = explode('@', $arUser['EMAIL']);
                        if ($strEmail[0]) {
                            if (mb_strlen($strEmail[0]) > 3) {
                                $newAr = array_fill(0, mb_strlen($strEmail[0]) - 3, '*');
                                $newStr = implode('', $newAr);
                                $email = mb_substr($strEmail[0], 0, 3) . $newStr . '@' . $strEmail[1];
                            }
                        }
                        $arEmail[$arUser['ID']] = $email ?: $arUser['EMAIL'];
                    }
                };
            };
        }

        if (!empty($arEmail)) {
            $resultEmail['LIST'] = $arEmail;
            $resultEmail['STRING'] = implode(', ', $arEmail);
            if ($userID) {
                if ($arEmail[$userID]) {
                    if (count($arEmail) == 1) $resultEmail['MESSAGE'] = 'Профиль с ' . $type . ' ' . $value . ' уже создан в вашем кабинете.';
                    else {
                        unset($arEmail[$userID]);
                        $resultEmail['STRING'] = implode(', ', $arEmail);
                        $resultEmail['MESSAGE'] = $resultEmail['MESSAGE'] . 'Профиль с ' . $type . ' ' . $value . '  уже создан в вашем кабинете. Также с этой информацией создан профиль другим пользователем ' . $resultEmail['STRING'] . '. Если вам незнаком этот пользователь, Вы можете обратиться в <span style="text-decoration: underline" id="CALL_FORM">техническую поддержку</span>.';
                    }
                } else {
                    $resultEmail['MESSAGE'] = 'Профиль с ' . $type . ' ' . $value . ' уже создан другим пользователем ' . $resultEmail['STRING'] . '. Если вам незнаком этот пользователь, Вы можете обратиться в <span style="text-decoration: underline"  id="CALL_FORM">техническую поддержку</span>.';
                }
            } else {
                $resultEmail['MESSAGE'] = 'Компания с таким ИНН уже зарегистрирована в оптовом кабинете с почтовым адресом ' . $resultEmail['STRING'] . '.';
            }
        }

        return $resultEmail;
    }


    public static function getPropertyProduct($type, $props)
    {
        $arrayProp = [];
        if ($type == 'tea') {
            $characteristic = ['REGION', 'CML2_BASE_UNIT', 'CML2_MANUFACTURER', 'VID_CHAYA', 'OSNOVA', 'KATEGORIYA_CHAYA', 'SOSTAV_DLYA_KARTINOK_V_TOVARE'];
            $scale = ['SKOROST_ZAVARIVANIYA', 'KREPOST', 'AROMAT', 'VKUS_1'];

            foreach ($props['PROPERTIES'] as $key => $prop) {
                if ($key == 'VREMYA_ZAVARIVANIYA' && !empty($prop['VALUE'])) {
                    $arrayProp['CUB'][$key]['TITLE'] = 'Время заваривания';
                    $arrayProp['CUB'][$key]['VALUE'] = $prop['VALUE'];
                    $arrayProp['CUB'][$key]['CLASS_IMG'] = '<i class="icon-time"></i>';
                }
                if ($key == 'TEMPERATURA_ZAVARIVANIYA' && !empty($prop['VALUE'])) {
                    $arrayProp['CUB'][$key]['TITLE'] = 'Температура заваривания';
                    $arrayProp['CUB'][$key]['VALUE'] = $prop['VALUE'];
                    $arrayProp['CUB'][$key]['CLASS_IMG'] = '<i class="icon-temperature"></i>';
                }
                if ($key == 'VES_CHAYA' && !empty($prop['VALUE'])) {
                    $arrayProp['CUB'][$key]['TITLE'] = 'Вес чая';
                    $arrayProp['CUB'][$key]['VALUE'] = $prop['VALUE'];
                    $arrayProp['CUB'][$key]['CLASS_IMG'] = '<i class="icon-weight"></i>';
                }
                if (in_array($key, $characteristic) && !empty($prop['VALUE'])) {
                    $arrayProp['CHARACTERISTIC'][$key]['TITLE'] = $prop['NAME'];
                    $arrayProp['CHARACTERISTIC'][$key]['VALUE'] = $prop['VALUE'];
                }
                if (in_array($key, $scale) && !empty($prop['VALUE'])) {
                    $arrayProp['SCALE'][$key]['TITLE'] = $prop['NAME'];
                    $arrayProp['SCALE'][$key]['VALUE'] = $prop['VALUE'];
                }
            }
        }



    }


}
