<?php

namespace Webfly\Helper;

\Bitrix\Main\Loader::includeModule('main');
\Bitrix\Main\Loader::includeModule('sale');

class Profile extends Buyer
{

    public const PROP_ORDER_CODE_GUID = [
        'CODE' => 'COMPANY_UF_CRM_1724146140',
        'IDS' => [B2B_UR_PERSON_TYPE_ID => 126, B2B_FIZ_PERSON_TYPE_ID => 129,]
    ];

    public const PROP_ORDER_COMPANY_TITLE = [
        'CODE' => 'COMPANY_TITLE',
        'IDS' => [B2B_UR_PERSON_TYPE_ID => 43, B2B_FIZ_PERSON_TYPE_ID => 132,]
    ];

    public const PROP_ORDER_COMPANY_ADR = [
        'CODE' => 'COMPANY_ADR',
        'IDS' => [B2B_UR_PERSON_TYPE_ID => 44,]
    ];

    public const PROP_ORDER_COMPANY_INN = [
        'CODE' => 'INN',
        'IDS' => [B2B_UR_PERSON_TYPE_ID => 45,]
    ];

    public const PROP_ORDER_PROFILE_ID = [
        'CODE' => 'PROFILE_ID',
        'IDS' => [B2B_UR_PERSON_TYPE_ID => 150, B2B_FIZ_PERSON_TYPE_ID => 151,]
    ];

    public const PROP_ORDER_EDO_USE = [
        'CODE' => 'EDO_USE',
        'IDS' => [B2B_UR_PERSON_TYPE_ID => 64,]
    ];

    public const PROP_ORDER_EDO = [
        'CODE' => 'EDO',
        'IDS' => [B2B_UR_PERSON_TYPE_ID => 65,]
    ];

    public const PROP_ORDER_ADDRESS = [
        'CODE' => 'ADDRESS',
        'IDS' => [B2B_UR_PERSON_TYPE_ID => 94, B2B_FIZ_PERSON_TYPE_ID => 87,]
    ];

    public const PROP_ORDER_LOCATION = [
        'CODE' => 'LOCATION',
        'IDS' => [B2B_UR_PERSON_TYPE_ID => 96, B2B_FIZ_PERSON_TYPE_ID => 86,]
    ];

    public const PROP_ORDER_BUYER_NAME = [
        'CODE' => 'COMPANY_UF_COMPANY_NAME',
        'IDS' => [B2B_FIZ_PERSON_TYPE_ID => 133,]
    ];

    public const PROP_ORDER_BUYER_LAST_NAME = [
        'CODE' => 'COMPANY_UF_COMPANY_LAST_NAME',
        'IDS' => [B2B_FIZ_PERSON_TYPE_ID => 134,]
    ];

    public const PROP_ORDER_BUYER_SECOND_NAME = [
        'CODE' => 'COMPANY_UF_COMPANY_SECOND_NAME',
        'IDS' => [B2B_FIZ_PERSON_TYPE_ID => 135]
    ];


    public const PROP_ORDER_PHONE = [
        'CODE' => 'COMPANY_PHONE',
        'IDS' => [B2B_FIZ_PERSON_TYPE_ID => 130]
    ];

    public const PROP_ORDER_EMAIL = [
        'CODE' => 'COMPANY_EMAIL',
        'IDS' => [B2B_FIZ_PERSON_TYPE_ID => 131]
    ];

    public $typePerson;
    public $idProfile = 0;
    public $nameProfile;


    public $codeGuidValue;
    public $idPropGuid = false;

    public $companyTitleValue;
    public $companyAdrValue;
    public $companyInnValue;


    public $edoUseValue;
    public $edoValue;

    public $addressValue;
    public $locationValue;

    public $buyerNameValue;
    public $buyerLastNameValue;
    public $buyerSecondNameValue;

    public $emailValue;
    public $phoneValue;


    public $propsProfile;


    function __construct($individual = false, $selectPerson = false)
    {
        if ($selectPerson) {
            $this->typePerson = $selectPerson;
        } else {
            if ($individual) $this->typePerson = B2B_FIZ_PERSON_TYPE_ID;
            else $this->typePerson = B2B_UR_PERSON_TYPE_ID;
        }
    }


    public function getConstants()
    {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }


    public function addProfileBuyer($userID)
    {
        $arProfileFields['NAME'] = $this->nameProfile ?: "Без имени";
        $arProfileFields['PERSON_TYPE_ID'] = $this->typePerson;
        $arProfileFields['USER_ID'] = $userID;
        $this->idProfile = \CSaleOrderUserProps::Add($arProfileFields);
    }

    public function setNameProfile($arFieldsUser = [], $companyName = '')
    {
        if ($this->typePerson == B2B_FIZ_PERSON_TYPE_ID) {
            $this->nameProfile = trim(implode(' ', [$arFieldsUser['LAST_NAME'], $arFieldsUser['NAME'], $arFieldsUser['SECOND_NAME']]));
        } else {
            $this->nameProfile = $companyName;
        }
    }


    public function setCodeGuidValue($value)
    {
        $this->codeGuidValue = $value;
    }


