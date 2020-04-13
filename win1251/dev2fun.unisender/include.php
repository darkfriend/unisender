<?php
/**
 * @author dev2fun (darkfriend)
 * @copyright darkfriend
 * @version 1.0.0
 */

namespace Dev2fun\UniSender;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

IncludeModuleLangFile(__FILE__);
include_once __DIR__.'/vendor/autoload.php';

\Bitrix\Main\Loader::registerAutoLoadClasses(
    'dev2fun.unisender',
    [
        'Dev2fun\UniSender\Base' => __FILE__,
        'Dev2fun\UniSender\Config' => 'classes/general/Config.php',
        'Dev2fun\UniSender\Posting' => 'classes/general/Posting.php',
        'Dev2fun\UniSender\UniSenderApi' => 'classes/general/UniSenderApi.php',
        'Dev2fun\UniSender\UniSender' => 'classes/general/UniSender.php',
    ]
);

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use darkfriend\helpers\DebugHelper;
use Unisender\ApiWrapper\UnisenderApi;

class Base
{
    public static $module_id = 'dev2fun.unisender';
    public static $languages = [
        'ru',
        'en',
        'ua',
    ];
    public static $requireFields = [
        'singleListId',
        'fromName',
        'senderEmail',
        'apiKey',
    ];
    public static $errors = [];

    public static function checkRequireFields($arFields)
    {
        self::$errors = [];
        foreach ($arFields as $keyField => $arField) {
            if(in_array($keyField, self::$requireFields) && empty($arField)) {
                self::$errors[] = $keyField;
            }
        }
        return empty(self::$errors);
    }

    public static function getOption($name, $default='')
    {
        return Option::get(self::$module_id, $name, $default);
    }

    public static function BeforePostingSendMail($arFields)
    {
        // TODO: disabled
        return $arFields;
        if(self::getOption('enableSubscribe', 'Y') !== 'Y') {
            return $arFields;
        }
        if(!self::getOption('apiKey', '')) {
            return $arFields;
        }
        if(!is_array($arFields) || empty($arFields)) {
            return $arFields;
        }
        $args = [
            'subject' => $arFields['SUBJECT'],
            'to' => [
                [
                    'email' => $arFields['EMAIL'],
                ]
            ],
            'track_opens' => true,
            'track_clicks' => true
        ];
        $post = Posting::getById($arFields['POSTING_ID']);
        if($post['BODY_TYPE']=='text') {
            $args['text'] = $arFields['BODY'];
            $args['html'] = null;
        } else {
            $args['html'] = $arFields['BODY'];
            $args['text'] = null;
        }
        $args['from_email'] = $post['FROM_FIELD'];
        $args['from_name'] = 'StarsTicket.de';

        if(!empty($post['BCC_FIELD'])) {
            $args['bcc_address'] = $post['BCC_FIELD'];
        }

        $result = Posting::send($args);

        \CEventLog::Add([
            'SEVERITY' => 'INFO',
            'AUDIT_TYPE_ID' => 'MailChimp',
            'MODULE_ID' => 'main',
            'DESCRIPTION' => print_r($result,true),
        ]);

        if(isset($result['status']) && $result['status']=='error') {
            return false;
        } elseif (!empty($result[0])) {
            $oPosting = new \CPosting;
            $oPosting->ChangeStatus(
                $arFields['POSTING_ID'],
                'S'
            );
        }

        return 1;
    }

    /**
     * @param \Bitrix\Main\Event $event
     * @return bool|\Bitrix\Main\Event
     */
    public static function OnBeforeMailSend($event)
    {
        if(self::getOption('enableSingle', 'Y') !== 'Y') {
            return false;
        }
        if(!self::getOption('apiKey', '')) {
            return false;
        }
        if(!self::getOption('singleListId', '')) {
            return false;
        }

        $params = \array_shift($event->getParameters());

//        if(
//            isset($params['HEADER']['X-EVENT_NAME'])
//            && strpos($params['HEADER']['X-Priority'], 'Highest') === false
//        ) {
//            return $event;
//        }

        $args = [
            'email' => $params['TO'],
            'sender_name' => self::getOption('fromName', ''),
            'sender_email' => self::getOption('senderEmail', ''),
//            'sender_email' => $params['HEADER']['From'],
            'subject' => $params['SUBJECT'],
            'body' => $params['BODY'],
            'list_id' => self::getOption('singleListId', ''),
            'track_read' => self::getOption('trackRead', 'Y') === 'Y' ? 1 : 0,
            'track_links' => self::getOption('trackLinks', 'Y') === 'Y' ? 1 : 0,
            'error_checking' => 1,
//            'metadata' => self::getOption('metadata', []),
        ];

        if(!empty($params['HEADER'])) {
            $args['headers'] = [];
            $headerSupport = [
                'Reply-To',
                'X-Priority',
            ];
            foreach ($params['HEADER'] as $keyHeader => $valHeader) {
                if(!in_array($keyHeader, $headerSupport)) {
                    continue;
                }
                if($keyHeader==='X-Priority') {
                    $keyHeader = 'Priority';
                }
                $args['headers'][] = "$keyHeader: $valHeader";
            }
            $args['headers'] = implode(\PHP_EOL, $args['headers']);
        }

        if(!empty($params['BCC'])) {
            $args['cc'] = $params['BCC'];
        }

        if(!empty($params['ATTACHMENT'])) {
            foreach ($params['ATTACHMENT'] as $file) {
                if(!file_exists($file['PATH'])) {
                    \CEventLog::Add([
                        'SEVERITY' => 'ERROR',
                        'AUDIT_TYPE_ID' => 'FILE_NOT_FOUND',
                        'MODULE_ID' => self::$module_id,
                        'DESCRIPTION' => "File \"{$file['PATH']}\" is not found!",
                    ]);
                    continue;
                }
                $args['attachments'][$file['NAME']] = file_get_contents($file['PATH']);
            }
        }

//        DebugHelper::print_pre($args); die();

        $uniSenderApi = new UniSenderApi(
            self::getOption('apiKey', ''),
            self::getOption('encoding', 'UTF-8'),
            4
        );
        $result = $uniSenderApi->sendEmail($args);

        \CEventLog::Add([
            'SEVERITY' => 'INFO',
            'AUDIT_TYPE_ID' => 'SINGLE_SEND_RESULT',
            'MODULE_ID' => self::$module_id,
            'DESCRIPTION' => print_r($result,true),
        ]);

        if(!defined('ONLY_EMAIL')) {
            define('ONLY_EMAIL', 'Y');
        }
        $event->addResult(new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            [
                'TO' => ONLY_EMAIL,
                'RESULT' => 'SUCCESS',
            ],
            self::$module_id
        ));

        return $event;
    }
}