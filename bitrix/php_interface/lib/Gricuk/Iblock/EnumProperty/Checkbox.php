<?php

namespace Gricuk\Iblock\EnumProperty;


class Checkbox extends Base
{
    public function getEditHTML($enum, $propertyValue, $propertyFormCfg)
    {
        $arProperty = $this->property;

        $bWasSelect = false;
        $result2 = '';
        foreach ($enum as $idEnum => $valEnum) {
            $bSelected = $propertyValue["VALUE"] == $idEnum;
            $bWasSelect = $bWasSelect || $bSelected;
            $result2 .= '<label><input type="radio" value="' . $idEnum . '" name="' . $propertyFormCfg["VALUE"] . '"' . ($bSelected ? ' checked' : '') . '>' . $valEnum . '</label><br>';
        }

        $result = $result2;
        return $result;
    }
}
