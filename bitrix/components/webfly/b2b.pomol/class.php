<? use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

class B2BPomolComponent extends CBitrixComponent
{
    public function executeComponent()
    {

        global $USER;
        $userId = $USER->GetId();

        $pomol = $this->request->get('pomol') ?: '';
        if (!empty($pomol) && !empty($userId)) {
            $user = new CUser;
            $fields = array("UF_ID_POMOL" => $pomol);
            $res = $user->Update($userId, $fields);
        }

        foreach ($this->arParams['SECTION'] as $sId => $section) {
            $baseSection = CIBlockSection::GetNavChain($this->arParams['IBLOCK_ID'], $section, ['ID'])->fetch();
            $sectionView = \Webfly\Helper\Helper::getSectionView($baseSection['ID']);
        }

        if ($sectionView == 'UPAKOVKA_POMOL') {
            $linkPropertyID = 1785;
            $pomolPropertyId = 1795;

            $dbItems = \Bitrix\Iblock\ElementTable::getList(array(
                'select' => array('PROPERTY_POMOL.VALUE', 'PROPERTY_ENUM.VALUE', 'PROPERTY_ENUM.SORT'),
                'filter' => array(
                    'IBLOCK_ID' => 94,
                    'ACTIVE' => 'Y',
                    'PARENT_ELEMENT.ACTIVE' => 'Y',
                    'SECT_PARENT.DEPTH_LEVEL' => 1,
                    'SECT_PARENT.ID' => $baseSection,
                    'PROPERTY.IBLOCK_PROPERTY_ID' => $linkPropertyID,
                    'PROPERTY_POMOL.IBLOCK_PROPERTY_ID' => $pomolPropertyId
                ),
                'order' => ['PROPERTY_ENUM.SORT' => 'asc'],
                'limit' => 30, // целое число, ограничение выбираемого кол-ва
                'group' => 'PROPERTY_POMOL.VALUE',
                'runtime' => array(
                    new Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY',
                        '\Bitrix\Iblock\ElementPropertyTable',
                        array(
                            '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                        )
                    ),
                    new Bitrix\Main\Entity\ReferenceField(
                        'PARENT_ELEMENT',
                        '\Bitrix\Iblock\ElementTable',
                        array(
                            '=this.PROPERTY.VALUE' => 'ref.ID',
                        )
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'SECT',
                        '\Bitrix\Iblock\SectionTable',
                        array('=this.PARENT_ELEMENT.IBLOCK_SECTION_ID' => 'ref.ID')
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'SECT_PARENT',
                        '\Bitrix\Iblock\SectionTable',
                        [
                            '=this.PARENT_ELEMENT.IBLOCK_ID' => 'ref.IBLOCK_ID',
                            '>=this.SECT.LEFT_MARGIN' => 'ref.LEFT_MARGIN',
                            '<=this.SECT.RIGHT_MARGIN' => 'ref.RIGHT_MARGIN',
                        ]
                    ),
                    new Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY_POMOL',
                        '\Bitrix\Iblock\ElementPropertyTable',
                        array(
                            '=this.ID' => 'ref.IBLOCK_ELEMENT_ID',
                        )
                    ),
                    new Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY_ENUM',
                        '\Bitrix\Iblock\PropertyEnumerationTable',
                        array(
                            '=this.PROPERTY_POMOL.VALUE' => 'ref.ID'
                        )
                    ),

                ),
            ));
            if (!empty($dbItems)) {
                while ($arItem = $dbItems->fetch()) {
                    $arPomol[$arItem["IBLOCK_ELEMENT_PROPERTY_POMOL_VALUE"]] = $arItem["IBLOCK_ELEMENT_PROPERTY_ENUM_VALUE"];
                }
            }

            if (!empty($arPomol)) $this->arResult['POMOL_FIELDS'] = $arPomol;


            if (!empty($userId)) {
                $order = ['sort' => 'asc'];
                $tmp = 'sort';
                $filter = ['ID' => $userId];
                $rsUsers = CUser::GetList($order, $tmp, $filter, ["SELECT" => ["UF_ID_POMOL"]]);
                while ($arUser = $rsUsers->Fetch()) {
                    $selectedPomol = $arUser['UF_ID_POMOL'];
                }
            }


            if (!empty($selectedPomol)) {
                $this->arResult['SELECTED'] = $selectedPomol;
            }
            $this->includeComponentTemplate();
        }


    }

}
