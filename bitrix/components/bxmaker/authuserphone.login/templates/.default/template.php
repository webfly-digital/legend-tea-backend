<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
/**
 * 
 * @var \CBitrixComponent $component
 * @var array $arParams
 * @var array $arResult
 */
$CPN = 'bxmaker.authuserphone.login';
$oManager = \BXmaker\AuthUserPhone\Manager::getInstance();
$this->setFrameMode(true);
$isEnabledPhoneMask = isset($arParams['PHONE_MASK_PARAMS']['type']) && $arParams['PHONE_MASK_PARAMS']['type'] === 'bitrix';
if ($arParams['PHONE_MASK_PARAMS'] && $arParams['PHONE_MASK_PARAMS']['type'] === 'bitrix' && $arParams['PHONE_MASK_PARAMS']['onlySelected']) {
    echo \BXmaker\AuthUserPhone\Html\Helper::getStyleForBitrixPhoneCountryOnlySelected();
}
$rand = $arParams['RAND_STRING'];
?>
    <div class="bxmaker-authuserphone-login bxmaker-authuserphone-login--auth <?php 
echo $arParams['IS_ENABLED_REGISTER'] !== 'Y' ? 'bxmaker-authuserphone-login--noreg' : '';
?>"
         id="bxmaker-authuserphone-login--<?php 
echo $rand;
?>" data-rand="<?php 
echo $rand;
?>"
         data-consent="<?php 
echo $arParams['CONSENT_SHOW'];
?>">

        <?php 
if ($arParams['IS_ENABLED_REGISTER'] == 'Y') {
    ?>
            <span class="bxmaker-authuserphone-login__change-form bxmaker-authuserphone-login__onlyauth"><?php 
    echo GetMessage($CPN . 'BTN_REGISTER');
    ?></span>
            <span class="bxmaker-authuserphone-login__change-form bxmaker-authuserphone-login__onlyreg"><?php 
    echo GetMessage($CPN . 'AUTH_TITLE');
    ?></span>
        <?php 
}
?>

        <div class="bxmaker-authuserphone-login__title bxmaker-authuserphone-login__onlyauth"><?php 
echo GetMessage($CPN . 'AUTH_TITLE');
?></div>
        <div class="bxmaker-authuserphone-login__title  bxmaker-authuserphone-login__onlyreg "><?php 
echo GetMessage($CPN . 'BTN_REGISTER');
?> </div>

        <?php 
$frame = $this->createFrame('bxmaker-authuserphone-login--' . $rand)->begin('<div class="bxmaker-authuserphone-login-loading"></div>');
$frame->setAnimation(true);
?>


        <?php 
