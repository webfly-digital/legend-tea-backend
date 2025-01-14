<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc as Loc;
use BXmaker\AuthUserPhone\Exception\BaseException;
use BXmaker\AuthUserPhone\Manager;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
class BXmakerAuthUserPhoneSimpleComponent extends \CBitrixComponent
{
    const EVENT_TYPE_AJAX = 'BXmakerAuthUserPhoneSimpleComponentAjax';
    const EVENT_TYPE_AJAX_ANSWER = 'BXmakerAuthUserPhoneSimpleComponentAjaxAnswer';
    private $oManager = null;
    private $oRequest = null;
    public function onPrepareComponentParams($arParams)
    {
        // подключаем модуль
        if (!\Bitrix\Main\Loader::includeModule('bxmaker.authuserphone')) {
            throw new \Bitrix\Main\LoaderException($this->getMessage('MODULE_NOT_INSTALLED'));
        }
        $oManager = \BXmaker\AuthUserPhone\Manager::getInstance();
        // для ajax
        $this->arResult['_ORIGINAL_PARAMS'] = $arParams;
        $arParams['RAND_STRING'] = isset($arParams['RAND_STRING']) ? $arParams['RAND_STRING'] : $this->randString();
        $arParams['IS_AJAX'] = $this->getParamBool($arParams, 'IS_AJAX', 'N');
        $arParams['IS_ENABLED_REQUEST_CONSENT'] = $this->getParamBool($arParams, 'IS_ENABLED_REQUEST_CONSENT', $this->manager()->param()->isEnabledRequestConsent() && !$this->manager()->isAuthorized() ? 'Y' : 'N');
        $arParams['REQUEST_CONSENT_ID'] = $this->getParamInt($arParams, 'CONSENT_ID', $this->manager()->param()->getRequestConsentId());
        $arParams['REQUEST_CONSENT_FIELDS'] = $arParams['REQUEST_CONSENT_FIELDS'] ?? null;
        $arParams['IS_ENABLED_REQUEST_ADS_AGREEMENT'] = $this->getParamBool($arParams, 'IS_ENABLED_REQUEST_ADS_AGREEMENT', $this->manager()->param()->isEnabledRegisterRequestAdsAgreement() ? 'Y' : 'N');
        $arParams['REQUEST_ADS_AGREEMENT_LABEL'] = $this->getParamBool($arParams, 'REQUEST_ADS_AGREEMENT_LABEL', $this->manager()->param()->getRegisterRequestAdsAgreementLabel());
        $arParams['IS_ENABLED_RELOAD_AFTER_AUTH'] = $this->getParamBool($arParams, 'IS_ENABLED_RELOAD_AFTER_AUTH', $this->manager()->param()->isEnabledReloadAfterAuth() ? 'Y' : 'N');
        $arParams['CONFIRM_QUEUE'] = isset($arParams['CONFIRM_QUEUE']) && !empty($arParams['CONFIRM_QUEUE']) ? $arParams['CONFIRM_QUEUE'] : $this->manager()->param()->getConfirmQueue();
        $arParams['IS_ENABLED_CONFIRM_BY_SMS_CODE'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_SMS_CODE', $this->manager()->param()->isEnabledConfirmBySmsCode() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_USER_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_USER_CALL', $this->manager()->param()->isEnabledConfirmByUserCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_BOT_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_BOT_CALL', $this->manager()->param()->isEnabledConfirmByBotCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_BOT_SPEECH'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_BOT_SPEECH', $this->manager()->param()->isEnabledConfirmByBotSpeech() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_SIM_PUSH'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_SIM_PUSH', $this->manager()->param()->isEnabledConfirmBySimPush() ? 'Y' : 'N');
        $arParams['USE_BXMAKER_AUTHUSEREMAIL'] = $this->getParamBool($arParams, 'USE_BXMAKER_AUTHUSEREMAIL', $this->manager()->param()->isEnabledUseBXmakerAuthUserEmail() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_EMAIL_CODE'] = $arParams['USE_BXMAKER_AUTHUSEREMAIL'] === 'Y' && \Bitrix\Main\Loader::includeModule('bxmaker.authuseremail');
        $arParams['IS_ENABLED_CHECK_USER_PROFILE_AFTER_AUTH'] = $this->getParamBool($arParams, 'IS_ENABLED_CHECK_USER_PROFILE_AFTER_AUTH', $this->manager()->param()->isEnabledCheckUserProfileAfterAuth() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CHECK_USER_PROFILE_AFTER_REGISTRATION'] = $this->getParamBool($arParams, 'IS_ENABLED_CHECK_USER_PROFILE_AFTER_REGISTRATION', $this->manager()->param()->isEnabledCheckUserProfileAfterRegisteration() ? 'Y' : 'N');
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
     * Вернет языкозависимое сообщение
     * 
     * @param $name
     * @param array $arReplace
     * 
     * @return string
     */
    public function getMessage($name, $arReplace = [])
    {
        return \Bitrix\Main\Localization\Loc::getMessage('BXMAKER.AUTHUSERPHONE.COMPONENT.SIMPLE.CLASS.' . $name, $arReplace);
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
        global $USER;
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
                case 'startConfirm':
                    $oJsonResponse->setResponse($this->startConfirmAction());
                    break;
                case 'authByPhone':
                    $oJsonResponse->setResponse($this->authByPhoneAction());
                    break;
                case 'authByEmail':
                    $oJsonResponse->setResponse($this->authByEmailAction());
                    break;
                case 'getConsent':
                    $oJsonResponse->setResponse($this->getConsentAction());
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
     * В случае если email указан не валидный, выбросит исключение
     * 
     * @param $email
     * 
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    private function checkEmail($email)
    {
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
        if (\Bitrix\Main\Loader::includeSharewareModule("bxma" . "ker.a" . "" . "uthuser" . "phone") === constant("MODULE_DEMO_E" . "X" . "PI" . "R" . "E" . "D")) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException(\Bitrix\Main\Localization\Loc::getMessage("BXMAKER" . ".AUTHUSERPH" . "ON" . "E.DEMO_EXPIRED"), "BXMAKER_DEM" . "O_EXP" . "IRED");
        }
        try {
            $type = (string) $this->request()->getPost('confirmType');
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
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL)->checkCanDoRequest()->setRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL)->checkCanDoRequest()->setRequest();
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
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL)->checkCanDoRequest()->setRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL)->checkCanDoRequest()->setRequest();
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
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH)->checkCanDoRequest()->setRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH)->checkCanDoRequest()->setRequest();
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
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH)->checkCanDoRequest()->setRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH)->checkCanDoRequest()->setRequest();
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
     * Пытается авторизовать пользователя
     * 
     * @return array
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function authByPhoneAction()
    {
        $phone = (string) $this->request()->getPost('phone');
        $phone = $this->manager()->getPreparedPhone($phone);
        $this->checkPhone($phone);
        // проверяем подтвержден ли номер
        $this->checkConfirm($phone);
        $userId = null;
        $resUser = $this->manager()->findUserIdByPhone($phone, true);
        if ($resUser->isSuccess()) {
            // если польвзоатель найден
            $userId = $resUser->getResult();
        } else {
            // ищем неактивного пользователя
            $resUser = $this->manager()->findUserIdByPhone($phone, false);
            if ($resUser->isSuccess()) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_USER_IS_NOT_ACTIVE'), 'ERROR_USER_IS_NOT_ACTIVE');
            }
        }
        // если пользователь не найден, автоматичеки создаем его
        if (is_null($userId)) {
            $arUserFields = [];
            if ($this->arParams['IS_ENABLED_REQUEST_ADS_AGREEMENT'] == 'Y' && (string) $this->request()->getPost('adsAgreement') === 'true') {
                $arUserFields[\BXmaker\AuthUserPhone\Manager::ADS_AGREEMENT_USER_FIELD_CODE] = 'Y';
            }
            // регистрация
            $resRegister = $this->manager()->register($phone, $arUserFields);
            if (!$resRegister->isSuccess()) {
                $resRegister->throwException();
            }
            $userId = (int) $resRegister->getResult();
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
        }
        // авторизация
        $resAuth = $this->manager()->authorize($userId, true);
        if (!$resAuth->isSuccess()) {
            $resAuth->throwException();
        }
        // смена пароля после входа по временному коду
        if ($this->manager()->param()->isEnabledChangePasswordAfterAuthByTemporaryCode() && !$this->manager()->isSetUserRegisterFlag()) {
            $this->manager()->setPassword($userId);
        }
        $arResponse = $this->extendResponseAfterAuth($userId, ['msg' => $this->getMessage('AUTH_OK'), 'type' => $this->manager()->isSetUserRegisterFlag() ? 'REG' : 'AUTH']);
        return $arResponse;
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
        $type = trim((string) $this->request()->getPost('confirmType'));
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
     * Проверит полученный код из смс,
     * если он не валидный - выбросит исключение
     * 
     * @param $phone
     * @param $code
     * 
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function checkConfirmActionSmsCode($phone, $code)
    {
        if (!$this->manager()->isValidSmsCode($phone, $code)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_SMS_CODE'), 'ERROR_INVALID_SMS_CODE');
        }
    }
    /**
     * 
     * Проверит получен ли входящий звонок от пользователя
     * если нет - выбросит исключение
     * 
     * @param $phone
     * @param $value - телефон на который должен позвонить пользователь
     * 
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\IO\FileNotFoundException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function checkConfirmActionUserCall($phone, $value)
    {
        $result = $this->manager()->service()->checkUserCall($phone, $value);
        if (!$result->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_USER_CALL'), 'ERROR_INVALID_USER_CALL');
        }
    }
    /**
     * 
     * Проверит введенный код
     * 
     * @param $phone
     * @param $code
     * 
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function checkConfirmActionBotCall($phone, $code)
    {
        $result = $this->manager()->service()->checkBotCall($phone, $code);
        if (!$result->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_BOT_CALL'), 'ERROR_INVALID_BOT_CALL');
        }
    }
    /**
     * 
     * Проверит введенный код
     * 
     * @param $phone
     * @param $code
     * 
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function checkConfirmActionBotSpeech($phone, $code)
    {
        $result = $this->manager()->service()->checkBotSpeech($phone, $code);
        if (!$result->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_BOT_SPEECH'), 'ERROR_INVALID_BOT_SPEECH');
        }
    }
    /**
     * 
     * Проверит получен ли подтверждение SIM-push
     * если нет - выбросит исключение
     * 
     * @param $phone
     * @param $value - телефон на который должен позвонить пользователь
     * 
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\IO\FileNotFoundException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function checkConfirmActionSimPush($phone)
    {
        $result = $this->manager()->service()->checkSimPush($phone);
        if (!$result->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_USER_CALL'), 'ERROR_INVALID_USER_CALL');
        }
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
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($ex->getMessage(), 'ERROR_NEED_WAIT_TIMEOUT', $ex->getCustomData());
        } catch (\BXmaker\AuthUserEmail\Exception\NeedCaptchaException $ex) {
            $arCustomData = $ex->getCustomData();
            $arCustomData['captchaId'] = $arCustomData['captchaId'] ?? $arCustomData['captchaSid'];
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($ex->getMessage(), 'ERROR_NEED_CAPTCHA', $arCustomData);
        } catch (\BXmaker\AuthUserEmail\Exception\LimitException $ex) {
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