<?php
/**
 * @author dev2fun (darkfriend)
 * @copyright darkfriend
 * @version 1.1.0
 */
if (class_exists("dev2fun_unisender")) return;

include_once __DIR__.'/../vendor/autoload.php';

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\ModuleManager,
    Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc,
    Bitrix\Main\IO\Directory,
    Bitrix\Main\Config\Option;

Loader::registerAutoLoadClasses(
    "dev2fun.unisender",
    [
        'Dev2fun\\UniSender\\Base' => 'include.php',
        'Dev2fun\\UniSender\\Config' => 'classes/general/Config.php',
        //        'Dev2fun\MultiDomain\SubDomain' => 'classes/general/SubDomain.php',
        //        'Dev2fun\MultiDomain\HLHelpers' => 'lib/HLHelpers.php',
    ]
);

class dev2fun_unisender extends CModule
{
    var $MODULE_ID = "dev2fun.unisender";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_GROUP_RIGHTS = 'Y';

    public function dev2fun_unisender()
    {
        include(__DIR__ . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->MODULE_NAME = Loc::getMessage('D2F_MODULE_NAME_UNISENDER');
        $this->MODULE_DESCRIPTION = Loc::getMessage('D2F_MODULE_DESCRIPTION_UNISENDER');
        $this->PARTNER_NAME = 'dev2fun';
        $this->PARTNER_URI = 'https://dev2fun.com';
    }

    public function DoInstall()
    {
        global $APPLICATION;
        if (!check_bitrix_sessid()) return;
        try {
            $this->installDB();
            $this->installFiles();
            $this->registerEvents();
            ModuleManager::registerModule($this->MODULE_ID);
            \CAdminNotify::Add([
                'MESSAGE' => Loc::getMessage('D2F_UNISENDER_NOTICE_THANKS'),
                'TAG' => $this->MODULE_ID . '_install',
                'MODULE_ID' => $this->MODULE_ID,
            ]);
        } catch (Exception $e) {
            $GLOBALS['D2F_UNISENDER_ERROR'] = $e->getMessage();
            $GLOBALS['D2F_UNISENDER_ERROR_NOTES'] = Loc::getMessage('D2F_UNISENDER_INSTALL_ERROR_NOTES');
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('D2F_UNISENDER_STEP_ERROR'),
                __DIR__ . '/error.php'
            );
            return false;
        }
        $APPLICATION->IncludeAdminFile(Loc::getMessage('D2F_UNISENDER_STEP1'), __DIR__ . '/step1.php');
    }

    public function installDB()
    {
        $hlId = $this->_installHighload();
        if (!$hlId) throw new Exception(\Darkfriend\HLHelpers::$LAST_ERROR);
        Option::set($this->MODULE_ID, 'highload_unisender', $hlId);

        Option::set($this->MODULE_ID, 'enableSingle', 'N');
        Option::set($this->MODULE_ID, 'enableSubscribe', 'N');
        Option::set($this->MODULE_ID, 'language', 'ru');
        //        Option::set($this->MODULE_ID, 'enableCompression', 'Y');
        Option::set($this->MODULE_ID, 'platformName', 'UniSender for Bitrix');
        Option::set($this->MODULE_ID, 'senderEmail', '');
        return true;
    }

    private function _installHighload()
    {
        $hl = \Darkfriend\HLHelpers::getInstance();
        $hlId = $hl->create('Dev2funUnisenderLog', 'dev2fun_unisender_log');
        if (!$hlId) {
            throw new Exception(\Darkfriend\HLHelpers::$LAST_ERROR);
        }
        $hl->addField($hlId, [
            'FIELD_NAME' => 'UF_EMAIL',
            'USER_TYPE_ID' => 'string',
            'SORT' => '100',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'Y',
            'EDIT_FORM_LABEL' => [
                'ru' => 'Email',
                'en' => 'Email',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Email',
                'en' => 'Email',
            ],
        ]);
        $hl->addField($hlId, [
            'FIELD_NAME' => 'UF_SEND_ID',
            'USER_TYPE_ID' => 'double',
            'SORT' => '200',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'Y',
            'EDIT_FORM_LABEL' => [
                'ru' => 'UniSender ID',
                'en' => 'UniSender ID',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'UniSender ID',
                'en' => 'UniSender ID',
            ],
        ]);
        $hl->addField($hlId, [
            'FIELD_NAME' => 'UF_DATE',
            'USER_TYPE_ID' => 'datetime',
            'SORT' => '300',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SETTINGS' => [
                'DEFAULT_VALUE' => ['TYPE'=>'NOW'],
            ],
            'EDIT_FORM_LABEL' => [
                'ru' => 'Date',
                'en' => 'Date',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Date',
                'en' => 'Date',
            ],
        ]);
        return $hlId;
    }

    public function installFiles()
    {
        CopyDirFiles(
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/dev2fun.unisender/install/admin",
            $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin",
            true,
            true
        );
    }

    public function registerEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'subscribe',
            'BeforePostingSendMail',
            $this->MODULE_ID,
            'Dev2fun\\UniSender\\Base',
            'BeforePostingSendMail'
        );
        $eventManager->registerEventHandler(
            'main',
            'OnBeforeMailSend',
            $this->MODULE_ID,
            'Dev2fun\\UniSender\\Base',
            'OnBeforeMailSend'
        );
        $eventManager->registerEventHandler(
            'main',
            'OnBuildGlobalMenu',
            $this->MODULE_ID,
            'Dev2fun\\UniSender\\Base',
            'DoBuildGlobalMenu'
        );
        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        if (!check_bitrix_sessid()) return;
        try {
            $this->unInstallDB();
            $this->unInstallFiles();
            $this->unRegisterEvents();
            \CAdminNotify::Add([
                'MESSAGE' => Loc::getMessage('D2F_UNISENDER_NOTICE_WHY'),
                'TAG' => $this->MODULE_ID . '_uninstall',
                'MODULE_ID' => $this->MODULE_ID,
            ]);
            ModuleManager::unRegisterModule($this->MODULE_ID);
        } catch (Exception $e) {
            $GLOBALS['D2F_UNISENDER_ERROR'] = $e->getMessage();
            $GLOBALS['D2F_UNISENDER_ERROR_NOTES'] = Loc::getMessage('D2F_UNISENDER_UNINSTALL_ERROR_NOTES');
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('D2F_UNISENDER_STEP_ERROR'),
                __DIR__ . '/error.php'
            );
            return false;
        }

        $APPLICATION->IncludeAdminFile(GetMessage('D2F_UNISENDER_UNSTEP1'), __DIR__ . '/unstep1.php');
    }

    public function unInstallDB()
    {
        $hlId = Option::get($this->MODULE_ID, 'highload_unisender');
        if($hlId) {
            \Darkfriend\HLHelpers::getInstance()->deleteHighloadBlock($hlId);
        }
        Option::delete($this->MODULE_ID);
        return true;
    }

    public function unInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/admin/dev2fun_unisender_log.php");
    }

    public function unRegisterEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'subscribe',
            'BeforePostingSendMail',
            $this->MODULE_ID
        );
        $eventManager->unRegisterEventHandler(
            'main',
            'OnBeforeMailSend',
            $this->MODULE_ID
        );
        $eventManager->unRegisterEventHandler(
            'main',
            'OnBuildGlobalMenu',
            $this->MODULE_ID
        );
        return true;
    }
}
