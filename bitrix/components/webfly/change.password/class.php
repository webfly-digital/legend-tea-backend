<? use Bitrix\Main\Loader;
use Bitrix\Main\Grid\Declension;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

class ChangePasswordComponent extends CBitrixComponent
{
    protected function changePassword()
    {
        if (!check_bitrix_sessid()){
            $this->arResult['ERROR'] = 'Ваша сессия истекла, пожалуйста, перезагрузите страницу';
        }

        $postList = $this->request->getPostList();
        $userData = \CUser::GetByID($this->arResult["ID"])->Fetch();
        $checkOldPassword = \Bitrix\Main\Security\Password::equals($userData['PASSWORD'], $postList['OLD_PASSWORD']);
        if ($checkOldPassword) {
            if ($postList['NEW_PASSWORD'] != $postList['NEW_PASSWORD_CONFIRM']){
                $this->arResult['ERROR'] = 'Пароли не совпадают';
            }else{
                $user = new \CUser;
                $fields = [
                    "PASSWORD" => $postList['NEW_PASSWORD'],
                    "CONFIRM_PASSWORD" => $postList['NEW_PASSWORD_CONFIRM'],
                ];
                if (!$user->Update($this->arResult["ID"], $fields))
                    $this->arResult['ERROR'] = $user->LAST_ERROR;
                else
                    $this->arResult['MESSAGE'] = 'Пароль успешно сменен';
            }
        }else{
            $this->arResult['ERROR'] = 'Старый пароль указан неверно';
        }
    }

    public function executeComponent()
    {
        global $USER;
        if (!$USER->isAuthorized()) return;

        $this->arResult["ID"] = intval($USER->GetID());
        $this->arResult["LOGIN"] = $USER->GetLogin();
        $this->arResult["GROUP_POLICY"] = \CUser::GetGroupPolicy($this->arResult["ID"]);

        $letDeclension = new Declension('символ', 'символа', 'символов');
        $passMinLength = $this->arResult["GROUP_POLICY"]['PASSWORD_LENGTH'] ?: 0;
        $letText = $letDeclension->get($passMinLength);
        $this->arResult["GROUP_POLICY"]['PASS_TEXT'] = "Не менее {$passMinLength} {$letText}";

        $isPost = $this->request->isPost();
        if ($isPost) {
            $this->changePassword();
        }

        $this->includeComponentTemplate();
    }
}
