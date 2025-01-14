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
<h3>Скрипт для замены свойств Хит, Новинка, Акции, Советуем</h3>

<form method="post" id="ajax_form-hit">
 <p>Выберите инфоблок: 
	<select size="5" multiple name='aspro'>
	 <?foreach($arIblocksTmp as $key => $value):?>
		<option value="<?=$key;?>"><?=$key.' '.$value;?></option>
	<?endforeach;?>
   </select>
</p>
<p>Перед началом рекомендуем протестировать на 1 товаре:
	<input type="text" name = 'product-id'>
</p>
<p>Укажите id Хит <a href="http://joxi.ru/vAWOglaHgQNyxA" target="_blank">Скрин</a>:
	<input type="text" name = 'product-hit'>
</p>
<p>Укажите id Советуем <a href="http://joxi.ru/5mdBwzJc3V76XA" target="_blank">Скрин</a>:
	<input type="text" name = 'product-recommend'>
</p>
<p>Укажите id Новинка <a href="http://joxi.ru/KAgBQY4cEbPVo2" target="_blank">Скрин</a>:
	<input type="text" name = 'product-new'>
</p>
<p>Укажите id Акция <a href="http://joxi.ru/MAjBWz4cjK9E7A" target="_blank">Скрин</a>:
	<input type="text" name = 'product-stock'>
</p>

<p>Укажите id Хит (клиентское свойство) <a href="http://joxi.ru/eAOWkeQS9j5YBA" target="_blank">Скрин</a>:
	<input type="text" name = 'product-hit-сlient'>
</p>
<p>Укажите символьный код Хит (клиентское свойство) <a href="http://joxi.ru/LmGnZy1Uw9oVJr" target="_blank">Скрин</a>:
	<input type="text" name = 'сode-hit-сlient'>
</p>
<p>Укажите id Новинка (клиентское свойство) <a href="http://joxi.ru/Vrwz1Y4U7YLzjA" target="_blank">Скрин</a>:
	<input type="text" name = 'product-new-client'>
</p>
<p>Укажите символьный код Новинка (клиентское свойство) <a href="http://joxi.ru/D2Pd1e5tqzV3X2" target="_blank">Скрин</a>:
	<input type="text" name = 'code-new-client'>
</p>
<p>Укажите id Советуем (клиентское свойство): 
	<input type="text" name = 'product-recommend-client'>
</p>
<p>Укажите символьный код Советуем (клиентское свойство): 
	<input type="text" name = 'code-recommend-client'>
</p>
<p>Укажите id Акция (клиентское свойство) <a href="http://joxi.ru/p274zRQTKgqg1A" target="_blank">Скрин</a>:
	<input type="text" name = 'product-stock-client'>
</p>

<p>Укажите символьный код Акция (клиентское свойство) <a href="http://joxi.ru/n2YXGd3Ub6vgvm" target="_blank">Скрин</a>:
	<input type="text" name = 'code-stock-client'>
</p>

 <p><input class="btn btn-success" id="btn-hit" type="submit" /></p>
</form>
<div id="result_form-hit"></div> 

<script>
$( document ).ready(function() {
console.log(1);
    $("#btn-hit").click(
		function(){
			sendAjaxForm('result_form-hit', 'ajax_form-hit', 'hit.php');
			return false; 
		}
	);
});
 
function sendAjaxForm(result_form_hit, ajax_form_hit, url) {
    $.ajax({
        url:     url, 
        type:     "POST",
        dataType: "html", 
        data: $("#"+ajax_form_hit).serialize(), 
        success: function(response) { //Данные отправлены успешно
	 $('#result_form-hit').html(response);
    	},
    	error: function(response) { // Данные не отправлены
            $('#result_form-hit').html('Ошибка. Данные не отправлены.');
    	}
 	});
}
</script>



<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>