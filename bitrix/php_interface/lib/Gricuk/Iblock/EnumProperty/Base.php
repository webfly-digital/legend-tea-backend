<?php

namespace Gricuk\Iblock\EnumProperty;


use Bitrix\Main\Loader;

class Base extends \Bitrix\Main\UserField\TypeBase
{
    const USER_TYPE_ID = "customList";
    protected $property = NULL;

    /** Интерфейсный метод, который описывает всю работу со свойством
     * @return array
     */
    public static function GetUserTypeDescription()
    {
        $params = array(
            "USER_TYPE" => static::USER_TYPE_ID,
            "USER_TYPE_ID" => static::USER_TYPE_ID,
            "CLASS_NAME" => __CLASS__,
            "DESCRIPTION" => "Кастомный список",
            "BASE_TYPE" => \CUserTypeManager::BASE_TYPE_STRING,
            "GetPropertyFieldHtml" => [__CLASS__, 'GetEditFormHTML'],
            "GetEditFormHTML" => [__CLASS__, "GetEditFormHTML"],
            "PrepareSettings" => [__CLASS__, 'PrepareSettings'],
            "GetSettingsHTML" => [__CLASS__, 'GetSettingsHTML']
        );

        return $params;
    }

    public function __construct($property)
    {
        $this->property = $property;
    }

    /** Интерфейсный метод, который описывает тип поля в БД
     * @param $arUserField
     * @return string
     */
    public static function GetDBColumnType($arUserField)
    {
        global $DB;
        switch (strtolower($DB->type)) {
            case "mysql":
                return "text";
            case "oracle":
                return "varchar2(2000 char)";
            case "mssql":
                return "varchar(2000)";
        }
        return "text";
    }

    /**Подгатавливает массив настроект для записи в БД
     * @param $arUserField
     * @return array
     */
public static function PrepareSettings($arUserField)
    {
        $module = trim($arUserField["USER_TYPE_SETTINGS"]["MODULE"]);
        $ormFieldCodeId = trim($arUserField["USER_TYPE_SETTINGS"]["ORM_FIELD_CODE_ID"]);
        $ormFieldCodeName = trim($arUserField["USER_TYPE_SETTINGS"]["ORM_FIELD_CODE_NAME"]);
        $ormClass = trim($arUserField["USER_TYPE_SETTINGS"]["ORM_CLASS"]);
        $height = intval($arUserField["USER_TYPE_SETTINGS"]["LIST_HEIGHT"]);
        $disp = $arUserField["USER_TYPE_SETTINGS"]["DISPLAY"];
        $caption_no_value = trim($arUserField["USER_TYPE_SETTINGS"]["CAPTION_NO_VALUE"]);
        $show_no_value = $arUserField["USER_TYPE_SETTINGS"]["SHOW_NO_VALUE"] === 'N' ? 'N' : 'Y';

        if ($disp !== "CHECKBOX" && $disp !== "LIST" && $disp !== 'UI') {
            $disp = "LIST";
        }

        return array(
            "MODULE" => $module,
            "ORM_CLASS" => $ormClass,
            "ORM_FIELD_CODE_ID" => $ormFieldCodeId,
            "ORM_FIELD_CODE_NAME" => $ormFieldCodeName,
            "DISPLAY" => $disp,
            "LIST_HEIGHT" => ($height < 1 ? 1 : $height),
            "CAPTION_NO_VALUE" => $caption_no_value, // no default value - only in output
            "SHOW_NO_VALUE" => $show_no_value, // no default value - only in output
        );
    }

