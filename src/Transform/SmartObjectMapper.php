<?php
namespace Quazardous\Eclectic\Transform;

use Quazardous\Eclectic\Transform\MappedRowInterface;
use Quazardous\Eclectic\Transform\RowMapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Util\Inflector;

/**
 * A single row mapper.
 * 
 * Can be used directly to map single row/data with pseudo fields.
 * @see RowsMapper
 * 
 * A field callbacks stack will be triggered only once then the final value will be cached.
 * 
 * When a field is accessed, the "smart" object will try different combination to retrieve the data.
 * $row->foo_bar will trigger:
 *  - foo_bar
 *  - fooBar
 * $row->fooBar will trigger:
 *  - fooBar
 *  - foo_bar
 */
class SmartObjectMapper implements MappedRowInterface, RowMapperInterface
{

    protected $data;
    protected $map;
    protected $options;
    protected $cache;

    public function __construct($data, array $map = [], array $options = [])
    {
        $this->data = $data;
        $this->map = $map;
        $this->options = $options;
        
        $this->options += [
            'use_cache' => true,
        ];
        $this->cache = [];
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getRawValue($field)
    {
        if (is_array($this->data)) {
            if (array_key_exists($field, $this->data)) {
                return $this->data[$field];
            }
        } elseif (is_object($this->data)) {
            if ($this->getAccessor()->isReadable($this->data, $field)) {
                return $this->getAccessor()->getValue($this->data, $field);
            }
        }
        return null;
    }
    
    public function getValue($field)
    {
        if ($this->options['use_cache'] && array_key_exists($field, $this->cache)) {
            return $this->cache[$field];
        }
        $value = $this->getRawValue($field);
        if (array_key_exists($field, $this->map)) {
            foreach ($this->map[$field] as $map) {
                if (is_callable($map)) {
                    $value = $map($this, $field, $value);
                } else {
                    $value = $map;
                }
            }
        }
        if ($this->options['use_cache']) {
            $this->cache[$field] = $value;
        }
        return $value;
    }
    
    protected function valueExists($field)
    {
        if (array_key_exists($field, $this->map)) {
            return true;
        }
        if (is_array($this->data)) {
            return array_key_exists($field, $this->data);
        } elseif (is_object($this->data)) {
            return $this->getAccessor()->isReadable($this->data, $field);
        }
        return false;
    }
    
    public function __get($field)
    {
        if ($this->valueExists($field)) {
            return $this->getValue($field);
        }
        $_field = Inflector::tableize($field);
        if ($_field != $field && $this->valueExists($_field)) {
            return $this->getValue($_field);
        }
        $_field = Inflector::camelize($field);
        if ($_field != $field && $this->valueExists($_field)) {
            return $this->getValue($_field);
        }
        return null;
    }
    
    public function __isset($field)
    {
        if ($this->valueExists($field)) {
            return true;
        }
        $_field = Inflector::tableize($field);
        if ($_field != $field && $this->valueExists($_field)) {
            return true;
        }
        $_field = Inflector::camelize($field);
        if ($_field != $field && $this->valueExists($_field)) {
            return true;
        }
        return false;
    }

    protected $accessor;
    
    /**
     * @return \Symfony\Component\PropertyAccessor\PropertyAccessorInterface
     */
    protected function getAccessor()
    {
        if (empty($this->accessor)) {
            $builder = PropertyAccess::createPropertyAccessorBuilder();
            $this->accessor = $builder->getPropertyAccessor();
        }
        return $this->accessor;
    }
    
    public function __call($name, array $arguments)
    {
        return call_user_func_array([$this->data, $name], $arguments);
    }
    
    public function addField($name, $value = null)
    {
        if (empty($this->map[$name])) {
            $this->map[$name] = [];
        }
        $this->map[$name][] = $value;
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
        unset($this->map[$name]);
    }
}