<?php

namespace Quazardous\Eclectic\Helper\Tests;

use Quazardous\Eclectic\Helper\SQL;
use PHPUnit\Framework\TestCase;

class SqlTest extends TestCase
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

    public function testIn()
    {
        $criteria = [
            'test' => ['IN' => ['foo', 'bar']],
        ];
        $parameters = [];
        $res = SQL::getCriteriaWhere('test', $criteria, 'test', $parameters);
        $this->assertEquals('test IN (:test1_1,:test1_2)', $res);
        $this->assertEquals(['test1_1' => 'foo', 'test1_2' => 'bar'], $parameters);
        
        $parameters = [];
        $res = SQL::getCriteriaWhere('test', $criteria, 'test', $parameters, '=', false);
        $this->assertEquals('test IN (:test1)', $res);
        $this->assertEquals(['test1' => ['foo', 'bar']], $parameters);
    }
}