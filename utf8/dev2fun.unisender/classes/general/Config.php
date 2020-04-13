<?php

namespace Dev2fun\UniSender;


use Bitrix\Main\Config\Option;

/**
 * Class Config
 * @package Dev2fun\UniSender
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.0.0
 */
class Config
{
    private $options;
    private static $instance;

    /**
     * Singleton instance.
     * @return self
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    public function init()
    {
        $this->options = Option::getForModule('dev2fun.unisender');
    }

    public function get($name)
    {
        return $this->options[$name];
    }

    public function set($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function setAll($arOption)
    {
        $this->options = array_merge(
            $this->options,
            $arOption
        );
    }
}