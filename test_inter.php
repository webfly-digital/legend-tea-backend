<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?
global $USER;
if (!$USER->IsAdmin()) die;


\Bitrix\Main\Loader::includeModule('main');

use Bitrix\Main\UserTable;
use Bitrix\Main;


if (!function_exists('mb_ucfirst') && extension_loaded('mbstring')) {
    /**
     * mb_ucfirst - преобразует первый символ в верхний регистр
     * @param string $str - строка
     * @param string $encoding - кодировка, по-умолчанию UTF-8
     * @return string
     */
    function mb_ucfirst($str, $encoding = 'UTF-8')
    {
        $str = mb_ereg_replace('^[\ ]+', '', $str);
        $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) .
            mb_substr($str, 1, mb_strlen($str), $encoding);
        return $str;
    }
}

?>

<form action="/test_inter.php">
    <input name="step" value="1" hidden>
    <span>По чему будем схлопывать</span>
    <select name="type">
        <option value="phone" <?= $_REQUEST['type'] == 'phone' ? 'selected' : "" ?>>phone</option>
        <option value="mail" <?= $_REQUEST['type'] == 'mail' ? 'selected' : "" ?>>mail</option>
    </select>
    <input name="value" placeholder="телефон или имейл" value="<?= $_REQUEST['value'] ?>"><br>
    <input name="ID" placeholder="основной ИД юзера" value="<?= $_REQUEST['ID'] ?>"><br>
    <input name="UF_CONTACT_GUID" placeholder="UF_CONTACT_GUID" value="<?= $_REQUEST['UF_CONTACT_GUID'] ?>"><br>
    <input name="UF_PARTNER_GUID" placeholder="UF_PARTNER_GUID" value="<?= $_REQUEST['UF_PARTNER_GUID'] ?>"><br>
    <input name="UPD_PROFILE" type="checkbox" <?= $_REQUEST['UPD_PROFILE'] != 'on' ?: 'checked' ?>>UPD_PROFILE<br>
    <input name="UPD_ORDER" type="checkbox" <?= $_REQUEST['UPD_ORDER'] != 'on' ?: 'checked' ?>>UPD_ORDER<br>
    <input name="UPD_ACCOUNT" type="checkbox" <?= $_REQUEST['UPD_ACCOUNT'] != 'on' ?: 'checked' ?>>UPD_ACCOUNT<br>
    <input name="UPD_USER" type="checkbox" <?= $_REQUEST['UPD_USER'] != 'on' ?: 'checked' ?>>UPD_USER<br>
    <input name="DEL_USER" type="checkbox" <?= $_REQUEST['DEL_USER'] != 'on' ?: 'checked' ?>>DEL_USER<br>
    <input id='ALL' name="ALL" type="checkbox" <?= $_REQUEST['ALL'] != 'on' ?: 'checked' ?>>ALL<br>
    <button type="submit">найти дубли</button>
</form>
<br>
<form action="/test_inter.php" style="margin-bottom: 36px">
    <button type="submit">сброс</button>
</form>
<script>

    BX('ALL').addEventListener("click", function (e) {
       document.querySelector('input[name="UPD_PROFILE"]').click()
        document.querySelector('input[name="UPD_ORDER"]').click()
        document.querySelector('input[name="UPD_ACCOUNT"]').click()
        document.querySelector('input[name="UPD_USER"]').click()
        document.querySelector('input[name="DEL_USER"]').click()
    })

</script>
<?

