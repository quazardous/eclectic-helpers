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
    
    /**
     * Deep set a value in an associative array.
     * @param array $array
     * @param string|array $path
     * @param mixed $value
     * @param string $delim
     */
    public static function deepSet(array &$array, $path, $value, $delim = '/')
    {
        if (!is_array($path)) {
            $path = explode($delim, $path);
        }
    
        $last_key = array_pop($path);
    
        $base = &$array;
        foreach ($path as $key) {
            if (!array_key_exists($key, $base)) {
                $base[$key] = [];
            }
            $base = &$base[$key];
        }
        $base[$last_key] = $value;
    }
    
    /**
     * Deep get a value in an associative array.
     * @param array $array
     * @param string|array $path
     * @param string $delim
     * @return NULL|mixed
     */
    public static function deepGet(array $array, $path, $delim = '/')
    {
        if (!is_array($path)) {
            $path = explode($delim, $path);
        }
        $base = &$array;
        foreach ($path as $key) {
            if (!array_key_exists($key, $base)) {
                return null;
            }
            $base = &$base[$key];
        }
        return $base;
    }
}
