<?php

namespace Dev2fun\UniSender;


use darkfriend\helpers\CurlHelper;

/**
 * Class Posting
 * @package Dev2fun\UniSender
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.0.0
 */
class Posting
{
    protected static $posts = [];

    public static function getById($id)
    {
        if(!isset(self::$posts[$id])) {
            self::$posts[$id] = \CPostingGeneral::GetByID($id)->Fetch();
        }
        return self::$posts[$id];
    }

    public static function getType($id)
    {
        $post = self::getById($id);

        if(empty($post['BODY_TYPE'])) {
            return 'text';
        }

        return $post['BODY_TYPE'];
    }

    public static function send($args)
    {
        $curl = CurlHelper::getInstance(true);
        return $curl->request(
            self::$url,
            [
                'key' => self::$key,
                'message' => $args,
            ],
            'post',
            'json',
            'json'
        );
    }
}