<?php

use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Grid\Declension;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\PersonType;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
    die();

CBitrixComponent::includeComponentClass("bitrix:sale.order.ajax");
Loader::includeModule('iblock');
Loader::includeModule('ipol.sdek');

class WebflyCheckout extends SaleOrderAjax
{

    /**
     * Для загрузки языковых файлов
     */
    const SOA_PATH = '/bitrix/components/bitrix/sale.order.ajax';
    /**
     * Все методы ниже вызываются в мутаторе
     * вынесены чисто для удобства,
     * чтоб не было спагетти-кода в мутаторе
     */
    const PERSONAL_GROUP_ID = [12, 14];

    const RECIPIENT_ORDER_PROPS = [
        5 => [
            'GROUP_ID' => 20,
            'PROP_IDS' => [118, 119, 120, 121],
        ],
        6 => ['GROUP_ID' => 21],
    ];
    //const PERSONAL_HIDE_PROPS_ID = [46, 54, 64, 65, 98, 99, 112, 113, 114, 115,];
    const PERSONAL_HIDE_PROPS_ID = [132, 64, 65,];

    const OFFICE_SCHEDULE = 'Время работы: Вт-Вск: 08:00-16:00 в г. Пятигорске';

    const SKIP_PROP_GROUPS = [12, 21, 16, 17]; //убираем Личные свойства юр лица и кто получат заказ физ лица
    const STREET_PROPS = [70, 73];

//    public function onPrepareComponentParams($arParams)
//    {
//        $arParams = parent::onPrepareComponentParams($arParams);
//        $this->arResult['AUTH']['new_user_phone_auth'] = 'Y';
//        return $arParams;
//    }

