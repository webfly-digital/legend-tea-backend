<?php

use Bitrix\Main\Loader,
    Bitrix\Main\Engine\ActionFilter,
    Bitrix\Main\Engine\Contract\Controllerable,
    Bitrix\Highloadblock as HL,
    Bitrix\Main\Application,
    Bitrix\Main\Web\Cookie;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

Loader::includeModule('highloadblock');

class B2BFavoritesComponent extends CBitrixComponent implements Controllerable
{
    const COOKIE_NAME = 'B2B_FAVORITE';

    public function configureActions()
    {
        return [
            'getFavorite' => [
                'prefilters' => [],
            ],
            'addToFavorite' => [
                'prefilters' => [],
            ]
        ];
    }

    /**
     * Обновление состояние сердечек в списке товаров
     * @param $jsItems
     * @return array|string
     */
    public function getFavoriteAction($jsItems)
    {
        global $USER;
        if ($USER->isAuthorized()) {
            $res = $this->getFavoriteByUser($jsItems);
        } else {
            $res = $this->getFavoriteByGuest($jsItems);
        }
        return $res;
    }

    public function getUserFavorites()
    {
        global $USER;
        if ($USER->isAuthorized()) {
            $res = $this->getFavoriteByUser([]);
        } else {
            $res = $this->getFavoriteByGuest([]);
        }
        return $res;
    }

    protected function getFavoriteByUser($jsItems = [])
    {
        global $USER;
        $res = [];
        $userID = $USER->getId();
        if ($jsItems)
            $fields = ['UF_PRODUCT_ID' => array_keys($jsItems), '=UF_USER_ID' => $userID];
        else
            $fields = ['=UF_USER_ID' => $userID];
        $hlblock = HL\HighloadBlockTable::getById(HL_B2B_FAV)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entityClass = $entity->getDataClass();
        $items = $entityClass::getList(['filter' => $fields, 'select' => ['ID', 'UF_PRODUCT_ID']]);
        while ($ob = $items->fetch()) {
            if ($jsItems)
                $res[] = $jsItems[$ob['UF_PRODUCT_ID']];
            else
                $res[] = $ob['UF_PRODUCT_ID'];
        }
        return $res;
    }

    protected function getFavoriteByGuest($jsItems = [])
    {
        $res = [];
        $cookieName = self::COOKIE_NAME;

        $application = Application::getInstance();
        $context = $application->getContext();
        $request = $context->getRequest();

        $existsCookie = $request->getCookie($cookieName);

        $guestFavorites = [];

        if ($existsCookie) {
            $guestFavorites = unserialize($existsCookie);
            if (!is_array($guestFavorites)) {
                $guestFavorites = [];
            }
        }

        if ($guestFavorites) {
            foreach ($guestFavorites as $pID) {
                if ($jsItems)
                    $res[] = $jsItems[$pID];
                else
                    $res[] = $pID;
            }
        }
        return $res;
    }

    /**
     * Клик по сердечку
     * @param $productId
     * @return string
     */
    public function addToFavoriteAction($productId)
    {
        global $USER;
        if ($USER->isAuthorized()) {
            $res = $this->addToFavoriteByUser($productId);
        } else {
            $res = $this->addToFavoriteByGuest($productId);
        }

        return $res ?: 'ERROR';
    }

    /**
     * Добавляет/убирает товар из избранного (ХЛ блок)
     * у авторизованного юзера
     * @param $productId
     * @return string
     */
    protected function addToFavoriteByUser($productId)
    {
        global $USER;
        $res = 'ERROR';
        $userID = $USER->getId();
        $hlblock = HL\HighloadBlockTable::getById(HL_B2B_FAV)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entityClass = $entity->getDataClass();

        $item = $entityClass::getList(['filter' => ['=UF_PRODUCT_ID' => $productId, '=UF_USER_ID' => $userID], 'select' => ['ID'], 'limit' => 1])->fetch();

        if (!empty($item['ID'])) {
            $addRes = $entityClass::delete($item['ID']);
            if ($addRes->isSuccess()) {
                $res = 'DELETE';
            }
        } else {
            $addRes = $entityClass::add(['UF_PRODUCT_ID' => $productId, 'UF_USER_ID' => $userID]);
            if ($addRes->isSuccess()) {
                $res = 'ADD';
            }
        }
        return $res;
    }

    /**
     * Добавляет/убирает товар из избранного (куки)
     * у НЕавторизованного юзера
     * @param $productId
     * @return string
     */
    protected function addToFavoriteByGuest($productId)
    {
        $res = 'ERROR';
        $cookieName = self::COOKIE_NAME;

        $application = Application::getInstance();
        $context = $application->getContext();
        $response = $context->getResponse();
        $request = $context->getRequest();


        $existsCookie = $request->getCookie($cookieName);

        $guestFavorites = [];
        if ($existsCookie) {
            $guestFavorites = unserialize($existsCookie);
            if (!is_array($guestFavorites)) {
                $guestFavorites = [];
            }
            if (in_array($productId, $guestFavorites)) {//delete
                $guestFavorites = array_filter($guestFavorites, function ($var) use ($productId) {
                    return $var != $productId;
                });
                $res = 'DELETE';
            } else {//add
                $guestFavorites[] = $productId;
                $res = 'ADD';
            }
        } else {
            $guestFavorites[] = $productId;
            $res = 'ADD';
        }
        $favorites = serialize($guestFavorites);
        $cookie = new Cookie($cookieName, $favorites);
        $response->addCookie($cookie);
        $response->flush("");
        return $res;
    }

    public function executeComponent()
    {
        $favorites = $this->getUserFavorites();
        return $favorites;
    }
}
