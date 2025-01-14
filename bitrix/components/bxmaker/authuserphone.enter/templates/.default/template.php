<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
/**
 * 
 * @var CBitrixComponentTemplate $this
 * @var array $arResult
 * @var array $arParams
 */
$this->setFrameMode(true);
$oManager = \BXmaker\AuthUserPhone\Manager::getInstance();
\Bitrix\Main\UI\Extension::load('bxmaker.authuserphone.enter');
$jsNodeId = 'bxmaker-authuserphone-enter__' . $arParams['RAND_STRING'];
$jsVarMain = 'BXmakerAuthuserphoneEnter__' . $arParams['RAND_STRING'];
$jsVarParam = 'BXmakerAuthuserphoneEnterParams__' . $arParams['RAND_STRING'];
if ($arParams['PHONE_MASK_PARAMS'] && $arParams['PHONE_MASK_PARAMS']['type'] === 'bitrix' && $arParams['PHONE_MASK_PARAMS']['onlySelected']) {
    echo \BXmaker\AuthUserPhone\Html\Helper::getStyleForBitrixPhoneCountryOnlySelected();
}
?>

<div id="<?php 
echo $jsNodeId;
?>">
    <div class="bxmaker-authuserphone-loader"></div>
</div>

<?php 
$frame = $this->createFrame($jsNodeId . '-frame')->begin('');
$frame->setAnimation(true);
?>

<script type="text/javascript" class="bxmaker-jsdata">
    <?php 
// component parameters
$signer = new \Bitrix\Main\Security\Sign\Signer();
$signedParameters = $signer->sign(base64_encode(serialize($arResult['_ORIGINAL_PARAMS'])));
$signedTemplate = $signer->sign($arResult['TEMPLATE']);
echo sprintf('var %s = %s;', $jsVarParam, \Bitrix\Main\Web\Json::encode(['rand' => $arParams['RAND_STRING'], 'siteId' => SITE_ID, 'template' => $signedTemplate, 'parameters' => $signedParameters, 'ajaxUrl' => $this->getComponent()->getPath() . '/ajax.php', 'isAuthorized' => $arResult['IS_AUTHORIZED'] == 'Y', 'confirmQueue' => $arParams['CONFIRM_QUEUE'], 'isEnabledConfirmBySmsCode' => $arParams['IS_ENABLED_CONFIRM_BY_SMS_CODE'] == 'Y', 'isEnabledConfirmByUserCall' => $arParams['IS_ENABLED_CONFIRM_BY_USER_CALL'] == 'Y', 'isEnabledConfirmByBotCall' => $arParams['IS_ENABLED_CONFIRM_BY_BOT_CALL'] == 'Y', 'isEnabledConfirmByBotSpeech' => $arParams['IS_ENABLED_CONFIRM_BY_BOT_SPEECH'] == 'Y', 'isEnabledConfirmBySimPush' => $arParams['IS_ENABLED_CONFIRM_BY_SIM_PUSH'] == 'Y', 'isEnabledConfirmByEmailCode' => $arParams['IS_ENABLED_CONFIRM_BY_EMAIL_CODE'] == 'Y', 'isEnabledReloadAfterAuth' => $arParams['IS_ENABLED_RELOAD_AFTER_AUTH'] == 'Y', 'isEnabledRegister' => $arParams['IS_ENABLED_REGISTER'] == 'Y', 'isEnabledAuthByPasswordFirst' => $arParams['IS_ENABLED_AUTH_BY_PASSWORD_FIRST'] == 'Y', 'isEnabledAuthByLogin' => $arParams['IS_ENABLED_AUTH_BY_LOGIN'] == 'Y', 'isEnabledAuthByEmail' => $arParams['IS_ENABLED_AUTH_BY_EMAIL'] == 'Y', 'isEnabledRegisterFIO' => $arParams['IS_ENABLED_REGISTER_FIO'] == 'Y', 'isEnabledRegisterFIOSplit' => $arParams['IS_ENABLED_REGISTER_FIO_SPLIT'] == 'Y', 'isEnabledRegisterLastName' => $arParams['IS_ENABLED_REGISTRATION_LAST_NAME'] == 'Y', 'isEnabledRegisterFirstName' => $arParams['IS_ENABLED_REGISTRATION_FIRST_NAME'] == 'Y', 'isEnabledRegisterSecondName' => $arParams['IS_ENABLED_REGISTRATION_SECOND_NAME'] == 'Y', 'isEnabledRegisterBirthday' => $arParams['IS_ENABLED_REGISTER_BIRTHDAY'] == 'Y', 'isEnabledRegisterLogin' => $arParams['IS_ENABLED_REGISTER_LOGIN'] == 'Y', 'isEnabledRegisterEmail' => $arParams['IS_ENABLED_REGISTER_EMAIL'] == 'Y', 'isEnabledRegisterPassword' => $arParams['IS_ENABLED_REGISTER_PASSWORD'] == 'Y', 'isEnabledRestoreByEmail' => $arParams['IS_ENABLED_RESTORE_BY_EMAIL'] == 'Y', 'isEnabledRequestConsent' => $arParams['IS_ENABLED_REQUEST_CONSENT'] == 'Y', 'isEnabledRequestAdsAgreement' => $arParams['IS_ENABLED_REQUEST_ADS_AGREEMENT'] == 'Y', 'requestAdsAgreementLabel' => $arParams['REQUEST_ADS_AGREEMENT_LABEL'], 'phoneMaskParams' => $arParams['PHONE_MASK_PARAMS'], 'registerFIODadata' => $arParams['REGISTER_FIO_DADATA']]));
?>

    (function () {
        function init() {
            var count = 0;
            var interval = setInterval(function () {
                if (count++ > 30) {
                    clearInterval(interval);
                }
                if (!!document.getElementById('<?php 
echo $jsNodeId;
?>')) {
                    clearInterval(interval);
                    window.<?php 
echo $jsVarMain;
?> = new window.BXmaker.Authuserphone.Enter(<?php 
echo sprintf(" '%s', %s ", $jsNodeId, $jsVarParam);
?>);
                }
            }, 100);
        }

        if (!!window.BXmaker && !!window.BXmaker.Authuserphone && !!window.BXmaker.Authuserphone.Enter) {
            init();
        } else {
            BX.loadExt('bxmaker.authuserphone.enter').then(function () {
                init();
            });
        }
    })();
</script>

<?php 
$frame->end();
?>

<?php 