    /**
     * Группировка товаров по разделам
     * и подсчет суммы
     * @param $result
     */
    public function basketHandler(&$result)
    {
        if (empty($result["JS_DATA"]["GRID"]['ROWS'])) return;

        $itemIDs = [];
        $itemsSum = 0;
        foreach ($result["JS_DATA"]["GRID"]['ROWS'] as &$basketItem) {
            $basketItem['data']['PRICE_FORMATED'] = \SaleFormatCurrency(
                    $basketItem['data']['PRICE'],
                    'RUB',
                    true
                ) . ' ₽';
            $basketItem['data']['SUM_FORMATED'] = \SaleFormatCurrency(
                    $basketItem['data']['SUM_NUM'],
                    'RUB',
                    true
                ) . ' ₽';
            $itemsSum += $basketItem['data']['SUM_NUM'];
            $itemIDs[] = $basketItem['data']['PRODUCT_ID'];
        }

        $result['JS_DATA']['TOTAL']['PRODUCTS_PRICE_FORMATED'] = \SaleFormatCurrency(
                $itemsSum,
                'RUB',
                true
            ) . ' ₽';

        $sections = [];
        $mainLvlSections = [];
        $firstLvlSections = [];
        $ready = $result["JS_DATA"]["GRID"]['ROWS'];

        self::setItemsSections($itemIDs, $sections, $ready);

        if ($sections) {
            foreach ($sections as $sectionID) {
                $sParents = \CIBlockSection::GetNavChain(CATALOG_IBLOCK_ID, $sectionID, ['ID', 'NAME', 'DEPTH_LEVEL'], true);
                $parentSection = current(array_filter($sParents, function ($v) {
                    return $v['DEPTH_LEVEL'] == 1;
                }));
                if ($parentSection) {
                    $firstLvlSections[$parentSection['ID']]['SUM'] = 0;
                    $firstLvlSections[$parentSection['ID']]['ID'] = $parentSection['ID'];
                    $firstLvlSections[$parentSection['ID']]['NAME'] = $parentSection['NAME'];
                    $firstLvlSections[$parentSection['ID']]['PRODUCT_SECTION_ID'][] = $sectionID;

                    $mainLvlSections[$sectionID]['ID'] = $sectionID;
                    $mainLvlSections[$sectionID]['BASE_SECTION_ID'] = $parentSection['ID'];

                    $sectionView = \Webfly\Helper\Helper::getSectionView($parentSection['ID']);

                    switch ($sectionView) {
                        case 'UPAKOVKA_POMOL':
                            $mainLvlSections[$sectionID]['COLUMNS'] = [
                                'UPAKOVKA' => ['NAME' => 'Упаковка', 'CODE' => 'UPAKOVKA'],
                                'POMOL' => ['NAME' => 'Помол', 'CODE' => 'POMOL']
                            ];
                            break;
                        case 'UPAKOVKA':
                            $mainLvlSections[$sectionID]['COLUMNS'] = [
                                'UPAKOVKA' => ['NAME' => 'Упаковка', 'CODE' => 'UPAKOVKA']
                            ];
                            break;
                        case 'OBEM':
                            $mainLvlSections[$sectionID]['COLUMNS'] = [
                                'OBEM' => ['NAME' => 'Объем', 'CODE' => 'OBEM']
                            ];
                            break;
                        default:
                            $mainLvlSections[$sectionID]['COLUMNS'] = [];
                            break;
                    }
                }

            }
        }

        if ($firstLvlSections) {
            $sectionSort = \CIBlockSection::getList([], ['IBLOCK_ID' => CATALOG_IBLOCK_ID, 'ID' => array_merge($sections, array_keys($firstLvlSections))], false, ['ID', 'NAME', 'UF_B2B_SORT', 'UF_DELIVERY_TIME'], false);
            while ($ob_sort = $sectionSort->fetch()) {
                if (in_array($ob_sort['ID'], array_keys($firstLvlSections))) {
                    $firstLvlSections[$ob_sort['ID']]['UF_B2B_SORT'] = $ob_sort['UF_B2B_SORT'];
                    $firstLvlSections[$ob_sort['ID']]['UF_DELIVERY_TIME'] = $ob_sort['UF_DELIVERY_TIME'] ?: 14;
                }
                if (in_array($ob_sort['ID'], $sections)) {
                    $mainLvlSections[$ob_sort['ID']]['NAME'] = $ob_sort['NAME'];
                    $mainLvlSections[$ob_sort['ID']]['UF_B2B_SORT'] = $ob_sort['UF_B2B_SORT'];
                }
            }
            uasort($firstLvlSections, function ($a, $b) {
                if ($a['UF_B2B_SORT'] == $b['UF_B2B_SORT']) {
                    return 0;
                }
                return ($a['UF_B2B_SORT'] < $b['UF_B2B_SORT']) ? -1 : 1;
            });
            uasort($mainLvlSections, function ($a, $b) {
                if ($a['NAME'] == $b['NAME']) {
                    return 0;
                }
                return ($a['NAME'] < $b['NAME']) ? -1 : 1;
            });
            $result['JS_DATA']['BASKET'] = [];

            $productIDs = [];
            $priceIDs = [];
            if ($mainLvlSections) {
                foreach ($mainLvlSections as &$pSection) {
                    foreach ($ready as $rKey => $rItem) {
                        if ($rItem['data']['PRODUCT_SECTION_ID'] == $pSection['ID']) {
                            $productColumns = [];
                            if (is_array($pSection['COLUMNS']) && is_array($rItem['data']['PROPERTIES']))
                                $productColumns = array_intersect(array_keys($pSection['COLUMNS']), array_keys($rItem['data']['PROPERTIES']));

                            if ($productColumns) {
                                $rItem['data']['COLUMNS'] = $productColumns;
                            }

                            if ($rItem['data']["PARENT_ID"]) {
                                $productIDs[] = $rItem['data']["PARENT_ID"];
                                $priceIDs[$rItem['data']["PARENT_ID"]] = 'CATALOG_PRICE_' . $rItem['data']["PRICE_TYPE_ID"];
                            } else {
                                $productIDs[] = $rItem['data']["PRODUCT_ID"];
                                $priceIDs[$rItem['data']["PRODUCT_ID"]] = 'CATALOG_PRICE_' . $rItem['data']["PRICE_TYPE_ID"];
                            }
                            $pSection['ROWS'][$rKey] = $rItem;

                        }
                    }
                }
                unset ($pSection);
                unset ($rKey);
                unset ($rItem);
            }

            foreach ($firstLvlSections as &$pSection) {
                foreach ($ready as &$rItem) {
                    if (in_array($rItem['data']['PRODUCT_SECTION_ID'], $pSection['PRODUCT_SECTION_ID'])) {
                        $pSection['SUM'] += $rItem['data']["SUM_NUM"];
                        unset ($rItem);
                    }
                }
                $pSection['SUM_FORMAT'] = \SaleFormatCurrency(
                        $pSection['SUM'],
                        'RUB',
                        true
                    ) . ' ₽';
            }
        }
        $result['JS_DATA']['BASKET'] = $firstLvlSections;
        $result['JS_DATA']['PRODUCTS'] = $mainLvlSections;
        $result['JS_DATA']['CARDS'] = [];

        $detailInfo = [];
        $listProductSKU = [];
        if ($productIDs) {
            $arAll = array_merge($productIDs, $itemIDs);
            $detailInfo = \Webfly\Helper\Helper::getDetailInfo($arAll);
            $listProductSKU = CCatalogSKU::getOffersList($productIDs, 0, ['ACTIVE' => 'Y'], array_merge(['PROPERTY_UPAKOVKA', 'PROPERTY_POMOL', 'CATALOG_QUANTITY'], $priceIDs));
        }

        if ($detailInfo) {
            foreach ($result['JS_DATA']['PRODUCTS'] as $sId => $sData) {
                if (!$sData['ROWS']) continue;

                foreach ($sData['ROWS'] as $bId => $item) {
                    $itemId = $item['data']["PARENT_ID"] ?: $item['data']["PRODUCT_ID"];
                    $detailItem = $detailInfo[$itemId];

                    if ($detailItem) {
                        if ($detailInfo[$item['data']["PRODUCT_ID"]]['FIELDS']['CATALOG_QUANTITY'] < 1) {
                            $arTimeDelivery[$sData['BASE_SECTION_ID']] = $result['JS_DATA']['BASKET'][$sData['BASE_SECTION_ID']]['UF_DELIVERY_TIME'];
                            $detailItem['TIME_DELIVERY'] = $result['JS_DATA']['BASKET'][$sData['BASE_SECTION_ID']]['UF_DELIVERY_TIME'];
                        }

                        if ($detailItem['DISPLAY_PROPERTIES']) {
                            foreach ($detailItem['DISPLAY_PROPERTIES'] as $dId => $displayProperty) {
                                $detailItem['DISPLAY_PROPERTIES'][$dId]['DISPLAY_VALUE'] = (is_array($displayProperty['DISPLAY_VALUE'])
                                    ? implode(' / ', $displayProperty['DISPLAY_VALUE'])
                                    : $displayProperty['DISPLAY_VALUE']);
                            }
                        }

                        if ($detailItem['LABEL']) {
                            $detailItem['LABEL']['CLASS'] = 'label ' . $detailItem['LABEL']['CLASS'];
                            $detailItem['LABEL']['ICON'] = 'icon icon-' . $detailItem['LABEL']['ICON'];
                        }

                        if ($detailItem['RANGES']) {
                            foreach ($detailItem['RANGES'] as $rangeKey => $rangeItem) {
                                $detailItem['RANGES'][$rangeKey]['VALUE'] = '--val: ' . $rangeItem['VALUE'] . '%';
                            }
                        }

                        if ($detailItem['PROPERTIES']['PROPERTY_OPISANIE_DLYA_SAYTA'] || $detailItem['FIELDS']['DETAIL_TEXT'])
                            $detailItem['DESCRIPTION'] = $detailItem['PROPERTIES']['PROPERTY_OPISANIE_DLYA_SAYTA'] ? htmlspecialcharsBack($detailItem['PROPERTIES']['PROPERTY_OPISANIE_DLYA_SAYTA']) : $detailItem['FIELDS']['DETAIL_TEXT'];


                        if (empty($result['JS_DATA']['PRODUCTS'][$sId]['ROWS'][$bId]['data']['DETAIL_PICTURE_SRC'])) $result['JS_DATA']['PRODUCTS'][$sId]['ROWS'][$bId]['data']['DETAIL_PICTURE_SRC'] = SITE_TEMPLATE_PATH . '/assets/static/img/img-holder2.png';

                        if (in_array($sData['BASE_SECTION_ID'], SECTION_GENERATION_PDF)) $detailItem['GENERATE_PDF'] = 'Y';

                        $result['JS_DATA']['PRODUCTS'][$sId]['ROWS'][$bId]['data']['DETAIL_DATA'] = $detailItem;
                    }
                }

            }
        }

        if ($arTimeDelivery) {
            $now = new \DateTime();
            $interval = new DateInterval('P' . max($arTimeDelivery) . 'D');
            $newDate = $now->add($interval);
            $result['JS_DATA']['TOTAL']['DELIVERY_TIME'] = $newDate->format('d.m.Y');
        }


        foreach ($result['JS_DATA']['PRODUCTS'] as $sId => $sData) {
            foreach ($sData['ROWS'] as $bId => $item) {
                if ($item['data']["PARENT_ID"]) {
                    $arrProduct[$item['data']["PARENT_ID"]]['PARENT_ID'] = $item['data']["PARENT_ID"];
                    $arrProduct[$item['data']["PARENT_ID"]]['SKU'][$item['data']["PRODUCT_ID"]]['PRODUCT_ID'] = $item['data']["PRODUCT_ID"];
                    $arrProduct[$item['data']["PARENT_ID"]]['SKU'][$item['data']["PRODUCT_ID"]]['QUANTITY'] = $item['data']["QUANTITY"];
                    $arrProduct[$item['data']["PARENT_ID"]]['SKU'][$item['data']["PRODUCT_ID"]]['PRICE'] = $item['data']["PRICE_FORMATED"];
                    if ($item['data']["DATE_UPDATE"]) {
                        $dateTime = new \DateTime($item['data']["DATE_UPDATE"]);
                        $arrProduct[$item['data']["PARENT_ID"]]['SKU'][$item['data']["PRODUCT_ID"]]['BASKET_DATE_UPDATE'] = $dateTime->format('YmdHis');
                    }
                    $arrProduct[$item['data']["PARENT_ID"]]['DETAIL_DATA'] = $item['data']["DETAIL_DATA"];
                } else {
                    $arrProduct[$item['data']["PRODUCT_ID"]]['PRODUCT_ID'] = $item['data']["PRODUCT_ID"];
                    $arrProduct[$item['data']["PRODUCT_ID"]]['QUANTITY'] = $item['data']["QUANTITY"];
                    $arrProduct[$item['data']["PRODUCT_ID"]]['PRICE'] = $item['data']["PRICE_FORMATED"];
                    $arrProduct[$item['data']["PRODUCT_ID"]]['DETAIL_DATA'] = $item['data']["DETAIL_DATA"];;
                }
                foreach ($result['JS_DATA']['PRODUCTS'][$sId]['ROWS'][$bId]['data']['COLUMNS'] as $key => $column) if ($column == "UPAKOVKA") unset($result['JS_DATA']['PRODUCTS'][$sId]['ROWS'][$bId]['data']['COLUMNS'][$key]);
            }
        }

        function cmp($a, $b)
        {
            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        }

        if (!empty($arrProduct)) {
            foreach ($arrProduct as $id => $item) {
                $card = [];
                $itemId = $item["PARENT_ID"] ?: $item["PRODUCT_ID"];

                if ($item['SKU']) {
                    $arSKU = [];
                    $pomols = [];
                    $card['PARENT_ID'] = $item["PARENT_ID"];

                    $arIDProducts = array_column($item['SKU'], 'PRODUCT_ID');
                    foreach ($item['SKU'] as $skuID => $sku) {
                        $upak = [];
                        foreach ($listProductSKU[$id] as $idSKU => $itemSKU) {
                            if (!empty($itemSKU['PROPERTY_POMOL_VALUE'])) {
                                $pomol = [];
                                $pomol['SKU_ID'] = $itemSKU['ID'];
                                $pomol['PROPERTY_POMOL_ENUM_ID'] = $itemSKU['PROPERTY_POMOL_ENUM_ID'];
                                $pomol['PROPERTY_POMOL_VALUE'] = $itemSKU['PROPERTY_POMOL_VALUE'];
                                $pomol['PROPERTY_POMOL_VALUE_ID'] = $itemSKU['PROPERTY_POMOL_VALUE_ID'];
                                $pomol['CATALOG_QUANTITY'] = $itemSKU['CATALOG_QUANTITY'];

                                if ($priceIDs[$itemId]) $pomol['PRICE'] = \SaleFormatCurrency($itemSKU[$priceIDs[$itemId]], 'RUB', true) . ' ₽';

                                if (in_array($itemSKU['ID'], $arIDProducts) && $item['SKU'][$itemSKU['ID']]['QUANTITY']) {
                                    $pomol['QUANTITY'] = $item['SKU'][$itemSKU['ID']]['QUANTITY'];
                                    $pomol['BASKET_DATE_UPDATE'] = $item['SKU'][$itemSKU['ID']]['BASKET_DATE_UPDATE'];
                                    $pomol['SELECTED'] = 'Y';
                                }

                                $pomols[$itemSKU['PROPERTY_UPAKOVKA_ENUM_ID']][$itemSKU["PROPERTY_POMOL_ENUM_ID"]] = $pomol;
                            } else {
                                $upak = [];
                                $upak['SKU_ID'] = $itemSKU['ID'];
                                $upak['CATALOG_QUANTITY'] = $itemSKU['CATALOG_QUANTITY'];

                                if ($priceIDs[$itemId]) $upak['PRICE'] = \SaleFormatCurrency($itemSKU[$priceIDs[$itemId]], 'RUB', true) . ' ₽';

                                if (in_array($itemSKU['ID'], $arIDProducts) && $item['SKU'][$itemSKU['ID']]['QUANTITY']) $upak['QUANTITY'] = $item['SKU'][$itemSKU['ID']]['QUANTITY'];;
                            }

                            if (!empty($itemSKU['PROPERTY_UPAKOVKA_VALUE'])) {
                                $upak['PROPERTY_UPAKOVKA_VALUE'] = $itemSKU['PROPERTY_UPAKOVKA_VALUE'];
                                $upak['PROPERTY_UPAKOVKA_ENUM_ID'] = $itemSKU['PROPERTY_UPAKOVKA_ENUM_ID'];
                                $upak['PROPERTY_UPAKOVKA_VALUE_ID'] = $itemSKU['PROPERTY_UPAKOVKA_VALUE_ID'];

                                $arSKU[$itemSKU['PROPERTY_UPAKOVKA_ENUM_ID']] = $upak;
                                if ($pomols[$itemSKU['PROPERTY_UPAKOVKA_ENUM_ID']]) $arSKU[$itemSKU['PROPERTY_UPAKOVKA_ENUM_ID']]['POMOL'] = $pomols[$itemSKU['PROPERTY_UPAKOVKA_ENUM_ID']];
                            }
                        }
                    }

                    $newSortArr = [];
                    foreach ($arSKU as $key => $itemSKU) if ($itemSKU['PROPERTY_UPAKOVKA_VALUE']) $newSortArr[$key] = preg_replace("/[^0-9]/", '', $itemSKU['PROPERTY_UPAKOVKA_VALUE']);
                    if (!empty($newSortArr)) array_multisort($newSortArr, SORT_ASC, $arSKU); //сортируем по граммовкам

                    foreach ($arSKU as $key => $sku) {
                        if ($sku['POMOL']) {
                            $existSelect = array_column($sku['POMOL'], 'SELECTED', 'PROPERTY_POMOL_ENUM_ID');
                            if (!empty($existSelect)) {

                                if (count($existSelect) > 1) {
                                    $existBasketSelect = array_column($sku['POMOL'], 'BASKET_DATE_UPDATE', 'PROPERTY_POMOL_ENUM_ID');
                                    asort($existBasketSelect);

                                    $keySelect = array_key_last($existBasketSelect);
                                    foreach ($existSelect as $keySel => $itemSel) if ($keySelect != $keySel) unset($arSKU[$key]['POMOL'][$keySel]['SELECTED']);
                                } else $keySelect = key($existSelect);

                                $arSKU[$key]['PRICE'] = $sku['POMOL'][$keySelect]['PRICE'];
                                $arSKU[$key]['QUANTITY'] = $sku['POMOL'][$keySelect]['QUANTITY'];
                                $arSKU[$key]['SELECTED'] = $sku['POMOL'][$keySelect]['SKU_ID'];
                                $arSKU[$key]['CATALOG_QUANTITY'] = $sku['POMOL'][$keySelect]['CATALOG_QUANTITY'];
                            } else {
                                foreach ($sku['POMOL'] as $key2 => $pomol) {
                                    if (!empty($pomol['QUANTITY'])) {
                                        $arSKU[$key]['PRICE'] = $pomol['PRICE'];
                                        $arSKU[$key]['QUANTITY'] = $pomol['QUANTITY'];
                                        $arSKU[$key]['SELECTED'] = $pomol['SKU_ID'];
                                        $arSKU[$key]['CATALOG_QUANTITY'] = $pomol['CATALOG_QUANTITY'];
                                        break;
                                    }
                                }
                            }
                            $arJson['PRICE'] = (array_column($sku['POMOL'], 'PRICE', 'SKU_ID'));
                            $arJson['CATALOG_QUANTITY'] = (array_column($sku['POMOL'], 'CATALOG_QUANTITY', 'SKU_ID'));
                            $arSKU[$key]['JSON'] = json_encode($arJson);
                        }
                        if ($arSKU[$key]['POMOL']) ksort($arSKU[$key]['POMOL']);
                        if (!key_exists('PRICE', $arSKU[$key])) $arSKU[$key]['PRICE'] = current($arSKU[$key]['POMOL'])['PRICE'];
                        if (!key_exists('CATALOG_QUANTITY', $arSKU[$key])) $arSKU[$key]['CATALOG_QUANTITY'] = current($arSKU[$key]['POMOL'])['CATALOG_QUANTITY'];
                        $arSKU[$key]['ID'] = 'sku-key-' . $key;//04.07.2024
                    }

                    $card['LIST_SKU'] = $arSKU;
                } else {
                    $card['PRODUCT_ID'] = $item["PRODUCT_ID"];
                    $card['PRICE'] = $item['PRICE'];
                    $card['QUANTITY'] = $item['QUANTITY'];
                }

                $card['DETAIL_DATA'] = $item['DETAIL_DATA'];
                $card['DETAIL_PICTURE_SRC'] = SITE_TEMPLATE_PATH . '/assets/static/img/img-holder2.png';

                $card['ID'] = $itemId;//04.07.2024
                $result['JS_DATA']['CARDS'][$itemId] = $card;
            }
        }

    }


