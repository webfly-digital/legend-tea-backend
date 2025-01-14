<?php

namespace Webfly\Upload;

class UsersToXML //уже не используем выгрузку
{

    const FILE_PATH_UPLOAD_ALL = '/var/www/www-root/data/www/legend-tea.ru/upload/1c/allUsers.xml';
    const FILE_PATH_UPLOAD_UPDATE = '/var/www/www-root/data/www/legend-tea.ru/upload/1c/updateUsers.xml';
    const TYPE = [5 => 'ЮрЛицо', 6 => 'ФизЛицо'];
    const PROPS_ID_YR = [5 => ['NAME' => 43, 'YR_ADDRESS' => 44, 'DELIVERY_ADDRESS' => 94, 'EMAIL' => 98, 'PHONE' => 99, 'INN' => 45]]; //Наименование, юр адрес, адрес доставк, имейл, телефон, инн
    const PROPS_ID_FIZ = [6 => ['NAME' => 54, 'DELIVERY_ADDRESS' => 87, 'EMAIL' => 55, 'PHONE' => 56]]; //Наименование, адрес доставк, имейл, телефон,

    function __construct()
    {
    }

    public static function uploadUsers($idUser = false, $idProfile = false)
    {
        $file = self::FILE_PATH_UPLOAD_UPDATE;
        if ($idUser) $arProfile = self::getUsers($idUser);
        if ($idProfile) $arProfile = self::getUsers(false, $idProfile);
        if ($idUser == false && $idProfile == false) {
            $arProfile = self::getUsers();
            $file = self::FILE_PATH_UPLOAD_ALL;
        }
        if (!empty($arProfile)) self::createXML($file, $arProfile);
    }

    public static function getUsers($idUser = false, $idProfile = false)
    {
        \Bitrix\Main\Loader::includeModule('sale');
        \Bitrix\Main\Loader::includeModule('main');
        $arProfile = [];
        $arProps = static::PROPS_ID_YR + static::PROPS_ID_FIZ;
        $arPropsID = array_merge(array_values(current(static::PROPS_ID_YR)), array_values(current(static::PROPS_ID_FIZ)));
        $arTypeID = array_keys(static::TYPE);

        $filter = ["PROFILE_PERSON_TYPE_ID" => $arTypeID, 'PROFILE_VALUE_ORDER_PROPS_ID' => $arPropsID];
        if ($idUser) $filter['ID'] = $idUser;
        if ($idProfile) $filter['PROFILE_ID'] = $idProfile;
        $res = \Bitrix\Main\UserTable::getList([
            'filter' => $filter,
            'runtime' => [
                new  \Bitrix\Main\Entity\ReferenceField('PROFILE', \Bitrix\Sale\UserPropsTable::getEntity(),
                    ['=this.ID' => 'ref.USER_ID'], ['join_type' => 'LEFT']),
                new  \Bitrix\Main\Entity\ReferenceField('PROFILE_VALUE', \Bitrix\Sale\UserPropsValueTable::getEntity(),
                    ['=this.PROFILE_ID' => 'ref.USER_PROPS_ID'], ['join_type' => 'LEFT']),
            ],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'PERSONAL_PHONE', 'PERSONAL_BIRTHDAY', 'PROFILE_' => 'PROFILE', 'PROFILE_VALUE_' => 'PROFILE_VALUE']]);
        while ($ob = $res->fetch()) {
            $profID = $ob["PROFILE_ID"];
            $profType = $ob["PROFILE_PERSON_TYPE_ID"];

            $arProfile[$profID]["PROFILE_ID"] = $profID;
            $arProfile[$profID]['TYPE'] = $profType;
            $arProfile[$profID]['TYPE'] = $profType;
            if ($ob["PROFILE_VALUE_ORDER_PROPS_ID"] == $arProps[$profType]['NAME']) $arProfile[$profID]['NAME'] = $ob["PROFILE_VALUE_VALUE"];
            if ($ob["PROFILE_VALUE_ORDER_PROPS_ID"] == $arProps[$profType]['EMAIL']) $arProfile[$profID]['EMAIL'] = $ob["PROFILE_VALUE_VALUE"];
            if ($ob["PROFILE_VALUE_ORDER_PROPS_ID"] == $arProps[$profType]['PHONE']) $arProfile[$profID]['PHONE'] = $ob["PROFILE_VALUE_VALUE"];
            if ($ob["PROFILE_VALUE_ORDER_PROPS_ID"] == $arProps[$profType]['DELIVERY_ADDRESS']) {
                $address = $ob["PROFILE_VALUE_VALUE"];
                $pos = mb_strripos($ob["PROFILE_VALUE_VALUE"], '#');
                if ($pos === false) {
                } else  $address = mb_substr($ob["PROFILE_VALUE_VALUE"], 0, $pos);
                $arProfile[$profID]['DELIVERY_ADDRESS'] = $address;
            }
            if ($ob["PROFILE_VALUE_ORDER_PROPS_ID"] == $arProps[$profType]['YR_ADDRESS']) $arProfile[$profID]['YR_ADDRESS'] = $ob["PROFILE_VALUE_VALUE"];
            if ($ob["PROFILE_VALUE_ORDER_PROPS_ID"] == $arProps[$profType]['INN']) $arProfile[$profID]['INN'] = $ob["PROFILE_VALUE_VALUE"];

            $user['USER_ID'] = $ob['ID'];
            if ($ob['NAME']) $user['NAME'] = $ob['NAME'];
            if ($ob['LAST_NAME']) $user['LAST_NAME'] = $ob['LAST_NAME'];
            if ($ob['SECOND_NAME']) $user['SECOND_NAME'] = $ob['SECOND_NAME'];
            if ($ob['EMAIL']) $user['EMAIL'] = $ob['EMAIL'];
            if ($ob['PERSONAL_PHONE']) $user['PHONE'] = $ob['PERSONAL_PHONE'];
            if ($ob['PERSONAL_BIRTHDAY']) {
                $data = new \DateTime($ob['PERSONAL_BIRTHDAY']);
                $user['BIRTHDAY'] = $data->format('d.m.Y');
            }
            $arProfile[$profID]['USER'] = $user;
        }

