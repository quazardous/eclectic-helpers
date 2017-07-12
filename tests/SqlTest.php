<?php
namespace Quazardous\Eclectic\Helper\Tests;

use Quazardous\Eclectic\Helper\SQL;

class SqlTest extends \PHPUnit_Framework_TestCase
{
    public function testNull()
    {
        $criteria = [
            'test' => null,
        ];
        $parameters = [];
        $res = SQL::getCriteriaWhere('test', $criteria, 'test', $parameters);
        $this->assertEquals('test IS NULL', $res);
    }
    
    public function testNotNull()
    {
        $criteria = [
            'test' => ['<>' => null],
        ];
        $parameters = [];
        $res = SQL::getCriteriaWhere('test', $criteria, 'test', $parameters);
        $this->assertEquals('test IS NOT NULL', $res);
    }
}