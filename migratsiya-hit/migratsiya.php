<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Заполнение");
\Bitrix\Main\Loader::includeModule('iblock');
$obUserField  = new CUserTypeEntity;
$obEnum = new CUserFieldEnum; 
$ibp = new CIBlockProperty;
$ibpenum = new CIBlockPropertyEnum;
$ib = new CIBlock;
$IBLOCK_ID_ASPRO = $_POST['aspro'];
$IBLOCK_ID_NEW = $_POST['client'];

$arrIblockId = array($IBLOCK_ID_ASPRO, $IBLOCK_ID_NEW);
foreach($arrIblockId as $iblockId){
	$res = CIBlock::GetProperties($iblockId, Array(), Array());
	while($res_arr = $res->Fetch()){
		$arrProperty[$res_arr["CODE"]] = $res_arr;
		$arrCode[$iblockId][$res_arr["ID"]] = $res_arr["CODE"];
	}
}

foreach($arrCode as $key => $value){
	if($key == $IBLOCK_ID_NEW ){
		$arrCodeNew = $value;
	}
}

foreach($arrProperty as $key => $value){
	if (!in_array($key, $arrCodeNew)) {
		$arFieldsQ[] = $value;
	}
}
foreach($arFieldsQ as $fields){
	$fields["IBLOCK_ID"] = $IBLOCK_ID_NEW;
	if($fields["LIST_TYPE"] == "C" || $fields["LIST_TYPE"] == "L"){
	   $EnumPropID[$fields["ID"]] = $ibp->Add($fields);
	}else{
		$PropID = $ibp->Add($fields);
	}
}
$property_enums = CIBlockPropertyEnum::GetList(Array(), Array("IBLOCK_ID"=>$IBLOCK_ID_ASPRO));
while($enum_fields = $property_enums->GetNext())
{
	if (array_key_exists($enum_fields["PROPERTY_ID"], $EnumPropID)) {
		$ibpenum->Add(Array('PROPERTY_ID'=>$EnumPropID[$enum_fields["PROPERTY_ID"]], 'VALUE'=> $enum_fields['VALUE'], 'XML_ID' => $enum_fields['XML_ID']));
	}
}

//Пользовательские поля

$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID" => "IBLOCK_".$IBLOCK_ID_ASPRO."_SECTION", 'LANG' => 'ru'));
while($arRes = $rsData->Fetch())
{
	$arRes["EDIT_FORM_LABEL"] = array('ru' => $arRes["EDIT_FORM_LABEL"], 'en' => $arRes["EDIT_FORM_LABEL"]);
	$arRes["ENTITY_ID"] = "IBLOCK_".$IBLOCK_ID_NEW."_SECTION";
	if($arRes["USER_TYPE_ID"] == "enumeration"){
		$arEnum[$arRes["ID"]] = array();
		$arEnum[$arRes["ID"]]['NEW'] = $obUserField->Add($arRes);
		$arEnum[$arRes["ID"]]['OLD'] = $arRes["ID"];
		$arResEnum[] = $arRes["ID"];
	}
	else{
		$obUserField->Add($arRes);
	}
}

$rsEnum = $obEnum->GetList(array(), array("USER_FIELD_ID" => $arResEnum));
while ($arF = $rsEnum->Fetch()) {
	$arrEnumVal[$arF["USER_FIELD_ID"]]['n'.count($arrEnumVal[$arF["USER_FIELD_ID"]])] = array('XML_ID' => $arF["XML_ID"] ,'VALUE' => $arF["VALUE"]);
}

foreach ($arrEnumVal as $propKey => $listValue) {
	$obEnum->SetEnumValues($arEnum[$propKey]['NEW'], $arrEnumVal[$propKey]);
}

//Получаем код информационного блока Аспро
$res = CIBlock::GetList(Array(), Array('id'=> $IBLOCK_ID_ASPRO, ), false);
while($ar_res = $res->Fetch())
{
   $codeAspro = $ar_res["CODE"];
   $iblockTypeAspro = $ar_res["IBLOCK_TYPE_ID"];
}


//Смена типа (указать решение в input)

$arFields = Array(
  "IBLOCK_TYPE_ID" => $iblockTypeAspro,
  "CODE" => $codeAspro
  );
$res = $ib->Update($IBLOCK_ID_NEW, $arFields);

//Удаление информационного блока
if($USER->IsAdmin())
{
    $DB->StartTransaction();
    if(!CIBlock::Delete($IBLOCK_ID_ASPRO))
    {
        $strWarning .= GetMessage("IBLOCK_DELETE_ERROR");
        $DB->Rollback();
    }
    else
        $DB->Commit();
}
	$result = "Успешно!";

echo $result;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>