    /** Интерфейсный метод, который описывает настройки поля в настройках свойства
     * @param bool $arProperty
     * @param $strHTMLControlName
     * @param $arPropertyFields
     * @return string
     */
    public static function GetSettingsHTML($arProperty = false, $strHTMLControlName, &$arPropertyFields)
    {
        $result = '';
        $userProperties = $arProperty["USER_TYPE_SETTINGS"];

        $value = $userProperties["MODULE"];
        $result .= '
		<tr>
			<td class="adm-detail-valign-top">' . 'Модуль' . ':</td>
			<td>
				<input type="text" name="' . $strHTMLControlName["NAME"] . '[MODULE]" value="' . $value . '">
			</td>
		</tr>
		';

        $value = $userProperties["ORM_CLASS"];
        $result .= '
		<tr>
			<td class="adm-detail-valign-top">' . 'ORM класс' . ':</td>
			<td>
				<input type="text" name="' . $strHTMLControlName["NAME"] . '[ORM_CLASS]" value="' . $value . '">
			</td>
		</tr>
		';

        if ($userProperties["ORM_FIELD_CODE_ID"]) {
            $value = trim($userProperties["ORM_FIELD_CODE_ID"]);
        } else {
            $value = '';
        }

        $result .= '
		<tr>
			<td>' . "Код поля для ID" . ':</td>
			<td>
				<input type="text" name="' . $strHTMLControlName["NAME"] . '[ORM_FIELD_CODE_ID]" size="10" value="' . htmlspecialcharsbx($value) . '">
			</td>
		</tr>
		';

        if ($userProperties["ORM_FIELD_CODE_NAME"]) {
            $value = trim($userProperties["ORM_FIELD_CODE_NAME"]);
        } else {
            $value = '';
        }

        $result .= '
		<tr>
			<td>' . "Код поля для отображения" . ':</td>
			<td>
				<input type="text" name="' . $strHTMLControlName["NAME"] . '[ORM_FIELD_CODE_NAME]" size="10" value="' . htmlspecialcharsbx($value) . '">
			</td>
		</tr>
		';

        if ($userProperties["DISPLAY"]) {
            $value = $userProperties["DISPLAY"];
        } else {
            $value = "LIST";
        }

        $result .= '
		<tr>
			<td class="adm-detail-valign-top">' . "Способ отображения" . ':</td>
			<td>
				<label><input type="radio" name="' . $strHTMLControlName["NAME"] . '[DISPLAY]" value="LIST" ' . ("LIST" == $value ? 'checked="checked"' : '') . '>' . "Список" . '</label><br>
				<label><input type="radio" name="' . $strHTMLControlName["NAME"] . '[DISPLAY]" value="CHECKBOX" ' . ("CHECKBOX" == $value ? 'checked="checked"' : '') . '>' . "Чекбоксы" . '</label><br>
				<label><input type="radio" disabled name="' . $strHTMLControlName["NAME"] . '[DISPLAY]" value="UI" ' . ("UI" == $value ? 'checked="checked"' : '') . '>' . "UI" . '</label><br>
			</td>
		</tr>
		';


        if ($userProperties["LIST_HEIGHT"]) {
            $value = intval($userProperties["LIST_HEIGHT"]);
        } else {
            $value = 5;
        }

        $result .= '
		<tr>
			<td>' . 'Высота списка' . ':</td>
			<td>
				<input type="text" name="' . $strHTMLControlName["NAME"] . '[LIST_HEIGHT]" size="10" value="' . $value . '">
			</td>
		</tr>
		';

        if ($userProperties["CAPTION_NO_VALUE"]) {
            $value = trim($userProperties["CAPTION_NO_VALUE"]);
        } else {
            $value = '';
        }

        $result .= '
		<tr>
			<td>' . "Подпись при отсутствии значения" . ':</td>
			<td>
				<input type="text" name="' . $strHTMLControlName["NAME"] . '[CAPTION_NO_VALUE]" size="10" value="' . htmlspecialcharsbx($value) . '">
			</td>
		</tr>
		';

        if ($userProperties["CAPTION_NO_VALUE"]) {
            $value = trim($userProperties["CAPTION_NO_VALUE"]);
        } else {
            $value = '';
        }


        if ($userProperties["SHOW_NO_VALUE"]) {
            $value = trim($userProperties["SHOW_NO_VALUE"]);
        } else {
            $value = '';
        }


        $result .= '
		<tr>
			<td>' . "Показывать пустое значение для обязательного поля" . ':</td>
			<td>
				<input type="hidden" name="' . $strHTMLControlName["NAME"] . '[SHOW_NO_VALUE]" value="N" />
				<label><input type="checkbox" name="' . $strHTMLControlName["NAME"] . '[SHOW_NO_VALUE]" value="Y" ' . ($value === 'N' ? '' : ' checked="checked"') . ' /> ' . GetMessage('MAIN_YES') . '</label>
			</td>
		</tr>
		';

        return $result;
    }

