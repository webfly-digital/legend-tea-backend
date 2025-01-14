<?php

/**
 * @global \CMain $APPLICATION
 */
define("PUBLIC_AJAX_MODE", true);
define('BX_NO_ACCELERATOR_RESET', true);
// чтобы не глючило на VMBitrix 3.1 из-за Zend при отправке бэкапа в облако.
define('BX_SECURITY_SHOW_MESSAGE', false);
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC", "Y");
define("NOT_CHECK_PERMISSIONS", true);
define("NOT_CHECK_FILE_PERMISSIONS", true);
define('CHK_EVENT', false);
define("NO_AGENT_CHECK", true);
define("DisableEventsCheck", true);
// define('BX_SECURITY_SESSION_READONLY', true);
// define('BX_SECURITY_SESSION_VIRTUAL', true);
$siteId = isset($_REQUEST['siteId']) && is_string($_REQUEST['siteId']) ? $_REQUEST['siteId'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if (!empty($siteId) && is_string($siteId)) {
    define('SITE_ID', $siteId);
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter());
$signer = new \Bitrix\Main\Security\Sign\Signer();
try {
    $template = $signer->unsign((string) $request->get('template'));
    $paramString = $signer->unsign((string) $request->get('parameters'));
} catch (\Bitrix\Main\Security\Sign\BadSignatureException $e) {
    die('ERROR_SIGN');
}
$parameters = unserialize(base64_decode($paramString));
$parameters['IS_AJAX'] = 'Y';
$APPLICATION->IncludeComponent('bxmaker:authuserphone.edit', $template, $parameters, false);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';