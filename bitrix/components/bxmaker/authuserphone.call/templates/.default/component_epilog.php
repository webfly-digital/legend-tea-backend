<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
if ($arParams['PHONE_MASK_PARAMS'] && $arParams['PHONE_MASK_PARAMS']['type'] === 'bitrix') {
    \CJSCore::Init(['phone_number']);
}