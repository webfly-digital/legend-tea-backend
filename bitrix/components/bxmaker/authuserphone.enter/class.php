<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc as Loc;
use BXmaker\AuthUserPhone\Exception\BaseException;
use BXmaker\AuthUserPhone\Manager;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
class BXmakerAuthUserPhoneEnterComponent extends \CBitrixComponent
{
    const EVENT_TYPE_AJAX = 'BXmakerAuthUserPhoneEnterComponentAjax';
    const EVENT_TYPE_AJAX_ANSWER = 'BXmakerAuthUserPhoneEnterComponentAjaxAnswer';
    private $oManager = null;
    private $oRequest = null;
    public function onPrepareComponentParams($arParams)
    {
        // подключаем модуль
        if (!\Bitrix\Main\Loader::includeModule('bxmaker.authuserphone')) {
            throw new \Bitrix\Main\LoaderException($this->getMessage('MODULE_NOT_INSTALLED'));
        }
        // для ajax
        $this->arResult['_ORIGINAL_PARAMS'] = $arParams;
        $arParams['RAND_STRING'] = isset($arParams['RAND_STRING']) ? $arParams['RAND_STRING'] : $this->randString();
        $arParams['IS_AJAX'] = $this->getParamBool($arParams, 'IS_AJAX', 'N');
        $arParams['IS_ENABLED_REQUEST_CONSENT'] = $this->getParamBool($arParams, 'IS_ENABLED_REQUEST_CONSENT', $this->manager()->param()->isEnabledRequestConsent() && !$this->manager()->isAuthorized() ? 'Y' : 'N');
        $arParams['REQUEST_CONSENT_ID'] = $this->getParamInt($arParams, 'CONSENT_ID', $this->manager()->param()->getRequestConsentId());
        $arParams['REQUEST_CONSENT_FIELDS'] = $arParams['REQUEST_CONSENT_FIELDS'] ?? null;
        $arParams['CONFIRM_QUEUE'] = isset($arParams['CONFIRM_QUEUE']) && !empty($arParams['CONFIRM_QUEUE']) ? $arParams['CONFIRM_QUEUE'] : $this->manager()->param()->getConfirmQueue();
        $arParams['IS_ENABLED_CONFIRM_BY_SMS_CODE'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_SMS_CODE', $this->manager()->param()->isEnabledConfirmBySmsCode() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_USER_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_USER_CALL', $this->manager()->param()->isEnabledConfirmByUserCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_BOT_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_BOT_CALL', $this->manager()->param()->isEnabledConfirmByBotCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_BOT_SPEECH'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_BOT_SPEECH', $this->manager()->param()->isEnabledConfirmByBotSpeech() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_SIM_PUSH'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_SIM_PUSH', $this->manager()->param()->isEnabledConfirmBySimPush() ? 'Y' : 'N');
        $arParams['USE_BXMAKER_AUTHUSEREMAIL'] = $this->getParamBool($arParams, 'USE_BXMAKER_AUTHUSEREMAIL', $this->manager()->param()->isEnabledUseBXmakerAuthUserEmail() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_EMAIL_CODE'] = $arParams['USE_BXMAKER_AUTHUSEREMAIL'] === 'Y' && \Bitrix\Main\Loader::includeModule('bxmaker.authuseremail');
        $arParams['IS_ENABLED_RELOAD_AFTER_AUTH'] = $this->getParamBool($arParams, 'IS_ENABLED_RELOAD_AFTER_AUTH', $this->manager()->param()->isEnabledReloadAfterAuth() ? 'Y' : 'N');
        $arParams['IS_ENABLED_AUTH_BY_PASSWORD_FIRST'] = $this->getParamBool($arParams, 'IS_ENABLED_AUTH_BY_PASSWORD_FIRST', $this->manager()->param()->isEnabledAuthByPasswordFirst() ? 'Y' : 'N');
        $arParams['IS_ENABLED_AUTH_BY_LOGIN'] = $this->getParamBool($arParams, 'IS_ENABLED_AUTH_BY_LOGIN', $this->manager()->param()->isEnabledAuthByLogin() ? 'Y' : 'N');
        $arParams['IS_ENABLED_AUTH_BY_EMAIL'] = $this->getParamBool($arParams, 'IS_ENABLED_AUTH_BY_EMAIL', $this->manager()->param()->isEnabledAuthByEmail() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_FIO'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_FIO', $this->manager()->param()->isEnabledRegisterFIO() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_FIO_REQUIRED'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_FIO_REQUIRED', $this->manager()->param()->isEnabledRegisterFIORequired() ? 'Y' : 'N');
        $arParams['REGISTER_FIO_DADATA'] = $this->getParamStr($arParams, 'REGISTER_FIO_DADATA', $this->manager()->param()->registerFIODadata());
        $arParams['IS_ENABLED_REGISTER_FIO_SPLIT'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_FIO_SPLIT', $this->manager()->param()->isEnabledRegisterFIOSplit() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTRATION_LAST_NAME'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTRATION_LAST_NAME', $this->manager()->param()->isEnabledRegisterLastName() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTRATION_LAST_NAME_REQUIRED'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTRATION_LAST_NAME_REQUIRED', $this->manager()->param()->isEnabledRegisterLastNameRequired() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTRATION_FIRST_NAME'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTRATION_FIRST_NAME', $this->manager()->param()->isEnabledRegisterFirstName() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTRATION_FIRST_NAME_REQUIRED'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTRATION_FIRST_NAME_REQUIRED', $this->manager()->param()->isEnabledRegisterFirstNameRequired() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTRATION_SECOND_NAME'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTRATION_SECOND_NAME', $this->manager()->param()->isEnabledRegisterSecondName() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTRATION_SECOND_NAME_REQUIRED'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTRATION_SECOND_NAME_REQUIRED', $this->manager()->param()->isEnabledRegisterSecondNameRequired() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_BIRTHDAY'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_BIRTHDAY', $this->manager()->param()->isEnabledRegisterBirthday() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_BIRTHDAY_REQUIRED'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_BIRTHDAY_REQUIRED', $this->manager()->param()->isEnabledRegisterBirthdayRequired() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_LOGIN'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_LOGIN', $this->manager()->param()->isEnabledRegisterLogin() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_LOGIN_REQUIRED'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_LOGIN_REQUIRED', $this->manager()->param()->isEnabledRegisterLoginRequired() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_EMAIL'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_EMAIL', $this->manager()->param()->isEnabledRegisterEmail() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_EMAIL_REQUIRED'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_EMAIL_REQUIRED', $this->manager()->param()->isEnabledRegisterEmailRequired() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_PASSWORD'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_PASSWORD', $this->manager()->param()->isEnabledRegisterPassword() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_PASSWORD_REQUIRED'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_PASSWORD_REQUIRED', $this->manager()->param()->isEnabledRegisterPasswordRequired() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REQUEST_ADS_AGREEMENT'] = $this->getParamBool($arParams, 'IS_ENABLED_REQUEST_ADS_AGREEMENT', $this->manager()->param()->isEnabledRegisterRequestAdsAgreement() ? 'Y' : 'N');
        $arParams['REQUEST_ADS_AGREEMENT_LABEL'] = $this->getParamBool($arParams, 'REQUEST_ADS_AGREEMENT_LABEL', $this->manager()->param()->getRegisterRequestAdsAgreementLabel());
        $arParams['IS_ENABLED_CHECK_USER_PROFILE_AFTER_AUTH'] = $this->getParamBool($arParams, 'IS_ENABLED_CHECK_USER_PROFILE_AFTER_AUTH', $this->manager()->param()->isEnabledCheckUserProfileAfterAuth() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CHECK_USER_PROFILE_AFTER_REGISTRATION'] = $this->getParamBool($arParams, 'IS_ENABLED_CHECK_USER_PROFILE_AFTER_REGISTRATION', $this->manager()->param()->isEnabledCheckUserProfileAfterRegisteration() ? 'Y' : 'N');
        $arParams['IS_ENABLED_AUTO_REGISTER'] = $this->getParamBool($arParams, 'IS_ENABLED_AUTO_REGISTER', $this->manager()->param()->isEnabledAutoRegister() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER', $this->manager()->param()->isEnabledRegister() ? 'Y' : 'N');
        $arParams['IS_ENABLED_RESTORE_BY_EMAIL'] = $this->getParamBool($arParams, 'IS_ENABLED_RESTORE_BY_EMAIL', $this->manager()->param()->isEnabledResotreByEmail() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CAPTCHA_FOR_RESTORE_BY_EMAIL'] = $this->getParamBool($arParams, 'IS_ENABLED_CAPTCHA_FOR_RESTORE_BY_EMAIL', $this->manager()->param()->isEnabledCheckCaptchaForResotreByEmail() ? 'Y' : 'N');
        $arParams['PHONE_MASK_PARAMS'] = isset($arParams['PHONE_MASK_PARAMS']) ? $arParams['PHONE_MASK_PARAMS'] : $this->manager()->param()->getPhoneMaskParams();
        return parent::onPrepareComponentParams($arParams);
    }
    /**
     * 
     * Подготовка паартра типа строка
     * 
     * @param $arParams
     * @param $name
     * @param string $defaultValue
     * 
     * @return string
     */
    private function getParamStr($arParams, $name, $defaultValue = '')
    {
        return isset($arParams[$name]) ? $arParams[$name] : $defaultValue;
    }
    /**
     * 
     * Подготовка парамтра int
     * 
     * @param $arParams
     * @param $name
     * @param int $defaultValue
     * 
     * @return int
     */
    private function getParamInt($arParams, $name, $defaultValue = 0)
    {
        return isset($arParams[$name]) && intval($arParams[$name]) > 0 ? intval($arParams[$name]) : $defaultValue;
    }
    /**
     * 
     * Подготовка параметра типа флаг
     * 
     * @param $arParams
     * @param $name
     * @param string $defaultValue
     * 
     * @return string
     */
    private function getParamBool($arParams, $name, $defaultValue = 'N')
    {
        return isset($arParams[$name]) && in_array($arParams[$name], ['N', 'Y']) > 0 ? $arParams[$name] : $defaultValue;
    }
    /**
     * 
     * @return \BXmaker\AuthUserPhone\Manager|null
     */
    public function manager()
    {
        if (is_null($this->oManager)) {
            $this->oManager = \BXmaker\AuthUserPhone\Manager::getInstance();
        }
        return $this->oManager;
    }
    /**
     * 
     * @return \Bitrix\Main\HttpRequest|\Bitrix\Main\Request
     */
    public function request()
    {
        if (is_null($this->oRequest)) {
            $this->oRequest = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        }
        return $this->oRequest;
    }
    /**
     * 
     * Вернет языкозависимое сообщение
     * 
     * @param $name
     * @param array $arReplace
     * 
     * @return string
     */
    public function getMessage($name, $arReplace = [])
    {
        return \Bitrix\Main\Localization\Loc::getMessage('BXMAKER.AUTHUSERPHONE.COMPONENT.ENTER.CLASS.' . $name, $arReplace);
    }
    public function executeComponent()
    {
        $this->setFrameMode(true);
        try {
            $this->arResult['IS_AUTHORIZED'] = isset($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized() ? 'Y' : 'N';
            // обработка ajax запросов --
            $this->ajaxHandler();
            $this->arResult['TEMPLATE'] = $this->getTemplateName();
            $this->includeComponentTemplate();
        } catch (\Exception $e) {
            ShowError($e->getMessage());
        }
        return parent::executeComponent();
    }
    public function ajaxHandler()
    {
        // AJAX
        $app = \Bitrix\Main\Application::getInstance();
        $req = $app->getContext()->getRequest();
        // обработка только ajax запросов
        if (!$req->isAjaxRequest() || $this->arParams['IS_AJAX'] != 'Y') {
            return true;
        }
        $oManager = \BXmaker\AuthUserPhone\Manager::getInstance();
        $oJsonResponse = new \BXmaker\AuthUserPhone\Ajax\JsonResponse();
        try {
            // вызов события
            $this->manager()->sendEvent(self::EVENT_TYPE_AJAX, ['jsonResponse' => $oJsonResponse, 'component' => $this]);
            if (!$req->getPost('method')) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_NEED_METHOD'), 'ERROR_NEED_METHOD');
            }
            if (!check_bitrix_sessid()) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_SESSID'), 'ERROR_INVALID_SESSID');
            }
            $method = $req->getPost('method');
            switch ($method) {
                case 'refreshCaptcha':
                    $oJsonResponse->setResponse($oManager->captcha()->getForJs());
                    break;
                case 'authByPassword':
                    $oJsonResponse->setResponse($this->authByPasswordAction());
                    break;
                case 'authByPhone':
                    $oJsonResponse->setResponse($this->authByPhoneAction());
                    break;
                case 'authByEmail':
                    $oJsonResponse->setResponse($this->authByEmailAction());
                    break;
                case 'startConfirm':
                    $oJsonResponse->setResponse($this->startConfirmAction());
                    break;
                case 'forget':
                    $oJsonResponse->setResponse($this->forgetAction());
                    break;
                case 'getConsent':
                    $oJsonResponse->setResponse($this->getConsentAction());
                    break;
                case 'register':
                    $oJsonResponse->setResponse($this->registerAction());
                    break;
                default:
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_UNDEFINED_METHOD'), 'ERROR_UNDEFINED_METHOD');
            }
        } catch (\BXmaker\AuthUserPhone\Exception\BaseException $ex) {
            if ($ex->getCustomCode() === 'ERROR_INVALID_SESSID') {
                // $ex->setCustomDataItem('sessid', bitrix_sessid());
            }
            $oJsonResponse->setException($ex);
        } catch (\Throwable $ex) {
            $oJsonResponse->setException($ex);
        }
        // вызов события
        $this->manager()->sendEvent(self::EVENT_TYPE_AJAX_ANSWER, ['jsonResponse' => $oJsonResponse, 'component' => $this]);
        $oJsonResponse->output();
    }
    /**
     * 
     * В случае если номер телефона указан не валидный, выбросит исключение
     * 
     * @param $phone
     * 
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    private function checkPhone($phone)
    {
        if (!$this->manager()->isValidPhone($phone)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_PHONE'), 'ERROR_INVALID_PHONE');
        }
    }
    /**
     * 
     * В случае если тип подтвреждения указан не валидный, выбросит исключение
     * 
     * @param $type
     * 
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    private function checkType($type)
    {
        $ar = $this->getAvailableConfirmList();
        if (!in_array($type, $ar)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_CONFIRM_TYPE'), 'ERROR_INVALID_CONFIRM_TYPE');
        }
    }
    /**
     * 
     * Вернет массив доступный вараинтов подтверждения
     * 
     * @return string[]
     */
    private function getAvailableConfirmList()
    {
        $ar = $this->arParams['CONFIRM_QUEUE'];
        $arDelete = [];
        if ($this->arParams['IS_ENABLED_CONFIRM_BY_SMS_CODE'] != 'Y') {
            $arDelete[] = \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE;
        }
        if ($this->arParams['IS_ENABLED_CONFIRM_BY_USER_CALL'] != 'Y') {
            $arDelete[] = \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL;
        }
        if ($this->arParams['IS_ENABLED_CONFIRM_BY_BOT_CALL'] != 'Y') {
            $arDelete[] = \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL;
        }
        if ($this->arParams['IS_ENABLED_CONFIRM_BY_BOT_SPEECH'] != 'Y') {
            $arDelete[] = \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH;
        }
        if ($this->arParams['IS_ENABLED_CONFIRM_BY_SIM_PUSH'] != 'Y') {
            $arDelete[] = \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH;
        }
        // добавляем
        if ($this->arParams['IS_ENABLED_CONFIRM_BY_EMAIL_CODE'] == 'Y') {
            $ar[] = \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_EMAIL_CODE;
        }
        $ar = array_diff($ar, $arDelete);
        if (empty($ar)) {
            $ar[] = \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE;
        }
        return $ar;
    }
    /**
     * 
     * Выполнит необходимые проверки и начнет проверку номера,
     * иначе выбросит исключение
     * 
     * @return array
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function startConfirmAction()
    {
        $arReturn = [];
        $email = trim((string) $this->request()->getPost('email'));
        $phone = (string) $this->request()->getPost('phone');
        $phone = $this->manager()->getPreparedPhone($phone);
        $formattedPhone = $this->formattedPhone($phone);
        if (\CModule::IncludeModuleEx("bxmaker.auth" . "u" . "s" . "" . "" . "erphon" . "" . "e") == constant("MODULE_DE" . "MO" . "_EXPI" . "RED")) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException(\Bitrix\Main\Localization\Loc::getMessage("BXMAKER.AUTHUSERPHONE.DEMO" . "_EX" . "PIRE" . "D"), "BXMAKER_" . "DEMO_EXPIR" . "" . "" . "" . "E" . "" . "" . "" . "" . "" . "" . "" . "D");
        }
        try {
            $type = (int) $this->request()->getPost('confirmType');
            $this->checkType($type);
            switch ($type) {
                case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE:
                    $this->checkPhone($phone);
                    $arReturn = $this->startConfirmActionSmsCode($phone);
                    break;
                case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL:
                    $this->checkPhone($phone);
                    $arReturn = $this->startConfirmActionUserCall($phone);
                    break;
                case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL:
                    $this->checkPhone($phone);
                    $arReturn = $this->startConfirmActionBotCall($phone);
                    break;
                case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH:
                    $this->checkPhone($phone);
                    $arReturn = $this->startConfirmActionBotSpeech($phone);
                    break;
                case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH:
                    $this->checkPhone($phone);
                    $arReturn = $this->startConfirmActionSimPush($phone);
                    break;
                case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_EMAIL_CODE:
                    $arReturn = $this->startConfirmActionEmailCode($email);
                    break;
            }
        } catch (\BXmaker\AuthUserPhone\Exception\BaseException $ex) {
            $ex->setCustomDataItem('formattedPhone', $formattedPhone);
            throw $ex;
        }
        // дополним
        $arReturn['formattedPhone'] = $formattedPhone;
        return $arReturn;
    }
    /**
     * 
     * Старт подтверждения чере смс код
     * 
     * @param $phone
     * 
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function startConfirmActionSmsCode($phone)
    {
        // проверка таймаута сначала, чтобы не фиксировать попытки, когда время еще не вышло
        $this->manager()->checkSmsCodeTimeout($phone);
        // проверка лимитов и captcha по ip
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoRequest();
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        // отправляем код
        $result = $this->manager()->service()->startSmsCode($phone);
        if (!$result->isSuccess()) {
            $result->throwException();
        }
        $arReturn = ['msg' => $result->getMore('MSG'), 'length' => $result->getMore('LENGTH'), 'timeout' => $result->getMore('TIMEOUT')];
        return $arReturn;
    }
    /**
     * 
     * Старт подтверждения через звонок от пользователя
     * 
     * @param $phone
     * 
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function startConfirmActionUserCall($phone)
    {
        // проверка таймаута сначала, чтобы не фиксировать попытки, когда время еще не вышло
        $this->manager()->checkUserCallTimeout($phone);
        // проверка лимитов и captcha по ip
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL)->checkCanDoRequest();
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        // отправляем код
        $result = $this->manager()->service()->startUserCall($phone);
        if (!$result->isSuccess()) {
            $result->throwException();
        }
        $arReturn = ['msg' => $result->getMore('MSG'), 'callTo' => $result->getMore('CALL_TO'), 'timeout' => $result->getMore('TIMEOUT')];
        return $arReturn;
    }
    /**
     * 
     * Старт подтверждения через звонок от бота
     * 
     * @param $phone
     * 
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function startConfirmActionBotCall($phone)
    {
        // проверка таймаута сначала, чтобы не фиксировать попытки, когда время еще не вышло
        $this->manager()->checkBotCallTimeout($phone);
        // проверка лимитов и captcha по ip
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL)->checkCanDoRequest();
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        // отправляем код
        $result = $this->manager()->service()->startBotCall($phone);
        if (!$result->isSuccess()) {
            $result->throwException();
        }
        $arReturn = ['msg' => $result->getMore('MSG'), 'length' => $result->getMore('LENGTH'), 'timeout' => $this->manager()->getBotCallTimeout($phone)];
        return $arReturn;
    }
    /**
     * 
     * Старт подтверждения через голосовой код
     * 
     * @param $phone
     * 
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function startConfirmActionBotSpeech($phone)
    {
        // проверка таймаута сначала, чтобы не фиксировать попытки, когда время еще не вышло
        $this->manager()->checkBotSpeechTimeout($phone);
        // проверка лимитов и captcha по ip
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH)->checkCanDoRequest();
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        // отправляем код
        $result = $this->manager()->service()->startBotSpeech($phone);
        if (!$result->isSuccess()) {
            $result->throwException();
        }
        $arReturn = ['msg' => $result->getMore('MSG'), 'length' => $result->getMore('LENGTH'), 'timeout' => $this->manager()->getBotSpeechTimeout($phone)];
        return $arReturn;
    }
    /**
     * 
     * Старт подтверждения через SIM-push
     * 
     * @param $phone
     * 
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function startConfirmActionSimPush($phone)
    {
        // проверка таймаута сначала, чтобы не фиксировать попытки, когда время еще не вышло
        $this->manager()->checkSimPushTimeout($phone);
        // проверка лимитов и captcha по ip
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH)->checkCanDoRequest();
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        // отправляем код
        $result = $this->manager()->service()->startSimPush($phone);
        if (!$result->isSuccess()) {
            $result->throwException();
        }
        $arReturn = ['msg' => $result->getMore('MSG'), 'delay' => $result->getMore('DELAY'), 'timeout' => $this->manager()->getSimPushTimeout($phone)];
        return $arReturn;
    }
    /**
     * 
     * Форматирвоание номера который проверяем для вывода в публичной части
     * 
     * @param $phone
     * 
     * @return string
     */
    public function formattedPhone($phone)
    {
        return $this->manager()->format()->international($phone);
    }
    /**
     * 
     * Проверит пришло ли подтверждение номера телефона,
     * если пришло подтверждение, то проверит его и в случае не валидности выбросит исключение,
     * если не пришло подтвреждение, то выбросит исключение о необходимости подтверждения
     * 
     * @param $phone
     * 
     * @return void
     * 
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\IO\FileNotFoundException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     */
    public function checkConfirm($phone)
    {
        $oService = \BXmaker\AuthUserPhone\Service::getInstance();
        // проверка прошла успешно
        $type = (int) $this->request()->getPost('confirmType');
        $value = trim((string) $this->request()->getPost('confirmValue'));
        // проверка типа подтверждения
        switch ($type) {
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE:
                if (!$this->manager()->param()->isEnabledConfirmBySmsCode()) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_CONFIRM_TYPE'), 'ERROR_CONFIRM_TYPE');
                }
                $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck();
                $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck();
                // проверяем заполненость
                if (empty($value)) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_NEED_CONFIRM'), 'ERROR_NEED_CONFIRM');
                }
                $this->manager()->limitIP()->setCheck();
                $this->manager()->limit()->setCheck();
                $result = $oService->checkSmsCode($phone, $value);
                if (!$result->isSuccess()) {
                    $result->throwException();
                }
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL:
                if (!$this->manager()->param()->isEnabledConfirmByUserCall()) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_CONFIRM_TYPE'), 'ERROR_CONFIRM_TYPE');
                }
                $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL)->checkCanDoCheck();
                $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL)->checkCanDoCheck();
                // проверяем заполненость
                if (empty($value)) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_NEED_CONFIRM'), 'ERROR_NEED_CONFIRM');
                }
                $this->manager()->limitIP()->setCheck();
                $this->manager()->limit()->setCheck();
                $result = $oService->checkUserCall($phone, $value);
                if (!$result->isSuccess()) {
                    $result->throwException();
                }
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL:
                if (!$this->manager()->param()->isEnabledConfirmByBotCall()) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_CONFIRM_TYPE'), 'ERROR_CONFIRM_TYPE');
                }
                $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL)->checkCanDoCheck();
                $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL)->checkCanDoCheck();
                // проверяем заполненость
                if (empty($value)) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_NEED_CONFIRM'), 'ERROR_NEED_CONFIRM');
                }
                $this->manager()->limitIP()->setCheck();
                $this->manager()->limit()->setCheck();
                $result = $oService->checkBotCall($phone, $value);
                if (!$result->isSuccess()) {
                    $result->throwException();
                }
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH:
                if (!$this->manager()->param()->isEnabledConfirmByBotSpeech()) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_CONFIRM_TYPE'), 'ERROR_CONFIRM_TYPE');
                }
                $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH)->checkCanDoCheck();
                $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH)->checkCanDoCheck();
                // проверяем заполненость
                if (empty($value)) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_NEED_CONFIRM'), 'ERROR_NEED_CONFIRM');
                }
                $this->manager()->limitIP()->setCheck();
                $this->manager()->limit()->setCheck();
                $result = $oService->checkBotSpeech($phone, $value);
                if (!$result->isSuccess()) {
                    $result->throwException();
                }
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH:
                if (!$this->manager()->param()->isEnabledConfirmBySimPush()) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_CONFIRM_TYPE'), 'ERROR_CONFIRM_TYPE');
                }
                $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH)->checkCanDoCheck();
                $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH)->checkCanDoCheck();
                // проверяем заполненость
                if (empty($value)) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_NEED_CONFIRM'), 'ERROR_NEED_CONFIRM');
                }
                $this->manager()->limitIP()->setCheck();
                $this->manager()->limit()->setCheck();
                $result = $oService->checkSimPush($phone);
                if (!$result->isSuccess()) {
                    $result->throwException();
                }
                break;
            default:
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_NEED_CONFIRM'), 'ERROR_NEED_CONFIRM');
        }
    }
    /**
     * 
     * Расширить объект ответа на авторизацию и регистрацию
     * 
     * @param $userId
     * @param $arResponse
     * 
     * @return mixed
     */
    public function extendResponseAfterAuth($userId, $arResponse)
    {
        // переадресация пользователя для заполнения профиля
        if ($this->arParams['IS_ENABLED_CHECK_USER_PROFILE_AFTER_AUTH'] == 'Y' && !$this->manager()->isSetUserRegisterFlag() && $this->manager()->isNeedFillUserProfile($userId)) {
            $arResponse['redirect'] = $this->manager()->getUserProfileUrl();
        }
        if ($this->arParams['IS_ENABLED_CHECK_USER_PROFILE_AFTER_REGISTRATION'] == 'Y' && $this->manager()->isSetUserRegisterFlag() && $this->manager()->isNeedFillUserProfile($userId)) {
            $arResponse['redirect'] = $this->manager()->getUserProfileUrl();
        }
        if ($this->arParams['IS_ENABLED_RELOAD_AFTER_AUTH'] == 'Y') {
            $arResponse['reload'] = true;
        }
        return $arResponse;
    }
    /**
     * 
     * Выполнит попытку авторизовтаь польвазоетля по паролю или выбросит исключени с ошибкой, вернет результат для ajax
     * ответа
     * 
     * @return string[]
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\Db\SqlQueryException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     */
    public function authByPasswordAction()
    {
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_BY_PASSWORD.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $ple = trim((string) $this->request()->getPost('ple'));
        if (empty($ple)) {
            $arText = [$this->getMessage('AUTH_BY_PASSWORD.PHONE')];
            if ($this->arParams['IS_ENABLED_AUTH_BY_LOGIN'] == 'Y') {
                $arText[] = $this->getMessage('AUTH_BY_PASSWORD.LOGIN');
            }
            if ($this->arParams['IS_ENABLED_AUTH_BY_EMAIL'] == 'Y') {
                $arText[] = $this->getMessage('AUTH_BY_PASSWORD.EMAIL');
            }
            if (count($arText) > 1) {
                $text = implode(', ', array_slice($arText, 0, -1));
                $text .= ' ' . $this->getMessage('AUTH_BY_PASSWORD.OR') . ' ';
                $text .= end($arText);
            } else {
                $text = implode(', ', $arText);
            }
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_BY_PASSWORD.ERROR_PLE', ['#TEXT#' => $text]), 'ERROR_INVALID_PLE');
        }
        $password = trim((string) $this->request()->getPost('password'));
        if (empty($password)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_BY_PASSWORD.ERROR_PASSWORD'), 'ERROR_PASSWORD');
        }
        $userId = null;
        $phone = $this->manager()->getPreparedPhone($ple);
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck();
        if ($this->manager()->isValidPhone($phone)) {
            // проверяем смс код
            $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck();
            $this->manager()->limit()->setCheck();
        }
        // отмечаем попытку проверки
        $this->manager()->limitIP()->setCheck();
        // ищем пользователя - номер + пароль
        if ($this->manager()->isValidPhone($phone)) {
            $userIdResult = $this->manager()->findUserIdByPhonePassword($phone, $password);
            if ($userIdResult->isSuccess()) {
                $userId = (int) $userIdResult->getResult();
            }
        }
        // ищем пользователя по email адресу
        if ($this->arParams['IS_ENABLED_AUTH_BY_EMAIL'] == 'Y' && is_null($userId) && $this->manager()->isValidEmail($ple)) {
            $userIdResult = $this->manager()->findUserIdByEmailPassword($ple, $password);
            if ($userIdResult->isSuccess()) {
                $userId = (int) $userIdResult->getResult();
            }
        }
        // ищем пользователя по логину
        if ($this->arParams['IS_ENABLED_AUTH_BY_LOGIN'] == 'Y' && is_null($userId)) {
            $userIdResult = $this->manager()->findUserIdByLoginPassword($ple, $password);
            if ($userIdResult->isSuccess()) {
                $userId = (int) $userIdResult->getResult();
            }
        }
        // ищем в принципе аккаунт без проверки пароля или кода,
        // если его нету, то в завивсимоти от настроек попробуем создать
        // либо вренем ошибку что не верны данные
        if (is_null($userId)) {
            $userIdResult = $this->manager()->findUserIdByPLE($ple, true, $this->arParams['IS_ENABLED_AUTH_BY_LOGIN'], $this->arParams['IS_ENABLED_AUTH_BY_EMAIL']);
            if ($userIdResult->isSuccess()) {
                $arList = [$this->getMessage('AUTH_BY_PASSWORD.PHONE')];
                if ($this->arParams['IS_ENABLED_AUTH_BY_LOGIN'] == 'Y') {
                    $arList[] = $this->getMessage('AUTH_BY_PASSWORD.LOGIN');
                }
                if ($this->arParams['IS_ENABLED_AUTH_BY_EMAIL'] == 'Y') {
                    $arList[] = $this->getMessage('AUTH_BY_PASSWORD.EMAIL');
                }
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_BY_PASSWORD.ERROR_USER_PLE_PASSWORD', ['#TEXT#' => implode(', ', $arList)]), 'ERROR_USER_PLE_PASSWORD');
            }
        }
        // если не найден в принципе акканут
        if (is_null($userId)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_BY_PASSWORD.USER_NOT_FOUND'), 'USER_NOT_FOUND');
        }
        // авторизация
        $resultAuth = $this->manager()->authorize($userId);
        if (!$resultAuth->isSuccess()) {
            $resultAuth->throwException();
        }
        $arResponse = $this->extendResponseAfterAuth($userId, ['msg' => $this->getMessage('AUTH_BY_PASSWORD.AUTH_OK'), 'type' => $this->manager()->isSetUserRegisterFlag() ? 'REG' : 'AUTH']);
        return $arResponse;
    }
    /**
     * 
     * Выполнит попытку авторизовтаь польвазоетля по паролю или выбросит исключени с ошибкой, вернет результат для ajax
     * ответа
     * 
     * @return string[]
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\Db\SqlQueryException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     */
    public function authByPhoneAction()
    {
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_BY_PHONE.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $phone = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($phone);
        $this->checkPhone($phone);
        // если нет авторегистрации, то проверяем на существовани пользователя
        if ($this->arParams['IS_ENABLED_AUTO_REGISTER'] != 'Y') {
            // и пользователь не найден
            $userIdResult = $this->manager()->findUserIdByPhone($phone, false);
            if (!$userIdResult->isSuccess()) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_BY_PHONE.ERROR_USER_NOT_FOUND'), 'ERROR_USER_NOT_FOUND');
            }
        }
        // проверяем подтвержден ли номер
        $this->checkConfirm($phone);
        $userId = null;
        // ищем пользователя активного
        $userIdResult = $this->manager()->findUserIdByPhone($phone, true);
        if ($userIdResult->isSuccess()) {
            // если найден активный
            $userId = (int) $userIdResult->getResult();
        } else {
            $userIdResult = $this->manager()->findUserIdByPhone($phone, false);
            if ($userIdResult->isSuccess()) {
                // сообщаем что не активен, так как наличие акаунта с таким номером проверено выше
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_BY_PHONE.ERROR_USER_IS_NOT_ACTIVE'), 'ERROR_USER_IS_NOT_ACTIVE');
            }
        }
        if (is_null($userId)) {
            $registerResult = $this->manager()->register($phone);
            if (!$registerResult->isSuccess()) {
                $registerResult->throwException();
            }
            $userId = (int) $registerResult->getResult();
            // отправка писем
            $this->manager()->sendEmailNewUser($userId);
            if ($this->manager()->param()->isEnabledRegistrationSendUserInfo()) {
                $this->manager()->sendEmailUserInfo($userId);
            }
            if ($this->manager()->param()->isEnabledRegistrationEmailConformation()) {
                $this->manager()->sendEmailNewUserConfirm($userId);
            }
        }
        // авторизация
        $resultAuth = $this->manager()->authorize($userId);
        if (!$resultAuth->isSuccess()) {
            $resultAuth->throwException();
        }
        $arResponse = $this->extendResponseAfterAuth($userId, ['msg' => $this->getMessage('AUTH_BY_PHONE.AUTH_OK'), 'type' => $this->manager()->isSetUserRegisterFlag() ? 'REG' : 'AUTH']);
        return $arResponse;
    }
    /**
     * 
     * Выполнить отправку на email временной ссылки для входа без аутентификации
     * 
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     */
    public function forgetAction()
    {
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('FORGET_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $phone = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($phone);
        $email = trim((string) $this->request()->getPost('email'));
        if ($this->arParams['IS_ENABLED_RESTORE_BY_EMAIL'] == 'Y' && $this->manager()->isValidEmail($email)) {
            // проверка капчи
            if ($this->arParams['IS_ENABLED_CAPTCHA_FOR_RESTORE_BY_EMAIL'] == 'Y') {
                $this->manager()->captcha()->checkOnRequest();
            }
            $sendResult = $this->manager()->sendRestoreEmailByPLE($email, false, false);
            if (!$sendResult->isSuccess()) {
                $sendResult->throwException();
            }
            return ['msg' => $sendResult->getMore('MSG')];
        }
        if (empty($phone) || !$this->manager()->isValidPhone($phone)) {
            if ($this->arParams['IS_ENABLED_RESTORE_BY_EMAIL'] == 'Y') {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('FORGET_ACTION.ERROR_PHONE_EMAIL_IS_WRONG'), 'ERROR_PHONE_EMAIL_IS_WRONG');
            } else {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('FORGET_ACTION.ERROR_PHONE_IS_WRONG'), 'ERROR_PHONE_IS_WRONG');
            }
        }
        // ищем пользователя
        $userIdResult = $this->manager()->findUserIdByPhone($phone, false);
        if (!$userIdResult->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('FORGET_ACTION.ERROR_USER_NOT_FOUND'), 'ERROR_USER_NOT_FOUND');
        }
        $this->checkConfirm($phone);
        // ищем пользователя
        $userIdResult = $this->manager()->findUserIdByPhone($phone, true);
        if (!$userIdResult->isSuccess()) {
            // сообщаем что не активен, так как наличие акаунта с таким номером проверено выше
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('FORGET_ACTION.ERROR_USER_IS_NOT_ACTIVE'), 'ERROR_USER_IS_NOT_ACTIVE');
        }
        $userId = (int) $userIdResult->getResult();
        // авторизация
        $resultAuth = $this->manager()->authorize($userId);
        if (!$resultAuth->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($resultAuth->getFirstError()->getMessage(), $resultAuth->getFirstError()->getCode());
        }
        // смена пароля после входя по временному коду
        if ($this->manager()->param()->isEnabledChangePasswordAfterAuthByTemporaryCode()) {
            // првоерка что пользователь НЕ зарегистрирвоан в рамках данного запроса
            if (!$this->manager()->isSetUserRegisterFlag()) {
                // смнеа пароля на произвольный
                $this->manager()->setPassword($userId);
            }
        }
        $arResponse = $this->extendResponseAfterAuth($userId, ['msg' => $this->getMessage('FORGET_ACTION.AUTH_OK'), 'type' => $this->manager()->isSetUserRegisterFlag() ? 'REG' : 'AUTH', 'isAuthorized' => true]);
        return $arResponse;
    }
    /**
     * 
     * Выполнить регистрацию пользователя
     * 
     * @return array
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function registerAction()
    {
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        if ($this->arParams['IS_ENABLED_REGISTER'] != 'Y') {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_IS_DISABLED'), 'ERROR_REGISTRATION_IS_DISABLED');
        }
        $arUserFields = $this->prepareFioForRegistration();
        // если нужно апрашивать логин
        $birthday = trim((string) $this->request()->getPost('birthday'));
        if ($this->arParams['IS_ENABLED_REGISTER_BIRTHDAY'] == 'Y' && $this->arParams['IS_ENABLED_REGISTER_BIRTHDAY_REQUIRED'] == 'Y' && empty($birthday)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTER_BIRTHDAY_REQUIRED'), 'ERROR_REGISTER_BIRTHDAY_REQUIRED');
        }
        if (!empty($birthday) && !preg_match('#^\\d\\d\\.\\d\\d\\.\\d\\d\\d\\d$#', $birthday)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTER_BIRTHDAY_WRONG'), 'ERROR_REGISTER_BIRTHDAY_WRONG');
        }
        if ($this->arParams['IS_ENABLED_REGISTER_BIRTHDAY'] == 'Y' && !empty($birthday)) {
            $arUserFields['PERSONAL_BIRTHDAY'] = $birthday;
        }
        // phone ----------
        $phone = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($phone);
        $this->checkPhone($phone);
        if ($this->manager()->isExistPhone($phone)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_PHONE_USED'), 'ERROR_PHONE_USED');
        }
        // если нужно апрашивать логин
        $login = trim((string) $this->request()->getPost('login'));
        if ($this->arParams['IS_ENABLED_REGISTER_LOGIN'] == 'Y' && $this->arParams['IS_ENABLED_REGISTER_LOGIN_REQUIRED'] == 'Y' && empty($login)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_LOGIN_REQUIRED'), 'ERROR_REGISTRATION_LOGIN_REQUIRED');
        }
        if ($this->arParams['IS_ENABLED_REGISTER_LOGIN'] == 'Y' && !empty($login)) {
            if ($this->manager()->isExistLogin($login)) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_LOGIN_USED'), 'ERROR_LOGIN_USED');
            }
            $arUserFields['LOGIN'] = $login;
        }
        // если нужно запрашивать email
        $email = trim((string) $this->request()->getPost('email'));
        if ($this->arParams['IS_ENABLED_REGISTER_EMAIL'] == 'Y' && $this->arParams['IS_ENABLED_REGISTER_EMAIL_REQUIRED'] == 'Y' && empty($email)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_EMAIL_REQUIRED'), 'ERROR_REGISTRATION_EMAIL_REQUIRED');
        }
        if ($this->arParams['IS_ENABLED_REGISTER_EMAIL'] == 'Y' && !empty($email)) {
            if (!$this->manager()->isValidEmail($email)) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_EMAIL_NOT_SET'), 'ERROR_EMAIL_NOT_SET');
            }
            if ($this->manager()->param()->isEnabledUniqueEmail() && $this->manager()->isExistEmail($email)) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_EMAIL_USED'), 'ERROR_EMAIL_USED');
            }
            $arUserFields['EMAIL'] = $email;
        }
        // пароль
        $password = trim((string) $this->request()->getPost('password'));
        if ($this->arParams['IS_ENABLED_REGISTER_PASSWORD'] == 'Y' && $this->arParams['IS_ENABLED_REGISTER_PASSWORD_REQUIRED'] == 'Y' && empty($password)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_PASSWORD_REQUIRED'), 'ERROR_REGISTRATION_PASSWORD_REQUIRED');
        }
        if ($this->arParams['IS_ENABLED_REGISTER_PASSWORD'] == 'Y' && !empty($password)) {
            // проверка длины пароля
            $resultCheckPassword = $this->manager()->checkNewPassword($password, $this->manager()->getUserDefaultGroupIds());
            if (!$resultCheckPassword->isSuccess()) {
                $resultCheckPassword->throwException();
            }
            $arUserFields['PASSWORD'] = $password;
            $arUserFields['CONFIRM_PASSWORD'] = $password;
        }
        if ($this->arParams['IS_ENABLED_REQUEST_ADS_AGREEMENT'] == 'Y' && (string) $this->request()->getPost('adsAgreement') === 'true') {
            $arUserFields[\BXmaker\AuthUserPhone\Manager::ADS_AGREEMENT_USER_FIELD_CODE] = 'Y';
        }
        // проверка кода
        $this->checkConfirm($phone);
        $registerResult = $this->manager()->register($phone, $arUserFields);
        if (!$registerResult->isSuccess()) {
            $registerResult->throwException();
        }
        $userId = (int) $registerResult->getResult();
        // сохраняем согласие
        if ($this->arParams['IS_ENABLED_REQUEST_CONSENT'] == 'Y' && $this->arParams['REQUEST_CONSENT_ID']) {
            $this->manager()->saveConsent($userId, $this->arParams['REQUEST_CONSENT_ID'], (string) $this->request()->getPost('consentUrl'));
        }
        // отправка писем
        $this->manager()->sendEmailNewUser($userId);
        if ($this->manager()->param()->isEnabledRegistrationSendUserInfo()) {
            $this->manager()->sendEmailUserInfo($userId);
        }
        if ($this->manager()->param()->isEnabledRegistrationEmailConformation()) {
            $this->manager()->sendEmailNewUserConfirm($userId);
        }
        $authorizeResult = $this->manager()->authorize($userId);
        if (!$authorizeResult->isSuccess()) {
            $authorizeResult->throwException();
        }
        $arResponse = $this->extendResponseAfterAuth($userId, ['msg' => $this->getMessage('REGISTER_ACTION.AUTH_OK'), 'type' => 'REG']);
        return $arResponse;
    }
    public function prepareFioForRegistration()
    {
        $arUserFields = [];
        if ($this->arParams['IS_ENABLED_REGISTER_FIO_SPLIT'] === 'Y') {
            $lastName = trim((string) $this->request()->getPost('lastName'));
            $firstName = trim((string) $this->request()->getPost('firstName'));
            $secondName = trim((string) $this->request()->getPost('secondName'));
            if ($this->arParams['IS_ENABLED_REGISTRATION_LAST_NAME'] == 'Y' && $this->arParams['IS_ENABLED_REGISTRATION_LAST_NAME_REQUIRED'] == 'Y' && strlen($lastName) < 2) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_LAST_NAME_REQUIRED'), 'ERROR_REGISTRATION_LAST_NAME_REQUIRED');
            }
            if ($this->arParams['IS_ENABLED_REGISTRATION_FIRST_NAME'] == 'Y' && $this->arParams['IS_ENABLED_REGISTRATION_FIRST_NAME_REQUIRED'] == 'Y' && strlen($firstName) < 2) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_FIRST_NAME_REQUIRED'), 'ERROR_REGISTRATION_FIRST_NAME_REQUIRED');
            }
            if ($this->arParams['IS_ENABLED_REGISTRATION_SECOND_NAME'] == 'Y' && $this->arParams['IS_ENABLED_REGISTRATION_SECOND_NAME_REQUIRED'] == 'Y' && strlen($secondName) < 2) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_SECOND_NAME_REQUIRED'), 'ERROR_REGISTRATION_SECOND_NAME_REQUIRED');
            }
            $arUserFields['LAST_NAME'] = $lastName;
            $arUserFields['NAME'] = $firstName;
            $arUserFields['SECOND_NAME'] = $secondName;
        } else {
            // если нужно апрашивать логин
            $fio = trim((string) $this->request()->getPost('fio'));
            if ($this->arParams['IS_ENABLED_REGISTER_FIO'] == 'Y' && $this->arParams['IS_ENABLED_REGISTER_FIO_REQUIRED'] == 'Y' && empty($fio)) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_FIO_REQUIRED'), 'ERROR_REGISTRATION_FIO_REQUIRED');
            }
            if ($this->arParams['IS_ENABLED_REGISTER_FIO'] == 'Y' && !empty($fio)) {
                $arParts = explode(' ', (string) $fio);
                $arParts = array_diff($arParts, ['']);
                if (count($arParts) > 3) {
                    $arUserFields['LAST_NAME'] = $arParts[0];
                    $arUserFields['NAME'] = $arParts[1];
                    $arUserFields['SECOND_NAME'] = $arParts[2];
                } elseif (count($arParts) == 3) {
                    $arUserFields['LAST_NAME'] = $arParts[0];
                    $arUserFields['NAME'] = $arParts[1];
                    $arUserFields['SECOND_NAME'] = $arParts[2];
                } elseif (count($arParts) == 2) {
                    $arUserFields['LAST_NAME'] = $arParts[0];
                    $arUserFields['NAME'] = $arParts[1];
                } elseif (count($arParts) == 1) {
                    $arUserFields['NAME'] = $arParts[0];
                }
            }
            $dadataItem = (array) $this->request()->getPost('dadataItem');
            if (!empty($dadataItem['data'])) {
                $arUserFields['LAST_NAME'] = (string) $dadataItem['data']['surname'];
                $arUserFields['NAME'] = (string) $dadataItem['data']['name'];
                $arUserFields['SECOND_NAME'] = (string) $dadataItem['data']['patronymic'];
                if ($dadataItem['data']['gender'] === 'FEMALE') {
                    $arUserFields['PERSONAL_GENDER'] = 'F';
                } elseif ($dadataItem['data']['gender'] === 'MALE') {
                    $arUserFields['PERSONAL_GENDER'] = 'M';
                }
            }
        }
        return $arUserFields;
    }
    /**
     * 
     * Вернет текст соглашения
     * 
     * @return array
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function getConsentAction()
    {
        if ($this->arParams['IS_ENABLED_REQUEST_CONSENT'] != 'Y' || intval($this->arParams['REQUEST_CONSENT_ID']) <= 0) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('GET_CONSENT.ERROR_NOT_AVAILABLE'), 'ERROR_NOT_AVAILABLE');
        }
        $agreement = new \Bitrix\Main\UserConsent\Agreement($this->arParams['REQUEST_CONSENT_ID']);
        if (!$agreement->isExist() || !$agreement->isActive()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('GET_CONSENT.ERROR_NOT_ACTIVE'), 'ERROR_NOT_AVAILABLE');
        }
        if (is_array($this->arParams['REQUEST_CONSENT_FIELDS'])) {
            $arFields = $this->arParams['REQUEST_CONSENT_FIELDS'];
        } else {
            $arFields = [$this->getMessage('GET_CONSENT.CONSENT_PHONE')];
            if ($this->arParams['IS_ENABLED_REGISTER_LOGIN'] == 'Y') {
                $arFields[] = $this->getMessage('GET_CONSENT.CONSENT_LOGIN');
            }
            if ($this->arParams['IS_ENABLED_REGISTER_EMAIL'] == 'Y') {
                $arFields[] = $this->getMessage('GET_CONSENT.CONSENT_EMAIL');
            }
            if ($this->arParams['IS_ENABLED_REGISTER_PASSWORD'] == 'Y') {
                $arFields[] = $this->getMessage('GET_CONSENT.CONSENT_PASSWORD');
            }
            if ($this->arParams['IS_ENABLED_REGISTER_BIRTHDAY'] == 'Y') {
                $arFields[] = $this->getMessage('GET_CONSENT.CONSENT_BIRTHDAY');
            }
        }
        $agreement->setReplace(['button_caption' => $this->getMessage('GET_CONSENT.BTN_REGISTER'), 'fields' => $arFields]);
        $arResponse = ['html' => $agreement->getHtml(), 'label' => $agreement->getLabelText(), 'url' => $agreement->getUrl()];
        return $arResponse;
    }
    public function startConfirmActionEmailCode($email)
    {
        if (!\Bitrix\Main\Loader::includeModule('bxmaker.authuseremail')) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('MODULE_BXMAKER_AUTHUSEREMAIL_NOT_INSTALLED'), 'ERROR_INVALID_CONFIRM_TYPE');
        }
        $emailManager = \BXmaker\AuthUserEmail\Manager::getInstance();
        $email = $emailManager->getPreparedEmail($email);
        if (empty($email)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_EMPTY_EMAIL'), 'ERROR_INVALID_EMAIL');
        }
        if (!$emailManager->isValidEmail($email)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_EMAIL'), 'ERROR_INVALID_EMAIL');
        }
        // проверяем email включено требование авторегистрации в модулей авторизации по email коду
        if (!$this->manager()->isExistEmail($email) && $emailManager->param()->isEnabledAuthNeedRegistration()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_EMAIL_NOT_FOUND'), 'ERROR_EMAIL_NOT_FOUND');
        }
        try {
            $context = \Bitrix\Main\Application::getInstance()->getContext();
            $req = $context->getRequest();
            $captchaSid = (string) $req->getPost('captchaId');
            $captchaCode = (string) $req->getPost('captchaCode');
            if (!empty($captchaSid) && !empty($captchaCode)) {
                $arPostList = $context->getRequest()->getPostList()->toArray();
                $arRequestList = $context->getRequest()->toArray();
                $_REQUEST['captcha_sid'] = $captchaSid;
                $_REQUEST['captcha_word'] = $captchaCode;
                $_POST['captcha_sid'] = $captchaSid;
                $_POST['captcha_word'] = $captchaCode;
                $arRequestList['captcha_sid'] = $captchaSid;
                $arRequestList['captcha_word'] = $captchaCode;
                $arPostList['captcha_sid'] = $captchaSid;
                $arPostList['captcha_word'] = $captchaCode;
                $context->getRequest()->set($arRequestList);
                $context->getRequest()->getPostList()->set($arPostList);
            }
            $emailManager->sendCode($email);
        } catch (\BXmaker\AuthUserEmail\Exception\TimeoutException $ex) {
            $arCustomData = $ex->getCustomData();
            $arCustomData['length'] = $emailManager->param()->getCodeLength();
            $arCustomData['email'] = $email;
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($ex->getMessage(), 'ERROR_NEED_WAIT_TIMEOUT', $arCustomData);
        } catch (\BXmaker\AuthUserEmail\Exception\NeedCaptchaException $ex) {
            $arCustomData = $ex->getCustomData();
            $arCustomData['captchaId'] = $arCustomData['captchaId'] ?? $arCustomData['captchaSid'];
            $arCustomData['email'] = $email;
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($ex->getMessage(), 'ERROR_NEED_CAPTCHA', $arCustomData);
        } catch (\BXmaker\AuthUserEmail\Exception\LimitException $ex) {
            $arCustomData = $ex->getCustomData();
            $arCustomData['email'] = $email;
            throw new \BXmaker\AuthUserPhone\Exception\LimitException($ex->getMessage());
        } catch (\BXmaker\AuthUserEmail\Exception\DBException $ex) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($ex->getMessage(), 'ERROR_DB');
        }
        $arReturn = ['email' => $email, 'length' => $emailManager->param()->getCodeLength(), 'timeout' => $emailManager->param()->getCodeTimeout()];
        return $arReturn;
    }
    public function authByEmailAction()
    {
        if (!\Bitrix\Main\Loader::includeModule('bxmaker.authuseremail')) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('MODULE_BXMAKER_AUTHUSEREMAIL_NOT_INSTALLED'), 'ERROR_INVALID_CONFIRM_TYPE');
        }
        $email = trim((string) $this->request()->getPost('email'));
        $emailManager = \BXmaker\AuthUserEmail\Manager::getInstance();
        $email = $emailManager->getPreparedEmail($email);
        if (empty($email)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_EMPTY_EMAIL'), 'ERROR_INVALID_EMAIL');
        }
        if (!$emailManager->isValidEmail($email)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_EMAIL'), 'ERROR_INVALID_EMAIL');
        }
        try {
            $context = \Bitrix\Main\Application::getInstance()->getContext();
            $req = $context->getRequest();
            $captchaSid = (string) $req->getPost('captchaId');
            $captchaCode = (string) $req->getPost('captchaCode');
            if (!empty($captchaSid) && !empty($captchaCode)) {
                $arPostList = $context->getRequest()->getPostList()->toArray();
                $arRequestList = $context->getRequest()->toArray();
                $_REQUEST['captcha_sid'] = $captchaSid;
                $_REQUEST['captcha_word'] = $captchaCode;
                $_POST['captcha_sid'] = $captchaSid;
                $_POST['captcha_word'] = $captchaCode;
                $arRequestList['captcha_sid'] = $captchaSid;
                $arRequestList['captcha_word'] = $captchaCode;
                $arPostList['captcha_sid'] = $captchaSid;
                $arPostList['captcha_word'] = $captchaCode;
                $context->getRequest()->set($arRequestList);
                $context->getRequest()->getPostList()->set($arPostList);
            }
            $code = trim((string) $this->request()->getPost('confirmValue'));
            // проверяем код, с учетом лимитов
            $emailManager->checkCode($email, $code);
            // ищем пользователя
            $arUser = $emailManager->findUserByEmail($email, true);
            if (is_array($arUser)) {
                // елси найден
                $userId = $arUser['ID'];
            } else {
                $arUser = $emailManager->findUserByEmail($email, false);
                if (!is_null($arUser)) {
                    // если найден неактивный
                    throw new \BXmaker\AuthUserEmail\Exception\BaseException($this->getMessage('ERROR_USER_IS_NOT_ACTIVE'), 'ERROR_USER_IS_NOT_ACTIVE');
                }
                // елси требуетс ярегистрация и пользователь не найден
                if ($emailManager->param()->isEnabledAuthNeedRegistration()) {
                    throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_EMAIL_NOT_FOUND'), 'ERROR_EMAIL_NOT_FOUND');
                }
            }
            // регсистрируем если нет профиля
            if (!$userId) {
                $userId = $emailManager->register($email);
            }
            // авторизация
            $resAuth = $this->manager()->authorize($userId, true);
            if (!$resAuth->isSuccess()) {
                $resAuth->throwException();
            }
            $emailManager->clear($email);
        } catch (\BXmaker\AuthUserEmail\Exception\NeedCaptchaException $ex) {
            $arCustomData = $ex->getCustomData();
            $arCustomData['captchaId'] = $arCustomData['captchaId'] ?? $arCustomData['captchaSid'];
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($ex->getMessage(), 'ERROR_NEED_CAPTCHA', $arCustomData);
        } catch (\BXmaker\AuthUserEmail\Exception\BaseException $ex) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($ex->getMessage(), $ex->getCustomCode(), $ex->getCustomData());
        }
        $arResponse = $this->extendResponseAfterAuth($userId, ['msg' => $this->getMessage('AUTH_OK'), 'type' => $this->manager()->isSetUserRegisterFlag() ? 'REG' : 'AUTH']);
        return $arResponse;
    }
}
?>