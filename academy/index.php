<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Академия");

 {
    $APPLICATION->IncludeComponent(
	"bitrix:learning.course.list", 
	"learning", 
	array(
		"COMPONENT_TEMPLATE" => "learning",
		"COURSE_DETAIL_TEMPLATE" => "course/index.php?COURSE_ID=#COURSE_ID#&INDEX=Y",
		"SORBY" => "SORT",
		"SORORDER" => "ASC",
		"CHECK_PERMISSIONS" => "Y",
		"COURSES_PER_PAGE" => "30",
		"SET_TITLE" => "Y",
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "AUTO"
	),
	false
);
}

?>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>