    public function setCompanyTitleValue()
    {
        $this->companyTitleValue = $this->nameProfile;
    }

    public function setCompanyInnValue($value)
    {
        $this->companyInnValue = $value;
    }

    public function setCompanyAdrValue($value)
    {
        $this->companyAdrValue = $value;
    }


    public function setEdoUseValue($value)
    {
        $this->edoUseValue = $value;
    }

    public function setEdoValue($value)
    {
        $this->edoValue = $value;
    }


    public function setAddressValue($value)
    {
        $this->addressValue = $value;
    }

    public function setLocationValue($value)
    {
        $this->locationValue = $value;
    }

    public function setBuyerNameValue($value)
    {
        $this->buyerNameValue = $value;
    }

    public function setBuyerLastNameValue($value)
    {
        $this->buyerLastNameValue = $value;
    }

    public function setBuyerSecondNameValue($value)
    {
        $this->buyerSecondNameValue = $value;
    }

    public function setEmailValue($value)
    {
        $this->emailValue = $value;
    }

    public function setPhoneValue($value)
    {
        $this->phoneValue = $value;
    }


    public function addPropsProfileBuyer()
    {
        $arProps = self::getPropertiesType(['ID', 'NAME', 'CODE']);

        foreach ($arProps as $prop) {
            $PROPS[$prop['ID']] = [
                "USER_PROPS_ID" => $this->idProfile,
                "ORDER_PROPS_ID" => $prop['ID'],
                "NAME" => $prop['NAME'],
                "CODE" => $prop['CODE'],
            ];


            if ($prop['CODE'] == self::PROP_ORDER_PROFILE_ID['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->idProfile;
            if ($prop['CODE'] == self::PROP_ORDER_CODE_GUID['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->codeGuidValue;


            if ($prop['CODE'] == self::PROP_ORDER_COMPANY_TITLE['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->nameProfile;
            if ($prop['CODE'] == self::PROP_ORDER_COMPANY_ADR['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->companyAdrValue;
            if ($prop['CODE'] == self::PROP_ORDER_COMPANY_INN['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->companyInnValue;


            if ($prop['CODE'] == self::PROP_ORDER_EDO_USE['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->edoUseValue;
            if ($prop['CODE'] == self::PROP_ORDER_EDO['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->edoValue;


            if ($prop['CODE'] == self::PROP_ORDER_ADDRESS['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->addressValue;
            if ($prop['CODE'] == self::PROP_ORDER_LOCATION['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->locationValue;


            if ($prop['CODE'] == self::PROP_ORDER_BUYER_NAME['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->buyerNameValue;
            if ($prop['CODE'] == self::PROP_ORDER_BUYER_LAST_NAME['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->buyerLastNameValue;
            if ($prop['CODE'] == self::PROP_ORDER_BUYER_SECOND_NAME['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->buyerSecondNameValue;


            if ($prop['CODE'] == self::PROP_ORDER_PHONE['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->emailValue;
            if ($prop['CODE'] == self::PROP_ORDER_EMAIL['CODE']) $PROPS[$prop['ID']]['VALUE'] = $this->phoneValue;
        }

        foreach ($PROPS as &$prop) {
            $prop['ID'] = \CSaleOrderUserPropsValue::Add($prop);
            if ($prop['CODE'] == self::PROP_ORDER_CODE_GUID['CODE']) $this->idPropGuid = $prop['ID'];
        }
    }

    /**
     * Получение свойств для создания профиля
     *
     * @param string $profile_id
     * @return array
     */
    public function getPropertiesType($select = [])
    {
        $listConstants = $this->getConstants();

        foreach ($listConstants as $propCode => $item) {
            if ($item["IDS"][$this->typePerson]) {
                $this->propsProfile[] = $item["IDS"][$this->typePerson];
            }
        }

        $arFilter = ['ID' => $this->propsProfile, 'PERSON_TYPE_ID' => $this->typePerson];
        $dbProps = \CSaleOrderProps::GetList([], $arFilter, false, false, $select);
        while ($arProp = $dbProps->Fetch()) {
            $resProp[] = $arProp;
        }

        return $resProp;
    }

    public function arrayTo1C(&$dataUserTo1C)
    {
        $dataUserTo1C['PROFILE_ID'] = $this->idProfile;
        $dataUserTo1C['PROFILE_TYPE_ID'] = $this->typePerson;
        $dataUserTo1C['COMPANY'] = $this->profileName;
        $dataUserTo1C['COMPANY_ADR'] = $this->companyAdrValue;
        $dataUserTo1C['INN'] = $this->companyInnValue;
        $dataUserTo1C['COMPANY_UF_COMPANY_LAST_NAME'] = $this->buyerLastNameValue;
        $dataUserTo1C['COMPANY_UF_COMPANY_NAME'] = $this->buyerNameValue;

    }

    public function updatePropCodeGuid($value)
    {
        if (!empty($this->idPropGuid)) $this->updateProp($this->idPropGuid, ['VALUE' => $value]);
    }

    public function updateProp($propID, $updProp)
    {
        \CSaleOrderUserPropsValue::update($propID, $updProp);
    }


}