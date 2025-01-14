<?
namespace Sale\Handlers\Delivery;

use \Bitrix\Main\Error;
use \Bitrix\Sale\Shipment;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ArgumentNullException;
use \Bitrix\Sale\Delivery\Services\Manager;
use \Bitrix\Sale\Delivery\CalculationResult;
use \Bitrix\Main\Loader;
use \Bitrix\Sale\Delivery;
use Russianpost\Post\Hllist;
use Russianpost\Post\Optionpost;
use Russianpost\Post\Tools;
use Bitrix\Main\Diag;

Loc::loadMessages(__FILE__);

Loader::includeModule('russianpost.post');

class RussianpostProfile extends \Bitrix\Sale\Delivery\Services\Base
{
	protected static $isProfile = true;
	protected $parent = null;
	protected $serviceType = 0;
	protected $fillData = '';
	protected $calculateId = 0;
	protected $calcResult = null;
	protected $calcPrice = 0;
	const PROFILE_PICKUP = 1;
	const PROFILE_COURIER = 2;
	const PROFILE_WORLDPICKUP = 3;
	const PROFILE_WORLDCOURIER = 4;
	const PROFILE_PICKUPNOTE = 5;


	public function __construct(array $initParams)
	{
		if(empty($initParams["PARENT_ID"]))
			throw new ArgumentNullException('initParams[PARENT_ID]');
		parent::__construct($initParams);
		$this->parent = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($this->parentId);

		if(!($this->parent instanceof RussianpostHandler))
			throw new ArgumentNullException('this->parent is not instance of RussianpostHandler');
		//$initParams["PROFILE_ID"] = $initParams["PROFILE_ID"] + 1;
		if(isset($initParams['PROFILE_ID']) && intval($initParams['PROFILE_ID']) > 0)
			$this->serviceType = intval($initParams['PROFILE_ID']);
		elseif(isset($this->config['MAIN']['SERVICE_TYPE']) && intval($this->config['MAIN']['SERVICE_TYPE']) > 0)
			$this->serviceType = $this->config['MAIN']['SERVICE_TYPE'];
		/*elseif (isset($_REQUEST['PROFILE_ID']) && intval($_REQUEST['PROFILE_ID']) > 0)
		{
			$this->serviceType = intval($_REQUEST['PROFILE_ID']);
		}*/

		if($this->id <= 0 && $this->serviceType > 0)
		{
			//$srvRes = $this->parent->getServiceTypes();
			//$srvTypes = $srvRes->getData();

			$srvTypes = $this->parent->getProfilesListFull();
			$srvParams = $this->parent->getProfileDefaultParamsByServType($this->serviceType);
			/*if(!empty($srvTypes[$this->serviceType]))
			{
				$this->name = $srvTypes[$this->serviceType]['Name'];
				$this->description = $srvTypes[$this->serviceType]['ShortDescription'];
                $this->id = $srvTypes[$this->serviceType]['ID'];
			}*/
			if(!empty($srvParams))
			{
				$this->name = $srvParams['NAME'];
				$this->description = $srvParams['DESCRIPTION'];
				$this->code = $srvParams['CODE'];
				//$this->id = $srvParams['CONFIG']['MAIN']['SERVICE_TYPE'];
				$this->config = $srvParams['CONFIG'];
				$this->logotip = $srvParams['LOGOTIP'];
			}
		}
		if($this->id > 0)
		{
			$arConfig = $this->getConfig();
			if(empty($arConfig))
			{

			}
		}
		$this->inheritParams();
	}

	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLV_RUSSIANPOST_POST_PROFILE_TITLE");
	}

	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLV_RUSSIANPOST_POST_PROFILE_DESCRIPTION");
	}

	public function getParentService()
	{
		return $this->parent;
	}

	public function isCalculatePriceImmediately()
	{
		return $this->getParentService()->isCalculatePriceImmediately();
	}

	public static function isProfile()
	{
		return self::$isProfile;
	}

	protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment = null)
	{
		$b24path = array (
			'ORDER' => '/bitrix/components/bitrix/crm.order.details/ajax.php',
			'SHIPMENT' => '/bitrix/components/bitrix/crm.order.shipment.details/ajax.php',
			'ORDER1' => '/shop/orders/details/',
			'SHIPMENT1' => '/shop/orders/shipment/details/',
		);
		$curPage = $GLOBALS['APPLICATION']->GetCurPage();
		$result = new \Bitrix\Sale\Delivery\CalculationResult();
		if(!empty($shipment))
		{
			$order = $shipment->getCollection()->getOrder();
			$deliveryIds = $order->getDeliverySystemId();
			$idCalculatedDelivery = 0;
			#currency convertation
			$baseCurrency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();
			$currencyList = \Bitrix\Currency\CurrencyManager::getCurrencyList();
			$profileCurrency = '';
			$orderCurrency = $order->getCurrency();
			$orderPrice = $order->getPrice()-$order->getDeliveryPrice();
			$arCalculateParams = array();
			$arCalculateParams['BASE_CURRENCY'] = $baseCurrency;
			$arCalculateParams['CURRENCY_LIST'] = $currencyList;
			$arCalculateParams['ORDER_CURRENCY'] = $orderCurrency;
			$arCalculateParams['CUR_PAGE'] = $curPage;


			foreach($deliveryIds as $deliveryId)
			{
				if($deliveryId > 0)
				{
					$service = Delivery\Services\Manager::getById($deliveryId);

					if(strpos($service['CLASS_NAME'], '\Sale\Handlers\Delivery\RussianpostProfile') !== false)
					{
						$deliveryType = $service['CONFIG']['MAIN']['SERVICE_TYPE'];
						$idCalculatedDelivery = $deliveryId;
						$profileCurrency = $service['CURRENCY'];
						$arCalculateParams['PROFILE_CURRENCY'] = $profileCurrency;
						$arCalculateParams['CALCULATED_DELIVERY'] = $idCalculatedDelivery;
						break;
					}
				}
			}
			if($this->calculateId != $idCalculatedDelivery || $this->calcPrice != $orderPrice)
			{
				//lo($_REQUEST);
				$this->calculateId = $idCalculatedDelivery;
				$this->calcPrice = $orderPrice;
				$requestBitrix = \Bitrix\Main\Context::getCurrent()->getRequest();
				if($requestBitrix->isAdminSection())
				{
					if(isset($_REQUEST['formData']))
					{
						$arRequest = $_REQUEST['formData'];
					}
					else
					{
						$arRequest = $_REQUEST;
					}
					$arShipmetsDeliveryIds = array();
					foreach ($arRequest['SHIPMENT'] as $arShipment)
					{
						$arShipmetsDeliveryIds[$arShipment['DELIVERY_ID']] = $arShipment['DELIVERY_ID'];
					}
				}
				if(strpos($curPage, $b24path['ORDER']) !== false || strpos($curPage, $b24path['SHIPMENT']) !== false ||
					strpos($curPage, $b24path['ORDER1']) !== false || strpos($curPage, $b24path['SHIPMENT1']) !== false)
				{
					if(isset($_REQUEST['FORM_DATA']))
					{
						$arRequest = $_REQUEST['FORM_DATA'];
					}
					else
					{
						$arRequest = $_REQUEST;
					}
					$arShipmetsDeliveryIds = array();
					foreach ($arRequest['SHIPMENT'] as $arShipment)
					{
						$arShipmetsDeliveryIds[$arShipment['DELIVERY_ID']] = $arShipment['DELIVERY_ID'];
					}
				}
				if($deliveryType == self::PROFILE_PICKUP)
				{
					if($this->fillData == '')
						$this->fillData = 'fill data post';
					$arCalculateParams['PROFILE'] = self::PROFILE_PICKUP;
					if($requestBitrix->isAdminSection())
					{
						if ($deliveryTypeProp = \CSaleOrderProps::GetList(array(), array('PERSON_TYPE_ID' => $order->getPersonTypeId(), 'CODE' => 'RUSSIANPOST_TYPEDLV'))->Fetch())
						{
							$delivery_type_prop_id = $deliveryTypeProp['ID'];
						}
						else
						{
							$delivery_type_prop_id = 0;
						}
						if(isset($arRequest['russianpost_admin_data']) && $arRequest['russianpost_admin_data'] == 'Y')
						{
							$_SESSION['russianpost_post_calc']['shipment_type'] = $arRequest['PROPERTIES'][$delivery_type_prop_id];
							$result = Tools::CalculateProfile($shipment, $arCalculateParams, $_REQUEST);
						}
						else
						{
							unset($_SESSION['russianpost_post_calc']['select_pvz']);
							$result = Tools::CalculateProfile($shipment, $arCalculateParams, $_REQUEST);
						}
					}
					elseif(strpos($curPage, $b24path['ORDER']) !== false || strpos($curPage, $b24path['SHIPMENT']) !== false ||
						strpos($curPage, $b24path['ORDER1']) !== false || strpos($curPage, $b24path['SHIPMENT1']) !== false)
					{
						if ($deliveryTypeProp = \CSaleOrderProps::GetList(array(), array('PERSON_TYPE_ID' => $order->getPersonTypeId(), 'CODE' => 'RUSSIANPOST_TYPEDLV'))->Fetch())
						{
							$delivery_type_prop_id = $deliveryTypeProp['ID'];
						}
						else
						{
							$delivery_type_prop_id = 0;
						}
						if(isset($arRequest['russianpost_crm_data']) && $arRequest['russianpost_crm_data'] == 'Y')
						{
							#currency convertation
							/*if($baseCurrency != 'RUB' && isset($currencyList['RUB']))
							{
								$priceTmp = \CCurrencyRates::ConvertCurrency(($arRequest['russianpost_result_price']/100), "RUB", $baseCurrency);
							}
							else*/if($profileCurrency!= '' && $profileCurrency != 'RUB' && isset($currencyList['RUB']))
							{
								$priceTmp = \CCurrencyRates::ConvertCurrency(($arRequest['russianpost_result_price']/100), "RUB", $profileCurrency);
							}
							else
							{
								$priceTmp = $arRequest['russianpost_result_price']/100;
							}

							$result->setDeliveryPrice(
								roundEx(
									$priceTmp,
									SALE_VALUE_PRECISION
								)
							);
							if(LANG_CHARSET == 'windows-1251')
							{
								$arRequest['russianpost_delivery_description'] = iconv("UTF-8", "WINDOWS-1251", $arRequest['russianpost_delivery_description']);
							}
							if(isset($_REQUEST['order']['russianpost_delivery_description']) && $_REQUEST['order']['russianpost_delivery_description'] != '')
							{
								$result->setPeriodDescription($arRequest['russianpost_delivery_description']);
								$_SESSION['russianpost_post_calc']['delivery_description'] = $arRequest['russianpost_delivery_description'];
							}
							$_SESSION['russianpost_post_calc']['shipment_type'] = $arRequest['PROPERTY_'.$delivery_type_prop_id];
						}
						else
						{
							unset($_SESSION['russianpost_post_calc']['select_pvz']);
							$result = Tools::CalculateProfile($shipment, $arCalculateParams, $_REQUEST);
						}
					}
					else
					{
						$order = $shipment->getCollection()->getOrder(); // заказ
						$props = $order->getPropertyCollection();
						$locProp = $props->getDeliveryLocation();
						if($locProp)
						{
							$locationCode = $locProp->getValue();
						}
						if(isset($_REQUEST['order']['russianpost_select_location']) && $_REQUEST['order']['russianpost_select_location']!=''
							&& $locationCode!='' && $locationCode!= $_REQUEST['order']['russianpost_select_location'])
						{
							$_REQUEST['order']['russianpost_result_address'] = '';
							$_REQUEST['order']['russianpost_street_address'] = '';
							unset($_SESSION['russianpost_post_calc']['select_pvz']);
							$_SESSION['russianpost_post_calc']['clear_address'] = 'Y';
						}
						if((isset($_REQUEST['order']['russianpost_result_price']) && $_REQUEST['order']['russianpost_result_price']>=0)
							&& (isset($_REQUEST['order']['DELIVERY_ID']) && $_REQUEST['order']['DELIVERY_ID'] == $idCalculatedDelivery) && $_REQUEST['order']['russianpost_select_pvz'] == 'Y'
							&& ($_REQUEST['order']['russianpost_result_address'] != '' || $_REQUEST['order']['russianpost_street_address'] != ''))
						{
							#currency convertation
							/*if($baseCurrency != 'RUB' && isset($currencyList['RUB']))
							{
								$priceTmp = \CCurrencyRates::ConvertCurrency(($_REQUEST['order']['russianpost_result_price']/100), "RUB", $baseCurrency);
							}
							else*/if($profileCurrency!='' && $profileCurrency != 'RUB' && isset($currencyList['RUB']))
							{
								$priceTmp = \CCurrencyRates::ConvertCurrency(($_REQUEST['order']['russianpost_result_price']/100), "RUB", $profileCurrency);
							}
							else
							{
								$priceTmp = $_REQUEST['order']['russianpost_result_price']/100;
							}

							$result->setDeliveryPrice(
								roundEx(
									$priceTmp,
									SALE_VALUE_PRECISION
								)
							);
							if(LANG_CHARSET == 'windows-1251')
							{
								$_REQUEST['order']['russianpost_delivery_description'] = iconv("UTF-8", "WINDOWS-1251", $_REQUEST['order']['russianpost_delivery_description']);
							}
							if(isset($_REQUEST['order']['russianpost_delivery_description']) && $_REQUEST['order']['russianpost_delivery_description'] != '')
							{
								$result->setPeriodDescription($_REQUEST['order']['russianpost_delivery_description']);
								$_SESSION['russianpost_post_calc']['delivery_description'] = $_REQUEST['order']['russianpost_delivery_description'];
							}
							if(isset($_REQUEST['order']['DELIVERY_ID']) && $_REQUEST['order']['DELIVERY_ID'] == $idCalculatedDelivery)
							{
								$_SESSION['russianpost_post_calc']['price'] = $_REQUEST['order']['russianpost_result_price'];
								$_SESSION['russianpost_post_calc']['shipment_type'] = $_REQUEST['order']['russianpost_result_type'];
								$_SESSION['russianpost_post_calc']['select_pvz'] = $_REQUEST['order']['russianpost_select_pvz'];
							}
							else
							{
								unset($_SESSION['russianpost_post_calc']['select_pvz']);
							}

						}
						else
						{
							if(isset($_SESSION['russianpost_post_calc']['price'])
								&& (isset($_SESSION['russianpost_post_calc']['checked_delivery']) && $_SESSION['russianpost_post_calc']['checked_delivery'] == $idCalculatedDelivery)
								&& $_SESSION['russianpost_post_calc']['select_pvz'] == 'Y')
							{
								$result->setDeliveryPrice(
									roundEx(
										$_SESSION['russianpost_post_calc']['price']/100,
										SALE_VALUE_PRECISION
									)
								);
								if(isset($_SESSION['russianpost_post_calc']['delivery_description']))
								{
									$result->setPeriodDescription($_SESSION['russianpost_post_calc']['delivery_description']);
								}
							}
							else
							{
								unset($_SESSION['russianpost_post_calc']['select_pvz']);
								if($_SESSION['russianpost_post_calc']['old_type'] != 'OLD')
								{
									$arCalculateParams['FIX_PRICE'] = 'FIX';
								}
								$result = Tools::CalculateProfile($shipment, $arCalculateParams, $_REQUEST);
							}

						}
					}
				}
				if($deliveryType == self::PROFILE_COURIER)
				{
					if($this->fillData == '')
						$this->fillData = 'fill data courier';
					$arCalculateParams['PROFILE'] = self::PROFILE_COURIER;
					$arCalculateParams['ADMIN_SECTION'] = $requestBitrix->isAdminSection();
					$result = Tools::CalculateProfile($shipment, $arCalculateParams, $_REQUEST);
				}
				if($deliveryType == self::PROFILE_WORLDPICKUP)
				{
					$arCalculateParams['PROFILE'] = self::PROFILE_WORLDPICKUP;
					$arCalculateParams['ADMIN_SECTION'] = $requestBitrix->isAdminSection();
					$result = Tools::CalculateProfile($shipment, $arCalculateParams, $_REQUEST);
				}
				if($deliveryType == self::PROFILE_WORLDCOURIER)
				{
					$arCalculateParams['PROFILE'] = self::PROFILE_WORLDCOURIER;
					$arCalculateParams['ADMIN_SECTION'] = $requestBitrix->isAdminSection();
					$result = Tools::CalculateProfile($shipment, $arCalculateParams, $_REQUEST);
				}
				if($deliveryType == self::PROFILE_PICKUPNOTE)
				{
					$arCalculateParams['PROFILE'] = self::PROFILE_PICKUPNOTE;
					$arCalculateParams['ADMIN_SECTION'] = $requestBitrix->isAdminSection();
					$result = Tools::CalculateProfile($shipment, $arCalculateParams, $_REQUEST);

				}
				$this->calcResult = $result;
			}
			else
			{
				if(!empty($this->calcResult))
					$result = $this->calcResult;
			}
		}
		return $result;
	}

	protected function 	inheritParams()
	{
		if(strlen($this->name) <= 0) $this->name = $this->parent->getName();
		if(intval($this->logotip) <= 0) $this->logotip = $this->parent->getLogotip();
		if(strlen($this->description) <= 0) $this->description = $this->parent->getDescription();

		if(empty($this->trackingParams)) $this->trackingParams = $this->parent->getTrackingParams();
		if(strlen($this->trackingClass) <= 0) $this->trackingClass = $this->parent->getTrackingClass();

		/*$parentES = \Bitrix\Sale\Delivery\ExtraServices\Manager::getExtraServicesList($this->parentId);
		$allowEsCodes = self::getProfileES($this->serviceType);

		if(!empty($parentES))
		{
			foreach($parentES as $esFields)
			{
				if(
					strlen($esFields['CODE']) > 0
					&& !$this->extraServices->getItemByCode($esFields['CODE'])
					&& in_array($esFields['CODE'], $allowEsCodes)
				)
				{
					$this->extraServices->addItem($esFields, $this->currency);
				}
			}
		}*/
	}

	public static function onAfterAdd($serviceId, array $fields = array())
	{
		if($serviceId <= 0)
			return false;

		$result = true;
		if (isset($_REQUEST['PROFILE_ID']) && intval($_REQUEST['PROFILE_ID']) > 0)
		{
			$srv = new self($fields);
			$srvParams = $srv->parent->getProfileDefaultParamsByServType(intval($_REQUEST['PROFILE_ID']));
			$srvParams['NAME'] = $fields['NAME'];
			$srvParams['DESCRIPTION'] = $fields['DESCRIPTION'];
			$srvParams['LOGOTIP'] = $fields['LOGOTIP'];
			$res = Manager::update($serviceId, $srvParams);
			$result = $result && $res->isSuccess();
		}

		return $result;
	}
}
?>