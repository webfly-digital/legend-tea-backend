<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
/**
 * 
 * @var \CBitrixComponentTemplate $this
 * @var \CBitrixComponent $component
 * @var \CMain $APPLICATION
 * @var array $arResult
 * @var array $arParams
 */
$CPN = 'BXMAKER.AUTHUSERPHONE.COMPONENT.CALL.TEMPLATE.DEFAULT.';
$oManager = \BXmaker\AuthUserPhone\Manager::getInstance();
$this->setFrameMode(true);
$rand = $arParams['RAND_STRING'];
$isEnabledPhoneMask = isset($arParams['PHONE_MASK_PARAMS']['type']) && $arParams['PHONE_MASK_PARAMS']['type'] === 'bitrix';
if ($arParams['PHONE_MASK_PARAMS'] && $arParams['PHONE_MASK_PARAMS']['type'] === 'bitrix' && $arParams['PHONE_MASK_PARAMS']['onlySelected']) {
    echo \BXmaker\AuthUserPhone\Html\Helper::getStyleForBitrixPhoneCountryOnlySelected();
}
?>


    <div class="bxmaker-authuserphone-call" id="bxmaker-authuserphone-call--<?php 
echo $rand;
?>" data-rand="<?php 
echo $rand;
?>">
        <?php 
$frame = $this->createFrame('bxmaker-authuserphone-call--' . $rand)->begin();
$frame->setAnimation(true);
?>

        <div class="bxmaker-authuserphone-call-title"><?php 
echo GetMessage($CPN . 'AUTH_TITLE');
?></div>

        <?php 
