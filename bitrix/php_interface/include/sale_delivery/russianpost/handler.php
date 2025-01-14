<?php

namespace Sale\Handlers\Delivery;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Sale\Delivery\CalculationResult;
use \Bitrix\Sale\Location\GroupTable;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Error;
use Bitrix\Sale\Internals\CompanyTable;
use Bitrix\Sale\Result;
use \Bitrix\Sale\Shipment;
use Bitrix\Main\EventManager;
use Bitrix\Main\Text\Encoding;
use Bitrix\Sale\BusinessValue;
use Bitrix\Main\SystemException;
use Sale\Handlers\Delivery\Spsr\Cache;
use Sale\Handlers\Delivery\Spsr\Request;
use Sale\Handlers\Delivery\Spsr\Location;
use Bitrix\Sale\Delivery\Services\Manager;
use Sale\Handlers\Delivery\Spsr\Calculator;
use Bitrix\Sale\Delivery\ExtraServices\Table;

Loc::loadMessages(__FILE__);

Loader::includeModule('russianpost.post');
Loader::registerAutoLoadClasses(
	null,
	array(
		'\Sale\Handlers\Delivery\RussianpostProfile' => '/bitrix/php_interface/include/sale_delivery/russianpost/profile.php',
		'\Sale\Handlers\Delivery\RussianpostTracking' => '/bitrix/php_interface/include/sale_delivery/russianpost/tracking.php',
	)
);

Loader::registerAutoLoadClasses(
	'russianpost.post',
	array(
		'\Russianpost\Post\Request' => '/lib/Request.php',
	)
);

/*
 * @package Bitrix\Sale\Delivery\Services
 */
class RussianpostHandler extends \Bitrix\Sale\Delivery\Services\Base
{
	protected static $isCalculatePriceImmediately = true;
	protected  static $whetherAdminExtraServicesShow = true;
	/** @var bool $canHasProfiles This handler can has profiles */
	protected static $canHasProfiles = true;
	protected $trackingClass = '\Sale\Handlers\Delivery\RussianpostTracking';

