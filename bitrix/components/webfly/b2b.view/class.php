<? use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

class B2BViewComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        global $USER;
        $userId = $USER->GetId();
        $propIdView = 385;
        $prop = [];
        $view = $this->request->get('view') ?: '';

        $obEnum = new \CUserFieldEnum;
        $rsEnum = $obEnum->GetList([], ['USER_FIELD_ID' => $propIdView]);
        while ($arEnum = $rsEnum->GetNext()) {
            $prop[$arEnum['ID']] = $arEnum;
            if ($view && $view == $arEnum['XML_ID']) $saveIdView = $arEnum['ID'];
        }

        if (!empty($view) && !empty($userId) && !empty($saveIdView)) {
            $user = new CUser;
            $fields = ["UF_VIEW" => $saveIdView];
            $res = $user->Update($userId, $fields);
        }


        if (!empty($userId)) {
            $order = ['sort' => 'asc'];
            $tmp = 'sort';
            $filter = ['ID' => $userId];
            $rsUsers = CUser::GetList($order, $tmp, $filter, ["SELECT" => ["UF_VIEW"]]);
            while ($arUser = $rsUsers->Fetch()) {
                if ($prop[$arUser['UF_VIEW']]) $selectedView = $prop[$arUser['UF_VIEW']]['XML_ID'];
            }
        }

        $this->arResult['SELECTED'] = 'image';
        if (!empty($selectedView)) {
            $this->arResult['SELECTED'] = $selectedView;
        }
        $this->includeComponentTemplate();


    }

}
