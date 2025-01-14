<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Миграция");
\Bitrix\Main\Loader::includeModule('iblock');

$iblocks = CIBlock::GetList(array(), array());
while($arIBlock = $iblocks->GetNext()) 
{
	$arIblocksTmp[$arIBlock['ID']] = $arIBlock["NAME"];
}


?>
<h3>Скрипт для миграции</h3>
<form method="post" id="ajax_form">
 <p>Инфоблок аспро: 
	<select size="5" multiple name="aspro">
	 <?foreach($arIblocksTmp as $key => $value):?>
		<option value="<?=$key;?>"><?=$key.' '.$value;?></option>
	<?endforeach;?>
   </select>
</p>
 <p>Инфоблок клиента: 
	<select size="5" multiple name="client">
	 <?foreach($arIblocksTmp as $key => $value):?>
		<option value="<?=$key;?>"><?=$key.' '.$value;?></option>
	<?endforeach;?>
   </select>
</p>
 <p><input class="btn btn-success" id="btn" type="submit" /></p>
</form>
<div id="result_form"></div> 

<script>
$( document ).ready(function() {
    $("#btn").click(
		function(){
			sendAjaxForm('result_form', 'ajax_form', 'migratsiya.php');
			return false; 
		}
	);
});
 
function sendAjaxForm(result_form, ajax_form, url) {
    $.ajax({
        url:     url, 
        type:     "POST",
        dataType: "html", 
        data: $("#"+ajax_form).serialize(), 
        success: function(response) { //Данные отправлены успешно
			$('#result_form').html(response);
    	},
    	error: function(response) { // Данные не отправлены
            $('#result_form').html('Ошибка. Данные не отправлены.');
    	}
 	});
}
</script>

<a href="index-hit.php">Скрипт "Наши предложения"</a>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>