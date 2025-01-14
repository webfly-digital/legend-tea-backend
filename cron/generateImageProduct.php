<?php
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('NOT_CHECK_PERMISSIONS', true);

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/..");

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

$example = new  \Webfly\Generate\GeneratePhotoProduct($_SERVER['DOCUMENT_ROOT']);
$example->execute();