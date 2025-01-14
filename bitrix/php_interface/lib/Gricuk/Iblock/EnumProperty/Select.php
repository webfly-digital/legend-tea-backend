<?php

namespace Gricuk\Iblock\EnumProperty;


class Select extends Base
{
    public function getEditHTML($enum, $propertyValue, $propertyFormCfg)
    {
        $arProperty = $this->property;
        $bWasSelect = false;
        $result2 = '';
        foreach ($enum as $idEnum => $valEnum) {
            $bSelected = $propertyValue["VALUE"] == $idEnum;
            $bWasSelect = $bWasSelect || $bSelected;
            $result2 .= '<option value="' . $idEnum . '"' . ($bSelected ? ' selected' : '') . '>' . $valEnum . '</option>';
        }

        if ($arProperty["SETTINGS"]["LIST_HEIGHT"] > 1) {
            $size = ' size="' . $arProperty["SETTINGS"]["LIST_HEIGHT"] . '"';
        } else {
            $propertyValue["VALIGN"] = "middle";
            $size = '';
        }

        $result = '<select name="' . $propertyFormCfg["VALUE"] . '"' . $size . '>';

        $result .= $result2;
        $result .= '</select>';

        return $result;
    }
}