if ($arResult['USER_IS_AUTHORIZED'] == 'Y') {
    ?>
            <div class="bxmaker-authuserphone-login-msg bxmaker-authuserphone-login-msg--success"
                 style="margin-bottom:0;">
                <?php 
    echo GetMessage($CPN . 'USER_IS_AUTHORIZED');
    ?>
            </div>
        <?php 
} else {
    ?>

            <div class="bxmaker-authuserphone-login-msg"></div>

            <?php 
    $authInputNames = [GetMessage($CPN . 'INPUT_PHONE')];
    $bLogin = $arParams['IS_ENABLED_AUTH_BY_LOGIN'] == 'Y';
    $bEmail = $arParams['IS_ENABLED_AUTH_BY_EMAIL'] == 'Y';
    if ($bLogin) {
        $authInputNames[] = mb_strtolower(GetMessage($CPN . 'INPUT_LOGIN'));
    }
    if ($bEmail) {
        $authInputNames[] = mb_strtolower(GetMessage($CPN . 'INPUT_EMAIL'));
    }
    $name = $authInputNames[0];
    if (count($authInputNames) > 1) {
        $name = implode(', ', array_slice($authInputNames, 0, -1));
        $name .= ' ' . GetMessage($CPN . 'INPUT_OR') . ' ' . end($authInputNames);
    }
    ?>


            <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login__onlyauth <?php 
    echo $isEnabledPhoneMask && !$bLogin && !$bEmail ? 'bxmaker-authuserphone-login-row--flag' : '';
    ?>">

                <div class="bxmaker-authuserphone-login-row__flag bxmaker-authuserphone-login-row__flag--auth"></div>

                <input type="text" name="phone" class="phone" placeholder="<?php 
    echo $name;
    ?>"/>
            </div>

            <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login__onlyreg <?php 
    echo $isEnabledPhoneMask ? 'bxmaker-authuserphone-login-row--flag' : '';
    ?>">
                <div class="bxmaker-authuserphone-login-row__flag bxmaker-authuserphone-login-row__flag--reg"></div>

                <input type="text" name="register_phone" class="phone"
                       placeholder="<?php 
    echo GetMessage($CPN . 'INPUT_PHONE_REG');
    ?>"/>
            </div>

            <?php 
    if ($arParams['IS_ENABLED_REGISTER_LOGIN'] == 'Y') {
        ?>
                <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login__onlyreg">
                    <input  type="text" name="login" class="login"
                           placeholder="<?php 
        echo GetMessage($CPN . 'INPUT_LOGIN');
        ?>"/>
                </div>
            <?php 
    }
    ?>

            <?php 
    if ($arParams['IS_ENABLED_REGISTER_EMAIL'] == 'Y') {
        ?>
                <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login__onlyreg">
                    <input  type="text" name="email" class="email"
                           placeholder="<?php 
        echo GetMessage($CPN . 'INPUT_EMAIL');
        ?>"/>
                </div>
            <?php 
    }
    ?>


            <?php 
    if ($arParams['IS_ENABLED_REGISTER_PASSWORD'] == 'Y') {
        ?>
                <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login__onlyreg">
                    <input   type="password" name="password" class="password"
                           placeholder="<?php 
        echo GetMessage($CPN . 'INPUT_PASSWORD');
        ?>"/>

                    <span class="bxmaker-authuserphone-login__show-password"
                          title="<?php 
        echo GetMessage($CPN . 'BTN_SHOW_PASSWORD');
        ?>"
                          data-title-show="<?php 
        echo GetMessage($CPN . 'BTN_SHOW_PASSWORD');
        ?>"
                          data-title-hide="<?php 
        echo GetMessage($CPN . 'BTN_HIDE_PASSWORD');
        ?>"></span>
                </div>
            <?php 
    }
    ?>


            <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login__onlyreg">
                <input  type="text" name="sms_code" class="smscode"
                       placeholder="<?php 
    echo GetMessage($CPN . 'INPUT_SMS_CODE');
    ?>"/>
            </div>

            <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login__onlyauth">
                <input  type="password" name="password_sms_code" class="password"
                       placeholder="<?php 
    echo GetMessage($CPN . 'INPUT_PASSWORD_OR_SMS_CODE');
    ?>"/>

                <span class="bxmaker-authuserphone-login__show-password"
                      title="<?php 
    echo GetMessage($CPN . 'BTN_SHOW_PASSWORD');
    ?>"
                      data-title-show="<?php 
    echo GetMessage($CPN . 'BTN_SHOW_PASSWORD');
    ?>"
                      data-title-hide="<?php 
    echo GetMessage($CPN . 'BTN_HIDE_PASSWORD');
    ?>"></span>
            </div>


            <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login-captcha">
                <?php 
    /**
                * 
                * <input type="hidden" name="captcha_sid" value="0b853532ea27dba6a71666bb89ab6760"/>
                * <img src="/bitrix/tools/captcha.php?captcha_sid=0b853532ea27dba6a71666bb89ab6760" title="<?= GetMessage($CPN . 'UPDATE_CAPTCHA_IMAGE');?>" alt=""/>
                * <span class="bxmaker-authuserphone-login-captcha__reload" title="<?= GetMessage($CPN . 'UPDATE_CAPTCHA_IMAGE');?>"></span>
                * <input type="text" name="captcha_word" class="captcha_word" placeholder="<?= GetMessage($CPN . 'INPUT_CAPTHCA');?>"/>
                */
    ?>
            </div>

            <div class="bxmaker-authuserphone-login-row ">
                <span class="bxmaker-authuserphone-login-link"><?php 
    echo GetMessage($CPN . 'BTN_SEND_CODE');
    ?></span>
            </div>


            <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login__restore-email bxmaker-authuserphone-login__onlyauth">
                <span class="bxmaker-authuserphone-login-btn__send-email"><?php 
    echo GetMessage($CPN . 'BTN_SEND_EMAIL');
    ?></span>
            </div>

        <?php 
}
?>

        <?php 
