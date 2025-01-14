<? use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

class B2BOrderComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $this->arResult['SORT_FIELDS'] = [
            'SHOWS'=>'По популярности',
            'NAME'=>'По алфавиту',
            'PRICE_'.B2B_PRICE_ID=>'По цене',
        ];

        $sort = $this->request->get('sort')?:'';
        $order = $this->request->get('order')?:'';
        if ($sort && $order){
            $this->arResult['REQUEST'] = [
                'sort'=>$sort,
                'order'=>$order
            ];
        }
        $this->includeComponentTemplate();
    }
}