    /**интерфейсный метод для вывода HTML на странице редактирования элемента
     * @param $arProperty
     * @param $propertyValue
     * @param $propertyFormCfg
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function GetEditFormHTML($arProperty, $propertyValue, $propertyFormCfg)
    {
        $enum = static::getEnumList($arProperty);

        if (empty($enum))
            return '';

        $propertyFactory = new Factory();
        $property = $propertyFactory->getProperty($arProperty);
        $result = $property->getEditHTML($enum, $propertyValue, $propertyFormCfg);
        return $result;
    }

    /** интерфейсный метод для вывода HTML в списке
     * @param $arUserField
     * @param $arHtmlControl
     * @return string
     */
    public static function GetFilterHTML($arUserField, $arHtmlControl)
    {
        if (!is_array($arHtmlControl["VALUE"]))
            $arHtmlControl["VALUE"] = array();

        $enum = static::getEnumList($arUserField);
        if (empty($enum))
            return '';

        if ($arUserField["SETTINGS"]["LIST_HEIGHT"] < 5)
            $size = ' size="5"';
        else
            $size = ' size="' . $arUserField["SETTINGS"]["LIST_HEIGHT"] . '"';

        $result = '<select multiple name="' . $arHtmlControl["NAME"] . '[]"' . $size . '>';
        $result .= '<option value=""' . (!$arHtmlControl["VALUE"] ? ' selected' : '') . '>' . GetMessage("MAIN_ALL") . '</option>';
        foreach ($enum as $idEnum => $enumName) {
            $result .= '<option value="' . $idEnum . '"' . (in_array($idEnum, $arHtmlControl["VALUE"]) ? ' selected' : '') . '>' . $enumName . '</option>';
        }
        $result .= '</select>';
        return $result;
    }

    /**
     * @param $arUserField
     * @param $arHtmlControl
     * @return array
     */
public static function GetFilterData($arUserField, $arHtmlControl)
    {
        $items = static::getEnumList($arUserField);
        return array(
            "id" => $arHtmlControl["ID"],
            "name" => $arHtmlControl["NAME"],
            "type" => "list",
            "items" => $items,
            "params" => array("multiple" => "Y"),
            "filterable" => ""
        );
    }

    /** Вовзращает  массив из таблицы описанный в ORM_CLASS
     * @param $arUserField
     * @param array $arParams
     * @return array
     */
    protected static function getEnumList(&$arUserField, $arParams = array())
    {
        $enum = array();
        $showNoValue = $arUserField["MANDATORY"] != "Y"
            || $arUserField['SETTINGS']['SHOW_NO_VALUE'] != 'N'
            || (isset($arParams["SHOW_NO_VALUE"]) && $arParams["SHOW_NO_VALUE"] == true);

        if ($showNoValue
            && ($arUserField["SETTINGS"]["DISPLAY"] != "CHECKBOX" || $arUserField["MULTIPLE"] <> "Y")
        ) {
            $enum = array(null => htmlspecialcharsbx(static::getEmptyCaption($arUserField)));
        }

        $ormFieldCodeId = $arUserField["USER_TYPE_SETTINGS"]["ORM_FIELD_CODE_ID"];
        $ormFieldCodeName = $arUserField["USER_TYPE_SETTINGS"]["ORM_FIELD_CODE_NAME"];

        try {
            Loader::includeModule($arUserField["USER_TYPE_SETTINGS"]["MODULE"]);
        } catch (\Exception $e) {
            return [];
        }

        /** @var \Bitrix\Main\ORM\Query\Result $rsEnum */
        $rsEnum = call_user_func_array(
            array($arUserField["USER_TYPE_SETTINGS"]["ORM_CLASS"], "getList"),
            array()
        );

        while ($arEnum = $rsEnum->fetch()) {
            $enum[$arEnum[$ormFieldCodeId]] = $arEnum[$ormFieldCodeName];
        }


        $arUserField["FIELDS"] = $enum;

        return $enum;
    }

    /** Возвращает то что надо выводить в случае отсутвия значения
     * @param $arUserField
     * @return mixed|string
     */
    protected static function getEmptyCaption($arUserField)
    {
        return $arUserField["SETTINGS"]["CAPTION_NO_VALUE"] <> ''
            ? $arUserField["SETTINGS"]["CAPTION_NO_VALUE"]
            : "не задано";
    }

    /** Возвращает HTML для вывода свойства в админке
     * @param $propertyValue
     * @param $propertyFormCfg
     */
    protected function getEditHTML($enum, $propertyValue, $propertyFormCfg)
    {

    }
}
