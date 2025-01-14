<?
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('NOT_CHECK_PERMISSIONS', true);

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/..");

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

if (!empty($_FILES)) {
    $example = new  \Webfly\Generate\GeneratePriceList($_SERVER['DOCUMENT_ROOT']);

    if (!empty($_REQUEST['section'])) {
        $pathSects = $example->pathAllSect;
        if (!file_exists($pathSects)) mkdir($pathSects, 0777, true);
        $pathSect = $pathSects . $_REQUEST['section'] . '/';
        if (!file_exists($pathSect)) mkdir($pathSect, 0777, true);
    }
    if (!empty($_REQUEST['static'])) {
        $pathSect = $example->pathPdfStatic;
        if (!file_exists($pathSect)) mkdir($pathSect, 0777, true);
    }

    $uploadfile = $pathSect . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
        if (!empty($_REQUEST['last_page'])) {
            $example->execute('mergePDF');
        }
        echo "Файл корректен и был успешно загружен.\n" . $uploadfile;
    } else {
        echo "Возможная атака с помощью файловой загрузки!\n";
    }
}
?>

