<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc as Loc;
use BXmaker\Authuserphone\Exception\BaseException;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
class BXmakerAuthuserphoneLoginComponent extends \CBitrixComponent
{
    const EVENT_TYPE_AJAX = 'BXmakerAuthuserphoneLoginComponentAjax';
    const EVENT_TYPE_AJAX_ANSWER = 'BXmakerAuthuserphoneLoginComponentAjaxAnswer';
    public function onPrepareComponentParams($arParams)
    {
        if (!\Bitrix\Main\Loader::includeModule('bxmaker.authuserphone')) {
            throw new \Bitrix\Main\LoaderException($this->getMessage("MODULE_NOT_INSTALLED"));
        }
        $this->arResult['_ORIGINAL_PARAMS'] = $arParams;
        $arParams['IS_AJAX'] = $this->getParamBool($arParams, 'IS_AJAX', 'N');
        $arParams['RAND_STRING'] = $this->getParamStr($arParams, 'RAND_STRING', $this->randString());
        $arParams['CONSENT_SHOW'] = $this->getParamBool($arParams, 'CONSENT_SHOW', $this->manager()->param()->isEnabledRequestConsent() && !$this->manager()->IsAuthorized() ? 'Y' : 'N');
        $arParams['CONSENT_ID'] = $this->getParamInt($arParams, 'CONSENT_ID', $this->manager()->param()->getRequestConsentId());
        $arParams['CONFIRM_QUEUE'] = isset($arParams['CONFIRM_QUEUE']) && !empty($arParams['CONFIRM_QUEUE']) ? $arParams['CONFIRM_QUEUE'] : $this->manager()->param()->getConfirmQueue();
        $arParams['IS_ENABLED_CONFIRM_BY_SMS_CODE'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_SMS_CODE', $this->manager()->param()->isEnabledConfirmBySmsCode() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_USER_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_USER_CALL', $this->manager()->param()->isEnabledConfirmByUserCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_BOT_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_BOT_CALL', $this->manager()->param()->isEnabledConfirmByBotCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_RELOAD_AFTER_AUTH'] = $this->getParamBool($arParams, 'IS_ENABLED_RELOAD_AFTER_AUTH', $this->manager()->param()->isEnabledReloadAfterAuth() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_LOGIN'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_LOGIN', $this->manager()->param()->isEnabledRegisterLogin() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_EMAIL'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_EMAIL', $this->manager()->param()->isEnabledRegisterEmail() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER_PASSWORD'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER_PASSWORD', $this->manager()->param()->isEnabledRegisterPassword() ? 'Y' : 'N');
        $arParams['IS_ENABLED_AUTO_REGISTER'] = $this->getParamBool($arParams, 'IS_ENABLED_AUTO_REGISTER', $this->manager()->param()->isEnabledAutoRegister() ? 'Y' : 'N');
        $arParams['IS_ENABLED_REGISTER'] = $this->getParamBool($arParams, 'IS_ENABLED_REGISTER', $this->manager()->param()->isEnabledRegister() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CHECK_USER_PROFILE_AFTER_AUTH'] = $this->getParamBool($arParams, 'IS_ENABLED_CHECK_USER_PROFILE_AFTER_AUTH', $this->manager()->param()->isEnabledCheckUserProfileAfterAuth() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CHECK_USER_PROFILE_AFTER_REGISTRATION'] = $this->getParamBool($arParams, 'IS_ENABLED_CHECK_USER_PROFILE_AFTER_REGISTRATION', $this->manager()->param()->isEnabledCheckUserProfileAfterRegisteration() ? 'Y' : 'N');
        $arParams['IS_ENABLED_RESTORE_BY_EMAIL'] = $this->getParamBool($arParams, 'IS_ENABLED_RESTORE_BY_EMAIL', $this->manager()->param()->isEnabledResotreByEmail() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CAPTCHA_FOR_RESTORE_BY_EMAIL'] = $this->getParamBool($arParams, 'IS_ENABLED_CAPTCHA_FOR_RESTORE_BY_EMAIL', $this->manager()->param()->isEnabledCheckCaptchaForResotreByEmail() ? 'Y' : 'N');
        $arParams['IS_ENABLED_AUTH_BY_LOGIN'] = $this->getParamBool($arParams, 'IS_ENABLED_AUTH_BY_LOGIN', $this->manager()->param()->isEnabledAuthByLogin() ? 'Y' : 'N');
        $arParams['IS_ENABLED_AUTH_BY_EMAIL'] = $this->getParamBool($arParams, 'IS_ENABLED_AUTH_BY_EMAIL', $this->manager()->param()->isEnabledAuthByEmail() ? 'Y' : 'N');
        $arParams['PHONE_MASK_PARAMS'] = isset($arParams['PHONE_MASK_PARAMS']) ? $arParams['PHONE_MASK_PARAMS'] : $this->manager()->param()->getPhoneMaskParams();
        return parent::onPrepareComponentParams($arParams);
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
     * @param $name
     * @param array $arReplace
     * 
     * @return string
     */
    public function getMessage($name, $arReplace = [])
    {
        return \Bitrix\Main\Localization\Loc::getMessage('BXMAKER.AUTHUSERPHONE.COMPONENT.LOGIN.CLASS.' . $name, $arReplace);
    }
    public function executeComponent()
    {
        $this->setFrameMode(true);
        try {
            // обработка ajax запросов --
            $this->ajaxHandler();
            // подклчюаем js
            if (\BXmaker\AuthUserPhone\Manager::getInstance()->param()->isEnabledComponentJQuery()) {
                \CJSCore::Init();
                \BXmaker\AuthUserPhone\Manager::getInstance()->initComponentJQuery();
            }
            $this->arResult['USER_IS_AUTHORIZED'] = $this->manager()->isAuthorized();
            $this->arResult['TEMPLATE'] = $this->getTemplateName();
            $this->includeComponentTemplate();
        } catch (\Exception $e) {
            ShowError($e->getMessage());
        }
        return parent::executeComponent();
    }
    public function ajaxHandler()
    {
        // обработка только ajax запросов
        if (!$this->request()->isAjaxRequest() || $this->arParams['IS_AJAX'] != 'Y') {
            return true;
        }
        $oJsonResponse = new \BXmaker\AuthUserPhone\Ajax\JsonResponse();
        try {
            // вызов события
            $this->manager()->sendEvent(self::EVENT_TYPE_AJAX, ['jsonResponse' => $oJsonResponse, 'component' => $this]);
            if (!$this->request()->getPost('method')) {
                throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('NEED_METHOD'), 'NEED_METHOD');
            }
            if (!check_bitrix_sessid()) {
                throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('INVALID_SESSID'), 'INVALID_SESSID');
            }
            $method = $this->request()->getPost('method');
            switch ($method) {
                case 'refreshCaptcha':
                    $oJsonResponse->setResponse($this->manager()->captcha()->getForJs());
                    break;
                case 'auth':
                    $oJsonResponse->setResponse($this->authAction());
                    break;
                case 'sendCode':
                    $oJsonResponse->setResponse($this->sendCodeAction());
                    break;
                case 'sendEmail':
                    $oJsonResponse->setResponse($this->sendEmailAction());
                    break;
                case 'register':
                    $oJsonResponse->setResponse($this->registerAction());
                    break;
                default:
                    throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('UNDEFINED_METHOD'), 'BXMAKER_AUTHUSERPHONE_ERROR_AJAX_METHOD');
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
     * Проверка верно ли указан номер телефона
     * @param $phone
     * @return void
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     */
    public function checkPhone($phone)
    {
        if (!$this->manager()->isValidPhone($phone)) {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('CHECK_PHONE.ERROR_INVALID_PHONE'), 'ERROR_INVALID_PHONE');
        }
    }
    /**
     * 
     * Проверка заполнено ли поле телефон, логин, email
     * @param $phone
     * @return void
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     */
    public function checkPLE($ple)
    {
        if (empty($ple)) {
            $arText = [$this->getMessage('CHECK_PLE.PHONE')];
            if ($this->arParams['IS_ENABLED_AUTH_BY_LOGIN'] == 'Y') {
                $arText[] = $this->getMessage('CHECK_PLE.LOGIN');
            }
            if ($this->arParams['IS_ENABLED_AUTH_BY_EMAIL'] == 'Y') {
                $arText[] = $this->getMessage('CHECK_PLE.EMAIL');
            }
            if (count($arText) > 1) {
                $text = implode(', ', array_slice($arText, 0, -1));
                $text .= ' ' . $this->getMessage('CHECK_PLE.OR') . ' ';
                $text .= end($arText);
            } else {
                $text = implode(', ', $arText);
            }
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('CHECK_PLE.ERROR_PLE', ['#TEXT#' => $text]), 'ERROR_INVALID_PLE');
        }
    }
    /**
     * 
     * Выполнит попытку авторизовтаь польвазоетля или выбросит исключени с ошибкой, вернет результат для ajax ответа
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
    public function authAction()
    {
        // для отпаврки смс после атвориазции по номеру телеофна и коду из смс
        $bAuthByPhoneCode = false;
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('AUTH_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $ple = trim((string) $this->request()->getPost('phone'));
        $this->checkPLE($ple);
        $password = trim((string) $this->request()->getPost('passwordOrSmsCode'));
        if (empty($password)) {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('AUTH_ACTION.ERROR_PASSWORD'), 'ERROR_PASSWORD');
        }
        $userId = null;
        $phone = $this->manager()->getPreparedPhone($ple);
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck();
        if ($this->manager()->isValidPhone($phone)) {
            // проверяем смс код
            $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck()->setCheck();
        }
        $this->manager()->limitIP()->setCheck();
        // ищем пользвоателя - номер + смс код
        if ($this->manager()->isValidPhone($phone) && $this->manager()->isValidSmsCode($phone, $password)) {
            $userIdResult = $this->manager()->findUserIdByPhone($phone);
            if ($userIdResult->isSuccess()) {
                $userId = (int) $userIdResult->getResult();
                $bAuthByPhoneCode = true;
            }
        }
        // ищем пользователя - номер + пароль
        if (is_null($userId) && $this->manager()->isValidPhone($phone)) {
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
                $arList = [$this->getMessage('AUTH_ACTION.PHONE')];
                if ($this->arParams['IS_ENABLED_AUTH_BY_LOGIN'] == 'Y') {
                    $arList[] = $this->getMessage('AUTH_ACTION.LOGIN');
                }
                if ($this->arParams['IS_ENABLED_AUTH_BY_EMAIL'] == 'Y') {
                    $arList[] = $this->getMessage('AUTH_ACTION.EMAIL');
                }
                throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('AUTH_ACTION.ERROR_USER_PLE_PASSWORD', ['#TEXT#' => implode(', ', $arList)]), 'ERROR_USER_PLE_PASSWORD');
            }
        }
        // если нет пользователя, но включена авторегистрация,
        // то пробуем зарегистрировать по номеру телефона
        if (is_null($userId) && $this->manager()->isValidPhone($phone) && $this->manager()->isValidSmsCode($phone, $password) && $this->arParams['IS_ENABLED_AUTO_REGISTER'] == 'Y') {
            // регистрация по номеру телефона
            $registerResult = $this->manager()->register($phone);
            if ($registerResult->isSuccess()) {
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
        }
        if (is_null($userId)) {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('AUTH_ACTION.USER_NOT_FOUND'), 'USER_NOT_FOUND');
        }
        // авторизация
        $resultAuth = $this->manager()->authorize($userId);
        if (!$resultAuth->isSuccess()) {
            $resultAuth->throwException();
        }
        // смена пароля после входа по временному коду
        if ($bAuthByPhoneCode && $this->manager()->param()->isEnabledChangePasswordAfterAuthByTemporaryCode() && !$this->manager()->isSetUserRegisterFlag()) {
            $this->manager()->setPassword($userId);
        }
        $arResponse = $this->extendResponseAfterAuth($userId, ['msg' => $this->getMessage('AUTH_ACTION.AUTH_OK'), 'type' => $this->manager()->isSetUserRegisterFlag() ? 'REG' : 'AUTH']);
        return $arResponse;
    }
    /**
     * 
     * Выполнить отправку временного кода по смс по возможности или выбросит исключение с ошибкой, вернет результат для ajax ответа
     * @return array
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function sendCodeAction()
    {
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('SEND_CODE_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $ple = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($ple);
        $isRegistration = $this->request()->getPost('registration') == 'Y';
        $this->checkPhone($phone);
        // проверка таймаута
        $this->manager()->checkSmsCodeTimeout($phone);
        // проверка каптчи
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoRequest();
        // отмечаем попытку проверки
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        // проверка существования номера телефона, если не включена авторегистрация
        if (!$isRegistration && !$this->arParams['IS_ENABLED_AUTO_REGISTER'] == 'Y') {
            $userIdResult = $this->manager()->findUserIdByPhone($phone, false);
            if (!$userIdResult->isSuccess()) {
                throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('SEND_CODE_ACTION.ERROR_USER_NOT_FOUND'), 'ERROR_USER_NOT_FOUND');
            }
        }
        $sendCodeResult = $this->manager()->service()->startSmsCode($phone);
        if (!$sendCodeResult->isSuccess()) {
            $sendCodeResult->throwException();
        }
        return ['msg' => $sendCodeResult->getMore('MSG'), 'timeout' => $sendCodeResult->getMore('TIMEOUT'), 'length' => $sendCodeResult->getMore('LENGTH')];
    }
    /**
     * 
     * Выполнить отправку на email временной ссылки для входа без аутентификации
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     */
    public function sendEmailAction()
    {
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('SEND_EMAIL_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $ple = trim((string) $this->request()->getPost('phone'));
        $this->checkPLE($ple);
        if ($this->arParams['IS_ENABLED_RESTORE_BY_EMAIL'] != 'Y') {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('SEND_EMAIL_ACTION.ERROR_RESTORE_IS_DISABLED'), 'ERROR_RESTORE_IS_DISABLED');
        }
        // проверка капчи
        if ($this->arParams['IS_ENABLED_CAPTCHA_FOR_RESTORE_BY_EMAIL'] == 'Y') {
            $this->manager()->captcha()->checkOnRequest();
        }
        // оптарвка писем
        $result = $this->manager()->sendRestoreEmailByPLE($ple, true, $this->arParams['IS_ENABLED_AUTH_BY_LOGIN'], $this->arParams['IS_ENABLED_AUTH_BY_EMAIL']);
        if (!$result->isSuccess()) {
            $result->throwException();
        }
        return ['msg' => $result->getMore('MSG')];
    }
    /**
     * 
     * Выполнить регистрацию пользователя
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
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('REGISTER_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        if ($this->arParams['IS_ENABLED_REGISTER'] != 'Y') {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_IS_DISABLED'), 'ERROR_REGISTRATION_IS_DISABLED');
        }
        $phone = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($phone);
        $this->checkPhone($phone);
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck();
        if ($this->manager()->isExistPhone($phone)) {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_PHONE_USED'), 'ERROR_PHONE_USED');
        }
        $arUserFields = [];
        // если нужно апрашивать логин
        $login = trim((string) $this->request()->getPost('login'));
        if ($this->arParams['IS_ENABLED_REGISTER_LOGIN'] == 'Y' && !empty($login)) {
            if ($this->manager()->isExistLogin($login)) {
                throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_LOGIN_USED'), 'ERROR_LOGIN_USED');
            }
            $arUserFields['LOGIN'] = $login;
        }
        // если нужно запрашивать email
        $email = trim((string) $this->request()->getPost('email'));
        if ($this->arParams['IS_ENABLED_REGISTER_EMAIL'] == 'Y' && !empty($email)) {
            if (!$this->manager()->isValidEmail($email)) {
                throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_EMAIL_NOT_SET'), 'ERROR_EMAIL_NOT_SET');
            }
            if ($this->manager()->param()->isEnabledUniqueEmail() && $this->manager()->isExistEmail($email)) {
                throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_EMAIL_USED'), 'ERROR_EMAIL_USED');
            }
            $arUserFields['EMAIL'] = $email;
        }
        // пароль
        $password = trim((string) $this->request()->getPost('password'));
        if ($this->arParams['IS_ENABLED_REGISTER_PASSWORD'] == 'Y' && !empty($password)) {
            // проверка длины пароля
            $resultCheckPassword = $this->manager()->checkNewPassword($password, $this->manager()->getUserDefaultGroupIds());
            if (!$resultCheckPassword->isSuccess()) {
                $resultCheckPassword->throwException();
            }
            $arUserFields['PASSWORD'] = $password;
            $arUserFields['CONFIRM_PASSWORD'] = $password;
        }
        $this->manager()->limitIP()->setCheck();
        $this->manager()->limit()->setCheck();
        // проверка кода
        $smsCode = trim((string) $this->request()->getPost('smsCode'));
        if (!$this->manager()->isValidSmsCode($phone, $smsCode)) {
            throw new \BXmaker\Authuserphone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_SMS_CODE_IS_WRONG'), 'ERROR_SMS_CODE_IS_WRONG');
        }
        $registerResult = $this->manager()->register($phone, $arUserFields);
        if (!$registerResult->isSuccess()) {
            $registerResult->throwException();
        }
        $userId = (int) $registerResult->getResult();
        // сохраняем согласие
        if ($this->arParams['CONSENT_SHOW'] == 'Y' && $this->arParams['CONSENT_ID']) {
            $this->manager()->saveConsent($userId, (string) $this->request()->getPost('consent_id'), (string) $this->request()->getPost('consent_url'));
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
    /**
     * 
     * Расширить объект ответа на авторизацию и регистрацию
     * @param $userId
     * @param $arResponse
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
}
?>