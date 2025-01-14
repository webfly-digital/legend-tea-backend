<?php require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

global $APPLICATION;
$APPLICATION->RestartBuffer();

header('Content-Type: application/json; charset=utf-8');

$result = [
    "result" => [],
    "total" => 0,
    "time" => [
        "start" => microtime(true),
        "finish" => 0,
        "duration" => 0,
        "processing" => 0,
        "date_start" => date("Y-m-d H:i:s"),
        "date_finish" => 0,
    ]
];

$code = explode("?", $_REQUEST["code"])[0];
$method = explode(".", $_REQUEST["method"])[0];
$id = $_REQUEST["id"];


if ($code == "3rd9hyyrd9qg72hj" && is_numeric($id) && $method == "user") {
    $rsUsers = CUser::GetList(($by = "id"), ($order = "desc"), ["ID" => $id],
        [
            "FIELDS" => [
                "ID",
                "ACTIVE",
                "NAME",
                "LAST_NAME",
                "EMAIL",
                "LAST_LOGIN",
                "DATE_REGISTER",
                "IS_ONLINE",
                "TIMESTAMP_X",
                "PERSONAL_GENDER",
                "PERSONAL_BIRTHDAY",
                "PERSONAL_PHONE",
                "PERSONAL_STREET",
                "PERSONAL_ZIP",
            ],
            "SELECT" => [
                "UF_EMPLOYMENT_DATE",
                "UF_DEPARTMENT",
                "UF_CONTACT_GUID",
                "UF_PARTNER_GUID",
            ]
        ]
    );

    if ($arUser = $rsUsers->Fetch()) {
        $result["total"] = 1;
        $result["result"][0]["ID"] = $arUser["ID"];
        $result["result"][0]["ACTIVE"] = ($arUser["ACTIVE"] == "Y") ? true : false;
        $result["result"][0]["NAME"] = $arUser["NAME"];
        $result["result"][0]["LAST_NAME"] = $arUser["LAST_NAME"];
        $result["result"][0]["EMAIL"] = $arUser["EMAIL"];
        $result["result"][0]["PERSONAL_PHONE"] = $arUser["PERSONAL_PHONE"];
        $result["result"][0]["UF_CONTACT_GUID"] = $arUser["UF_CONTACT_GUID"];
        $result["result"][0]["UF_PARTNER_GUID"] = $arUser["UF_PARTNER_GUID"];

    }
}

if ($code == "3rd9hyyrd9qg72hj" && is_numeric($id) && $method == "deal") {
    \Bitrix\Main\Loader::IncludeModule('crm');

    $resDeal = \Bitrix\Crm\Binding\OrderEntityTable::getOwnerByOrderId($id);

    if (!empty($resDeal["OWNER_ID"])) {
        $result["total"] = 1;
        $result["result"][0]["DEAL_ID"] = $resDeal["OWNER_ID"];
    }
}

if ($code == "3rd9hyyrd9qg72hj" && is_numeric($id) && $method == "delivery_profile") {

    $user_id = '';
    $db_sales = CSaleOrder::GetList(array(), ["ID" => $id]);
    while ($ar_sales = $db_sales->Fetch()) {
        $user_id = $ar_sales["USER_ID"];
    }

    if ($user_id) {
        $db_sales = CSaleOrderUserProps::GetList(array("ID" => "ASC"), array("USER_ID" => $user_id));
        while ($ar_sales = $db_sales->Fetch()) {

            $result["total"] = 1;
            $result["result"][0]["ID"] = $ar_sales["ID"];
            $result["result"][0]["NAME"] = $ar_sales["NAME"];
            if ($ar_sales["PERSON_TYPE_ID"] == 1 or $ar_sales["PERSON_TYPE_ID"] == 6) $result["result"][0]["USER_TYPE"] = "Физическое лицо";
            if ($ar_sales["PERSON_TYPE_ID"] == 2 or $ar_sales["PERSON_TYPE_ID"] == 5) $result["result"][0]["USER_TYPE"] = "Юридическое лицо";

            $db_propVals = CSaleOrderUserPropsValue::GetList(array("ID" => "ASC"), array("USER_PROPS_ID" => $ar_sales["ID"]));
            while ($arPropVals = $db_propVals->Fetch()) {
                if ($arPropVals["PROP_CODE"] == "EMAIL" or $arPropVals["PROP_CODE"] == "CONTACT_EMAIL" or $arPropVals["PROP_CODE"] == "COMPANY_EMAIL") {
                    $result["result"][0]["EMAIL"] = $arPropVals["VALUE"];
                }
                if ($arPropVals["PROP_CODE"] == "PHONE" or $arPropVals["PROP_CODE"] == "CONTACT_PHONE" or $arPropVals["PROP_CODE"] == "COMPANY_PHONE") {
                    $result["result"][0]["PHONE"] = $arPropVals["VALUE"];
                }
                if ($arPropVals["PROP_CODE"] == "INN") {
                    $result["result"][0]["INN"] = $arPropVals["VALUE"];
                }
                if ($arPropVals["PROP_CODE"] == "COMPANY_UF_CRM_1724146140") {
                    $result["result"][0]["COMPANY_UF_CRM_1724146140"] = $arPropVals["VALUE"];
                }
                if ($arPropVals["PROP_CODE"] == "1C_GUID_PARTNER") {
                    $result["result"][0]["1C_GUID_PARTNER"] = $arPropVals["VALUE"];
                }
            }
        }
    }
}


if ($_REQUEST["code"] == CUSTOM_API_CODE_1C) {
    $method = explode("?", $_REQUEST["method"])[0];
    if ($method == 'send_mail_new_order' && is_numeric($id)) {
        $res = \Webfly\Helper\Functions::sendMailNewOrder($id);
        $result["result"] = $res;
    }
}

$result["time"]["finish"] = microtime(true);
$result["time"]["duration"] = (float)$result["time"]["finish"] - (float)$result["time"]["start"];
$result["time"]["date_finish"] = date("Y-m-d H:i:s");
echo json_encode($result);

