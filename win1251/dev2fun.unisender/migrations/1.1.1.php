<?php
/**
 * Created by PhpStorm.
 * User: darkfriend <hi@darkfriend.ru>
 * Date: 23.09.2020
 * Time: 1:25
 */

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
$curModuleName = 'dev2fun.unisender';

\Bitrix\Main\Loader::includeModule('main');
\Bitrix\Main\Loader::includeModule($curModuleName);

$hl = \Darkfriend\HLHelpers::getInstance();
$hlId = \Bitrix\Main\Config\Option::get($curModuleName, 'highload_unisender');

$hl->addField($hlId, [
    'FIELD_NAME' => 'UF_EVENT',
    'USER_TYPE_ID' => 'string',
    'SORT' => '500',
    'MULTIPLE' => 'N',
    'MANDATORY' => 'Y',
    'EDIT_FORM_LABEL' => [
        'ru' => 'EVENT',
        'en' => 'EVENT',
    ],
    'LIST_COLUMN_LABEL' => [
        'ru' => 'EVENT',
        'en' => 'EVENT',
    ],
]);

die("1.1.1 - Success");