if ($_REQUEST['step'] == 1):
    if (empty($_REQUEST['value']) || empty($_REQUEST['type'])) die;
    $filter = [];

    if ($_REQUEST['type'] == "phone") {
        $ex = new \Webfly\Download\UserFrom1C();
        $phoneTrim = $ex->getPhoneFormat($_REQUEST['value']);
        $filter[] = ['%PHONE_NUMBER' => $phoneTrim,];
        $filter[] = ['%PERSONAL_PHONE' => $phoneTrim];
        $filter[] = ['%PERSONAL_MOBILE' => $phoneTrim];
        $filter[] = ['%WORK_PHONE' => $phoneTrim];

        $filter[] = ['%PHONE_NUMBER_CLEAR' => $phoneTrim];
        $filter[] = ['%PERSONAL_PHONE_CLEAR' => $phoneTrim];
        $filter[] = ['%PERSONAL_MOBILE_CLEAR' => $phoneTrim];
        $filter[] = ['%WORK_PHONE_CLEAR' => $phoneTrim];
    }
    if ($_REQUEST['type'] == "mail") $filter['EMAIL'] = $_REQUEST['value'];

    if (!empty($filter)) {
        $filterOr = ['LOGIC' => 'OR'] + $filter;
    }

    if (empty($filterOr)) die;

    $res = UserTable::getList([
        'order' => ['ID' => 'desc',],
        'select' => ['EMAIL', 'ID', 'LOGIN', 'NAME', 'LAST_NAME', 'GROUP_' => 'USER_GROUP', 'GROUP_ID' => 'GROUP_GROUP_ID', 'WORK_PHONE', 'WORK_PHONE_CLEAR', 'PERSONAL_PHONE', 'PERSONAL_PHONE_CLEAR', 'PERSONAL_MOBILE', 'PERSONAL_MOBILE_CLEAR', 'PHONE_NUMBER_CLEAR', 'PHONE_NUMBER' => 'PHONE_AUTH.PHONE_NUMBER',],
        'filter' => $filterOr,
        'runtime' => [
            new \Bitrix\Main\Entity\ReferenceField('USER_GROUP', '\Bitrix\Main\UserGroupTable', array('=this.ID' => 'ref.USER_ID'), array('join_type' => 'LEFT')),
            new \Bitrix\Main\Entity\ExpressionField('PERSONAL_PHONE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['PERSONAL_PHONE']),
            new \Bitrix\Main\Entity\ExpressionField('PERSONAL_MOBILE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['PERSONAL_MOBILE']),
            new \Bitrix\Main\Entity\ExpressionField('PHONE_NUMBER_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['PHONE_AUTH.PHONE_NUMBER']),
            new \Bitrix\Main\Entity\ExpressionField('WORK_PHONE_CLEAR', 'REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(%s, "-", ""), "+", ""), "(", ""), ")", ""), " ", "")', ['WORK_PHONE']),
        ]
    ]);

    while ($item = $res->fetch()) {
        $arGroup = \Bitrix\Main\UserTable::getUserGroupIds($item['ID']);

//        if (count($arGroup) == 1 && current($arGroup) == 2) {
//        } else {
            $orderCount = \Bitrix\Sale\OrderTable::getCount(array('USER_ID' => $item['ID']));
            $item['ORDER_COUNT'] = $orderCount;
            $item['LINK'] = 'https://legend-tea.ru/bitrix/admin/user_edit.php?lang=ru&amp;ID=' . $item['ID'];
            if ($_REQUEST['type'] == "phone") {
                $key = $_REQUEST['value'];
            }
            if ($_REQUEST['type'] == "mail") $key = mb_strtolower($item["EMAIL"]);
            $arGroup[$key][$item['GROUP_ID']] = $item['GROUP_ID'];
            $arLogin[$key][$item['ID']] = $item['LOGIN'];
            $arList[$key][$item['ID']] = $item;

      //  }
    }

    echo '<pre>';
    var_dump($arList);
    echo '</pre>';

    if (empty($arList) && empty($_REQUEST['ID'])) die;


    foreach ($arList as $key => $arUsers) {
        $Ar = [];
        $uniqAr = [];
        $arCount = [];
        foreach ($arUsers as $idKey => $users) {
            $arList[$key]['GROUP_IDS'] = $arGroup[$key];
            $arList[$key]['IDS'] = array_keys($arUsers);
            $arList[$key]['ORDER_COUNT'] += $users['ORDER_COUNT'];
        }
    }


    foreach ($arList as $user) {
        $mainID = $_REQUEST['ID'];
        foreach ($user["IDS"] as $id) if ($id != $mainID) $listIdUpd[] = $id;

        if ($user["ORDER_COUNT"] > 0) {
            echo '<pre>';
            var_dump('mainID ' . $mainID);
            var_dump('user_ids_upd');
            var_dump($listIdUpd);
            echo '</pre>';


            $db_sales = CSaleOrderUserProps::GetList([], ["USER_ID" => $listIdUpd]);
            while ($ar_sales = $db_sales->Fetch()) {
                echo '<pre>';
                var_dump("UPD_PROFILE");
                var_dump($ar_sales);
                echo '</pre>';

                if ($_REQUEST['UPD_PROFILE'] == 'on') {
                    $res = CSaleOrderUserProps::Update($ar_sales['ID'], ['USER_ID' => $mainID]);
                    if ($res) {
                        echo 'profile ok ' . $ar_sales['ID'];
                    } else {
                        echo 'profile ' . $ar_sales['ID'];
                        var_dump($res);
                        die;
                    }
                }

            }


            $arOrder = \Bitrix\Sale\Internals\OrderTable::getList(array('filter' => array('USER_ID' => $listIdUpd), 'select' => ['ID']));
            while ($orderRes = $arOrder->Fetch()) {
                echo '<pre>';
                var_dump("UPD_ORDER");
                var_dump($orderRes);
                echo '</pre>';

                if ($_REQUEST['UPD_ORDER'] == 'on') {
                    $res = CSaleOrder::Update($orderRes['ID'], ['USER_ID' => $mainID]);
                    if ($res) {
                        echo 'order ok ' . $orderRes['ID'];
                    } else {
                        echo 'order ' . $orderRes['ID'];
                        var_dump($res);
                        die;
                    }
                }
            }

            $accountAr = [];
            $accountSum = 0;
            $dbAccountCurrency = CSaleUserAccount::GetList([], ["USER_ID" => $user["IDS"]]);
            while ($arAccountCurrency = $dbAccountCurrency->Fetch()) {
                if ($arAccountCurrency['USER_ID'] != $mainID) {
                    echo '<pre>';
                    var_dump("UPD_ACCOUNT");
                    var_dump($arAccountCurrency);
                    echo '</pre>';
                }

                $accountAr[$arAccountCurrency['USER_ID']] = $arAccountCurrency["ID"];
                $accountSum += $arAccountCurrency["CURRENT_BUDGET"];
            }
            if ($_REQUEST['UPD_ACCOUNT'] == 'on') {
                if (!empty($accountAr)) {
                    echo '<pre>';
                    var_dump("ARR_ACCOUNT");
                    var_dump($accountAr);
                    echo '</pre>';
                    if (!$accountAr[$mainID]) {
                        $arFields = array("USER_ID" => $mainID, "CURRENCY" => "RUB", "CURRENT_BUDGET" => $accountSum);
                        $accountID = CSaleUserAccount::Add($arFields);
                        if ($accountID) {
                            $accountAr[$mainID] = $accountID;
                            echo ' accountAdd ok ' . $accountID;
                        } else {
                            echo ' accountAdd ' . $accountID;
                            var_dump($res);
                            die;
                        }
                    }

                    if ($accountAr[$mainID]) {
                        if (count($accountAr) == 1) {
                        } else {
                            $res = CSaleUserAccount::Update($accountAr[$mainID], ['CURRENT_BUDGET' => $accountSum]);

                            if ($res) {
                                $resTransact = CSaleUserTransact::GetList([], array("USER_ID" => array_keys($accountAr)), false, false, ['ID', 'USER_ID']);
                                while ($arFieldTransact = $resTransact->Fetch()) {

                                    echo '<pre>';
                                    var_dump('Transact');
                                    var_dump($arFieldTransact);
                                    echo '</pre>';
                                    if ($arFieldTransact['USER_ID'] != $mainID) {
                                        $res = CSaleUserTransact::Update($arFieldTransact['ID'], ['USER_ID' => $mainID]);
                                        if ($res) {
                                            echo ' accountTransact ok ' . $arFieldTransact['USER_ID'];
                                        } else {
                                            echo ' daccounTransact ' . $arFieldTransact['USER_ID'];
                                            var_dump($res);
                                            die;
                                        }
                                    }
                                }
                                echo ' accountUpd ok ' . $accountAr[$mainID] . ' ' . $accountSum;
                                unset($accountAr[$mainID]);
                            } else {
                                echo ' accountUpd ' . $accountAr[$mainID];
                                var_dump($res);
                                die;
                            }

                            foreach ($accountAr as $keyAcc => $account) {
                                $res = CSaleUserAccount::Update($account, ['CURRENT_BUDGET' => '0']);
                                if ($res) {
                                    echo ' accountDel ok ' . $account;
                                    unset($accountAr[$keyAcc]);
                                } else {
                                    echo ' accountDel ' . $account;
                                    var_dump($res);
                                    die;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (empty($user['GROUP_IDS'])) $user['GROUP_IDS'] = [];

        $userObj = new \CUser;
        $fields["LOGIN"] = $user[$mainID]['PERSONAL_PHONE']?:$user[$mainID]['WORK_PHONE'];
        $fields["GROUP_ID"] = array_merge($user['GROUP_IDS'], [6, 23, 10]);
        $fields["PHONE_NUMBER"] = $fields["LOGIN"];
        $fields["PERSONAL_PHONE"] = $fields["LOGIN"];
        $fields["PERSONAL_MOBILE"] = $fields["LOGIN"];
       // $fields["UF_CONTACT_GUID"] = $_REQUEST['UF_CONTACT_GUID'] ?: '';
       // $fields["UF_PARTNER_GUID"] = $_REQUEST['UF_PARTNER_GUID'] ?: "";
        $fields['UF_1C'] = 'да';


        if ($_REQUEST['UPD_USER'] == 'on') {
            $res = $userObj->Update($mainID, $fields);
            if ($res) {
                echo '<pre>';
                var_dump("UPD_USER");
                var_dump($fields);
                var_dump($res);
                echo '</pre>';
                if ($_REQUEST['DEL_USER'] == 'on') {
                    foreach ($listIdUpd as $idUser) {
                        $res = \CUser::Delete($idUser);

                        if ($res) {
                            echo 'del ok ' . $idUser;
                        } else {
                            echo 'del  ' . $idUser;
                            var_dump($res);
                            die;
                        }
                    }
                }
            } else {
                var_dump($mainID);
                $strError = $userObj->LAST_ERROR;
                var_dump($strError);
                die;
            }
        }


    }
    ?>

<? endif; ?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
