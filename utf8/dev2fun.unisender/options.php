<?php
/**
 * @author dev2fun (darkfriend)
 * @copyright darkfriend
 * @version 1.0.0
 */
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;


if (!$USER->isAdmin()) {
    $APPLICATION->authForm('Nope');
}
$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();
$curModuleName = 'dev2fun.unisender';
//Loc::loadMessages($context->getServer()->getDocumentRoot()."/bitrix/modules/main/options.php");
Loc::loadMessages(__FILE__);
\Bitrix\Main\Loader::includeModule($curModuleName);

$aTabs = [
    [
        "DIV" => "edit1",
        "TAB" => Loc::getMessage("MAIN_TAB_SET"),
        "ICON" => "main_settings",
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET"),
    ],
//    [
//        "DIV" => "edit2",
//        "TAB" => Loc::getMessage("D2F_UNISENDER_TAB_2"),
//        "ICON" => "main_settings",
//        "TITLE" => Loc::getMessage("D2F_UNISENDER_TAB_2_TITLE_SET"),
//    ],
//    [
//        "DIV" => "edit3",
//        "TAB" => Loc::getMessage("D2F_UNISENDER_TAB_3"),
//        "ICON" => "main_settings",
//        "TITLE" => Loc::getMessage("D2F_UNISENDER_TAB_3_TITLE_SET"),
//    ],
//    [
//        "DIV" => "edit4",
//        "TAB" => Loc::getMessage("D2F_UNISENDER_TAB_4"),
//        "ICON" => "main_settings",
//        "TITLE" => Loc::getMessage("D2F_UNISENDER_TAB_4_TITLE_SET"),
//    ],
    //	array(
    //		"DIV" => "edit5",
    //		"TAB" => Loc::getMessage("D2F_UNISENDER_TAB_5"),
    //		"ICON" => "main_settings",
    //		"TITLE" => Loc::getMessage("D2F_UNISENDER_TAB_5_TITLE_SET")
    //	),
    //    array("DIV" => "edit8", "TAB" => GetMessage("MAIN_TAB_8"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_OPTION_EVENT_LOG")),
    //    array("DIV" => "edit5", "TAB" => GetMessage("MAIN_TAB_5"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_OPTION_UPD")),
    //    array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "main_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
];

//$tabControl = new CAdminTabControl("tabControl", array(
//    array(
//        "DIV" => "edit1",
//        "TAB" => Loc::getMessage("MAIN_TAB_SET"),
//        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_SET"),
//    ),
//));

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if ($request->isPost() && check_bitrix_sessid()) {
    $arFields = $request->getPost('options');
    if(empty($arFields['enableSingle'])) {
        $arFields['enableSingle'] = 'N';
    }
    if(empty($arFields['enableSubscribe'])) {
        $arFields['enableSubscribe'] = 'N';
    }
    if(empty($arFields['trackRead'])) {
        $arFields['trackRead'] = 'N';
    }
    if(empty($arFields['trackLinks'])) {
        $arFields['trackLinks'] = 'N';
    }

    $error = false;
    if($arFields['enableSingle']==='Y' && !\Dev2fun\UniSender\Base::checkRequireFields($arFields)) {
        $error = \Dev2fun\UniSender\Base::$errors;
    }

    if(!$error) {
        foreach ($arFields as $k => $arField) {
            Option::set($curModuleName, $k, $arField);
        }
        LocalRedirect($APPLICATION->GetCurUri());
    }

}

$postingList = array();
$uniSender = \Dev2fun\UniSender\UniSender::instance();
if($uniSender->hasKey()) {
    $postingList = $uniSender->getLists();
}


$moduleDie = '/bitrix/modules/dev2fun.unisender';
//$asset = \Bitrix\Main\Page\Asset::getInstance();
//$asset->addJs($moduleDie.'/assets/dist/basic/js/main.bundle.js');
//$asset->addJs($moduleDie.'/assets/dist/basic/js/polyfill.bundle.js');
//$asset->addCss($moduleDie.'/assets/dist/basic/css/bundle.css');

//$msg = new CAdminMessage([
//    'MESSAGE' => Loc::getMessage("D2F_UNISENDER_DONATE_MESSAGES", ['#LINK#' => 'http://yasobe.ru/na/thankyou_bitrix']),
//    'TYPE' => 'OK',
//    'HTML' => true,
//]);
//echo $msg->Show();

if(!empty($error)) {
    foreach ($error as &$errorItem) {
        $errorItem = Loc::getMessage('D2F_UNISENDER_FIELD_ERROR_'.$errorItem);
    }
    unset($errorItem);
    array_unshift(
        $error,
        Loc::getMessage('D2F_UNISENDER_FIELD_ERROR')
    );
    $msg = new CAdminMessage([
        'MESSAGE' => implode('<br>', $error),
        'TYPE' => 'ERROR',
        'HTML' => true,
    ]);
    echo $msg->Show();
}


$tabControl->begin();
//$assets = \Bitrix\Main\Page\Asset::getInstance();
//$assets->addJs('/bitrix/js/' . $curModuleName . '/script.js');
?>

<form
    method="post"
    action="<?= sprintf('%s?mid=%s&lang=%s', $request->getRequestedPage(), urlencode($mid), LANGUAGE_ID) ?>&<?= $tabControl->ActiveTabParam() ?>"
    enctype="multipart/form-data"
    name="editform"
    class="editform"
