<?php

namespace Webfly\Handlers;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;
use Bitrix\Crm\Service;

Loader::includeModule('sale');

class Main
{

    public static $userActiveState = [];
    public static $userB2BState = [];


    public static function OnBeforeUserUpdateHandler(&$arFields)
    {
        $userInfo = UserTable::getList(['limit' => 1, 'select' => ['ID', 'LID', 'ACTIVE'], 'filter' => ['ID' => $arFields['ID']]])->fetch();
        if ($userInfo['LID'] == 's3') {
            self::$userActiveState[$arFields['ID']] = $userInfo['ACTIVE'];
        }
        self::$userB2BState[$arFields['ID']] = self::isB2bUser($arFields['ID']);
    }

    /**
     * Проверяет, привязан ли юзер к b2b группе
     * @param $userId
     * @return bool
     */
    protected static function isB2bUser($userId)
    {
        if (!$userId) return;
        $isB2bUser = UserGroupTable::getList(['filter' => ['USER.ID' => $userId, 'GROUP_ID' => B2B_GROUP], 'select' => ['GROUP_ID']])->fetch();
        return $isB2bUser ? true : false;
    }

    public static function OnAfterUserUpdateHandler(&$arFields)
    {
        if ($arFields["RESULT"]) {
            $needUserNotify = (self::$userActiveState[$arFields['ID']] == 'N' && $arFields['ACTIVE'] == 'Y')
                || (!self::$userB2BState[$arFields['ID']] && self::isB2bUser($arFields['ID']));
            if ($needUserNotify)
                self::sendUserNotify($arFields);
        }

    }

    protected static function sendUserNotify($arFields)
    {
        $userInfo = UserTable::getList(['limit' => 1, 'select' => ['ID', 'EMAIL', 'LOGIN'], 'filter' => ['ID' => $arFields['ID']]])->fetch();
        Event::send(array(
            "EVENT_NAME" => "USER_ACTIVATE",
            "LID" => "s3",
            "C_FIELDS" => array(
                "EMAIL" => $userInfo['EMAIL'],
                "LOGIN" => $userInfo['LOGIN'],
            ),));
    }


    public static function OnBeforeUserAddUpdateHandler(&$arFields)
    {
        if ($arFields["PERSONAL_PHONE"]) {
            $phone = UserPhoneAuthTable::normalizePhoneNumber($arFields["PERSONAL_PHONE"]);
            $arFields['LOGIN'] = $arFields['PHONE_NUMBER'] = $arFields["PERSONAL_PHONE"] = $phone;//сделано, чтобы в s3 логин был телеофном, так как там стандартная регистрация

            if ($arFields["UF_BREGION"]) {
                $phonesForSearch = [];
                $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $arFields["PERSONAL_PHONE"]);
                $firstSymbol = mb_substr($phoneClear, 0, 1);
                $phonesForSearch[] = $phoneClear;
                $phoneTrim = mb_substr($phoneClear, 1);

                if ($firstSymbol == '+') {
                    $phonesForSearch[] = "8{$phoneTrim}";
                    $phonesForSearch[] = "7{$phoneTrim}";
                    $phonesForSearch[] = "{$phoneTrim}";
                } elseif ($firstSymbol == 7) {
                    $phonesForSearch[] = "8{$phoneTrim}";
                    $phonesForSearch[] = "+7{$phoneTrim}";
                    $phonesForSearch[] = "{$phoneTrim}";
                } elseif ($firstSymbol == 8) {
                    $phonesForSearch[] = "7{$phoneTrim}";
                    $phonesForSearch[] = "+7{$phoneTrim}";
                    $phonesForSearch[] = "{$phoneTrim}";
                } elseif ($firstSymbol == 9) {
                    $phonesForSearch[] = "7{$phoneTrim}";
                    $phonesForSearch[] = "+7{$phoneTrim}";
                    $phonesForSearch[] = "8{$phoneTrim}";
                }

                \Bitrix\Main\Loader::includeModule('crm');
                $obPhones = \Bitrix\Crm\FieldMultiTable::getList([
                    'filter' => [
                        'VALUE' => $phonesForSearch,
                        'TYPE_ID' => 'PHONE',
                        'ENTITY_ID' => 'CONTACT'
                    ],
                    'select' => ['ELEMENT_ID'],
                ]);
                while ($resPhone = $obPhones->fetch()) $arContact[$resPhone['ELEMENT_ID']] = $resPhone['ELEMENT_ID'];

                if ($arContact) {
                    $fields['UF_CRM_1656315498'] = $arFields["UF_BREGION"];
                    foreach ($arContact as $contact) {
                        $oContact = new \CCrmContact(false);
                        $updateContact = $oContact->Update($contact, $fields);
                    }
                }
            }
        }

