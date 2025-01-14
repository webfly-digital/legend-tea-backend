<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die;
}
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc as Loc;
use BXmaker\AuthUserPhone\Exception\BaseException;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
class BXmakerAuthUserPhoneEditComponent extends \CBitrixComponent
{
    const EVENT_TYPE_AJAX = 'BXmakerAuthUserPhoneEditComponentAjax';
    const EVENT_TYPE_AJAX_ANSWER = 'BXmakerAuthUserPhoneEditComponentAjaxAnswer';
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
        $arParams['CONFIRM_QUEUE'] = isset($arParams['CONFIRM_QUEUE']) && !empty($arParams['CONFIRM_QUEUE']) ? $arParams['CONFIRM_QUEUE'] : $this->manager()->param()->getConfirmQueue();
        $arParams['IS_ENABLED_CONFIRM_BY_SMS_CODE'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_SMS_CODE', $this->manager()->param()->isEnabledConfirmBySmsCode() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_USER_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_USER_CALL', $this->manager()->param()->isEnabledConfirmByUserCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_BOT_CALL'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_BOT_CALL', $this->manager()->param()->isEnabledConfirmByBotCall() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_BOT_SPEECH'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_BOT_SPEECH', $this->manager()->param()->isEnabledConfirmByBotSpeech() ? 'Y' : 'N');
        $arParams['IS_ENABLED_CONFIRM_BY_SIM_PUSH'] = $this->getParamBool($arParams, 'IS_ENABLED_CONFIRM_BY_SIM_PUSH', $this->manager()->param()->isEnabledConfirmBySimPush() ? 'Y' : 'N');
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
     * Вернет языкозависимое сообщение
     * @param $name
     * @param array $arReplace
     * 
     * @return string
     */
    public function getMessage($name, $arReplace = [])
    {
        return \Bitrix\Main\Localization\Loc::getMessage('BXMAKER.AUTHUSERPHONE.COMPONENT.EDIT.CLASS.' . $name, $arReplace);
    }
    public function executeComponent()
    {
        $this->setFrameMode(true);
        try {
            $this->arResult['IS_AUTHORIZED'] = isset($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized() ? 'Y' : 'N';
            $this->arResult['CURRENT_PHONE'] = $this->manager()->getPhone($this->manager()->getUserId());
            $this->arResult['CURRENT_FORMATTED_PHONE'] = $this->manager()->format()->international($this->arResult['CURRENT_PHONE']);
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
                case 'startConfirm':
                    $oJsonResponse->setResponse($this->startConfirmAction());
                    break;
                case 'checkConfirm':
                    $oJsonResponse->setResponse($this->checkConfirmAction());
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
     * @param $phone
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
     * @param $type
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
     * @return array
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function startConfirmAction()
    {
        $arReturn = [];
        $phone = (string) $this->request()->getPost('phone');
        $phone = $this->manager()->getPreparedPhone($phone);
        $this->checkPhone($phone);
        if (\Bitrix\Main\Loader::includeSharewareModule("bxmaker.a" . "uthuser" . "phone") === constant("MODULE_DEMO_EXPIRE" . "" . "D")) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException(\Bitrix\Main\Localization\Loc::getMessage("BXMAKER.AUTHUSERPHONE.DEMO_EXPIRED"), "BXMAKER_DEMO_EXPI" . "RE" . "" . "" . "D");
        }
        $type = (string) $this->request()->getPost('type');
        $this->checkType($type);
        switch ($type) {
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE:
                $arReturn = $this->startConfirmActionSmsCode($phone);
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL:
                $arReturn = $this->startConfirmActionUserCall($phone);
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL:
                $arReturn = $this->startConfirmActionBotCall($phone);
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH:
                $arReturn = $this->startConfirmActionBotSpeech($phone);
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH:
                $arReturn = $this->startConfirmActionSimPush($phone);
                break;
        }
        // дополним
        $arReturn['formattedPhone'] = $this->formattedPhone($phone);
        return $arReturn;
    }
    /**
     * 
     * Старт подтверждения чере смс код
     * @param $phone
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
     * @param $phone
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
     * @param $phone
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
     * @param $phone
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
     * @param $phone
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
     * Выполняет проверку подтверждения и в случае успеха сохранит новый номер телефона
     * @return array
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function checkConfirmAction()
    {
        $arReturn = [];
        $phone = (string) $this->request()->getPost('phone');
        $phone = $this->manager()->getPreparedPhone($phone);
        $this->checkPhone($phone);
        $type = (string) $this->request()->getPost('type');
        $this->checkType($type);
        // проверка необходимости капчи
        $this->manager()->limitIP()->setType($type)->checkCanDoCheck();
        $this->manager()->limit()->setPhone($phone)->setType($type)->checkCanDoCheck();
        if (\CModule::IncludeModuleEx("bxmaker.a" . "uth" . "u" . "" . "serph" . "one") === constant("MODULE_DE" . "MO_" . "E" . "X" . "P" . "IR" . "" . "E" . "D")) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException(\Bitrix\Main\Localization\Loc::getMessage("BXMAKER.AUTHUSERPHONE.DEMO_EXP" . "I" . "RED"), "BXMAKER_DEMO_EXPI" . "" . "RE" . "D");
        }
        $this->manager()->limitIP()->setCheck();
        $this->manager()->limit()->setCheck();
        // проверка
        switch ($type) {
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SMS_CODE:
                $code = $this->request()->getPost('code');
                $this->checkConfirmActionSmsCode($phone, $code);
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_USER_CALL:
                $code = $this->request()->getPost('code');
                $this->checkConfirmActionUserCall($phone, $code);
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_CALL:
                $code = $this->request()->getPost('code');
                $this->checkConfirmActionBotCall($phone, $code);
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_BOT_SPEECH:
                $code = $this->request()->getPost('code');
                $this->checkConfirmActionBotSpeech($phone, $code);
                break;
            case \BXmaker\AuthUserPhone\Manager::CONFIRM_TYPE_SIM_PUSH:
                $this->checkConfirmActionSimPush($phone);
                break;
        }
        // номер тот же
        $arReturn['currentPhone'] = $phone;
        $arReturn['currentFormattedPhone'] = $this->formattedPhone($phone);
        $arReturn['msg'] = $this->getMessage('PHONE_IS_SET');
        // ищем пользвоателя с таким номером телефона
        $userId = null;
        $resUser = $this->manager()->findUserIdByPhone($phone, false);
        if ($resUser->isSuccess()) {
            // если польвзоатель найден
            $userId = $resUser->getResult();
        }
        if (!is_null($userId) && $userId != $this->manager()->getUserId()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($this->getMessage('ERROR_PHONE_IS_USED'), 'ERROR_PHONE_IS_USED');
        }
        $resultSetPhone = $this->manager()->setPhone($this->manager()->getUserId(), $phone);
        if (!$resultSetPhone->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($resultSetPhone->getFirstError()->getMessage(), $resultSetPhone->getFirstError()->getCode(), $resultSetPhone->getFirstError()->getMore());
        }
        $this->manager()->setPhoneConfirmedFlagToMainModule($userId, $phone);
        return $arReturn;
    }
    /**
     * 
     * Проверит полученный код из смс,
     * если он не валидный - выбросит исключение
     * @param $phone
     * @param $code
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
     * @param $phone
     * @param $value - телефон на который ползователь должен был позвонить
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
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($result->getFirstError()->getMessage(), 'ERROR_INVALID_USER_CALL');
        }
    }
    /**
     * 
     * Проверит введенный код
     * @param $phone
     * @param $code
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function checkConfirmActionBotCall($phone, $code)
    {
        $result = $this->manager()->service()->checkBotCall($phone, $code);
        if (!$result->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($result->getFirstError()->getMessage(), 'ERROR_INVALID_BOT_CALL');
        }
    }
    /**
     * 
     * Проверит голосовй код
     * @param $phone
     * @param $code
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function checkConfirmActionBotSpeech($phone, $code)
    {
        $result = $this->manager()->service()->checkBotSpeech($phone, $code);
        if (!$result->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($result->getFirstError()->getMessage(), 'ERROR_INVALID_BOT_SPEECH');
        }
    }
    /**
     * 
     * Проверит SIM-push
     * @param $phone
     * @param $code
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \BXmaker\AuthUserPhone\Exception\BaseException
     */
    public function checkConfirmActionSimPush($phone)
    {
        $result = $this->manager()->service()->checkSimPush($phone);
        if (!$result->isSuccess()) {
            throw new \BXmaker\AuthUserPhone\Exception\BaseException($result->getFirstError()->getMessage(), 'ERROR_INVALID_SIM_PUSH');
        }
    }
    public function formattedPhone($phone)
    {
        return $this->manager()->format()->international($phone);
    }
}
?>