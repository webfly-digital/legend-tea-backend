<?

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency;
use Bitrix\Iblock;
use Bitrix\Catalog;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals\FacebookConversion;
use Bitrix\Iblock\Component\Element;
use Bitrix\Main\Engine\Contract\Controllerable;


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();


class pdfProductComponent extends CBitrixComponent implements Controllerable
{
    public function configureActions()
    {
        return [
            'generatePdf' => [
                'prefilters' => [],
            ],
        ];
    }


    public function getData()
    {
        \Bitrix\Main\Loader::includeModule('iblock');
        $filter['ID'] = $this->arParams['ELEMENT_ID'];

        $res = \CIBlockElement::GetList([], $filter, false, false, ['IBLOCK_ID', "ID", "*"],);
        while ($ob = $res->GetNextElement()) {
            $elem = $ob->GetFields();
            $sectAr[] = $elem['IBLOCK_SECTION_ID'];
            $elem['PROPERTIES'] = $ob->GetProperties();

            if ($elem['PROPERTIES']['HIT']['VALUE']) {
                switch (mb_strtolower($elem['PROPERTIES']['HIT']['VALUE'])) {
                    case 'новинка':
                        $elem['LABEL'] = ['CLASS' => 'green', 'ICON' => 'new', 'TEXT' => 'Новинка'];
                        break;
                    case 'хит':
                        $elem['LABEL'] = ['CLASS' => 'red', 'ICON' => 'fire', 'TEXT' => 'Хит'];
                        break;
                    case 'рекомендуем':
                        $elem['LABEL'] = ['CLASS' => 'yellow', 'ICON' => 'like', 'TEXT' => 'Советуем'];
                        break;
                    default:
                        break;
                }
            }
            $this->arResult['ELEMENTS'][] = $elem;
        }

        if ($sectAr) {
            foreach ($sectAr as $key => $sect) {
                $nav = \CIBlockSection::GetNavChain(false, $sect);
                $arSection = $nav->GetNext();
                if ($arSection['ID']) {
                    if ($arSection['ID'] == ID_SECTION_CHAY) $this->arResult['ELEMENTS'][$key]['TYPE'] = 'TEA';
                    if (in_array($arSection['ID'], [ID_SECTION_MONTIS])) $this->arResult['ELEMENTS'][$key]['TYPE'] = 'COFFFEE';



                    if ($this->arParams['PROFILE_ID']) {
                        $logo = $this->getProfileLogo();
                        if (!empty($logo)) {
                            $this->arResult['ELEMENTS'][$key]['LOGO'] = $logo;
                        }
                    }
                    if ($this->arParams['LOGO']) {
                        $this->arResult['ELEMENTS'][$key]['LOGO'] = $this->arParams['LOGO'];
                    }
                }
            }
        }
    }

    public function getProfileLogo()
    {
        \Bitrix\Main\Loader::includeModule('sale');

        $propValsLogo = \CSaleOrderUserPropsValue::GetList([], array("!VALUE" => false, 'PERSON_TYPE_ID' => B2B_UR_PERSON_TYPE_ID, 'CODE' => 'LOGO', 'USER_PROPS_ID' => $this->arParams['PROFILE_ID']));
        while ($itemLogo = $propValsLogo->fetch()) {

            if (is_numeric($itemLogo['VALUE'])) {
                $value = $itemLogo['VALUE'];
                if ($value) $res = CFile::getPath($value);
            } else {
                $value = unserialize($itemLogo['VALUE']);
                if ($value) $res = CFile::getPath(current($value));
            }
        }

        return $res;
    }

    public function generatePdfAction($formData)
    {
        if (empty($formData['ID_ELEM'])) return;

        $this->arParams['ELEMENT_ID'] = $formData['ID_ELEM'];
        if ($formData['PROFILE_ID']) $this->arParams['PROFILE_ID'] = $formData['PROFILE_ID'];
        if ($formData['LOGO']) $this->arParams['LOGO'] = $formData['LOGO'];
        $this->getData();

        $output = [];
        ob_start();
        $this->includeComponentTemplate('product');
        $output['product'] = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}
