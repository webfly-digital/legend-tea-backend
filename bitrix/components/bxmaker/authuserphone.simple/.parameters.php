<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
use Bitrix\Main\Localization\Loc as Loc;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
$arComponentParameters = array("GROUPS" => array(), "PARAMETERS" => array());
$arComponentParameters['PARAMETERS']['RELOAD_AFTER_AUTH'] = array("PARENT" => "BASE", "NAME" => \Bitrix\Main\Localization\Loc::getMessage("BXMAKER.AUTHUSERPHONE.COMPONENT.SIMPLE.PARAMETERS.RELOAD_AFTER_AUTH"), "TYPE" => "CHECKBOX", "ADDITIONAL_VALUES" => "N", "REFRESH" => "N", "DEFAULT" => "Y");