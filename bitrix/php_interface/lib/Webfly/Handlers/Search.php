<?php

namespace Webfly\Handlers;

use Bitrix\Main\Loader;

Loader::includeModule('iblock');

class Search
{
    /**
     * Добавляет к элементам параметр - ID раздела
     * Для поиска товаров по конкретному разделу
     * @param $arFields
     * @return mixed
     */
    public static function addSectionToItem($arFields)
    {
        if ($arFields["MODULE_ID"] == "iblock" && $arFields["PARAM2"] == CATALOG_IBLOCK_ID && substr($arFields["ITEM_ID"], 0, 1) != "S") {
            $arFields["PARAMS"]["iblock_section"] = array();
            $rsSections = \CIBlockElement::GetElementGroups($arFields["ITEM_ID"], true);
            while ($arSection = $rsSections->Fetch()) {
                $nav = \CIBlockSection::GetNavChain(CATALOG_IBLOCK_ID, $arSection["ID"]);
                while ($ar = $nav->Fetch()) {
                    if($ar['DEPTH_LEVEL'] == 1)
                        $arFields["PARAMS"]["iblock_section"][] = $ar['ID'];
                }
            }
        }
        return $arFields;
    }
}
