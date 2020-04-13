<?php

namespace darkfriend\helpers;

/**
 * Class ArrayHelper
 * @package darkfriend\devhelpers
 * @author darkfriend <hi@darkfriend.ru>
 * @version 1.0.2
 */
class ArrayHelper
{
    /**
     * Check value exists in an array.
     * Highload method searches haystack for needle.
     * @param string $needle
     * @param array $haystack
     * @return bool
     */
    public static function in_array($needle, $haystack)
    {
        $newHaystack = [];
        foreach (\array_values($haystack) as $v) {
            $newHaystack[$v] = true;
        }
        return isset($newHaystack[$needle]);
    }

    /**
     * Check multiple array
     * @param array $arr
     * @return bool
     */
    public static function isMulti($arr)
    {
        if (!\is_array($arr)) return false;
        $arr = \current($arr);
        return isset($arr) && \is_array($arr);
    }

    /**
     * Sort values array to order array
     * @param array $source - source array
     * @param array $orderArray - order array
     * @param callable|null $callback - callable algorithm
     * @return array
     */
    public static function sortValuesToArray($source, $orderArray, $callback = null)
    {
        if (!$callback) {
            $callback = function ($a, $b, $orderArray) {
                $keyCurrent = \array_search($a, $orderArray);
                $keyNext = \array_search($b, $orderArray);
                if (!isset($keyNext) && !isset($keyCurrent)) return -1;
                if ($keyCurrent == $keyNext) return 0;
                return ($keyCurrent > $keyNext) ? 1 : -1;
            };
        }
        \usort($source, function ($a, $b) use ($orderArray, $callback) {
            return $callback($a, $b, $orderArray);
        });
        return $source;
    }

    /**
     * Sort keys source array to order array
     * @param array $source - source array
     * @param array $orderArray - order array
     * @param callable|null $callback - callable algorithm
     * @return array
     */
    public static function sortKeysToArray($source, $orderArray, $callback = null)
    {
        if (!$callback) {
            $callback = function ($a, $b, $orderArray) {
                $keyCurrent = \array_search($a, $orderArray);
                $keyNext = \array_search($b, $orderArray);
                if (!isset($keyNext) && !isset($keyCurrent)) return -1;
                return \strcasecmp($keyCurrent, $keyNext);
            };
        }
        \uksort($source, function ($a, $b) use ($orderArray, $callback) {
            return $callback($a, $b, $orderArray);
        });
        return $source;
    }

    /**
     * Проверяет совпадение ключей в $sourceArray
     * @param array $arKeys
     * @param array $sourceArray
     * @return bool
     * @since 1.0.2
     */
    static public function keysExists($arKeys, $sourceArray) {
        return !array_diff_key(array_flip($arKeys), $sourceArray);
    }
}