<?php

namespace Webfly\Helper;

use \Bitrix\Main\Web\HttpClient;

class DaData
{
    const URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/';
    const token = '74220cb0a27ac3ba3e3b2b0e2bf9984deba24a1f';
    private $url;
    private $token;

    public function __construct($token = self::token, $url = self::URL)
    {
        $this->token = $token;
        $this->url = $url;
    }

    public function suggest($data, $resource = 'suggest/address'){
        $httpClient = new HttpClient(["socketTimeout" => 120,"streamTimeout" => 120]);
        $httpClient->setHeader('Content-type', 'application/json', true);
        $httpClient->setHeader('Accept', 'application/json', true);
        $httpClient->setHeader('Authorization', 'Token '. $this->token, true);
        $httpClient->post($this->url . $resource, json_encode($data));

        $status = $httpClient->getStatus(); // код статуса ответа

        if ($status != 200) {
            $error = $httpClient->getError(); // массив ошибок
            $errText = "CI_ERROR [{$status}]. Err: " . print_r($error, true) . ". URL: " . $this->url . $resource;
            throw new \Exception($errText);
        }
        $res = $httpClient->getResult(); // текст ответа
        try {
            return json_decode($res);
        } catch (\Exception $e) {
            $errText = "CI_ERROR JsonParse: " . $e->getMessage() . ". URL: " . $this->url;
            throw new \Exception($errText);
        }


    }

}
