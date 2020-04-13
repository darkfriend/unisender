<?php
/**
 * Created by PhpStorm.
 * User: darkfriend <hi@darkfriend.ru>
 * Date: 11.04.2020
 * Time: 13:55
 */

namespace Dev2fun\UniSender;


use darkfriend\helpers\Curl;

/**
 * Class UniSenderApi
 * @package Dev2fun\UniSender
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.0.0
 */
class UniSenderApi extends \Unisender\ApiWrapper\UnisenderApi
{
    protected $lang = 'ru';
    public $lastError;

    public function sendEmail(array $params)
    {
        $result = parent::sendEmail($params);
        if(isset($result['result'])) {
            $result = $result['result'];
        }
        if(!empty($result[0]['errors'])) {
            $this->lastError = $result[0]['errors'];
            return false;
        }
        return $result;
    }

    /**
     * @param string $methodName
     * @param array $params
     * @param array $options
     * @return array
     */
    protected function callMethod($methodName, $params = [], $options = [])
    {
        if ($this->platform !== '') {
            $params['platform'] = $this->platform;
        }

        if(empty($params['lang'])) {
            $params['lang'] = \LANGUAGE_ID;
        }

        if (strtoupper($this->encoding) !== 'UTF-8') {
            if (function_exists('iconv')) {
                array_walk_recursive($params, [$this, 'iconv']);
            } elseif (function_exists('mb_convert_encoding')) {
                array_walk_recursive($params, [$this, 'mb_convert_encoding']);
            }
        }

        $url = $methodName.'?format=json';

//        if ($this->compression) {
//            $url .= '&api_key='.$this->apiKey.'&request_compression=bzip2';
//            $content = bzcompress(http_build_query($params));
//        }
        $params = array_merge((array) $params, ['api_key' => $this->apiKey]);

//        $contextOptions = [
//            'http' => [
//                'method' => 'POST',
//                'header' => 'Content-type: application/x-www-form-urlencoded',
//                'content' => $content,
//            ],
//            'ssl' => [
//                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
//            ]
//        ];

        $options = array();

        if ($this->timeout) {
            $options['timeout'] = $this->timeout;
        }

        $retryCount = 0;
        $curl = Curl::getInstance($options);

        do {
            $host = $this->getApiHost();
            $result = $curl->request(
                $host.$url,
                $params,
                'post',
                'application/x-www-form-urlencoded'
            );
            ++$retryCount;
        } while ($result === false && $retryCount < $this->retryCount);

//        if(!empty($result['result'])) {
//            return $result['result'];
//        }
        return $result;
    }
}