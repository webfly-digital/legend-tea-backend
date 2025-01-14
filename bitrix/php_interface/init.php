<?
define('TOOLS_FOLDER', $_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/tools");
//include_once("handlers.php");

include_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php");//подключаем модуль для отправки писем через smtp
if (file_exists(TOOLS_FOLDER . "/config.php"))
    include_once(TOOLS_FOLDER . "/config.php");

if (SITE_ID == 's1') {
    include_once $_SERVER['DOCUMENT_ROOT'] . "/bitrix/php_interface/tools/s1-redirect.php";
}

AddEventHandler("iblock", "OnAfterIBlockElementAdd", array("aspro_import", "FillTheBrands"));
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", array("aspro_import", "FillTheBrands"));

//todo: обработчик для изменения свойства товара и SKU "Разрешить покупку при отсутствии товара"
\Bitrix\Main\EventManager::getInstance()->addEventHandler("catalog", "\Bitrix\Catalog\Product::OnBeforeAdd", "SetParamsProduct");
\Bitrix\Main\EventManager::getInstance()->addEventHandler("catalog", "\Bitrix\Catalog\Product::OnBeforeUpdate", "SetParamsProduct");

class aspro_import
{
    public static function FillTheBrands($arFields)
    {
        $arCatalogID = array(17);
        if (in_array($arFields['IBLOCK_ID'], $arCatalogID)) {
            $arItem = CIBlockElement::GetList(false, array('IBLOCK_ID' => 17, 'ID' => $arFields['ID']), false, false, array('ID', 'PROPERTY_CML2_MANUFACTURER'))->fetch();
            if ($arItem['PROPERTY_CML2_MANUFACTURER_VALUE']) {
                $arBrand = CIBlockElement::GetList(false, array('IBLOCK_ID' => 12, 'NAME' => $arItem['PROPERTY_CML2_MANUFACTURER_VALUE']))->fetch();
                if ($arBrand) {
                    CIBlockElement::SetPropertyValuesEx($arFields['ID'], false, array('BRAND' => $arBrand['ID']));
                } else {
                    $el = new CIBlockElement;
                    $arParams = array("replace_space" => "-", "replace_other" => "-");
                    $id = $el->Add(array(
                        'ACTIVE' => 'Y',
                        'NAME' => $arItem['PROPERTY_CML2_MANUFACTURER_VALUE'],
                        'IBLOCK_ID' => 12,
                        'CODE' => Cutil::translit($arItem['PROPERTY_CML2_MANUFACTURER_VALUE'], "ru", $arParams)
                    ));
                    if ($id) {
                        CIBlockElement::SetPropertyValuesEx($arFields['ID'], false, array('BRAND' => $id));
                    } else {
                        echo $el->LAST_ERROR;
                    }
                }
            }
        }
    }
}

const ID_SECTION_COFFE = [1444, 1433, 1438, 1381, 1436];
const IBLOCK_SKU = 94;
const IBLOCK_CATALOG = 93;

function SetParamsProduct(\Bitrix\Main\Event $event)
{
    $productFields = $event->getParameter("fields");
    $productId = $event->getParameter("id");
    $getIdProductBySKU = CIBlockElement::GetList([], ['IBLOCK_ID' => IBLOCK_SKU, 'ID' => $productId['ID']], false, false, ['ID', "CAN_BUY_ZERO", "NAME", 'PROPERTY_CML2_LINK']);
    while ($arIdProduct = $getIdProductBySKU->Fetch()) {
        $getIdProduct = CIBlockElement::getList([], ['IBLOCK_ID' => IBLOCK_CATALOG, 'ID' => $arIdProduct["PROPERTY_CML2_LINK_VALUE"], 'SECTION_ID' => ID_SECTION_COFFE], false, false, ['ID', "CAN_BUY_ZERO", "NAME", 'IBLOCK_SECTION_ID'])->fetch();
    }
    if (empty($getIdProduct)) {
        $getIdProduct = CIBlockElement::getList([], ['IBLOCK_ID' => IBLOCK_CATALOG, 'ID' => $productId['ID'], 'SECTION_ID' => ID_SECTION_COFFE], false, false, ['ID', "CAN_BUY_ZERO", "NAME", 'IBLOCK_SECTION_ID'])->fetch();
    }
    if (empty($getIdProduct)) {
        return;
    }
//изменяем значение свойства товара
    $productFields['CAN_BUY_ZERO'] = 'Y';
    //модификация данных
    $result = new \Bitrix\Main\Entity\EventResult();
    $result->modifyFields($productFields);
    return $result;
}

class CustomOffers
{
    public static function GetSKUPropsArray(&$arSkuProps, $iblock_id = 0, $arItem = array())
    {
        if ($iblock_id) {
            $arPropsSku = \CIBlockSectionPropertyLink::GetArray($iblock_id);
            if ($arPropsSku) {
                foreach ($arSkuProps as $key => $arProp) {
                    if ($arPropsSku[$arProp["ID"]]) {
                        $arSkuProps[$key]["DISPLAY_TYPE"] = $arPropsSku[$arProp["ID"]]["DISPLAY_TYPE"];
                    }
                }
            }
        }
        $arCurrentOffer = $arItem['OFFERS'][$arItem['OFFERS_SELECTED']];
        $j = 0;
        $arFilter = $arShowValues = array();

        /*get correct values*/
        $skuPropsCount = 0;
        foreach ($arSkuProps as $key => &$arProp) {
            $skuPropsCount++;
            $strName = 'PROP_' . $arProp['ID'];

            $arShowValues = self::GetRowValues($arFilter, $strName, $arItem);

            if (is_array($arShowValues)) {
                if (in_array($arCurrentOffer['TREE'][$strName], $arShowValues)) {
                    $arFilter[$strName] = $arCurrentOffer['TREE'][$strName];
                } else {
                    $arFilter[$strName] = $arShowValues[0];
                }

                $arCanBuyValues = $tmpFilter = array();
                $tmpFilter = $arFilter;

                //$arShowValues - массив ID значений свойств
                //$strName - ID свойства с префиксом PROP_
                foreach ($arShowValues as $value) {
                    $tmpFilter[$strName] = $value;
                    if (self::GetCanBuy($tmpFilter, $arItem)) {
                        $arCanBuyValues[] = $value;
                    }
                    if ($skuPropsCount == count($arSkuProps))
                        self::getPrice($tmpFilter, $arItem, $arProp);
                }
                $arSkuProps[$key] = self::UpdateRow($arFilter[$strName], $arShowValues, $arCanBuyValues, $arProp);
            }

        }
    }

    protected static function GetRowValues($arFilter, $index, $arItem)
    {

        $i = 0;
        $arValues = array();
        $boolSearch = false;
        $boolOneSearch = true;

        if (!$arFilter) {
            if ($arItem['OFFERS']) {
                foreach ($arItem['OFFERS'] as $arOffer) {
                    if (!in_array($arOffer['TREE'][$index], $arValues)) {
                        $arValues[] = $arOffer['TREE'][$index];
                    }
                }
            }
            $boolSearch = true;
        } else {
            if ($arItem['OFFERS']) {
                foreach ($arItem['OFFERS'] as $arOffer) {
                    $boolOneSearch = true;
                    foreach ($arFilter as $propName => $filter) {
                        if ($filter !== $arOffer['TREE'][$propName]) {
                            $boolOneSearch = false;
                            break;
                        }
                    }
                    if ($boolOneSearch) {
                        if (!in_array($arOffer['TREE'][$index], $arValues)) {
                            $arValues[] = $arOffer['TREE'][$index];
                        }
                        $boolSearch = true;
                    }
                }
            }
        }

        return ($boolSearch ? $arValues : false);
    }

    protected static function GetCanBuy($arFilter, $arItem)
    {

        $i = 0;
        $boolSearch = false;
        $boolOneSearch = true;

        foreach ($arItem['OFFERS'] as $arOffer) {
            $boolOneSearch = true;
            foreach ($arFilter as $propName => $filter) {
                if ($filter !== $arOffer['TREE'][$propName]) {
                    $boolOneSearch = false;
                    break;
                }
            }
            if ($boolOneSearch) {
                if ($arOffer['CAN_BUY']) {
                    $boolSearch = true;
                    break;
                }
            }
        }
        return $boolSearch;
    }

    protected static function getPrice($arFilter, $arItem, &$property)
    {
        $check = false;
        foreach ($arItem['OFFERS'] as $arOffer) {
            foreach ($arFilter as $propName => $filter) {
                if ($filter == $arOffer['TREE'][$propName]) {
                    $check = true;
                } else {
                    $check = false;
                    break;
                }
            }
            if ($check) {
                $curValue = array_filter($arFilter, function ($v, $k) use ($property) {
                    return 'PROP_' . $property['ID'] == $k;
                }, ARRAY_FILTER_USE_BOTH);
                if (!empty($curValue)) {
                    foreach ($property['VALUES'] as &$value) {
                        if ($value['ID'] == current($curValue)) {
                            $value['PRICE'] = str_replace(' руб.', 'P', $arOffer['MIN_PRICE']['PRINT_DISCOUNT_VALUE']);
                        }
                    }
                }
                break;
            }
        }
    }

    protected static function UpdateRow($arFilter, $arShowValues, $arCanBuyValues, $arProp)
    {
        $isCurrent = false;
        $showI = 0;

        if ($arProp['VALUES']) {
            foreach ($arProp['VALUES'] as $key => $arValue) {
                $value = $arValue['ID'];
                // $isCurrent = ($value === $arFilter && $value != 0);
                $isCurrent = ($value === $arFilter);

                $selectMode = (($arProp["DISPLAY_TYPE"] == "P" || $arProp["DISPLAY_TYPE"] == "R"));

                if (in_array($value, $arCanBuyValues)) {
                    $arProp['VALUES'][$key]['CLASS'] = ($isCurrent ? 'active' : '');
                } else {
                    $arProp['VALUES'][$key]['CLASS'] = ($isCurrent ? 'active missing' : 'missing');
                }
                if ($selectMode) {
                    $arProp['VALUES'][$key]['DISABLED'] = 'disabled';
                    $arProp['VALUES'][$key]['SELECTED'] = ($isCurrent ? 'selected' : '');
                } else {
                    $arProp['VALUES'][$key]['STYLE'] = 'style="display: none"';
                }

                if (in_array($value, $arShowValues)) {
                    if ($selectMode) {
                        $arProp['VALUES'][$key]['DISABLED'] = '';
                    } else {
                        $arProp['VALUES'][$key]['STYLE'] = '';
                    }

                    if ($value != 0)
                        ++$showI;

                    if ($value['PRICE']) {
                        $arProp['VALUES'][$key]['PRICE'] = $value['PRICE'];
                    }
                }
            }
            if (!$showI)
                $arProp['STYLE'] = 'style="display: none"';
            else
                $arProp['STYLE'] = 'style=""';
        }

        return $arProp;
    }
}

function dump($arr, $var_dump = false)
{
    global $USER;
    if ($USER->GetId() == "1") {
        echo "<pre style='background: #222;color: #54ff00;padding: 20px;'>";
        if ($var_dump) {
            var_dump($arr);
        } else {
            print_r($arr);
        }
        echo "</pre>";
    }
}


