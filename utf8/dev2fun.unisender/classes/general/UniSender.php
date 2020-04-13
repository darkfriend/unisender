<?php

namespace Dev2fun\UniSender;

/**
 * Class UniSender
 * @package Dev2fun\UniSender
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.0.0
 */
class UniSender
{
    protected $key;
    protected $api;
    private static $_instance;

    public static function instance()
    {
        if(!self::$_instance) {
            self::$_instance = new self();
            self::$_instance->init();
        }
        return self::$_instance;
    }

    public function init()
    {
        $this->key = Base::getOption('apiKey', null);
        if($this->key) {
            $this->api = new UniSenderApi(
                $this->key,
                Base::getOption('encoding', 'UTF-8'),
                4
            );
        }
    }

    /**
     * @return UniSenderApi
     */
    public function api(){
        return $this->api;
    }

    public function hasKey()
    {
        return !empty($this->key);
    }

    public function getLists()
    {
        $result = $this->api()->getLists();
        if(empty($result['result'])) {
            return array();
        }
        return $result['result'];
    }
}