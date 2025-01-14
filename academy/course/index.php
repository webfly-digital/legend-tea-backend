<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle(""); ?>
<noindex>
    <?
    $APPLICATION->IncludeComponent(
	"bitrix:learning.course", 
	"learning_course",
	array(
		"COMPONENT_TEMPLATE" => "learning_course",
		"COURSE_ID" => $_REQUEST["COURSE_ID"],
		"SEF_MODE" => "N",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "3600",
		"CHECK_PERMISSIONS" => "Y",
		"PATH_TO_USER_PROFILE" => "/company/personal/user/#USER_ID#/",
		"SET_TITLE" => "Y",
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "AUTO",
		"PAGE_WINDOW" => "10",
		"SHOW_TIME_LIMIT" => "Y",
		"PAGE_NUMBER_VARIABLE" => "PAGE",
		"TESTS_PER_PAGE" => "20",
		"VARIABLE_ALIASES" => array(
			"COURSE_ID" => "COURSE_ID",
			"INDEX" => "INDEX",
			"LESSON_ID" => "LESSON_ID",
			"CHAPTER_ID" => "CHAPTER_ID",
			"SELF_TEST_ID" => "SELF_TEST_ID",
			"TEST_ID" => "TEST_ID",
			"TYPE" => "TYPE",
			"TEST_LIST" => "TEST_LIST",
			"GRADEBOOK" => "GRADEBOOK",
			"FOR_TEST_ID" => "FOR_TEST_ID",
		)
	),
	false
); ?>
</noindex>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
