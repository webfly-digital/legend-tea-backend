<?php require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");


$ex = new \Webfly\Generate\GeneratePhotoProduct();
echo json_encode($ex->saveImageInProduct($_REQUEST, $_FILES));
