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
\Bitrix\Main\UI\Extension::load('bxmaker.authuserphone.edit');
$jsNodeId = 'bxmaker-authuserphone-edit__' . $arParams['RAND_STRING'];
$jsVarMain = 'BXmakerAuthuserphoneEdit__' . $arParams['RAND_STRING'];
$jsVarParam = 'BXmakerAuthuserphoneEditParams__' . $arParams['RAND_STRING'];
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
echo sprintf('var %s = %s;', $jsVarParam, \Bitrix\Main\Web\Json::encode(['rand' => $arParams['RAND_STRING'], 'siteId' => SITE_ID, 'template' => $signedTemplate, 'parameters' => $signedParameters, 'ajaxUrl' => $this->getComponent()->getPath() . '/ajax.php', 'currentPhone' => $arResult['CURRENT_PHONE'], 'currentFormattedPhone' => $arResult['CURRENT_FORMATTED_PHONE'], 'isAuthorized' => $arResult['IS_AUTHORIZED'] == 'Y', 'confirmQueue' => $arParams['CONFIRM_QUEUE'], 'isEnabledConfirmBySmsCode' => $arParams['IS_ENABLED_CONFIRM_BY_SMS_CODE'] == 'Y', 'isEnabledConfirmByUserCall' => $arParams['IS_ENABLED_CONFIRM_BY_USER_CALL'] == 'Y', 'isEnabledConfirmByBotCall' => $arParams['IS_ENABLED_CONFIRM_BY_BOT_CALL'] == 'Y', 'isEnabledConfirmByBotSpeech' => $arParams['IS_ENABLED_CONFIRM_BY_BOT_SPEECH'] == 'Y', 'isEnabledConfirmBySimPush' => $arParams['IS_ENABLED_CONFIRM_BY_SIM_PUSH'] == 'Y', 'phoneMaskParams' => $arParams['PHONE_MASK_PARAMS']]));
?>

        (function(){
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
?> = new window.BXmaker.Authuserphone.Edit(<?php 
echo sprintf(" '%s', %s ", $jsNodeId, $jsVarParam);
?>);
                    }
                }, 100);
            }

            if (!!window.BXmaker && !!window.BXmaker.Authuserphone && !!window.BXmaker.Authuserphone.Edit) {
                init();
            } else {
                BX.loadExt('bxmaker.authuserphone.edit').then(function () {
                    init();
                });
            }
        })();


    </script>

<?php 
$frame->end();
?>

<?php 