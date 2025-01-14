<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Контакты | Чайно-кофейная компания «Легенда Чая»");
$APPLICATION->SetPageProperty("description", "Контакты и адреса чайно-кофейной компании «Легенда Чая» в Пятигорске. Мы занимаемся продажей чая и кофе, а также сопутствующими товарами. Осуществляем доставку заказов по всей России. Заказывайте по ☎️: 8 800 700-78-87.");
$APPLICATION->SetTitle("Контакты");?>

<?CMax::ShowPageType('page_contacts');?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>