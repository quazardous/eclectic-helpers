<?php
namespace Quazardous\Eclectic\Helper;

class ArrayMap
{
    /**
     * Remap the keys of an associative array using an array or a callback.
     * @param array $array
     * @param array|callable $map
     * @throws \InvalidArgumentException
     * @return array
     */
    public static function remap($array, $map)
    {
        if (is_array($map)) {
            $ret = [];
            foreach ($map as $ok => $nk) {
                if (is_numeric($ok)) {
                    $ok = $nk;
                }
                $ret[$nk] = null;
                if (isset($array[$ok])) {
                    $ret[$nk] = $array[$ok];
                }
            }
            return $ret;
        }
        if (is_callable($map)) {
            $ret = [];
            foreach ($array as $k => $v) {
                $ret[$map($k)] = $v;
            }
            return $ret;
        }
        throw new \InvalidArgumentException("unknown map type");
    }
}