if ($arResult['USER_IS_AUTHORIZED'] == 'Y') {
    ?>
            <div class="bxmaker-authuserphone-call-msg bxmaker-authuserphone-call-msg--success">
                <?php 
    echo GetMessage($CPN . 'USER_IS_AUTHORIZED');
    ?>
            </div>
        <?php 
} else {
    ?>

            <div class="bxmaker-authuserphone-call-msg"></div>

            <div class="bxmaker-authuserphone-call__container">

                <!--Авторизация-->
                <div class="bxmaker-authuserphone-call__block--auth bxmaker-authuserphone-call__block active">

                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top <?php 
    echo $isEnabledPhoneMask ? 'bxmaker-authuserphone-call-input--flag' : '';
    ?>">

                            <div class="bxmaker-authuserphone-call-input__flag"></div>

                            <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--phone" name="phone" type="text"
                                   id="bxmaker-authuserphone-call__input-phone<?php 
    echo $rand;
    ?>" autocomplete="off" >
                            <label class="bxmaker-authuserphone-call-input__label"
                                   for="bxmaker-authuserphone-call__input-phone<?php 
    echo $rand;
    ?>">
                                <span class="bxmaker-authuserphone-call-input__label-text"><?php 
    echo GetMessage($CPN . 'INPUT_PHONE');
    ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top">
                            <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--password js-on-enter-continue" name="password" type="password"
                                  id="bxmaker-authuserphone-call__input-password<?php 
    echo $rand;
    ?>" autocomplete="off">
                            <label class="bxmaker-authuserphone-call-input__label"
                                   for="bxmaker-authuserphone-call__input-password<?php 
    echo $rand;
    ?>">
                                <span class="bxmaker-authuserphone-call-input__label-text"><?php 
    echo GetMessage($CPN . 'INPUT_PASSWORD');
    ?></span>
                            </label>

                            <span class="bxmaker-authuserphone-call-input__show-pass"
                                  title="<?php 
    echo GetMessage($CPN . 'INPUT_PASSWORD_SHOW');
    ?>"
                                  data-title-show="<?php 
    echo GetMessage($CPN . 'INPUT_PASSWORD_SHOW');
    ?>"
                                  data-title-hide="<?php 
    echo GetMessage($CPN . 'INPUT_PASSWORD_HIDE');
    ?>"></span>
                        </div>

                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--small js-baup-forget"><?php 
    echo GetMessage($CPN . 'BTN_FORGOT_PASSWORD');
    ?></div>
                    </div>

                    <div class="bxmaker-authuserphone-call-captcha"></div>


                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                            <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--button js-baup-auth-enter  js-baup-continue "><?php 
    echo GetMessage($CPN . 'BTN_INPUT');
    ?></div>
                        </div>
                    </div>

                    <?php 
    if ($arParams['IS_ENABLED_REGISTER'] == 'Y') {
        ?>
                        <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                            <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link js-baup-register"><?php 
        echo GetMessage($CPN . 'BTN_REGISTER');
        ?></div>
                        </div>
                    <?php 
    }
    ?>

                </div>

                <!--Регистрация-->
                <div class="bxmaker-authuserphone-call__block--register bxmaker-authuserphone-call__block ">

                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top  <?php 
    echo $isEnabledPhoneMask ? 'bxmaker-authuserphone-call-input--flag' : '';
    ?>">

                            <div class="bxmaker-authuserphone-call-input__flag"></div>

                            <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--register_phone" name="register_phone" type="text"
                                   id="bxmaker-authuserphone-call__input-register_phone<?php 
    echo $rand;
    ?>" autocomplete="off">
                            <label class="bxmaker-authuserphone-call-input__label"
                                   for="bxmaker-authuserphone-call__input-register_phone<?php 
    echo $rand;
    ?>">
                                <span class="bxmaker-authuserphone-call-input__label-text"><?php 
    echo GetMessage($CPN . 'INPUT_PHONE');
    ?></span>
                            </label>
                        </div>
                    </div>


                    <?php 
    if ($arParams['IS_ENABLED_REGISTER_LOGIN'] == 'Y') {
        ?>
                        <div class="bxmaker-authuserphone-call-row">
                            <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top">
                                <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--register_login" name="register_login" type="text"
                                       id="bxmaker-authuserphone-call__input-register_login<?php 
        echo $rand;
        ?>" autocomplete="off">
                                <label class="bxmaker-authuserphone-call-input__label"
                                       for="bxmaker-authuserphone-call__input-register_login<?php 
        echo $rand;
        ?>">
                                    <span class="bxmaker-authuserphone-call-input__label-text"><?php 
        echo GetMessage($CPN . 'INPUT_LOGIN');
        ?></span>
                                </label>
                            </div>
                        </div>
                    <?php 
    }
    ?>

                    <?php 
    if ($arParams['IS_ENABLED_REGISTER_EMAIL'] == 'Y') {
        ?>
                        <div class="bxmaker-authuserphone-call-row">
                            <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top">
                                <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--register_email" name="register_email" type="text"
                                       id="bxmaker-authuserphone-call__input-register_email<?php 
        echo $rand;
        ?>" autocomplete="off">
                                <label class="bxmaker-authuserphone-call-input__label"
                                       for="bxmaker-authuserphone-call__input-register_email<?php 
        echo $rand;
        ?>">
                                    <span class="bxmaker-authuserphone-call-input__label-text"><?php 
        echo GetMessage($CPN . 'INPUT_EMAIL');
        ?></span>
                                </label>
                            </div>
                        </div>
                    <?php 
    }
    ?>

                    <?php 
    if ($arParams['IS_ENABLED_REGISTER_PASSWORD'] == 'Y') {
        ?>
                        <div class="bxmaker-authuserphone-call-row">
                            <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top">
                                <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--register_password" name="register_password" type="password"
                                       id="bxmaker-authuserphone-call__input-register_password<?php 
        echo $rand;
        ?>" autocomplete="off">
                                <label class="bxmaker-authuserphone-call-input__label"
                                       for="bxmaker-authuserphone-call__input-register_password<?php 
        echo $rand;
        ?>">
                                    <span class="bxmaker-authuserphone-call-input__label-text"><?php 
        echo GetMessage($CPN . 'INPUT_PASSWORD');
        ?></span>
                                </label>

                                <span class="bxmaker-authuserphone-call-input__show-pass"
                                      title="<?php 
        echo GetMessage($CPN . 'INPUT_PASSWORD_SHOW');
        ?>"
                                      data-title-show="<?php 
        echo GetMessage($CPN . 'INPUT_PASSWORD_SHOW');
        ?>"
                                      data-title-hide="<?php 
        echo GetMessage($CPN . 'INPUT_PASSWORD_HIDE');
        ?>"></span>
                            </div>
                        </div>
                    <?php 
    }
    ?>

                    <?php 
    if ($arParams['CONSENT_SHOW'] == 'Y') {
        $arFields = [];
        $arFields[] = GetMessage($CPN . 'INPUT_PHONE');
        if ($oManager->param()->isEnabledRegisterEmail()) {
            $arFields[] = GetMessage($CPN . 'INPUT_EMAIL');
        }
        if ($oManager->param()->isEnabledRegisterLogin()) {
            $arFields[] = GetMessage($CPN . 'INPUT_LOGIN');
        }
        ?>
                        <div class="bxmaker-authuserphone-call-row">
                            <div class="bxmaker-authuserphone-call__consent ">
                                <?php 
        $APPLICATION->IncludeComponent("bitrix:main.userconsent.request", "bxmaker-authuserphone", ['ID' => $arParams['CONSENT_ID'], "IS_CHECKED" => 'N', "IS_LOADED" => "Y", "AUTO_SAVE" => "N", 'SUBMIT_EVENT_NAME' => 'bxmaker-authuserphone-call__consent--' . $rand, 'REPLACE' => ['button_caption' => GetMessage($CPN . 'BTN_REGISTER'), 'fields' => $arFields]], $component);
        ?>

                            </div>
                        </div>
                    <?php 
    }
    ?>

                    <div class="bxmaker-authuserphone-call-captcha"></div>


                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                            <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--button js-baup-register-enter"><?php 
    echo GetMessage($CPN . 'BTN_REGISTER');
    ?></div>
                        </div>
                    </div>

                    <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link js-baup-auth"><?php 
    echo GetMessage($CPN . 'BTN_AUTH');
    ?></div>
                    </div>

                </div>

                <!--Код из смс-->
                <div class="bxmaker-authuserphone-call__block--smscode bxmaker-authuserphone-call__block">

                    <div class="bxmaker-authuserphone-call-row">

                        <div class="bxmaker-authuserphone-call-confirm__smscode">
                            <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top">
                                <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--smscode js-on-enter-continue" name="smscode" type="text"
                                       id="bxmaker-authuserphone-call__input-smscode<?php 
    echo $rand;
    ?>" autocomplete="off">
                                <label class="bxmaker-authuserphone-call-input__label"
                                       for="bxmaker-authuserphone-call__input-smscode<?php 
    echo $rand;
    ?>">
                                    <span class="bxmaker-authuserphone-call-input__label-text"><?php 
    echo GetMessage($CPN . 'INPUT_SMS_CODE');
    ?></span>
                                </label>
                            </div>
                        </div>


                        <div class="bxmaker-authuserphone-call-captcha"></div>

                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--small js-baup-sendcode js-baup-confirm-request "><?php 
    echo GetMessage($CPN . 'BTN_SMS_CODE_REQUEST');
    ?></div>

                        <div class="js-timeout-info"></div>
                    </div>


                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                            <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--button js-baup-smscode-next js-baup-continue"><?php 
    echo GetMessage($CPN . 'BTN_NEXT');
    ?></div>
                        </div>
                    </div>

                    <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--second js-baup-back"><?php 
    echo GetMessage($CPN . 'BTN_BACK');
    ?></div>
                        &nbsp;
                        &nbsp;
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--second js-baup-change-confirm"><?php 
    echo GetMessage($CPN . 'CHANGE_CONFIRM');
    ?></div>
                    </div>

                </div>

                <!--Звонок от клиента-->
                <div class="bxmaker-authuserphone-call__block--usercall bxmaker-authuserphone-call__block">


                    <div class="bxmaker-authuserphone-call-row">

                        <div class="bxmaker-authuserphone-call-confirm__description">
                            <?php 
    echo GetMessage($CPN . 'CALLPHONE_INFO');
    ?>
                        </div>

                        <div class="bxmaker-authuserphone-call-confirm__callphone">
                            <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--callphone js-on-enter-continue"
                                   placeholder="- - - - - - - - - - -" type="text" name="callphone"
                                   id="bxmaker-authuserphone-call__input-callphone<?php 
    echo $rand;
    ?>" autocomplete="off">
                        </div>

                        <div class="bxmaker-authuserphone-call-captcha"></div>

                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--small js-baup-get-callphone js-baup-confirm-request "><?php 
    echo GetMessage($CPN . 'USER_CALL_REQUEST');
    ?></div>
                    </div>


                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                            <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--button js-baup-usercall-next js-baup-continue"><?php 
    echo GetMessage($CPN . 'BTN_NEXT');
    ?></div>
                        </div>
                    </div>

                    <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--second js-baup-back"><?php 
    echo GetMessage($CPN . 'BTN_BACK');
    ?></div>
                        &nbsp;
                        &nbsp;
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--second js-baup-change-confirm"><?php 
    echo GetMessage($CPN . 'CHANGE_CONFIRM');
    ?></div>
                    </div>

                </div>


                <!--Звонок от бота-->
                <div class="bxmaker-authuserphone-call__block--botcall bxmaker-authuserphone-call__block">

                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-confirm__description"
                             data-text="<?php 
    echo GetMessage($CPN . 'BOT_CALL_INFO');
    ?>"
                             data-text4="<?php 
    echo GetMessage($CPN . 'BOT_CALL_INFO_4');
    ?>"
                        >
                            <?php 
    echo GetMessage($CPN . 'BOT_CALL_INFO');
    ?>
                        </div>

                        <div class="bxmaker-authuserphone-call-confirm__input">
                            <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top">
                                <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--botcode" name="botcode" type="text"
                                       placeholder="<?php 
    echo GetMessage($CPN . 'INPUT_BOTCODE');
    ?>"
                                       data-placeholder="<?php 
    echo GetMessage($CPN . 'INPUT_BOTCODE');
    ?>"
                                       data-placeholder4="<?php 
    echo GetMessage($CPN . 'INPUT_BOTCODE_4');
    ?>"
                                       id="bxmaker-authuserphone-call__input-botcall_code<?php 
    echo $rand;
    ?>" autocomplete="off">
                            </div>
                        </div>

                        <div class="bxmaker-authuserphone-call-captcha"></div>

                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--small js-baup-get-botcall js-baup-confirm-request "><?php 
    echo GetMessage($CPN . 'BOT_CALL_REQUEST');
    ?></div>

                    </div>


                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                            <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--button js-baup-botcall-next js-baup-continue"><?php 
    echo GetMessage($CPN . 'BTN_NEXT');
    ?></div>
                        </div>
                    </div>

                    <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--second js-baup-back"><?php 
    echo GetMessage($CPN . 'BTN_BACK');
    ?></div>
                        &nbsp;
                        &nbsp;
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--second js-baup-change-confirm"><?php 
    echo GetMessage($CPN . 'CHANGE_CONFIRM');
    ?></div>
                    </div>

                </div>

                <!--Голосовой код-->
                <div class="bxmaker-authuserphone-call__block--botspeech bxmaker-authuserphone-call__block">

                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-confirm__description">
                            <?php 
    echo GetMessage($CPN . 'BOT_SPEECH_INFO');
    ?>
                        </div>

                        <div class="bxmaker-authuserphone-call-confirm__input">
                            <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top">
                                <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--botspeech" name="botspeech" type="text"
                                       placeholder="<?php 
    echo GetMessage($CPN . 'BOT_SPEECH_PLACEHOLDER');
    ?>"
                                       id="bxmaker-authuserphone-call__input-botspeech_code<?php 
    echo $rand;
    ?>" autocomplete="off">
                            </div>
                        </div>

                        <div class="bxmaker-authuserphone-call-captcha"></div>

                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link
                        bxmaker-authuserphone-call-btn--small js-baup-get-botspeech js-baup-confirm-request "><?php 
    echo GetMessage($CPN . 'BOT_SPEECH_REQUEST');
    ?></div>

                    </div>


                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                            <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--button js-baup-botspeech-next js-baup-continue"><?php 
    echo GetMessage($CPN . 'BTN_NEXT');
    ?></div>
                        </div>
                    </div>

                    <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--second js-baup-back"><?php 
    echo GetMessage($CPN . 'BTN_BACK');
    ?></div>
                        &nbsp;
                        &nbsp;
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link bxmaker-authuserphone-call-btn--second js-baup-change-confirm"><?php 
    echo GetMessage($CPN . 'CHANGE_CONFIRM');
    ?></div>
                    </div>

                </div>


                <!--Восстановление пароля-->
                <div class="bxmaker-authuserphone-call__block--forget bxmaker-authuserphone-call__block">

                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top <?php 
    echo $isEnabledPhoneMask ? 'bxmaker-authuserphone-call-input--flag' : '';
    ?>">

                            <div class="bxmaker-authuserphone-call-input__flag"></div>

                            <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--forget_phone js-on-enter-continue" name="forget_phone" type="text"
                                  id="bxmaker-authuserphone-call__input-forget_phone<?php 
    echo $rand;
    ?>" autocomplete="off">

                            <label class="bxmaker-authuserphone-call-input__label"
                                   for="bxmaker-authuserphone-call__input-forget_phone<?php 
    echo $rand;
    ?>">
                                <span class="bxmaker-authuserphone-call-input__label-text"><?php 
    echo GetMessage($CPN . 'INPUT_PHONE');
    ?></span>
                            </label>

                        </div>
                    </div>

                    <?php 
    if ($arParams['IS_ENABLED_RESTORE_BY_EMAIL'] == 'Y') {
        ?>
                        <div class="bxmaker-authuserphone-call-row">
                            <div class="bxmaker-authuserphone-call-input bxmaker-authuserphone-call-input--top">
                                <input class="bxmaker-authuserphone-call-input__field bxmaker-authuserphone-call-input__field--forget_email js-on-enter-continue" name="forget_email" type="email"
                                       id="bxmaker-authuserphone-call__input-forget_email<?php 
        echo $rand;
        ?>" autocomplete="off">
                                <label class="bxmaker-authuserphone-call-input__label"
                                       for="bxmaker-authuserphone-call__input-forget_email<?php 
        echo $rand;
        ?>">
                                    <span class="bxmaker-authuserphone-call-input__label-text"><?php 
        echo GetMessage($CPN . 'INPUT_EMAIL');
        ?></span>
                                </label>

                                <div class="bxmaker-authuserphone-call__forget-or"><?php 
        echo GetMessage($CPN . 'OR');
        ?></div>
                            </div>
                        </div>
                    <?php 
    }
    ?>

                    <div class="bxmaker-authuserphone-call-captcha"></div>


                    <div class="bxmaker-authuserphone-call-row">
                        <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                            <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--button js-baup-forget-enter js-baup-continue"><?php 
    echo GetMessage($CPN . 'BTN_NEXT');
    ?></div>
                        </div>
                    </div>

                    <div class="bxmaker-authuserphone-call-btn__area bxmaker-authuserphone-call-btn__area--center">
                        <div class="bxmaker-authuserphone-call-btn bxmaker-authuserphone-call-btn--link js-baup-auth"><?php 
    echo GetMessage($CPN . 'BTN_AUTH');
    ?></div>
                    </div>

                </div>

            </div>

        <?php 
}
?>

        <script type="text/javascript" class="bxmaker-authuserphone-call-jsdata">
            <?php 
