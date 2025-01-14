<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Заполнение");
\Bitrix\Main\Loader::includeModule('iblock');

$IBLOCK_ID = $_POST['aspro'];
$PRODICT_ID = $_POST['product-id'];
$HIT = $_POST['product-hit'];
$NEW = $_POST['product-new'];
$RECOMMEND = $_POST['product-recommend'];
$STOCK = $_POST['product-stock'];

$CLIENT_NEW = $_POST['product-new-client'];
$CLIENT_STOCK = $_POST['product-stock-client'];
$CLIENT_HIT = $_POST['product-hit-сlient'];
$CLIENT_RECOMMEND = $_POST['product-recommend-client'];


$arSelect = Array("ID", "IBLOCK_ID", 'PROPERTY_'.$_POST['code-new-client'], 'PROPERTY_'.$_POST['code-stock-client'], 'PROPERTY_'.$_POST['сode-hit-сlient'], 'PROPERTY_'.$_POST['code-recommend-client']);
$arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID, "ID" => $PRODICT_ID);

$dbRes = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
while($arRes = $dbRes->GetNext()){
if($arRes["PROPERTY_".$_POST['code-new-client']."_ENUM_ID"] || $arRes["PROPERTY_".$_POST['code-stock-client']."_ENUM_ID"] || $arRes["PROPERTY_".$_POST['сode-hit-сlient']."_ENUM_ID"] || $arRes["PROPERTY_".$_POST['code-recommend-client']."_ENUM_ID"]){
	$propHit[$arRes["ID"]] = array(
	"NEW" => $arRes["PROPERTY_".$_POST['code-new-client']."_ENUM_ID"],
	"STOCK" => $arRes["PROPERTY_".$_POST['code-stock-client']."_ENUM_ID"],
	"HIT" => $arRes["PROPERTY_".$_POST['сode-hit-сlient']."_ENUM_ID"],
	"RECOMMEND" => $arRes["PROPERTY_".$_POST['code-recommend-client']."_ENUM_ID"],
	);
} 

}
if(!empty($propHit)){
	foreach($propHit as $key => $value){

		if($value["NEW"] === $CLIENT_NEW){
			$PROP_ARRAY["HIT"][]=$NEW;
		}
		if($value["STOCK"] === $CLIENT_STOCK){
			$PROP_ARRAY["HIT"][]=$STOCK;
		}
		if($value["HIT"] === $CLIENT_HIT){
			$PROP_ARRAY["HIT"][]=$HIT;
		}
		if($value["RECOMMEND"] === $CLIENT_RECOMMEND){
			$PROP_ARRAY["HIT"][]=$RECOMMEND;
		}
		CIBlockElement::SetPropertyValuesEx($key, false, $PROP_ARRAY);
		echo 'Обновлен товар:' .$key. '<br>';
		$PROP_ARRAY = array();
	}
}