        return $arProfile;
    }

    public static function createXML($file, $arProfile, $newFile = false)
    {
        if (file_exists($file)) {
            $contentFile = file_get_contents($file);
            if ($contentFile) $createFile = false;
            else $createFile = true;
        } else  $createFile = true;

        if ($createFile || $newFile) {
            $str = '<?xml version="1.0" encoding="UTF-8"?><СписокКонтрагентов></СписокКонтрагентов>';
            file_put_contents($file, $str);
        }


        if (file_exists($file)) {
            $sxe = new \SimpleXMLElement($file, NULL, TRUE);
            $newXml = self::formatedXML($sxe, $arProfile);
            file_put_contents($file, $newXml);

            $conn_id = ftp_connect('ftp.legend-tea.ru');
            ftp_login($conn_id, '1c-obmen', 'oM5pA6uQ1jbS6g');

            $arStr = explode('/', $file);
            $upload = ftp_put($conn_id, $arStr[count($arStr) - 1], $file);
            ftp_close($conn_id);
        }
    }


/*    public static function deleteXML()
    {
        $file = self::FILE_PATH_UPLOAD_UPDATE;
        if (file_exists($file)) {
            unlink($file);
        }
    }*/

    public static function formatedXML($sxe, $arProfile)
    {
        foreach ($arProfile as $profile) {
            $contragent = $sxe->addChild('Контрагент');
            $contragent->addAttribute('ГУИД', '');
            $idContragent = $contragent->addChild('ИДпрофиля', $profile['PROFILE_ID']);
            $rolesContragent = $contragent->addChild('РолиКонтрагента');
            $vidContragent = $contragent->addChild('ВидКонтрагента', self::TYPE[$profile['TYPE']]);
            $nameContragent = $contragent->addChild('Наименование', $profile['NAME']);
            $nameFullContragent = $contragent->addChild('НаименованиеПолное', $profile['NAME']);
            $innContragent = $contragent->addChild('ИНН', $profile['INN']);
            $кппContragent = $contragent->addChild('КПП', '');

            $deliveryAddressContragent = $contragent->addChild('ФактическийАдрес', $profile['DELIVERY_ADDRESS']);
            $yrAddressContragent = $contragent->addChild('ЮридическийАдрес', $profile['YR_ADDRESS']);
            $phoneContragent = $contragent->addChild('Телефон', $profile['PHONE']);
            $emailContragent = $contragent->addChild('ЭлектроннаяПочта', $profile['EMAIL']);
            $regionContragent = $contragent->addChild('БизнесРегион', '');
            $activityContragent = $contragent->addChild('ВидДеятельности', '');
            $infoContragent = $contragent->addChild('ДополнительнаяИнформацияКонтрагента', '');
            $managerContragent = $contragent->addChild('ОсновнойМенеджер', '');

            $contactsFace = $contragent->addChild('КонтактныеЛица');
            $contact = $contactsFace->addChild('КонтактноеЛицо');
            $contact->addAttribute('ГУИД', '');
            $fioСontact = $contact->addChild('ФИОПолностью', $profile['USER']['NAME'] . ' ' . $profile['USER']['SECOND_NAME'] . ' ' . $profile['USER']['LAST_NAME']);
            $familyСontact = $contact->addChild('Фамилия', $profile['USER']['LAST_NAME']);
            $nameСontact = $contact->addChild('Имя', $profile['USER']['NAME']);
            $secondСontact = $contact->addChild('Отчетсво', $profile['USER']['SECOND_NAME']);
            $jobСontact = $contact->addChild('ДолжностьПоВизитке', '');
            $birthdayСontact = $contact->addChild('ДатаРождения', $profile['USER']['BIRTHDAY']);
            $genderСontact = $contact->addChild('Пол', '');
            $rolesСontact = $contact->addChild('РолиКонтактногоЛица');
            $emailСontact = $contact->addChild('Телефон', $profile['USER']['PHONE']);
            $emailСontact = $contact->addChild('ЭлектроннаяПочта', $profile['USER']['EMAIL']);
            $infoСontact = $contact->addChild('ДополнительнаяИнформацияКЛ', '');
            $partnerСontact = $contact->addChild('ГУИДПартнера', '');
            $idСontact = $contact->addChild('ИДПользователя', $profile['USER']['USER_ID']);
        }
        $newXml = $sxe->asXML();
        return $newXml;
    }
}