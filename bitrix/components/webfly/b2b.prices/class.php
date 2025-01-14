<? use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

class B2BPriceslComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $idPrice = $this->request->get('price') ?: '';
        if ($idPrice) $this->arResult['SELECTED_PRICE'] = $idPrice;


        $this->arResult['PRICES'] = [
            ID_TYPE1_PRICE_B2B => '15.000',
            ID_TYPE2_PRICE_B2B => '50.000',
            ID_TYPE3_PRICE_B2B => '100.000'
        ];

        $this->includeComponentTemplate();

    }

}