	/**
	 * @param array $initParams
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function __construct(array $initParams)
	{
		parent::__construct($initParams);
	}

	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_RUSSIANPOST_POST_TITLE");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVR_HANDL_RUSSIANPOST_POST_DESCRIPTION");
	}

	public function isCalculatePriceImmediately()
	{
		return self::$isCalculatePriceImmediately;
	}

	public static function whetherAdminExtraServicesShow()
	{
		return self::$whetherAdminExtraServicesShow;
	}

	protected function getConfigStructure()
	{
		$result = array(
			/*'MAIN' => array(
				'TITLE' => Loc::getMessage("SALE_DLV_RUSSIANPOST_POST_MAIN_SETTINGS"),
				'DESCRIPTION' => Loc::getMessage("SALE_DLV_RUSSIANPOST_POST_MAIN_SETTINGS_DESCR"),
				'ITEMS' => array(
					/*'API_KEY' => array(
						'TYPE' => 'STRING',
						'NAME' => '���� API',
					),
					'TEST_MODE' => array(
						'TYPE' => 'Y/N',
						'NAME' => Loc::getMessage("SALE_DLV_RUSSIANPOST_POST_TEST"),
						'DEFAULT' => 'N'
					),*/
			/*'PACKAGING_TYPE' => array(
				'TYPE' => 'ENUM',
				'NAME' => '��� ��������',
				'DEFAULT' => 'BOX',
				'OPTIONS' => array(
					'BOX' => '�������',
					'ENV' => '�������',
				)
			),
		)
	)*/
		);
		return $result;
	}

	protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment = null)
	{
		// �����-�� �������� �� ��������� ��������� � �����...

		throw new \Bitrix\Main\SystemException('Only profiles can calculate concrete');
	}

	public static function canHasProfiles()
	{
		return self::$canHasProfiles;
	}

	/**
	 * @return array Class names for profiles.
	 */
	public static function getChildrenClassNames()
	{
		return array(
			'\Sale\Handlers\Delivery\RussianpostProfile'
		);
	}

	public function getProfilesList()
	{
		$arProfiles = self::getPostProfiles();
		$arNames = array();
		$numProfile = 1;
		foreach ($arProfiles as $profile)
		{
			$arNames[$numProfile] = $profile['Name'];
			$numProfile++;
		}
		return $arNames;
	}

	public function getProfilesListFull()
	{
		$arProfiles = self::getPostProfiles();
		$arNames = array();
		$numProfile = 1;
		foreach ($arProfiles as $profile)
		{
			$arNames[$numProfile] = $profile;
			$numProfile++;
		}
		return $arNames;
	}

	protected static function getPostProfiles()
	{
		return array(
			"1" => array(
				"ID" => "1",
				"CODE" => "POST",
				"Name" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_PVZ'),
				"ShortDescription" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_PVZ_SDESCR'),
				"Description" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_PVZ_DESCR'),
			),
			"2" => array(
				"ID" => "2",
				"CODE" => "COURIER",
				"Name" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_COURIER'),
				"ShortDescription" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_COURIER_SDESCR'),
				"Description" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_COURIER_DESCR'),
			),
			"3" => array(
				"ID" => "3",
				"CODE" => "POST_WORLD",
				"Name" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_PVZ_WORLD'),
				"ShortDescription" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_PVZ_WORLD_SDESCR'),
				"Description" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_PVZ_WORLD_DESCR'),
			),
			"4" => array(
				"ID" => "4",
				"CODE" => "COURIER_WORLD",
				"Name" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_COURIER_WORLD'),
				"ShortDescription" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_COURIER_WORLD_SDESCR'),
				"Description" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_COURIER_WORLD_DESCR'),
			),
			"5" => array(
				"ID" => "5",
				"CODE" => "POST_DOC",
				"Name" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_PVZ_DOC'),
				"ShortDescription" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_PVZ_DOC'),
				"Description" => Loc::getMessage('SALE_DLV_RUSSIANPOST_POST_PVZ_DOC'),
			),
		);
	}

	public function getProfilesDefaultParams()
	{
		$result = array();

		$srvTypes = self::getPostProfiles();


		if(is_array($srvTypes))
		{
			$sort = 1;
			foreach($srvTypes as $profId => $params)
			{
				if($profId == 1)
				{
					$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/delivery.png');
					$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
				}
				elseif ($profId == 2)
				{
					$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/courier.png');
					$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
				}
				elseif ($profId == 3)
				{
					$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/world_delivery.jpg');
					$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
				}
				elseif ($profId == 4)
				{
					$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/world_courier.jpg');
					$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
				}
				elseif ($profId == 5)
				{
					$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/delivery.png');
					$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
					$this->active = false;
				}
				$result[] = array(
					"CODE" => $params['CODE'],
					"PARENT_ID" => $this->id,
					"NAME" => $params["Name"],
					"ACTIVE" => $this->active ? "Y" : "N",
					"SORT" => $sort,
					"DESCRIPTION" => $params["ShortDescription"],
					"CLASS_NAME" => '\Sale\Handlers\Delivery\RussianpostProfile',
					"CURRENCY" => $this->currency,
					'LOGOTIP'             => $arFileds1,
					"CONFIG" => array(
						"MAIN" => array(
							"SERVICE_TYPE" => $profId,
							"SERVICE_TYPE_NAME" => $params["Name"],
							"DESCRIPTION_INNER" => $params["Description"]
						)
					)
				);
				$sort++;
			}
		}

		return $result;
	}

	public function getProfileDefaultParamsByServType($profId)
	{
		$result = array();

		$srvTypes = self::getPostProfiles();


		if(is_array($srvTypes))
		{
			$sort = $profId;
			$params = $srvTypes[$profId];
			//foreach($srvTypes as $profId => $params)
			//{
			if($profId == 1)
			{
				$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/delivery.png');
				$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
			}
			elseif ($profId == 2)
			{
				$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/courier.png');
				$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
			}
			elseif ($profId == 3)
			{
				$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/world_delivery.jpg');
				$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
			}
			elseif ($profId == 4)
			{
				$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/world_courier.jpg');
				$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
			}
			elseif ($profId == 5)
			{
				$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/delivery.png');
				$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
			//	$this->active = false;
			}
			$result = array(
				"CODE" => $params['CODE'],
				"PARENT_ID" => $this->id,
				"NAME" => $params["Name"],
				"ACTIVE" => $this->active ? "Y" : "N",
				"SORT" => $sort,
				"DESCRIPTION" => $params["ShortDescription"],
				"CLASS_NAME" => '\Sale\Handlers\Delivery\RussianpostProfile',
				"CURRENCY" => $this->currency,
				'LOGOTIP'             => $arFileds1,
				"CONFIG" => array(
					"MAIN" => array(
						"SERVICE_TYPE" => $profId,
						"SERVICE_TYPE_NAME" => $params["Name"],
						"DESCRIPTION_INNER" => $params["Description"]
					)
				)
			);
			//$sort++;
			//}
		}

		return $result;
	}

	public static function onAfterAdd($serviceId, array $fields = array())
	{
		if($serviceId <= 0)
			return false;

		$result = true;
		//add logotip
		$arFile = \CFile::MakeFileArray('/bitrix/php_interface/include/sale_delivery/russianpost/main.png');
		$arFileds1 = \CFile::SaveFile($arFile, "sale/delivery/logotip");
		$arFieldsUpd = array('LOGOTIP'=> $arFileds1);
		$res_upd = Manager::update($serviceId, $arFieldsUpd);
		//Add profiles
		$fields["ID"] = $serviceId;
		$srv = new self($fields);
		$profiles = $srv->getProfilesDefaultParams();

		if(is_array($profiles))
		{
			foreach($profiles as $profile)
			{
				$res = Manager::add($profile);
				$result = $result && $res->isSuccess();
			}
		}

		return $result;
	}
}
?>