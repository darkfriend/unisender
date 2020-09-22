<?php
/**
 * @author dev2fun (darkfriend)
 * @copyright darkfriend
 * @version 1.1.1
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
use darkfriend\helpers\Curl;
use darkfriend\helpers\DebugHelper;
use Darkfriend\HLHelpers;
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
        self::$errors['fields'] = [];
        foreach ($arFields as $keyField => $arField) {
            if(\in_array($keyField, self::$requireFields) && empty($arField)) {
                self::$errors['fields'][] = $keyField;
            }
        }
        return empty(self::$errors['fields']);
    }

    public static function checkRequireModules()
    {
        self::$errors['modules'] = [];
        if (!\function_exists('curl_init')) {
            self::$errors['modules'][] = 'Curl';
        }
        return empty(self::$errors['modules']);
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
        if(!\is_array($arFields) || empty($arFields)) {
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

        if($params['CONTENT_TYPE']!=='html' && \preg_match('#('.\PHP_EOL.')#', $params['BODY'])) {
            $params['BODY'] = \str_replace(\PHP_EOL,'<br>', $params['BODY']);
        }

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
                if(!\in_array($keyHeader, $headerSupport)) {
                    continue;
                }
                if($keyHeader==='X-Priority') {
                    $keyHeader = 'Priority';
                    if(\strpos($valHeader, 'High')!==false) {
                        $valHeader = 'High';
                    } elseif(\strpos($valHeader, 'Normal')!==false) {
                        $valHeader = 'Normal';
                    } elseif(\strpos($valHeader, 'Low')!==false) {
                        $valHeader = 'Low';
                    }
                }
                $args['headers'][] = "$keyHeader: $valHeader";
            }
            $args['headers'] = \implode(\PHP_EOL, $args['headers']);
        }

        if(!empty($params['BCC'])) {
            $args['cc'] = $params['BCC'];
        }

        if(!empty($params['ATTACHMENT'])) {
            foreach ($params['ATTACHMENT'] as $file) {
                if(!\file_exists($file['PATH'])) {
                    \CEventLog::Add([
                        'SEVERITY' => 'ERROR',
                        'AUDIT_TYPE_ID' => 'FILE_NOT_FOUND',
                        'MODULE_ID' => self::$module_id,
                        'DESCRIPTION' => "File \"{$file['PATH']}\" is not found!",
                    ]);
                    continue;
                }
                $args['attachments'][$file['NAME']] = \file_get_contents($file['PATH']);
            }
        }

        $uniSenderApi = new UniSenderApi(
            self::getOption('apiKey', ''),
            self::getOption('encoding', 'UTF-8'),
            4
        );
        $result = $uniSenderApi->sendEmail($args);
        if($result) {
            if(\is_string($result)) {
                $result = \json_decode($result,true);
            }
            if(!empty($result['result'][0])) {
                HLHelpers::getInstance()->addElement(
                    self::getOption('highload_unisender'),
                    [
                        'UF_EMAIL' => $result['result'][0]['email'],
                        'UF_SEND_ID' => $result['result'][0]['id'],
                        'UF_EVENT' => $event->getEventType(),
                        'UF_DATE' => new \Bitrix\Main\Type\DateTime(),
                    ]
                );
            }
        }

        \CEventLog::Add([
            'SEVERITY' => 'INFO',
            'AUDIT_TYPE_ID' => 'SINGLE_SEND_RESULT',
            'MODULE_ID' => self::$module_id,
            'DESCRIPTION' => print_r($result,true),
        ]);

        if(!\defined('ONLY_EMAIL')) {
            \define('ONLY_EMAIL', 'Y');
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

    public static function DoBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu)
    {
        $aModuleMenu[] = array(
            "parent_menu" => "global_menu_settings",
            "icon" => "sys_menu_icon",
            "page_icon" => "default_page_icon",
            "sort" => "900",
            "text" => 'UniSender History',
            "title" => 'UniSender History',
            "url" => '/bitrix/admin/dev2fun_unisender_log.php?action=settings',
            "items_id" => 'menu_dev2fun_unisender',
            "section" => 'dev2fun_unisender',
            "more_url" => array(),
            // "items" => array(
            //     array(
            //         "text" => GetMessage("SUB_SETINGS_MENU_TEXT"),
            //         "title" => GetMessage("SUB_SETINGS_MENU_TITLE"),
            //         "url" => "/bitrix/admin/dev2fun_opengraph_manager.php?action=settings",
            //         "sort" => "100",
            //         "icon" => "sys_menu_icon",
            //         "page_icon" => "default_page_icon",
            //     ),
            // )
        );
    }

    public static function ShowThanksNotice()
    {
        \CAdminNotify::Add([
            'MESSAGE' => \Bitrix\Main\Localization\Loc::getMessage(
                'D2F_UNISENDER_DONATE_MESSAGE',
                ['#URL#' => '/bitrix/admin/settings.php?lang=ru&mid=dev2fun.unisender&mid_menu=1&tabControl_active_tab=donate']
            ),
            'TAG' => 'dev2fun_unisender_update',
            'MODULE_ID' => self::$module_id,
        ]);
    }
}