<?php
namespace Quazardous\Eclectic\Transform;

use Quazardous\Eclectic\Transform\MappedRowInterface;
use Quazardous\Eclectic\Transform\RowMapperInterface;

/**
 * An "array of rows" mapper.
 * 
 * The goal is to put value transformation/presentation stuff "outside" of the controller logic.
 * 
 *  So the steps in a controller are:
 *  // retrieve an array of rows:
 *  $rows = $app['service']->getRows();
 *  // put the rows in a mapper
 *  $rows = new \Quazardous\Eclectic\Transform\RowsMapper($rows);
 *  // add some fancy fields with presentation stuff
 *  $rows->addField('foo', function ($data, $field, $value) use ($app) {
 *      // presentation stuff
 *      return $newValue;
 *  });
 *  // twig render
 *  ...
 *  
 *  @see SmartObjectMapper
 *  The SmartObjectMapper will allow you to access the field in the Twig template.
 *  
 *  {% for row in rows %}
 *  {{ row.foo }}
 *  {% endfor %}
 *  
 */
class RowsMapper implements \Iterator, \ArrayAccess, \Countable, RowMapperInterface
{
    protected $rowClass = 'Quazardous\Eclectic\Transform\SmartObjectMapper';
    protected $rows;
    protected $transformedRows;
    protected $fieldsMap = [];
    protected $options = [];
    public function __construct(array $rows, array $map = [], array $options = [])
    {
        $this->fieldsMap = $map;
        $this->index = 0;
        $this->rows = array_values($rows);
        $this->transformedRows = [];
        $this->options = $options;
    }
    
    public function addField($name, $value = null)
    {
        if (empty($this->fieldsMap[$name])) {
            $this->fieldsMap[$name] = [];
        }
        $this->fieldsMap[$name][] = $value;
    }
    
    public function addFields(array $names, $value = null)
    {
        foreach ($names as $name) {
            $this->addField($name, $value);
        }
    }
    
    public function resetFields(array $names)
    {
        foreach ($names as $name) {
            $this->resetField($name);
        }
    }

    public function resetField($name)
    {
        unset($this->fieldsMap[$name]);
    }
    
    protected $index;
    
    protected function initTransformedRowAt($index)
    {
        if (!array_key_exists($index, $this->rows)) {
            throw new \InvalidArgumentException("Bad index");
        }
        if (!array_key_exists($index, $this->transformedRows)) {
            $c = $this->rowClass;
            $row = new $c($this->rows[$index], $this->fieldsMap, $this->options);
            if (!($row instanceof MappedRowInterface)) {
                throw new \LogicException("$c should implement MappedRowInterface");
            }
            $this->transformedRows[$index] = $row;
        }
    }
    
    public function current () {
        $this->initTransformedRowAt($this->index);
        return $this->transformedRows[$this->index];
    }
    
    public function next () {
        $this->index++;
    }
    
    public function key () {
        return $this->index;
    }
    
    public function valid () {
        return array_key_exists($this->index, $this->rows);
    }
    
    public function rewind () {
        $this->index = 0;
    }
    
    public function offsetExists ($offset) {
        return array_key_exists($offset, $this->rows);
    }
    
    public function offsetGet ($offset) {
        $this->initTransformedRowAt($offset);
        return $this->transformedRows[$offset];
    }
    
    public function offsetSet ($offset, $value) {
        throw new \LogicException("Not allowed");
    }
    
    public function offsetUnset ($offset) {
        throw new \LogicException("Not allowed");
    }
    
    public function count() {
        return count($this->rows);
    }
}