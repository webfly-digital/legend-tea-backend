<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc as Loc;
use BXmaker\AuthUserPhone\Exception\BaseException;
use BXmaker\AuthUserPhone\Manager;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
class BXmakerAuthUserPhoneCallComponent extends \CBitrixComponent
{
    const EVENT_TYPE_AJAX = 'BXmakerAuthUserPhoneCallComponentAjax';
    const EVENT_TYPE_AJAX_ANSWER = 'BXmakerAuthUserPhoneCallComponentAjaxAnswer';
    public function onPrepareComponentParams($arParams)
    {
        // подключаем модуль
        if (!\Bitrix\Main\Loader::includeModule('bxmaker.authuserphone')) {
            return parent::onPrepareComponentParams($arParams);
        }
        // для ajax
        $this->arResult['_ORIGINAL_PARAMS'] = $arParams;
        $arParams['CONSENT_SHOW'] = $this->getParamBool($arParams, 'CONSENT_SHOW', $this->manager()->param()->isEnabledRequestConsent() && !$this->manager()->isAuthorized() ? 'Y' : 'N');
        $arParams['CONSENT_ID'] = $this->getParamInt($arParams, 'CONSENT_ID', $this->manager()->param()->getRequestConsentId());
        $arParams['RAND_STRING'] = $this->getParamStr($arParams, 'RAND_STRING', $this->randString());
        $arParams['IS_AJAX'] = $this->getParamBool($arParams, 'IS_AJAX', 'N');
        $arParams['CONFIRM_QUEUE'] = isset($arParams['CONFIRM_QUEUE']) && !empty($arParams['CONFIRM_QUEUE']) ? $arParams['CONFIRM_QUEUE'] : $this->manager()->param()->getConfirmQueue();
        $arParams['IS_ENABLED_CONFIRM_BY_SMS_CODE'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_SMS_CODE', $this->manager()->param()->isEnabledConfirmBySmsCode() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_USER_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_USER_CALL', $this->manager()->param()->isEnabledConfirmByUserCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_BOT_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_BOT_CALL', $this->manager()->param()->isEnabledConfirmByBotCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_BOT_SPEECH'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_BOT_SPEECH', $this->manager()->param()->isEnabledConfirmByBotSpeech() ? 'Y' : 'N');
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
     * @return \BXmaker\AuthUserPhone\Manager
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
        return \Bitrix\Main\Localization\Loc::getMessage('BXMAKER.AUTHUSERPHONE.COMPONENT.CALL.CLASS.' . $name, $arReplace);
    }
    public function executeComponent()
    {
        $this->setFrameMode(true);
        try {
            $this->arResult['USER_IS_AUTHORIZED'] = $this->manager()->IsAuthorized() ? 'Y' : 'N';
            // обработка ajax запросов --
            $this->ajaxHandler();
            // подклчюаем js
            if (\BXmaker\AuthUserPhone\Manager::getInstance()->param()->isEnabledComponentJQuery()) {
                \CJSCore::Init();
                \BXmaker\AuthUserPhone\Manager::getInstance()->initComponentJQuery();
            }
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
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_NEED_METHOD'), 'ERROR_NEED_METHOD');
            }
            if (!check_bitrix_sessid()) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_INVALID_SESSID'), 'ERROR_INVALID_SESSID');
            }
            $method = $this->request()->getPost('method');
            switch ($method) {
                case 'refreshCaptcha':
                    $oJsonResponse->setResponse($this->manager()->captcha()->getForJs());
                    break;
                case 'auth':
                    $oJsonResponse->setResponse($this->authAction());
                    break;
                case 'forget':
                    $oJsonResponse->setResponse($this->forgetAction());
                    break;
                case 'sendCode':
                    $oJsonResponse->setResponse($this->sendCodeAction());
                    break;
                case 'userCall':
                    $oJsonResponse->setResponse($this->userCallAction());
                    break;
                case 'botCall':
                    $oJsonResponse->setResponse($this->botCallAction());
                    break;
                case 'botSpeech':
                    $oJsonResponse->setResponse($this->botSpeechAction());
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
     * Проверка верно ли указан номер телефона
     * @param $phone
     * @return void
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     */
    public function checkPhone($phone)
    {
        if (!$this->manager()->isValidPhone($phone)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_PHONE.ERROR_INVALID_PHONE'), 'ERROR_INVALID_PHONE');
        }
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
            default:
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('CHECK_CONFIRM.ERROR_NEED_CONFIRM'), 'ERROR_NEED_CONFIRM');
        }
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
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $phone = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($phone);
        $this->checkPhone($phone);
        $password = trim((string) $this->request()->getPost('password'));
        if (empty($password)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_ACTION.ERROR_PASSWORD'), 'ERROR_PASSWORD');
        }
        $userId = null;
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck();
        if ($this->manager()->isValidPhone($phone)) {
            // проверяем смс код
            $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoCheck();
        }
        // отмечаем попытку проверки
        $this->manager()->limitIP()->setCheck();
        $this->manager()->limit()->setCheck();
        // ищем пользователя - номер + пароль
        $userIdResult = $this->manager()->findUserIdByPhonePassword($phone, $password);
        if ($userIdResult->isSuccess()) {
            $userId = (int) $userIdResult->getResult();
        }
        // ищем в принципе аккаунт без проверки пароля
        // если его нету, то в завивсимоти от настроек попробуем создать
        // либо вренем ошибку что не верны данные
        if (is_null($userId)) {
            $userIdResult = $this->manager()->findUserIdByPhone($phone, false);
            if ($userIdResult->isSuccess()) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_ACTION.ERROR_PHONE_PASSWORD'), 'ERROR_PHONE_PASSWORD');
            }
        }
        // если нет пользователя, но включена авторегистрация,
        // то пробуем зарегистрировать по номеру телефона
        if (is_null($userId) && $this->arParams['IS_ENABLED_AUTO_REGISTER'] == 'Y') {
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
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('AUTH_ACTION.USER_NOT_FOUND'), 'USER_NOT_FOUND');
        }
        // авторизация
        $resultAuth = $this->manager()->authorize($userId);
        if (!$resultAuth->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($resultAuth->getFirstError()->getMessage(), $resultAuth->getFirstError()->getCode());
        }
        $arResponse = $this->extendResponseAfterAuth($userId, ['msg' => $this->getMessage('AUTH_ACTION.AUTH_OK'), 'type' => $this->manager()->isSetUserRegisterFlag() ? 'REG' : 'AUTH']);
        return $arResponse;
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
        $arResponse = $this->extendResponseAfterAuth($userId, ['msg' => $this->getMessage('FORGET_ACTION.AUTH_OK'), 'type' => $this->manager()->isSetUserRegisterFlag() ? 'REG' : 'AUTH']);
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
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('SEND_CODE_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $ple = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($ple);
        $this->checkPhone($phone);
        // проверка таймаута
        $this->manager()->checkSmsCodeTimeout($phone);
        // проверка каптчи
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE)->checkCanDoRequest();
        // отмечаем попытку проверки
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        $sendCodeResult = $this->manager()->service()->startSmsCode($phone);
        if (!$sendCodeResult->isSuccess()) {
            $sendCodeResult->throwException();
        }
        return ['msg' => $sendCodeResult->getMore('MSG'), 'timeout' => $sendCodeResult->getMore('TIMEOUT'), 'length' => $sendCodeResult->getMore('LENGTH')];
    }
    /**
     * 
     * Выполнить запрос роботизированного нмоера телеофна на который должен позвонить пользователья для подтвреждения своего номера телеофна, вернет результат для ajax ответа
     * @return array
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function userCallAction()
    {
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('USER_CALL_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $ple = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($ple);
        $this->checkPhone($phone);
        // проверка таймаута
        $this->manager()->checkUserCallTimeout($phone);
        // проверка каптчи
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL)->checkCanDoRequest();
        // отмечаем попытку проверки
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        $userCallResult = $this->manager()->service()->startUserCall($phone);
        if (!$userCallResult->isSuccess()) {
            $userCallResult->throwException();
        }
        return ['msg' => $this->getMessage('USER_CALL_ACTION.MSG'), 'callTo' => $userCallResult->getMore('CALL_TO'), 'timeout' => $userCallResult->getMore('TIMEOUT')];
    }
    /**
     * 
     * Выполнить запрос звонка от робота для получения кода из нмоера телефона робота, вернет результат для ajax ответа
     * @return array
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function botCallAction()
    {
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('BOT_CALL_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $ple = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($ple);
        $this->checkPhone($phone);
        // проверка таймаута
        $this->manager()->checkBotCallTimeout($phone);
        // проверка каптчи
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL)->checkCanDoRequest();
        // отмечаем попытку проверки
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        $botCallResult = $this->manager()->service()->startBotCall($phone);
        if (!$botCallResult->isSuccess()) {
            $botCallResult->throwException();
        }
        return ['msg' => $botCallResult->getMore('MSG'), 'length' => $botCallResult->getMore('LENGTH'), 'timeout' => $this->manager()->getBotCallTimeout($phone)];
    }
    /**
     * 
     * Выполнить запрос голосового кода, вернет результат для ajax ответа
     * @return array
     * @throws \BXmaker\Authuserphone\Exception\BaseException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\LimitException
     * @throws \BXmaker\AuthUserPhone\Exception\NeedCaptchaException
     * @throws \BXmaker\AuthUserPhone\Exception\TimeoutException
     */
    public function botSpeechAction()
    {
        if ($this->manager()->isAuthorized()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('BOT_SPEECH_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        $ple = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($ple);
        $this->checkPhone($phone);
        // проверка таймаута
        $this->manager()->checkBotSpeechTimeout($phone);
        // проверка каптчи
        $this->manager()->limitIP()->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH)->checkCanDoRequest();
        $this->manager()->limit()->setPhone($phone)->setType(\BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH)->checkCanDoRequest();
        // отмечаем попытку проверки
        $this->manager()->limitIP()->setRequest();
        $this->manager()->limit()->setRequest();
        $botSpeechResult = $this->manager()->service()->startBotSpeech($phone);
        if (!$botSpeechResult->isSuccess()) {
            $botSpeechResult->throwException();
        }
        return ['msg' => $botSpeechResult->getMore('MSG'), 'length' => $botSpeechResult->getMore('LENGTH'), 'timeout' => $this->manager()->getBotSpeechTimeout($phone)];
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
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.IS_AUTHORIZED'), 'ERROR_IS_AUTHORIZED');
        }
        if ($this->arParams['IS_ENABLED_REGISTER'] != 'Y') {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_REGISTRATION_IS_DISABLED'), 'ERROR_REGISTRATION_IS_DISABLED');
        }
        $phone = trim((string) $this->request()->getPost('phone'));
        $phone = $this->manager()->getPreparedPhone($phone);
        $this->checkPhone($phone);
        if ($this->manager()->isExistPhone($phone)) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_PHONE_USED'), 'ERROR_PHONE_USED');
        }
        $arUserFields = [];
        // если нужно апрашивать логин
        $login = trim((string) $this->request()->getPost('login'));
        if ($this->arParams['IS_ENABLED_REGISTER_LOGIN'] == 'Y' && !empty($login)) {
            if ($this->manager()->isExistLogin($login)) {
                throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('REGISTER_ACTION.ERROR_LOGIN_USED'), 'ERROR_LOGIN_USED');
            }
            $arUserFields['LOGIN'] = $login;
        }
        // если нужно запрашивать email
        $email = trim((string) $this->request()->getPost('email'));
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
        if ($this->arParams['IS_ENABLED_REGISTER_PASSWORD'] == 'Y' && !empty($password)) {
            // проверка длины пароля
            $resultCheckPassword = $this->manager()->checkNewPassword($password, $this->manager()->getUserDefaultGroupIds());
            if (!$resultCheckPassword->isSuccess()) {
                $resultCheckPassword->throwException();
            }
            $arUserFields['PASSWORD'] = $password;
            $arUserFields['CONFIRM_PASSWORD'] = $password;
        }
        // проверка кода
        $this->checkConfirm($phone);
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
}
?>