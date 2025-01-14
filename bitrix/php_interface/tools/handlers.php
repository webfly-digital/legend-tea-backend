<?php

use \Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();
//Модуль "Веб-формы"
$eventManager->addEventHandler('form', 'onAfterResultAdd', array("\Webfly\Handlers\Form", "addCrmLead"));//добавление лидов на портал после заполения форм
//search
$eventManager->addEventHandler('search', 'BeforeIndex', array("\Webfly\Handlers\Search", "addSectionToItem"));
//main
$eventManager->addEventHandler('main', 'OnBeforeUserAdd', ['\Webfly\Handlers\Main', 'OnBeforeUserAddUpdateHandler']);
$eventManager->addEventHandler('main', 'OnAfterUserAdd', ['\Webfly\Handlers\Main', 'OnAfterUserAddHandler']);
$eventManager->addEventHandler('main', 'OnBeforeUserUpdate', ['\Webfly\Handlers\Main', 'OnBeforeUserUpdateHandler']);
$eventManager->addEventHandler('main', 'OnAfterUserUpdate', ['\Webfly\Handlers\Main', 'OnAfterUserUpdateHandler']);
$eventManager->addEventHandler('main', 'OnAfterUserAuthorize', ['\Webfly\Handlers\Main', 'OnAfterUserAuthorizeHandler']);
$eventManager->addEventHandler('main', 'OnEpilog', ['\Webfly\Handlers\Main', 'OnEpilogHandler']);
//catalog
$eventManager->addEventHandler('catalog', 'OnSuccessCatalogImport1C', ['\Webfly\Handlers\Catalog', 'OnSuccessCatalogImport1CHandler']);
$eventManager->addEventHandler('catalog', 'OnGetOptimalPrice', ["\Webfly\Handlers\Catalog", "SetCatalogGroupId"]);
//sale
$eventManager->addEventHandler('sale', 'OnSaleOrderBeforeSaved', ["\Webfly\Handlers\Sale", "OnSaleOrderBeforeSaved"]);
$eventManager->addEventHandler('sale', 'OnOrderNewSendEmail', ["\Webfly\Handlers\Sale", "OnOrderNewSendEmailHandler"]);
//crm
$eventManager->addEventHandler('crm', 'OnBeforeCrmDealAdd', ["\Webfly\Handlers\CRM", "OnBeforeCrmDealAddHandler"]);
$eventManager->addEventHandler('crm', 'OnAfterCrmDealAdd', ["\Webfly\Handlers\CRM", "OnAfterCrmDealAddHandler"]);
$eventManager->addEventHandler('crm', 'OnAfterCrmDealUpdate', ["\Webfly\Handlers\CRM", "OnAfterCrmDealUpdateHandler"]);
$eventManager->addEventHandler('crm', 'OnAfterCrmContactAdd', ["\Webfly\Handlers\CRM", "OnAfterCrmContactAddHandler"]);
//$eventManager->addEventHandler('crm', 'OnBeforeCrmContactUpdate', ["\Webfly\Handlers\CRM", "OnBeforeCrmContactUpdateHandler"]);
//iblock
$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementAdd', ["\Webfly\Handlers\Iblock", "setArtNumberAsCode"]);
$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementUpdate', ["\Webfly\Handlers\Iblock", "setArtNumberAsCode"]);
$eventManager->addEventHandler('iblock', 'OnAfterIBlockElementAdd', ["\Webfly\Handlers\Iblock", "OnAfterIBlockElementAddHandler"]);
$eventManager->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', ["\Webfly\Handlers\Iblock", "OnAfterIBlockElementUpdateHandler"]);
//Кастомное свойство Привязки к ORM-сущностям
$eventManager->addEventHandler("iblock", "OnIBlockPropertyBuildList", ['\Gricuk\Iblock\EnumProperty\Base', "GetUserTypeDescription"]);
$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementAdd', ["\Webfly\Handlers\Iblock", "setDeliveryName"]);
$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementUpdate', ["\Webfly\Handlers\Iblock", "setDeliveryName"]);

$eventManager->addEventHandler('main', 'OnProlog', ["\Webfly\Handlers\Main", "OnPrologHandler"]);

