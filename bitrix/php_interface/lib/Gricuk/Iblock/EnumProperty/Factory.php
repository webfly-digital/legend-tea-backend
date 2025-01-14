<?php

namespace Gricuk\Iblock\EnumProperty;


class Factory
{
    public function __construct()
    {
    }

    public function getProperty($property)
    {
        switch ($property["USER_TYPE_SETTINGS"]["DISPLAY"]) {
            case "CHECKBOX":
                return new Checkbox($property);
            case "LIST":
            default:
                return new Select($property);
        }
    }
}
