<?php

namespace Quazardous\Eclectic\Helper;

use Doctrine\Common\Util\Inflector as BaseInflector;

class Inflector extends BaseInflector
{
    /**
     * Turn FooBar into foo-bar.
     * @param string $word
     * @return string
     */
    public static function hyphenize($word)
    {
        return strtr(static::tableize($word), '_ ', '--');
    }
}
