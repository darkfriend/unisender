<?php
/**
 * Created by PhpStorm.
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.1.0
 */

/**
 * @global CUser $USER
 * @global CMain $APPLICATION
 **/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Unisender\ApiWrapper\UnisenderApi;

$curModuleName = "dev2fun.unisender";
\Bitrix\Main\Loader::includeModule($curModuleName);
IncludeModuleLangFile(__FILE__);

$canRead = $USER->CanDoOperation('imagecompress_list_read');
$canWrite = $USER->CanDoOperation('imagecompress_list_write');
if(!$canRead && !$canWrite)
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));


$EDITION_RIGHT = $APPLICATION->GetGroupRight($curModuleName);
if ($EDITION_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

//$aTabs = array(
//    array(
//        "DIV" => "main",
//        "TAB" => GetMessage("SEC_MAIN_TAB"),
//        "ICON"=>"main_user_edit",
//        "TITLE"=>GetMessage("SEC_MAIN_TAB_TITLE"),
//    ),
//);

//$tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);

$bVarsFromForm = false;
$APPLICATION->SetTitle(GetMessage("SEC_IMG_COMPRESS_TITLE"));

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

//$tabControl->Begin();
//$tabControl->BeginNextTab();

?>
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/components.cards.min.css">
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/objects.grid.min.css">
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/objects.grid.responsive.min.css">
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/objects.containers.min.css">
    <link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/components.tables.min.css">
<?php

$arFilter=[];
$arOrder=["ID" => "ASC"];
$arSelect=['*'];
$page = $_REQUEST['page'] ?? 1;
$arMoreParams=[
    'limit' => 50,
    'offset' => ($page<=1) ? 0 : $page*50,
];

$hl = \Darkfriend\HLHelpers::getInstance();
$rows = $hl->getElementList(
    \Dev2fun\UniSender\Base::getOption('highload_unisender', ''),
    $arFilter,
    $arOrder,
    $arSelect,
    $arMoreParams
);
$statuses = [];

if($rows) {
    $emailId = [];
    foreach ($rows as $row) {
        $emailId[] = $row['UF_SEND_ID'];
    }
    $uniSenderApi = new UniSenderApi(
        \Dev2fun\UniSender\Base::getOption('apiKey', ''),
        \Dev2fun\UniSender\Base::getOption('encoding', 'UTF-8'),
        4
    );
    $result = $uniSenderApi->checkEmail([
        'email_id' => \implode(',',$emailId),
    ]);
    if($result) $result = \json_decode($result,true);
    if(!empty($result['result']['statuses'])) {
        foreach ($result['result']['statuses'] as $status) {
            $statuses[$status['id']] = $status;
        }
    }
}

//var_dump($USER_FIELD_MANAGER->GetUserType()); die();

//\darkfriend\helpers\DebugHelper::print_pre($hl->getElementsResource(7)->getFields()['UF_DATE']->configureSerialized());
?>

    <table class="c-table">
        <thead class="c-table__head">
        <tr class="c-table__row c-table__row--heading">
            <th class="c-table__cell">ID</th>
            <th class="c-table__cell">Email</th>
            <th class="c-table__cell">Unisender ID</th>
            <th class="c-table__cell">Date</th>
            <th class="c-table__cell">Status</th>
        </tr>
        </thead>
        <tbody class="c-table__body">
        <?php foreach ($rows as $row) { ?>
            <tr class="c-table__row">
                <td class="c-table__cell">
                    <?=$row['ID']?>
                </td>
                <td class="c-table__cell">
                    <?=$row['UF_EMAIL']?>
                </td>
                <td class="c-table__cell">
                    <?=$row['UF_SEND_ID']?>
                </td>
                <td class="c-table__cell">
                    <?=$row['UF_DATE']?>
                </td>
                <td class="c-table__cell">
                    <?php
                    if(!empty($statuses[$row['UF_SEND_ID']])) {
                        echo $statuses[$row['UF_SEND_ID']]['status'];
                    } else {
                        echo 'no data';
                    }
                    ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

<?php
//$tabControl->Buttons([
//    'disabled' => true,
//]);
//$tabControl->EndTab();
//$tabControl->End();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");