// component parameters
$signer = new \Bitrix\Main\Security\Sign\Signer();
$signedParameters = $signer->sign(base64_encode(serialize($arResult['_ORIGINAL_PARAMS'])));
$signedTemplate = $signer->sign($arResult['TEMPLATE']);
?>

            window.BXmakerAuthUserPhoneCallData = window.BXmakerAuthUserPhoneCallData || {};
            window.BXmakerAuthUserPhoneCallData["<?php 
echo $rand;
?>"] = <?php 
echo \Bitrix\Main\Web\Json::encode(['parameters' => $signedParameters, 'template' => $signedTemplate, 'siteId' => SITE_ID, 'consentShow' => $arParams['CONSENT_SHOW'], 'ajaxUrl' => $this->getComponent()->getPath() . '/ajax.php', 'rand' => $rand, 'confirmQueue' => $arParams['CONFIRM_QUEUE'], 'isEnabledConfirmBySmsCode' => $arParams['IS_ENABLED_CONFIRM_BY_SMS_CODE'] == 'Y', 'isEnabledConfirmByUserCall' => $arParams['IS_ENABLED_CONFIRM_BY_USER_CALL'] == 'Y', 'isEnabledConfirmByBotCall' => $arParams['IS_ENABLED_CONFIRM_BY_BOT_CALL'] == 'Y', 'isEnabledConfirmByBotSpeech' => $arParams['IS_ENABLED_CONFIRM_BY_BOT_SPEECH'] == 'Y', 'isEnabledRegister' => $arParams['IS_ENABLED_REGISTER'] == 'Y', 'phoneMaskParams' => $arParams['PHONE_MASK_PARAMS'], 'messages' => ['sms_code_request' => GetMessage($CPN . 'BTN_SMS_CODE_REQUEST'), 'sms_code_timeout' => GetMessage($CPN . 'SMS_CODE_TIMEOUT'), 'user_call_request' => GetMessage($CPN . 'USER_CALL_REQUEST'), 'user_call_timeout' => GetMessage($CPN . 'USER_CALL_TIMEOUT'), 'bot_call_request' => GetMessage($CPN . 'BOT_CALL_REQUEST'), 'bot_call_timeout' => GetMessage($CPN . 'BOT_CALL_TIMEOUT'), 'bot_speech_request' => GetMessage($CPN . 'BOT_SPEECH_REQUEST'), 'bot_speech_timeout' => GetMessage($CPN . 'BOT_SPEECH_TIMEOUT'), 'authTitle' => GetMessage($CPN . 'AUTH_TITLE'), 'registerTitle' => GetMessage($CPN . 'REG_TITLE'), 'forgetTitle' => GetMessage($CPN . 'FORGOT_TITLE'), 'smscodeTitle' => GetMessage($CPN . 'SMS_CODE_TITLE'), 'updateCaptcha' => GetMessage($CPN . 'UPDATE_CAPTCHA_IMAGE'), 'inputCaptchaWord' => GetMessage($CPN . 'INPUT_CAPTHCA_WORD')]]);
?>;
        </script>

        <?php 
$frame->beginStub();
?>
        <div class="bxmaker-authuserphone-call-loading"></div>
        <?php 
$frame->end();
?>

    </div>


<?php 