<?php

namespace Webfly\Helper;

class ClearTable
{

    public const CLEAR_TABLE_IBLOCK_ID = 139;
    public $arTable = [];
    public $delete = true;

    function __construct()
    {
    }

    public static function agent()
    {
        $example = new self();
        $example->execute();
        return '\Webfly\Helper\ClearTable::agent();';
    }


    public function execute()
    {
        \Bitrix\Main\Loader::includeModule("iblock");
        \Bitrix\Main\Loader::includeModule("crm");
        \Bitrix\Main\Loader::includeModule("mail");

        $this->getInfoTable();
        if (!empty($this->arTable)) {
            $this->getTable();
            $this->deleteItems();
        }
    }

    public function getInfoTable()
    {

        $resElem = \CIBlockElement::GetList([], array("IBLOCK_ID" => self::CLEAR_TABLE_IBLOCK_ID, "ACTIVE" => "Y"), false, false, array("ID", "PROPERTY_TABLE", "PROPERTY_DAYS"));
        while ($arElem = $resElem->fetch()) {
            $table['ID'] = $arElem['ID'];
            $table['DAYS'] = $arElem['PROPERTY_DAYS_VALUE'];
            $table['TABLE'] = $arElem['PROPERTY_TABLE_VALUE'];
            $this->arTable[] = $table;
        }
    }

    public function getTable()
    {
        foreach ($this->arTable as $key => $table) {
            $filter = false;
            $dbr = false;
            $dateFormat = false;
            $sort = ['ID' => 'ASC'];

            if ($table["DAYS"] && $table["TABLE"]) {
                $now = new \DateTime();
                $now->modify('-' . $table["DAYS"] . ' day');
                $dateFormat = $now->format('d.m.Y');

                switch ($table["TABLE"]) {
                    case 'b_mail_message':
                        $filter = ['<DATE_INSERT' => $dateFormat];
                        $params = [
                            'order' => $sort,
                            'filter' => $filter,
                            'select' => ['ID'],
                        ];
                        $dbr = \Bitrix\Mail\MailMessageTable::GetList($params);
                        break;
                    case 'b_crm_act':
                        $filter = ['<CREATED' => $dateFormat];
                        $dbr = \CCrmActivity::GetList($sort, $filter, false, false, ['ID']);
                        break;
                    case 'b_crm_event':
                        $filter = ['<DATE_CREATE' => $dateFormat];
                        $params = [
                            'order' => $sort,
                            'filter' => $filter,
                            'select' => ['ID'],
                        ];
                        $dbr = \Bitrix\Crm\EventTable::GetList($params);
                        break;
                }


                if (!empty($dbr) && $filter != false) {
                    while ($res = $dbr->fetch()) {
                        $this->arTable[$key]['IDS'][] = $res['ID'];
                    }

                }

            }
        }
    }

    public function deleteItems()
    {
        foreach ($this->arTable as $key => $table) {
            if ($table["DAYS"] && $table["TABLE"] && $table["IDS"]) {
//                $current = current($table["IDS"]);
//                $table["IDS"] = [];
//                $table["IDS"][] = $current;

                if ($this->delete) {
                    switch ($table["TABLE"]) {
                        case 'b_mail_message':
                            foreach ($table["IDS"] as $id) {
                                \CMailMessage::Delete($id);
                            }
                            unset($id);
                            break;
                        case 'b_crm_act':
                            foreach ($table["IDS"] as $id) {
                                \CCrmActivity::Delete($id);
                            }
                            unset($id);
                            break;
                        case 'b_crm_event':
                            foreach ($table["IDS"] as $id) {
                                $CCrmEvent = new \CCrmEvent();
                                $res = $CCrmEvent->Delete($id, ['CURRENT_USER' => 1]);
                            }
                            unset($id);
                            break;
                    }
                }

            }
        }

    }
}