if ($arParams['CONSENT_SHOW'] == 'Y') {
    $arFields = [];
    $arFields[] = GetMessage($CPN . 'INPUT_PHONE_REG');
    if ($oManager->param()->isEnabledRegisterEmail()) {
        $arFields[] = GetMessage($CPN . 'INPUT_EMAIL');
    }
    if ($oManager->param()->isEnabledRegisterLogin()) {
        $arFields[] = GetMessage($CPN . 'INPUT_LOGIN');
    }
    ?>
            <div class="bxmaker-authuserphone-login-row bxmaker-authuserphone-login-row--registration ">
                <?php 
    $APPLICATION->IncludeComponent("bitrix:main.userconsent.request", "bxmaker-authuserphone", ['ID' => $arParams['CONSENT_ID'], "IS_CHECKED" => 'N', "IS_LOADED" => "Y", "AUTO_SAVE" => "N", 'SUBMIT_EVENT_NAME' => 'bxmaker-authuserphone-login__consent--' . $rand, 'REPLACE' => ['button_caption' => GetMessage($CPN . 'BTN_REG_INTER'), 'fields' => $arFields]], $component);
    ?>

            </div>
        <?php 
}
?>

        <?php 
if ($arResult['USER_IS_AUTHORIZED'] != 'Y') {
    ?>

            <div class="bxmaker-authuserphone-login-row btn_box">
                <div class="bxmaker-authuserphone-login-btn " data-auth-title="<?php 
    echo GetMessage($CPN . 'BTN_INTER');
    ?>"
                     data-reg-title="<?php 
    echo GetMessage($CPN . 'BTN_REG_INTER');
    ?>"><?php 
    echo GetMessage($CPN . 'BTN_INTER');
    ?></div>
            </div>

        <?php 
}
?>

        <script type="text/javascript" class="bxmaker-authuserphone-jsdata">
            <?php 
// component parameters
$signer = new \Bitrix\Main\Security\Sign\Signer();
$signedParameters = $signer->sign(base64_encode(serialize($arResult['_ORIGINAL_PARAMS'])));
$signedTemplate = $signer->sign($arResult['TEMPLATE']);
?>

            window.BXmakerAuthUserPhoneLoginData = window.BXmakerAuthUserPhoneLoginData || {};
            window.BXmakerAuthUserPhoneLoginData["<?php 
echo $rand;
?>"] = <?php 
echo \Bitrix\Main\Web\Json::encode(['parameters' => $signedParameters, 'template' => $signedTemplate, 'siteId' => SITE_ID, 'ajaxUrl' => $this->getComponent()->getPath() . '/ajax.php', 'rand' => $rand, 'isEnabledRegister' => $arParams['IS_ENABLED_REGISTER'] == 'Y', 'isEnabledAuthByLogin' => $arParams['IS_ENABLED_AUTH_BY_LOGIN'] == 'Y', 'isEnabledAuthByEmail' => $arParams['IS_ENABLED_AUTH_BY_EMAIL'] == 'Y', 'phoneMaskParams' => $arParams['PHONE_MASK_PARAMS'], 'messages' => ['UPDATE_CAPTCHA_IMAGE' => GetMessage($CPN . 'UPDATE_CAPTCHA_IMAGE'), 'INPUT_CAPTHCA' => GetMessage($CPN . 'INPUT_CAPTHCA'), 'REGISTER_INFO' => GetMessage($CPN . 'REGISTER_INFO'), 'BTN_SEND_CODE' => GetMessage($CPN . 'BTN_SEND_CODE'), 'BTN_SEND_EMAIL' => GetMessage($CPN . 'BTN_SEND_EMAIL'), 'BTN_SEND_CODE_TIMEOUT' => GetMessage($CPN . 'BTN_SEND_CODE_TIMEOUT')]]);
?>;
        </script>


        <?php 
$frame->end();
?>

    </div>
<?php 