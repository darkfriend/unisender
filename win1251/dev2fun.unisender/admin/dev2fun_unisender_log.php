<?php
/**
 * Created by PhpStorm.
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.1.2
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
if(!$canRead && !$canWrite) {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

$EDITION_RIGHT = $APPLICATION->GetGroupRight($curModuleName);
if ($EDITION_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$bVarsFromForm = false;
$APPLICATION->SetTitle(GetMessage("SEC_IMG_COMPRESS_TITLE"));

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");
?>

<link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/components.cards.min.css">
<link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/objects.grid.min.css">
<link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/objects.grid.responsive.min.css">
<link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/objects.containers.min.css">
<link rel="stylesheet" href="https://unpkg.com/blaze@4.0.0-6/scss/dist/components.tables.min.css">

<?php

$arFilter = [];
$arOrder = ["ID" => "ASC"];
$arSelect = ['*'];
$limit = 50;
$page = !empty($_REQUEST['page']) ? \intval($_REQUEST['page']) : 1;
$arMoreParams = [
    'limit' => $limit,
    'offset' => ($page<=1) ? 0 : $page*$limit,
];

$hlId = \Dev2fun\UniSender\Base::getOption('highload_unisender', '');
$hl = \Darkfriend\HLHelpers::getInstance();

$entity = $hl->getEntityTable($hlId);
$count = $entity::getCount($arFilter);

$rows = $hl->getElementList(
    $hlId,
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
?>

<table class="c-table">
    <thead class="c-table__head">
    <tr class="c-table__row c-table__row--heading">
        <th class="c-table__cell">ID</th>
        <th class="c-table__cell">Email</th>
        <th class="c-table__cell">Unisender ID</th>
        <th class="c-table__cell">Date</th>
        <th class="c-table__cell">Event</th>
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
                <?=$row['UF_EVENT']?>
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
$steps = \round($count/$limit, 0);
if($steps) { ?>
    <div class="adm-navigation">
        <div class="adm-nav-pages-block">

            <?php if($page==1) { ?>
                <span class="adm-nav-page adm-nav-page-prev"></span>
            <?php } else { ?>
                <?php $s = $page-1; ?>
                <a href="<?=$APPLICATION->GetCurPageParam("page={$s}",['page'])?>" class="adm-nav-page adm-nav-page-prev"></a>
            <?php } ?>

            <?php for($i=1;$i<=$steps;$i++) { ?>
                <?php if($i==$page) {?>
                    <span class="adm-nav-page-active adm-nav-page"><?=$i?></span>
                <?php } else { ?>
                    <a href="<?=$APPLICATION->GetCurPageParam("page={$i}",['page'])?>" class="adm-nav-page"><?=$i?></a>
                <?php } ?>
            <?php } ?>

            <?php if($page==$steps) { ?>
                <span class="adm-nav-page adm-nav-page-next"></span>
            <?php } else { ?>
                <?php $s = $page+1; ?>
                <a class="adm-nav-page adm-nav-page-next" href="<?=$APPLICATION->GetCurPageParam("page={$s}",['page'])?>"></a>
            <?php } ?>

        </div>
    </div>
<?php } ?>

<?php
//$tabControl->Buttons([
//    'disabled' => true,
//]);
//$tabControl->EndTab();
//$tabControl->End();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");