<?php
/**
 * @author dev2fun (darkfriend)
 * @copyright darkfriend
 * @version 1.0.0
 */
if (class_exists("dev2fun_unisender")) return;

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
        Option::set($this->MODULE_ID, 'enableSingle', 'N');
        Option::set($this->MODULE_ID, 'enableSubscribe', 'N');
        Option::set($this->MODULE_ID, 'language', 'ru');
//        Option::set($this->MODULE_ID, 'enableCompression', 'Y');
        Option::set($this->MODULE_ID, 'platformName', 'UniSender for Bitrix');
        Option::set($this->MODULE_ID, 'senderEmail', '');
        return true;
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
        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        if (!check_bitrix_sessid()) return;
        try {
            $this->unInstallDB();
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
        Option::delete($this->MODULE_ID);
        return true;
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
        return true;
    }
}