>
    <?php
        echo bitrix_sessid_post();
        $tabControl->beginNextTab();
    ?>

<!--    <div id="app">-->
<!--        <app-settings></app-settings>-->
<!--    </div>-->
<!--    <tr class="heading">-->
<!--        <td colspan="2"><b>--><? //echo GetMessage("D2F_COMPRESS_HEADER_SETTINGS")?><!--</b></td>-->
<!--    </tr>-->
    <tr>
        <td width="40%">
            <label for="options[enableSingle]">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_ENABLE_SINGLE") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $enabled = Option::get($curModuleName, 'enableSingle', 'Y') === 'Y';
            ?>
            <input type="checkbox" value="Y" name="options[enableSingle]" <?=$enabled?'checked':''?>>
        </td>
    </tr>

    <tr>
        <td width="40%">
            <label for="options[enableSubscribe]">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_ENABLE_SUBSCRIBE") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $enabled = Option::get($curModuleName, 'enableSubscribe', 'Y') === 'Y';
            ?>
            <input type="checkbox" value="Y" name="options[enableSubscribe]" <?=$enabled?'checked':''?>>
        </td>
    </tr>

    <tr>
        <td width="40%">
            <label for="apiKey">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_API_KEY") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $apiKey = Option::get($curModuleName, 'apiKey', '');
            ?>
            <input type="text" value="<?=$apiKey?>" name="options[apiKey]">
        </td>
    </tr>

    <tr>
        <td width="40%">
            <label for="options[senderEmail]">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_SENDER_EMAIL") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $senderEmail = Option::get($curModuleName, 'senderEmail', '');
            ?>
            <input type="text" value="<?=$senderEmail?>" name="options[senderEmail]">
        </td>
    </tr>

    <tr>
        <td></td>
        <td>
            <?php
            echo BeginNote();
                echo Loc::getMessage('D2F_UNISENDER_LABEL_SENDER_EMAIL_FIELDS_TEXT');
            EndNote();
            ?>
        </td>
    </tr>

    <tr>
        <td width="40%">
            <label for="options[fromName]">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_FROM_NAME") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $fromName = Option::get($curModuleName, 'fromName', '');
            ?>
            <input type="text" value="<?=$fromName?>" name="options[fromName]">
        </td>
    </tr>

    <tr>
        <td width="40%">
            <label for="options[singleListId]">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_SINGLE_LIST_ID") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $singleListId = Option::get($curModuleName, 'singleListId', '');
            ?>
            <select name="options[singleListId]">
                <option value="" <?=empty($postingList)?'selected':''?>>-</option>
                <?php if($postingList) { ?>
                    <?php foreach ($postingList as $item) { ?>
                        <option
                            value="<?=$item['id']?>"
                            <?=intval($item['id'])===intval($singleListId)?'selected':''?>
                        >
                            <?=$item['title']?>
                        </option>
                    <?php } ?>
                <?php } ?>
            </select>
        </td>
    </tr>

    <tr>
        <td></td>
        <td>
            <?php
            echo BeginNote();
            echo Loc::getMessage('D2F_UNISENDER_LABEL_SINGLE_LIST_ID_FIELDS_TEXT');
            EndNote();
            ?>
        </td>
    </tr>

    <tr>
        <td width="40%">
            <label for="options[encoding]">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_ENCODING") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $encoding = Option::get($curModuleName, 'encoding', 'UTF-8');
            ?>
            <select name="options[encoding]">
                <option value="UTF-8" <?=$encoding=='UTF-8'?'selected':''?>>UTF-8</option>
                <option value="WINDOWS-1251" <?=$encoding=='WINDOWS-1251'?'selected':''?>>WINDOWS-1251</option>
            </select>
        </td>
    </tr>

    <tr>
        <td width="40%">
            <label for="options[lang]">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_LANGUAGES") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $lang = Option::get($curModuleName, 'lang', 'ru');
            ?>
            <select name="options[lang]">
                <?php foreach (\Dev2fun\UniSender\Base::$languages as $language) { ?>
                    <option value="UTF-8" <?=$lang===$language?'selected':''?>>
                        <?=$language?>
                    </option>
                <?php } ?>
            </select>
        </td>
    </tr>

    <tr>
        <td width="40%">
            <label for="options[trackRead]">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_TRACK_READ") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $trackRead = Option::get($curModuleName, 'trackRead', 'Y') === 'Y';
            ?>
            <input type="checkbox" value="Y" name="options[trackRead]" <?=$trackRead?'checked':''?>>
        </td>
    </tr>

    <tr>
        <td width="40%">
            <label for="options[trackLinks]">
                <?= Loc::getMessage("D2F_UNISENDER_LABEL_TRACK_LINKS") ?>:
            </label>
        </td>
        <td width="60%">
            <?php
            $trackLinks = Option::get($curModuleName, 'trackLinks', 'Y') === 'Y';
            ?>
            <input type="checkbox" value="Y" name="options[trackLinks]" <?=$trackLinks?'checked':''?>>
        </td>
    </tr>

    <?php
    $tabControl->Buttons([
        "btnSave" => true,
        "btnApply" => true,
        "btnCancel" => true,
        "back_url" => $APPLICATION->GetCurUri(),
    ]);
    $tabControl->End();
    ?>
</form>