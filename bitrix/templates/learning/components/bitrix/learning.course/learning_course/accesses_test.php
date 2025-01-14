<?
\Bitrix\Main\Loader::IncludeModule("learning");
\Bitrix\Main\Loader::IncludeModule("iblock");


$userId = $USER->getId();
$showChapter = true;

$resLess = \CLearnLesson::GetList([], ["ACTIVE" => "Y", "LESSON_ID" => $lessonId], ['LESSON_ID']);
while ($arLess = $resLess->GetNext()) {
    $lessonIdTable = $arLess['LESSON_ID'];
}

$resLessEdges = CLearnLesson::GetListOfParentPathes($lessonIdTable);
foreach ($resLessEdges as $path) $arChapter = $path->GetPathAsArray();


if($arChapter) {
    if($lessonId == $arResult["VARIABLES"]["CHAPTER_ID"]){//есливы зывается в  разделе
        $arChapter[] = $lessonIdTable;
    }
    
    $resIb = \CIBlockElement::GetList([], ['IBLOCK_ID' => 127, "ACTIVE" => "Y", "PROPERTY_CHAPTER_ID" => $arChapter], false, false, ['PROPERTY_CHAPTER_ID', 'PROPERTY_TEST_ID']);
    while ($arElem = $resIb->GetNext()) {
        $testId = $arElem["PROPERTY_TEST_ID_VALUE"];
        $showChapter = false;
    }
}

if ($testId) {
    $resTest = \CGradeBook::GetList([], ["STUDENT_ID" => $userId, "TEST_ID" => $testId, 'COMPLETED' => 'Y']);
    while ($arTest = $resTest->GetNext()) {
        $showChapter = true;
    }
    if (!$showChapter) {
        $resTest = \CTest::GetList([], ["ACTIVE" => "Y", "TEST_ID" => $testId],);
        while ($arTest = $resTest->GetNext()) {
            $nameTest = $arTest['NAME'];
            $linkTest = '/academy/course/?COURSE_ID=' . $arParams["COURSE_ID"] . '&TEST_LIST=Y';
        }
    }
}

?>