        $IdLocation = $arFields["UF_LOCATION_ID"];
        if ($IdLocation) {
            \Bitrix\Main\Loader::includeModule('sale');

            $obTreeLocation = \Bitrix\Sale\Location\LocationTable::getList(array(
                'filter' => [
                    '=ID' => $IdLocation,
                    '=PARENTS.NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    '=PARENTS.TYPE.NAME.LANGUAGE_ID' => LANGUAGE_ID,
                ],
                'select' => [
                    'CODE',
                    'I_ID' => 'PARENTS.ID',
                    'I_NAME_RU' => 'PARENTS.NAME.NAME',
                    'I_TYPE_CODE' => 'PARENTS.TYPE.CODE',
                    'I_TYPE_NAME_RU' => 'PARENTS.TYPE.NAME.NAME',
                ],
            ));
            $arFields["UF_LOCATION_CODE"] = '';
            $arFields["PERSONAL_COUNTRY"] = '';
            $arFields["UF_FEDERAL_DISTRICT"] = '';
            $arFields["PERSONAL_STATE"] = '';
            $arFields["UF_DISTRICT"] = '';
            $arFields["PERSONAL_CITY"] = '';
            while ($resTreeLocation = $obTreeLocation->fetch()) {
                if ($resTreeLocation['CODE']) $arFields["UF_LOCATION_CODE"] = $resTreeLocation['CODE'];
                if ($resTreeLocation['I_TYPE_CODE'] == 'COUNTRY') $arFields["PERSONAL_COUNTRY"] = $resTreeLocation['I_ID'];
                $name = $resTreeLocation['I_NAME_RU'] ?: '';
                if ($resTreeLocation['I_TYPE_CODE'] == 'COUNTRY_DISTRICT') $arFields["UF_FEDERAL_DISTRICT"] = $name;
                if ($resTreeLocation['I_TYPE_CODE'] == 'REGION') $arFields["PERSONAL_STATE"] = $name;
                if ($resTreeLocation['I_TYPE_CODE'] == 'SUBREGION') $arFields["UF_DISTRICT"] = $name;
                if ($resTreeLocation['I_TYPE_CODE'] == 'CITY') $arFields["PERSONAL_CITY"] = $name;
            }

        }
    }

    /**
     * OnBeforeUserRegister
     * Валидация полей регистрации, которые в базе - необязательные,
     * но нам надо, чтоб они были обязательными)
     * @param $arFields
     * @return bool|void
     */
    public
    static function OnBeforeUserRegisterHandler(&$arFields)
    {
        $skip = self::checkAdminSkip($arFields);
        if ($skip) return;//ничего не делать, если админка || юзер - админ

        $arFields['ACTIVE'] = 'Y';//юзер создается деактивированным, после проверки его активирует менеджер

        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $arFields['SECOND_NAME'] = $request->get('USER_SECOND_NAME');
        $arFields['PERSONAL_PHONE'] = UserPhoneAuthTable::normalizePhoneNumber($request->get('USER_PERSONAL_PHONE'));
        $arFields['PHONE_NUMBER'] = UserPhoneAuthTable::normalizePhoneNumber($request->get('USER_PERSONAL_PHONE'));

        global $APPLICATION;
        $errors = [];

        if (empty($arFields['LAST_NAME'])) $errors[] = "Поле \"Фамилия\" не заполнено";
        if (empty($arFields['NAME'])) $errors[] = "Поле \"Имя\" не заполнено";
        if (empty($arFields['PERSONAL_PHONE'])) $errors[] = "Поле \"Телефон\" не заполнено";

        $individual = $request->get('INDIVIDUAL');
        if (!$individual) {//юр лицо
            $inn = $request->get('INN');
            $company = $request->get('COMPANY');
            $useEdo = $request->get('USE_EDO');
            $edo = $request->get('EDO');

            if (empty($inn)) $errors[] = "Поле \"ИНН\" не заполнено";
            if (empty($company)) $errors[] = "Поле \"Юридическое название организации\" не заполнено";
            if ($useEdo && empty($edo)) $errors[] = "Выберите систему ЭДО";
        }

        if (!empty($errors)) {
            $APPLICATION->throwException(implode('<br>', $errors));
            return false;
        }
    }

    /**
     * Вспомагательный метод,
     * исключающий обработчики для админов и в админке
     * @param $arFields
     * @return bool
     */
    protected
    static function checkAdminSkip($arFields)
    {
        $skip = $isAdminGroup = false;
        $isAdminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);
        if ($arFields['GROUP_ID']) $isAdminGroup = in_array(1, $arFields['GROUP_ID']);
        if ($arFields['USER_ID']) {
            $userGroups = UserGroupTable::getList(['filter' => ['USER_ID' => $arFields['USER_ID'], 'USER.ACTIVE' => 'Y'], 'select' => ['GROUP_ID']])->fetchAll();
            if (in_array(1, array_column($userGroups, 'GROUP_ID'))) $isAdminGroup = true;
        }
        $skip = $isAdminSection || $isAdminGroup;
        return $skip;
    }

    /**
     * OnAfterUserRegister
     * Создание профиля покупателя
     * после регистрации юр лица
     * @param $arFields
     */
    public
    static function OnAfterUserRegisterHandler(&$arFields)
    {
        $skip = true;
        if ($arFields['USER_ID'] && $arFields['UF_INDIVIDUAL'] == 0)
            $skip = self::checkAdminSkip($arFields);

        if ($skip) return;//ничего не делать, если админка || юзер - админ || физ лицо

        Loader::includeModule('sale');
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $company = $request->get('COMPANY');
        $individual = $request->get('INDIVIDUAL');

        $arProfileFields = [
            "NAME" => $individual ? '<Без имени>' : $company,
            "USER_ID" => $arFields['USER_ID'],
            "PERSON_TYPE_ID" => !$individual ? B2B_UR_PERSON_TYPE_ID : B2B_FIZ_PERSON_TYPE_ID
        ];
        $PROFILE_ID = \CSaleOrderUserProps::Add($arProfileFields);
        //если профиль создан
        if ($PROFILE_ID) {
            if (!$individual) {
                $PROPS = [
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 61,
                        "NAME" => "Фамилия",
                        "VALUE" => $arFields['LAST_NAME']
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 62,
                        "NAME" => "Имя",
                        "VALUE" => $arFields['NAME']
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 63,
                        "NAME" => "Отчество",
                        "VALUE" => $arFields['SECOND_NAME']
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 46,
                        "NAME" => "Контактное лицо",
                        "VALUE" => trim(implode(' ', [$arFields['LAST_NAME'], $arFields['NAME'], $arFields['SECOND_NAME']]))
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 52,
                        "NAME" => "Телефон",
                        "VALUE" => $arFields['PERSONAL_PHONE']
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 51,
                        "NAME" => "E-mail",
                        "VALUE" => $arFields['EMAIL']
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 43,
                        "NAME" => "Юридическое название компании",
                        "VALUE" => $request->get('COMPANY')
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 44,
                        "NAME" => "Юридический адрес компании",
                        "VALUE" => $request->get('COMPANY_ADR')
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 45,
                        "NAME" => "ИНН",
                        "VALUE" => $request->get('INN')
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 64,
                        "NAME" => "Работаю по ЭДО",
                        "VALUE" => $request->get('USE_EDO') ? 'Y' : ''
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 65,
                        "NAME" => "ЭДО",
                        "VALUE" => $request->get('EDO')
                    ],
                ];
            } else {
                $PROPS = [
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 67,
                        "NAME" => "Фамилия",
                        "VALUE" => $arFields['LAST_NAME']
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 68,
                        "NAME" => "Имя",
                        "VALUE" => $arFields['NAME']
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 69,
                        "NAME" => "Отчество",
                        "VALUE" => $arFields['SECOND_NAME']
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 54,
                        "NAME" => "Контактное лицо",
                        "VALUE" => trim(implode(' ', [$arFields['LAST_NAME'], $arFields['NAME'], $arFields['SECOND_NAME']]))
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 56,
                        "NAME" => "Телефон",
                        "VALUE" => $arFields['PERSONAL_PHONE']
                    ],
                    [
                        "USER_PROPS_ID" => $PROFILE_ID,
                        "ORDER_PROPS_ID" => 55,
                        "NAME" => "E-mail",
                        "VALUE" => $arFields['EMAIL']
                    ],
                ];
            }
            //добавляем значения свойств к созданному ранее профилю
            foreach ($PROPS as $prop)
                \CSaleOrderUserPropsValue::Add($prop);
        }
        self::setB2BUserGroup($arFields['USER_ID']);
    }

    /**
     * Привязывает юзера к группе B2B
     * @param $userId
     */
    protected
    static function setB2bUserGroup($userId)
    {
        if (!$userId) return;
        $isB2bUser = self::isB2bUser($userId);
        if (!$isB2bUser) {
            self::setUserToB2b($userId);
        }
    }

    /**
     * Добавляет юзера к b2b-группе
     * @param $userId
     */
    protected
    static function setUserToB2b($userId)
    {
        if (!$userId) return;
        \CUser::SetUserGroup($userId, array_merge(\CUser::GetUserGroup($userId), [B2B_GROUP]));
    }

    /**
     * Перед попыткой залогинеться
     * проверяем - что ввел юзер - мыло или телефон
     * и подставляем в логин
     * @param $arFields
     */
    public
    static function OnBeforeUserLoginHandler(&$arFields)
    {
        $bAdminSection = (defined('ADMIN_SECTION') && ADMIN_SECTION === true);
        if ($bAdminSection) return;
        if (strstr($arFields['LOGIN'], '@')) {//пользователь ввел email
            //смотрим, есть ли юзер с таким мылом или логином
            $query = UserTable::query();
            $query->enablePrivateFields();
            $query->setSelect(['ID', 'LOGIN', 'EMAIL', 'PASSWORD']);
            $query->setFilter(['=EMAIL' => $arFields['LOGIN']]);
            $userData = $query->exec();
            while ($ob_user = $userData->fetch()) {
                if (\Bitrix\Main\Security\Password::equals($ob_user['PASSWORD'], $arFields['PASSWORD'])) {
                    $arFields['LOGIN'] = $ob_user['LOGIN'];
                    break;
                }
            }
        } else {//пользователь ввел НЕ email
            $loginToPhone = UserPhoneAuthTable::normalizePhoneNumber($arFields['LOGIN']);
            $checkPhone = UserPhoneAuthTable::validatePhoneNumber($loginToPhone);
            if ($checkPhone !== true) {
                $first = substr($loginToPhone, 0, 1);
                if ($first != '+' && $first != '8') $loginToPhone = '+' . $loginToPhone;
                $checkPhone = UserPhoneAuthTable::validatePhoneNumber($loginToPhone);
            }
            if ($checkPhone === true) {//юзер ввел телефон
                $query = UserTable::query();
                $query->enablePrivateFields();
                $query->setSelect(['ID', 'LOGIN', 'PASSWORD']);
                $query->setFilter(['=PERSONAL_PHONE' => $loginToPhone]);
                $userData = $query->exec();
                while ($ob_user = $userData->fetch()) {
                    if (\Bitrix\Main\Security\Password::equals($ob_user['PASSWORD'], $arFields['PASSWORD'])) {
                        $arFields['LOGIN'] = $ob_user['LOGIN'];
                        break;
                    }
                }
            }
        }

    }

    /**
     * Не даем розничным юзерам авторизовываться в b2b-кабинете,
     * пока их не подтвердит менеджер (пока не привяжет к группе)
     * Так же вызывается в хедере, чтобы перс авторизованный в розничном кабинете
     * не мог гулять по страницам b2b
     * @param $fields
     */
    public
    static function OnAfterUserLoginHandler(&$fields)
    {
        if ($fields['USER_ID'] <= 0) return;
        $isB2bUser = self::isB2bUser($fields['USER_ID']);
        if ($isB2bUser) return;
        else  self::setUserToB2b($fields['USER_ID']);

        $isAdminUser = self::isAdminUser($fields['USER_ID']);
        if ($isAdminUser) {
            self::setUserToB2b($fields['USER_ID']);
            return;
        }

        $isb2bProfile = self::isB2bProfile($fields['USER_ID']);
        if ($isb2bProfile) {
            self::setUserToB2b($fields['USER_ID']);
            return;
        }
    }

    /**
     * Проверяет, привязан ли юзер к админской группе
     * @param $userId
     * @return bool
     */
    protected
    static function isAdminUser($userId)
    {
        if (!$userId) return;
        $isAdminUser = UserGroupTable::getList(['filter' => ['USER.ID' => $userId, 'GROUP_ID' => 1], 'select' => ['GROUP_ID']])->fetch();
        return $isAdminUser ? true : false;
    }

    /**
     * Проверяет, есть ли у юзера профиль,
     * относящийся к b2b кабинету
     * @param $userId
     * @return bool|void
     */
    protected
    static function isB2bProfile($userId)
    {
        if (!$userId) return;
        $isB2bProfile = \CSaleOrderUserProps::GetList([], ['USER_ID' => $userId, 'PERSON_TYPE_ID' => [B2B_UR_PERSON_TYPE_ID, B2B_FIZ_PERSON_TYPE_ID]], false, false, ['ID'])->fetch();
        return $isB2bProfile ? true : false;
    }

    /**
     * Письмо админу, что розничный юзер
     * хочет зайти в b2b-кабинет
     * @param $userId
     */
    protected
    static function sendAdminB2BNotify($userId)
    {
        if (!$userId) return;
        $userInfo = UserTable::getList(['limit' => 1, 'select' => ['ID', 'EMAIL', 'NAME'], 'filter' => ['ID' => $userId]])->fetch();
        Event::send(array(
            "EVENT_NAME" => "SIMPLE_USER_TRY_AUTH_IN_B2B",
            "LID" => "s3",
            'MESSAGE_ID' => 256,
            "C_FIELDS" => array(
                "USER_ID" => $userInfo['ID'],
                "EMAIL" => $userInfo['EMAIL'],
                "NAME" => $userInfo['NAME'],
            ),));
    }

    /**
     * Проставляем в контаке в срм,
     * дату последней атворизации пользователя
     */
    static public function OnAfterUserAuthorizeHandler($arUser)
    {
        \Bitrix\Main\Loader::includeModule('crm');
        \Bitrix\Main\Loader::includeModule('sale');


        $userId = $arUser['user_fields']['ID'];
        if ($userId) {
            $dbResOrder = \Bitrix\Sale\Order::getList([
                'order' => ['ID' => 'DESC'],
                'limit' => 1,
                'select' => ['ID'],
                'filter' => ["USER_ID" => $userId]]);
            while ($arRes = $dbResOrder->Fetch()) {
                $orderId = $arRes['ID'];
            }

            if ($orderId) {
                $order = \Bitrix\Sale\Order::load($orderId);
                $communications = $order->getContactCompanyCollection();
                foreach ($communications as $communication) {
                    $arCont = $communication->getFields()->getValues();
                    if ($arCont['ENTITY_TYPE_ID'] == 3) {
                        $arContactID = $arCont["ENTITY_ID"];
                    }
                }

                if ($arContactID) {
                    $objDateTime = \Bitrix\Main\Type\DateTime::createFromPhp(new \DateTime());
                    $fields['UF_CRM_1681474825685'] = $objDateTime;
                    $oContact = new \CCrmContact(false);
                    $updateLead = $oContact->Update($arContactID, $fields);
                }
            }
        }
    }

    static public function OnEpilogHandler()
    {

        global $APPLICATION;
        $NavPageNomer = intval($APPLICATION->GetPageProperty("NavPageNomer"));
        if ($NavPageNomer > 1) {
            $title = $APPLICATION->GetPageProperty("title") . ' - page ' . $NavPageNomer;
            $APPLICATION->SetPageProperty('title', $title);
            $APPLICATION->SetPageProperty('description', '');
            $APPLICATION->SetPageProperty("robots", "noindex,follow");
        }
    }


    static public function OnPrologHandler()
    {
        if (SITE_ID == 's1' || SITE_ID == 's3') self::deleteBasket();
    }

    static public function deleteBasket()
    {
        $needSave = false;
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        if ($basket) {
            foreach ($basket as $basketItem) {
                $arProductIds [$basketItem->getId()] = $basketItem->getProductId();
            }
        }
        if (!empty($arProductIds)) {
            $arDelete = \Webfly\Helper\Functions::checkListElementsSite($arProductIds);
            if (!empty($arDelete)) {
                foreach ($basket as $basketItem) {
                    if ($arDelete[$basketItem->getId()]) {
                        $basketItem->delete();
                        $needSave = true;
                    }
                }
            }
        }
        if ($needSave) $basket->save();
    }

    static public function OnAfterUserAddHandler($arFields)
    {
        if ($arFields['RESULT'] && $arFields['UF_1C'] != 'да') self::AddContactInCrm($arFields);
    }

    //Проверяем есть ли контакт с таикми данными, если нет создаём
    static public function AddContactInCrm($arFields)
    {
        $createContact = true;
        if (empty($arFields['PHONE_NUMBER']) && !empty($arFields['PERSONAL_PHONE'])) $arFields['PHONE_NUMBER'] = $arFields['PERSONAL_PHONE'];
        if ($arFields['PHONE_NUMBER']) {
            $phonesForSearch = [];
            $phoneClear = str_replace(['+', ' ', '(', ')', '-'], "", $arFields["PHONE_NUMBER"]);
            $firstSymbol = mb_substr($phoneClear, 0, 1);
            $phonesForSearch[] = $phoneClear;
            $phoneTrim = mb_substr($phoneClear, 1);

            if ($firstSymbol == '+') {
                $phonesForSearch[] = "8{$phoneTrim}";
                $phonesForSearch[] = "7{$phoneTrim}";
                $phonesForSearch[] = "{$phoneTrim}";
            } elseif ($firstSymbol == 7) {
                $phonesForSearch[] = "8{$phoneTrim}";
                $phonesForSearch[] = "+7{$phoneTrim}";
                $phonesForSearch[] = "{$phoneTrim}";
            } elseif ($firstSymbol == 8) {
                $phonesForSearch[] = "7{$phoneTrim}";
                $phonesForSearch[] = "+7{$phoneTrim}";
                $phonesForSearch[] = "{$phoneTrim}";
            } elseif ($firstSymbol == 9) {
                $phonesForSearch[] = "7{$phoneTrim}";
                $phonesForSearch[] = "+7{$phoneTrim}";
                $phonesForSearch[] = "8{$phoneTrim}";
            }

            \Bitrix\Main\Loader::includeModule('crm');
            $obPhones = \Bitrix\Crm\FieldMultiTable::getList([
                'filter' => [
                    'VALUE' => $phonesForSearch,
                    'TYPE_ID' => 'PHONE',
                    'ENTITY_ID' => 'CONTACT'
                ],
                'select' => ['ELEMENT_ID'],
            ]);
            while ($resPhone = $obPhones->fetch()) {
                $createContact = false;
            }
        }
        if ($createContact) {
            $source = 'WEB';
            $typeID = 'CLIENT';
            if ($arFields['SITE_ID'] == 's3') {//б2б
                $source = 'UC_NXQ5YR';
                $typeID = '1';
            }
            $arNewFields = array(
                "NAME" => $arFields['NAME'],
                "LAST_NAME" => $arFields['LAST_NAME'],
                "SECOND_NAME" => $arFields['SECOND_NAME'],
                'FM' => [
                    'EMAIL' => array(
                        'n0' => array('VALUE' => $arFields["EMAIL"], 'VALUE_TYPE' => \Bitrix\Crm\Multifield\Type\Email::VALUE_TYPE_HOME)
                    ),
                    'PHONE' => array(
                        'n0' => array('VALUE' => $arFields["PHONE_NUMBER"], 'VALUE_TYPE' => \Bitrix\Crm\Multifield\Type\Phone::VALUE_TYPE_PAGER)
                    )
                ],
                "ASSIGNED_BY_ID" => 1,
                "SOURCE_ID" => $source,
                'TYPE_ID' => $typeID
            );
            $oContact = new \CCrmContact(false);
            $ID = $oContact->add($arNewFields, true, ['DISABLE_USER_FIELD_CHECK' => true, 'DISABLE_REQUIRED_USER_FIELD_CHECK' => true]);
        }
    }


}