    /**
     * Вспомагательный метод для basketHandler
     * @param $productIDs
     * @param $sections
     * @param $ready
     */
    protected
    function setItemsSections($productIDs, &$sections, &$ready)
    {
        if (!$productIDs) return;
        $skuIDs = [];
        $res = \CIBlockElement::getList([],
            ['ID' => $productIDs, 'IBLOCK_ID' => [CATALOG_IBLOCK_ID, SKU_IBLOCK_ID]], false, false,
            ['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'PROPERTY_CML2_ARTICLE', 'PROPERTY_UPAKOVKA', 'PROPERTY_OBEM', 'PROPERTY_POMOL']
        );

        while ($ob = $res->fetch()) {

            if ($ob['IBLOCK_ID'] == CATALOG_IBLOCK_ID) {
                if (!in_array($ob['IBLOCK_SECTION_ID'], $sections))
                    $sections[] = $ob['IBLOCK_SECTION_ID'];

                array_walk($ready, function (&$n, $key, $ob) {
                    if ($n['data']['PRODUCT_ID'] == $ob['ID'] || $n['data']['PARENT_ID'] == $ob['ID']) {
                        $n['data']['PRODUCT_SECTION_ID'] = $ob['IBLOCK_SECTION_ID'];
                        $n['data']['CML2_ARTICLE'] = $ob['PROPERTY_CML2_ARTICLE_VALUE'];
                        if ($n['data']['PRODUCT_ID'] == $ob['ID']) {
                            if ($ob['PROPERTY_UPAKOVKA_VALUE'])
                                $n['data']['PROPERTIES']['UPAKOVKA'] = $ob['PROPERTY_UPAKOVKA_VALUE'];
                            if ($ob['PROPERTY_OBEM_VALUE'])
                                $n['data']['PROPERTIES']['OBEM'] = $ob['PROPERTY_OBEM_VALUE'];
                            if ($ob['PROPERTY_POMOL_VALUE'])
                                $n['data']['PROPERTIES']['POMOL'] = $ob['PROPERTY_POMOL_VALUE'];
                        }
                    }
                }, $ob);
            } else {
                $skuIDs[] = $ob['ID'];

                array_walk($ready, function (&$n, $key, $ob) {
                    if ($n['data']['PRODUCT_ID'] == $ob['ID']) {
                        if ($ob['PROPERTY_UPAKOVKA_VALUE'])
                            $n['data']['PROPERTIES']['UPAKOVKA'] = $ob['PROPERTY_UPAKOVKA_VALUE'];
                        if ($ob['PROPERTY_OBEM_VALUE'])
                            $n['data']['PROPERTIES']['OBEM'] = $ob['PROPERTY_OBEM_VALUE'];
                        if ($ob['PROPERTY_POMOL_VALUE'])
                            $n['data']['PROPERTIES']['POMOL'] = $ob['PROPERTY_POMOL_VALUE'];
                    }
                }, $ob);
            }
        }

        if ($skuIDs) {
            $productIDs = [];
            foreach ($skuIDs as $sID) {
                $parentItem = \CCatalogSku::GetProductInfo($sID, SKU_IBLOCK_ID);
                if ($parentItem["ID"]) {
                    $productIDs[] = $parentItem["ID"];
                    $map['data'] = ['SKU_ID' => $sID, 'PARENT_ID' => $parentItem["ID"]];

                    array_walk($ready, function (&$n, $key, $map) {
                        if ($n['data']['PRODUCT_ID'] == $map['data']['SKU_ID']) {
                            $n['data']['PARENT_ID'] = $map['data']['PARENT_ID'];
                        }
                    }, $map);

                }
            }
            if ($productIDs) {
                self::setItemsSections($productIDs, $sections, $ready);
            }
        }
    }

    public
    function totalHandler(&$result)
    {
        $result['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE_FORMATED'] = \SaleFormatCurrency(
                $result['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'],
                'RUB',
                true
            ) . ' ₽';

        $result['JS_DATA']['TOTAL']['DELIVERY_PRICE_FORMATED'] = \SaleFormatCurrency(
                $result['JS_DATA']['TOTAL']['DELIVERY_PRICE'],
                'RUB',
                true
            ) . ' ₽';

        $result['JS_DATA']['TOTAL']['DISCOUNT_PRICE_FORMATED'] = \SaleFormatCurrency(
                $result['JS_DATA']['TOTAL']['DISCOUNT_PRICE'],
                'RUB',
                true
            ) . ' ₽';
    }

    /**
     * Данные для регионального блока
     * Тип плательщика, профиль, местоположение
     * @param $result
     */
    public
    function regionBlockHandler(&$result)
    {
        $result['JS_DATA']['REGION_DATA'] = [];
        $result['JS_DATA']['REGION_DATA']['persontype'] = $result['JS_DATA']['PERSON_TYPE'];
        $result['JS_DATA']['REGION_DATA']['profile'] = $result['JS_DATA']['USER_PROFILES'];
        $result['JS_DATA']['REGION_DATA']['showProfileSelect'] = 'Y';
        if ($result['JS_DATA']['REGION_DATA']['persontype']) {
            $activePerson = current(array_filter($result['JS_DATA']['REGION_DATA']['persontype'], function ($var) {
                return $var['CHECKED'] == 'Y';
            }));
        }
        if (!empty($result['JS_DATA']['ORDER_PROP']['properties'])) {
            $result['JS_DATA']['REGION_DATA']['properties']['location'] = current(array_filter($result['JS_DATA']['ORDER_PROP']['properties'], function ($var) {
                return $var['IS_LOCATION'] == 'Y';
            }));
        }
    }

    /**
     * Обработка СД
     * @param $result
     * @param $templateFolder
     */
    public
    function deliveryHandler(&$result)
    {
        /**
         * Убираем неподдерживаемые СД
         */
        $this->prepareDeliveries($result['JS_DATA']['DELIVERY']);

        if (empty($result['JS_DATA']['DELIVERY'])) return;

        $sdekAddressPropertyCode = Option::get('ipol.sdek', 'address');
        $sdekAddressIndex = '';
        $properties = $result['JS_DATA']['ORDER_PROP']["properties"];
        $sdekAddressIndex = key(array_filter($properties, function ($var) use ($sdekAddressPropertyCode) {
            return ($var['CODE'] == $sdekAddressPropertyCode);
        }));

        foreach ($result['JS_DATA']['DELIVERY'] as &$delivery) {
            if ($delivery['CHECKED'] == 'Y') {
                if ($delivery['TYPE'] == 'sdek') {
                    $delivery['SDEK_PVZ_BTN'] = 'Y';
                    if ($sdekAddressIndex != '') {
                        $result['JS_DATA']['ORDER_PROP']["properties"][$sdekAddressIndex]['READONLY'] = 'Y';
                    }
                } else {
                    if ($sdekAddressIndex != '') {
                        $result['JS_DATA']['ORDER_PROP']["properties"][$sdekAddressIndex]['READONLY'] = 'N';
                    }

                }
            }
            if (in_array($delivery['ID'], [OFFICE_PICKUP_DELIVERY, RETAIL_PICKUP_DELIVERY])) {
                if ($delivery['PERIOD_TEXT'])
                    $delivery['PERIOD_TEXT'] .= '<br>' . self::OFFICE_SCHEDULE;
                else
                    $delivery['PERIOD_TEXT'] = self::OFFICE_SCHEDULE;

                $delivery['PRICE_FORMATED'] = '';
                $delivery['SHOW_MAP'] = 'Y';
                if ($delivery['ID'] == OFFICE_PICKUP_DELIVERY) {
                    $delivery['MAP_TARGET'] = '#popup_map';
                } elseif ($delivery['ID'] == RETAIL_PICKUP_DELIVERY) {
                    $delivery['MAP_TARGET'] = '#popup_map-retail';
                }
            }

            if (in_array($delivery['ID'], [RUSSIAN_POST_DELIVERY])) {
                $delivery['SHOW_HTML'] = 'Y';
            }
        }
    }

    /**
     * @param array $orderDeliveries
     */
    protected
    function prepareDeliveries(&$orderDeliveries = [])
    {
        if (empty($orderDeliveries)) return;

        $ID = array_column($orderDeliveries, 'ID');

        $deliveryList = Delivery\Services\Table::getList([
            'filter' => ['ID' => $ID],
            'select' => ['ID', 'CODE', 'CLASS_NAME'],
        ]);

        $deliveries = [];
        while ($arDelivery = $deliveryList->fetch()) {
            $deliveryType = 'simple';
            if ($arDelivery['CODE'] == 'sdek:postamat' || $arDelivery['CODE'] == 'sdek:pickup') $deliveryType = 'sdek';

            if ($deliveryType != '') {
                $arDelivery['TYPE'] = $deliveryType;
                $deliveries[$arDelivery['ID']] = $arDelivery;
            }
        }

        if (!empty($deliveries)) {
            $filtered = [];
            $deliveryChecked = false;
            foreach ($orderDeliveries as $delivery) {
                if (!isset($deliveries[$delivery['ID']])) continue;

                if ($delivery['CHECKED'] == 'Y') $deliveryChecked = true;
                $delivery['TYPE'] = $deliveries[$delivery['ID']]['TYPE'];
                $filtered[] = $delivery;
            }
            /**
             * на всякий случай, если будет отфильтрована ранее выбранная СД
             */
            if (!$deliveryChecked && !empty($filtered)) $filtered[0]['CHECKED'] = 'Y';
            $orderDeliveries = $filtered;
        } else {
            $orderDeliveries = [];
        }
    }


    /**
     * будем заполнять свойства из  информацией пользоватлея, в том случае если выбран новый профиль
     * на стороне скрипта, если USER_DATA == пустая, то не ввыводим чекбока Я контактное лицо
     */

    public
    function userDataHandler(&$result)
    {
        $result['JS_DATA']['USER_DATA'] = [];
        $checked = false;
        foreach ($result['JS_DATA']['USER_PROFILES'] as $USER_PROFILE) {
            if ($USER_PROFILE['CHECKED'] == 'Y') {
                $checked = true;
                break;
            }
        }
        if (!$checked) {
            if (!$result['JS_DATA']["IS_AUTHORIZED"]) return;

            global $USER;
            $res = \Bitrix\Main\UserTable::getList([
                'filter' => ['=ID' => $USER->getId()],
                'select' => ['COMPANY_UF_COMPANY_NAME' => 'NAME', 'COMPANY_UF_COMPANY_LAST_NAME' => 'LAST_NAME', 'COMPANY_UF_COMPANY_SECOND_NAME' => 'SECOND_NAME', 'COMPANY_EMAIL' => 'EMAIL', 'COMPANY_PHONE' => 'PERSONAL_PHONE']]);
            $userData = [];
            while ($ob = $res->fetch()) {
                foreach ($ob as $key => $value) {
                    $userData[] = ['CODE' => $key, 'VALUE' => $value];
                }
            }

            $result['JS_DATA']['USER_DATA'] = $userData;
        }
    }


    /**
     * заполняем свойства, которые не привязаны к профилю
     * @param $result
     */
    public
    function fullPropsNoProfile(&$result)
    {
        global $USER;
        $idPropProfile = 43;
        $valuePropProfile = '';

        foreach ($result['JS_DATA']['ORDER_PROP']['properties'] as &$property) { //сначала заполяем инфу пустыми значениями
            if (in_array($property['ID'], self::RECIPIENT_ORDER_PROPS[B2B_UR_PERSON_TYPE_ID]['PROP_IDS'])) {
                $property['VALUE'][0] = '';
            }
            if ($property['IS_PROFILE_NAME'] == 'Y') {
                $idPropProfile = $property['ID'];
                $valuePropProfile = current($property['VALUE']);
            }
        }

        $checkedType = false;
        foreach ($result['JS_DATA']['PERSON_TYPE'] as $type) {
            if ($type['CHECKED'] == 'Y') $checkedType = $type['ID'];
        }
        if ($checkedType == B2B_UR_PERSON_TYPE_ID) {//если юр лицо
            $checkedProfile = false;
            foreach ($result['JS_DATA']['USER_PROFILES'] as $profile) {
                if ($profile['CHECKED'] == 'Y') $checkedProfile = $profile['ID'];
            }

            if ($checkedProfile && $valuePropProfile) {
                \Bitrix\Main\Loader::includeModule('sale');
                $filter['PERSON_TYPE_ID'] = B2B_UR_PERSON_TYPE_ID;
                $filter["USER_ID"] = $USER->getId();
                $filter['PROPERTY.ORDER_PROPS_ID'] = $idPropProfile;
                $filter['PROPERTY.VALUE'] = $valuePropProfile;

                $dbRes = \Bitrix\Sale\Order::getList([
                    'select' => ['ID', 'PROPERTY'],
                    'filter' => $filter,
                    'order' => ['ID' => 'DESC'],
                    'limit' => 1
                ]);

                while ($order = $dbRes->fetch()) {
                    $orderID = $order['ID'];
                }

                if ($orderID) {
                    $dbResOrder = \Bitrix\Sale\Order::getList([
                        'select' => ['ID', 'PROPERTY'],
                        'filter' => ['ID' => $orderID, 'PROPERTY.ORDER_PROPS_ID' => self::RECIPIENT_ORDER_PROPS[B2B_UR_PERSON_TYPE_ID]['PROP_IDS']],
                    ]);

                    while ($order = $dbResOrder->fetch()) {
                        if (in_array($order['SALE_INTERNALS_ORDER_PROPERTY_ORDER_PROPS_ID'], self::RECIPIENT_ORDER_PROPS[B2B_UR_PERSON_TYPE_ID]['PROP_IDS']))
                            $arPseudoProp[$order['SALE_INTERNALS_ORDER_PROPERTY_ORDER_PROPS_ID']] = $order['SALE_INTERNALS_ORDER_PROPERTY_VALUE'];
                    }
                }
                if ($arPseudoProp) {
                    foreach ($result['JS_DATA']['ORDER_PROP']['properties'] as &$property) {
                        if (!empty($arPseudoProp[$property['ID']])) {
                            $property['VALUE'][0] = $arPseudoProp[$property['ID']];
                        }
                    }
                }

            }
        }
    }

    public
    function getTypePayment(&$result)
    {
        $checkedType = false;
        foreach ($result['JS_DATA']['PERSON_TYPE'] as $type) {
            if ($type['CHECKED'] == 'Y') $checkedType = $type['ID'];
        }

        $result['JS_DATA']['CHECKED_TYPE'] = $checkedType;
    }

    public
    function getUserProfile(&$result)
    {
        $checkedProfile = 0;
        foreach ($result['JS_DATA']['USER_PROFILES'] as $profile) {
            if ($profile['CHECKED'] == 'Y') $checkedProfile = $profile['ID'];
        }
        $result['JS_DATA']['CHECKED_PROFILE'] = $checkedProfile;
    }


    public
    function getUserOrder()
    {
        global $USER;
        return $USER->GetID();
    }

    /**
     * Сортировка и группировка свойств
     * @param $result
     */
    public
    function propertiesHandler(&$result)
    {
        $this->fullPropsNoProfile($result);
        $this->getTypePayment($result);
        $this->getUserProfile($result);


        $isAjaxRequest = $this->request["via_ajax"] == "Y";

        if (empty($result['JS_DATA']['ORDER_PROP']['groups']) || empty($result['JS_DATA']['ORDER_PROP']['properties'])) return;

        usort($result['JS_DATA']['ORDER_PROP']['groups'], function ($a, $b) {
            if ($a['SORT'] == $b['SORT']) {
                return 0;
            }
            return ($a['SORT'] < $b['SORT']) ? -1 : 1;
        });

        foreach ($result['JS_DATA']['ORDER_PROP']['properties'] as &$property) {
            if ($property['MULTIPLE'] != 'Y') $property['VALUE'] = $property['VALUE'][0];
//            if ($property['CODE'] == 'COMPANY') {
//                $companyName = $property['VALUE'];
//                $property['HIDE'] = 'Y';
//                $property['NO_PRINT'] = 'Y';
//            }

            if ($property['IS_ZIP'] == 'Y') {
                $property['HTML_ID'] = 'zipProperty';
            }


            if ($property['CODE'] == 'INN') {
                $checkedProfile = false;
                if ($result['JS_DATA']['REGION_DATA']['profile']) {
                    foreach ($result['JS_DATA']['REGION_DATA']['profile'] as $profile) {
                        if ($profile['CHECKED'] == 'Y') $checkedProfile = true;
                    }
                }
                if (!empty($property['VALUE']) && $checkedProfile) $property['READONLY'] = 'Y';
                else {
                    $property['EVENT'] = 'Y';
                    if (!empty($property['VALUE'])) {
                        global $USER;
                        $arEmail = \Webfly\Helper\Helper::getExistUsersInn($property['VALUE'], $USER->GetID());
                        if (!empty($arEmail)) {
                            $result['JS_DATA']['TOTAL']['EXIST_COMPANY'] = $arEmail['MESSAGE'];
                            $result['JS_DATA']['TOTAL']['BTN_DISABLED'] = 'Y';
                        }
                    }
                }
            }


            if ($property['CODE'] == 'COMPANY_PHONE' || $property['CODE'] == 'COMPANY_EMAIL') {
                if ($result['JS_DATA']['CHECKED_PROFILE'] == 0) {
                    $property['EVENT'] = 'Y';

                    if (!empty($property['VALUE'])) {

                        $arData = \Webfly\Helper\Helper::getExistProfile($property['VALUE'], $property['CODE'], $this->getUserOrder());
                        if (!empty($arData)) {
                            $result['JS_DATA']['TOTAL']['EXIST_PROFILE'][] = $arData['MESSAGE'];
                            $result['JS_DATA']['TOTAL']['BTN_DISABLED'] = 'Y';
                        }
                    }

                }
            }


            if (!$property['DESCRIPTION']) $property['DESCRIPTION'] = ' ';

            if ($property['TYPE'] == 'ENUM') {
                foreach ($property['OPTIONS'] as $optionValue => $optionName) {
                    $vArr = ['VALUE' => $optionValue, 'NAME' => $optionName];
                    $vArr['SELECTED'] = $property['VALUE'] == $optionValue ? 'Y' : 'N';
                    $property['V_OPTIONS'][] = $vArr;
                }
            }

            if ($property['TYPE'] == 'Y/N') {
                $property['CHECKED'] = $property['VALUE'] == 'Y' ? $property['CHECKED'] = 'Y' : $property['CHECKED'] = 'N';
            }

            if ($result['JS_DATA']['CHECKED_TYPE'] == B2B_FIZ_PERSON_TYPE_ID && $result['JS_DATA']['CHECKED_PROFILE'] > 0 && ($property['IS_EMAIL'] == 'Y' || $property['IS_PHONE'] == 'Y')) {
                $property['READONLY'] = 'Y';
            }

        }
        $result['JS_DATA']['CONTACT_PERSON'] = [];
        $result['JS_DATA']['ORDER_PROP']['GROUPED_PROPERTIES'] = [];
        $result['JS_DATA']['ORDER_PROP']['ADDRESS_PROPERTIES'] = [];

        foreach ($result['JS_DATA']['ORDER_PROP']['groups'] as $group) {
            $groupId = $group['ID'];
            $groupProperty = array_values(array_filter($result['JS_DATA']['ORDER_PROP']['properties'], function ($property) use ($groupId) {
                return ($property["PROPS_GROUP_ID"] == $groupId && $property['IS_LOCATION'] != 'Y');
            }));

            if ($groupProperty) {
                $group['SKIP'] = in_array($group['ID'], self::SKIP_PROP_GROUPS) ? 'Y' : 'N';
                $group['SHOW'] = 'Y';
                /**
                 * группы "Личные данные" скрываем
                 * Если в ней есть незаполненные обязательные свойства, то показываем только их
                 */

                if (in_array($group['ID'], self::PERSONAL_GROUP_ID)) {
                    $group['SHOW'] = 'N';

                    $groupPropertyShow = [];
                    $groupPropertyHide = [];

                    $fioProps = ['COMPANY_UF_COMPANY_LAST_NAME', 'COMPANY_UF_COMPANY_NAME', 'COMPANY_UF_COMPANY_SECOND_NAME'];
                    $fio = [];


                    foreach ($groupProperty as $pKey => &$property) {
                        if (in_array($property['CODE'], $fioProps) && $property['VALUE'] && !empty($result['JS_DATA']['USER_DATA'])) {
                            $fio[] = trim($property['VALUE']);
                        }
                        if (in_array($property['ID'], self::PERSONAL_HIDE_PROPS_ID)) {
                            $property['HIDE'] = 'Y';
                            $groupPropertyHide[$pKey] = $property;
                        } else {
                            if (!$property['VALUE']) {
                                if (!$this->isRequestViaAjax) {
                                    $_SESSION['SHOW_ORDER_PROPERTIES'][] = $property['CODE'];
                                }
                                $property['HIDE'] = 'N';
                                $group['SHOW'] = 'Y';
                                $groupPropertyShow[$pKey] = $property;
                            } else {
                                $property['HIDE'] = 'N';
                                $group['SHOW'] = 'Y';
                                $groupPropertyShow[$pKey] = $property;
//                                if (in_array($property['CODE'], $_SESSION['SHOW_ORDER_PROPERTIES'])) {
//                                    $property['HIDE'] = 'N';
//                                    $group['SHOW'] = 'Y';
//                                    $groupPropertyShow[$pKey] = $property;
//                                } else {
//                                    $property['HIDE'] = 'Y';
//                                    $groupPropertyHide[$pKey] = $property;
//                                }
                            }
                        }

                    }

                    unset ($property);
                    unset ($pKey);


//                    if ($fio) {
//                        $fioString = implode(' ', $fio);
//                        foreach ($groupPropertyHide as &$property) {
//                            if ($property['CODE'] == 'FIO' || $property['CODE'] == 'COMPANY_TITLE') {
//                                $property['VALUE'] = $fioString;
//                            }
//                        }
//                    }
                    unset ($property);
                    $groupProperty = array_merge($groupPropertyShow, $groupPropertyHide);
                }


                if ($groupProperty) {

                    $propsByRow = [];
                    $rowIndex = 0;
                    $propIndex = 0;

                    foreach ($groupProperty as $key => &$prop) {
                        if ($prop['CODE'] == 'CONTACT_PERSON')
                            $result['JS_DATA']['CONTACT_PERSON'] = $prop;

                        $propsByRow[$rowIndex]['PROPS'][] = $prop;

                        $propIndex++;
                        if ($prop['ID'] == 43 || $prop['ID'] == 100 || $prop['ID'] == 103) {
                            $propIndex++;
                            $propsByRow[$rowIndex]['PROPS'][] = [];
                        }

                        if (in_array($prop['ID'], self::STREET_PROPS)) {
                            $propIndex++;
                        }
                        if ($propIndex % 2 == 0) {
                            $rowIndex++;
                        }
                    }


                    unset ($prop);

                    foreach ($propsByRow as $rowKey => &$propsOneRow) {
                        $hiddenProps = array_filter($propsOneRow['PROPS'], function ($v) {
                            return $v['HIDE'] == 'Y';
                        });
                        if (count($propsOneRow['PROPS']) == count($hiddenProps)) {
                            $propsOneRow['HIDE'] = 'Y';
                        } else {
                            $propsOneRow['HIDE'] = 'N';
                        }
                        $propsOneRow['ID'] = $rowKey;
                    }
                    if ($group['SKIP'] == 'Y') {
                        $result['JS_DATA']['ORDER_PROP']['ADDRESS_PROPERTIES'][] = array_merge($group, ['properties' => $propsByRow]);
                    }
                    $result['JS_DATA']['ORDER_PROP']['GROUPED_PROPERTIES'][] = array_merge($group, ['properties' => $propsByRow]);
                }
            }
        }

        if (!empty($result['JS_DATA']['TOTAL']['EXIST_PROFILE'])) {
            $result['JS_DATA']['TOTAL']['EXIST_PROFILE'] = implode('<br>', $result['JS_DATA']['TOTAL']['EXIST_PROFILE']);
        }


    }

    public
    function checkUserProfile(&$result)
    {
        global $USER;
        if (isset($result['JS_DATA']['LAST_ORDER_DATA']['FAIL'])
            && !$result['USER_VALS']['PERSON_TYPE_OLD']) {
            $userId = $USER->getId();
            $userPrimaryProfile = \CSaleOrderUserProps::GetList(array('ID' => 'asc'), ['USER_ID' => $userId, 'PERSON_TYPE_ID' => [B2B_UR_PERSON_TYPE_ID, B2B_FIZ_PERSON_TYPE_ID]])->fetch();
            if ($userPrimaryProfile['PERSON_TYPE_ID'] == B2B_FIZ_PERSON_TYPE_ID) {
                $result['JS_DATA']['REFRESH_FIELD_ID'] = "person-type-" . B2B_FIZ_PERSON_TYPE_ID;
            }
        }
    }

    /**
     * Исполнение компонента
     */
    public
    function executeComponent()
    {
        $documentRoot = Bitrix\Main\Application::getDocumentRoot();
        Loc::loadMessages($documentRoot . self::SOA_PATH . "/class.php");
        Loc::loadMessages($documentRoot . self::SOA_PATH . "/templates/.default/template.php");
        $this->isRequestViaAjax = $this->request->isPost() && $this->request->get('via_ajax') == 'Y';
        if (!$this->isRequestViaAjax) {
            $_SESSION['SHOW_ORDER_PROPERTIES'] = [];
        }
        parent::executeComponent();
    }

    /**
     * Обновление количества товара в корзине
     */
    protected
    function changeQuantityAction()
    {
        if ($this->isRequestViaAjax) {

            $change_quantity_item = $this->request->get('change_quantity_item');

            if (!empty($change_quantity_item['productId'])) {
                $registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
                /** @var Sale\Basket $basketClassName */
                $basketClassName = $registry->getBasketClassName();
                $basket = $basketClassName::loadItemsForFUser(Sale\Fuser::getId(), $this->getSiteId());
                $basketItems = $basket->getBasketItems();
                if ($basketItems) {
                    $bProductInBasket = false;
                    foreach ($basketItems as $basketItem) {
                        if ($basketItem->getField('PRODUCT_ID') == $change_quantity_item['productId']) {
                            $bProductInBasket = true;
                            $productByBasketItem = $basketItem;
                            break;
                        }
                    }
                }

                if ($bProductInBasket && $productByBasketItem instanceof Sale\BasketItem) {

                    if ($change_quantity_item['quantity'] > 0)
                        $res = $productByBasketItem->setField('QUANTITY', $change_quantity_item['quantity']);
                    else
                        $res = $productByBasketItem->delete(); // Удаление

                    if ($res->isSuccess()) {
                        $basket->save();
                        $this->refreshOrderAjaxAction();
                    } else {
                        $errorMessages = $res->getErrorMessages();
                        $this->showAjaxAnswer([
                            'error' => reset($errorMessages),
                        ]);
                    }

                } else {
                    $this->showAjaxAnswer([
                        'error' => 'Ошибка обновления количества товара',
                    ]);
                }
            }
        }
    }

    protected
    function currencyFormat($value)
    {
        \Bitrix\Main\Loader::includeModule('currency');
        return CCurrencyLang::CurrencyFormat($value, Bitrix\Currency\CurrencyManager::getBaseCurrency());
    }

    /**
     * Метод дополнен доп проверками на ошибки
     * @param $userId
     * @return Sale\Order
     */
    protected
    function getOrder($userId)
    {
        $order = parent::getOrder($userId);
        if ($this->action == 'saveOrderAjax') {
            if ($this->arUserResult['DELIVERY_ID']) {
                $delivery = Delivery\Services\Table::getList([
                    'filter' => ['ID' => $this->arUserResult['DELIVERY_ID']],
                    'select' => ['ID', 'CODE', 'CLASS_NAME'],
                ])->fetch();
                if (($delivery['CODE'] == 'sdek:postamat' || $delivery['CODE'] == 'sdek:pickup')
                    && \Ipolh\SDEK\option::get('noPVZnoOrder') == 'Y') {
                    $sdekAddrCode = \Ipolh\SDEK\option::get('pvzPicker');
                    if ($sdekAddrCode && $order) {
                        $propertyCollection = $order->getPropertyCollection();
                        $sdekAddrProperty = $propertyCollection->getItemByOrderPropertyCode($sdekAddrCode);
                        if ($sdekAddrProperty) {
                            $sdekAddrValue = $sdekAddrProperty->getValue();
                            if (strpos($sdekAddrValue, '#S') === false)
                                $this->addError('Необходимо выбрать пункт выдачи СДЭК', 'DELIVERY_ERR');
                        }

                    }
                }
            }
        }
        return $order;
    }

    /**
     * Метод компонента переопределен, чтобы можно было
     * использовать мутатор на загрузку страницы
     * и на аякс
     */
    protected
    function prepareResultArray()
    {
        parent::prepareResultArray();
        $result = $this->arResult;
        $this->applyTemplateMutator($result);
        $this->arResult = $result;
    }

    /**
     * Подключение мутатора шаблона
     * @param $result
     */
    protected
    function applyTemplateMutator(&$result)
    {
        if ($this->initComponentTemplate()) {
            $template = $this->getTemplate();
            $templateFolder = $template->GetFolder();

            if (!empty($templateFolder)) {
                $file = new Main\IO\File(Main\Application::getDocumentRoot() . $templateFolder . '/mutator.php');

                if ($file->isExists()) {
                    include($file->getPath());
                }
            }
        }
    }
}
