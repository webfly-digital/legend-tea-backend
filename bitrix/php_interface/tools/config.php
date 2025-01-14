<?php
//Iblocks
const CATALOG_IBLOCK_ID = 93;
const SKU_IBLOCK_ID = 94;
const CATALOG_PRICE_ID = 19;
const B2B_COMPANIES = 111;
const B2B_PRICE_ID = 24;
//HL
const HL_B2B_FAV = 12;
//Iblock sections
const COFFEE_SECTION_ID = 1437;
const COFFEE_LEGEND_SECTION_ID = 1820;
const TEA_SECTION_ID = 1445;
const TEA_LEGEND_SECTION_ID = 1821;
const SYRUP_SECTION_ID = 1420;
//Person types
const B2B_UR_PERSON_TYPE_ID = 5;
const B2B_FIZ_PERSON_TYPE_ID = 6;
const B2B_FIZ_FIO_PROP = ['COMPANY_UF_COMPANY_LAST_NAME', 'COMPANY_UF_COMPANY_NAME', 'COMPANY_UF_COMPANY_SECOND_NAME'];
//Order Props
const B2B_EDO_ORDER_PROP = 65;
//СД
const OFFICE_PICKUP_DELIVERY = 2;//СД Самовывоз
const RETAIL_PICKUP_DELIVERY = 85;//СД Самовывоз из розничной точки
const RUSSIAN_POST_DELIVERY = 89;//СД Почта России
const RUSSIAN_POST_TRACKING_URL = 'https://www.pochta.ru/tracking?barcode=';
const RUSSIAN_POST_TRACKING_FIELD = 'UF_CRM_BARCODE';
const SDEK_TRACKING_URL = 'https://www.cdek.ru/ru/tracking?order_id=';
const SDEK_TRACKING_FIELD = 'UF_CRM_1669901471445';
//Разное
const B2B_GROUP = 23;
const B2B_PAY_STATUS = 'PO';//с этого статуса можно оплачивать в b2b
const WORD_CODING = 'admin';


const PROP_CRM_CONTACT_GUID = 'UF_CRM_1723022437';

const  B2B_INFO_ORDER_PROP_GROUP_DELIVERY = [
    130 => [
        'IBLOCK_ID' => '130',
        'IBLOCK_PROP_ID' => '2481',
        'CODE_PROP' => 'DOCUMENTS',
        'PROP_IDS' => [
            5 => 100,
            6 => 103
        ],
        'CODE_CRM' => 'UF_CRM_1705390553'
    ],
    129 => [
        'IBLOCK_ID' => '129',
        'IBLOCK_PROP_ID' => '2480',
        'CODE_PROP' => 'STICKERS',
        'PROP_IDS' => [
            5 => 101,
            6 => 104
        ],
        'CODE_CRM' => 'UF_CRM_1705390523'
    ],
    128 => [
        'IBLOCK_ID' => '133',
        'IBLOCK_PROP_ID' => '2484',
        'CODE_PROP' => 'PACK',
        'PROP_IDS' => [
            5 => 102,
            6 => 105
        ],
        'CODE_CRM' => 'UF_CRM_1705390491'
    ],
    1 => [
        'CODE_PROP' => 'LAST_NAME_ORDER',
        'PROP_IDS' => [
            5 => 118,
            6 => 122
        ],
        'CODE_CRM' => 'UF_CRM_1723465193190'
    ],
    2 => [
        'CODE_PROP' => 'NAME_ORDER',
        'PROP_IDS' => [
            5 => 119,
            6 => 123
        ],
        'CODE_CRM' => 'UF_CRM_1723465183851'
    ],
    3 => [
        'CODE_PROP' => 'EMAIL_ORDER',
        'PROP_IDS' => [
            5 => 120,
            6 => 124
        ],
        'CODE_CRM' => 'UF_CRM_1723465214380'
    ],
    4 => [
        'CODE_PROP' => 'PHONE_ORDER',
        'PROP_IDS' => [
            5 => 121,
            6 => 125
        ],
        'CODE_CRM' => 'UF_CRM_1723465202126'
    ],
];

const DELIVERY_IBLOCK_ID = 136;
const PICKUP_DELIVERY_ID = 2;
const ID_USER_1C = 57;
const WEBHOOK_CODE_1C = 'q037yh71svm6wopa';
const CUSTOM_API_CODE_1C = '3rd9hyyrd9qg72hj';


//цены
const PRICE_BASE_OPT = 32;


//Б24
define('C_REST_WEB_HOOK_URL', 'https://crm.legend-tea.ru/rest/1/yly7dufvqyp4860p/');//адрес входящего вебхука
define('C_REST_LOG_TYPE_DUMP', true);//@todo: установить false, если нужно отключить логирование
define('C_REST_LOGS_DIR', $_SERVER["DOCUMENT_ROOT"] . '/_b24_log/');
//автозагружаемые классы
if (file_exists(TOOLS_FOLDER . "/autoload.php"))
    include_once(TOOLS_FOLDER . "/autoload.php");
//обработчики событий
if (file_exists(TOOLS_FOLDER . "/handlers.php"))
    include_once(TOOLS_FOLDER . "/handlers.php");

