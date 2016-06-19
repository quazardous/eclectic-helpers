<?php
namespace Quazardous\Eclectic\Transform;

use Quazardous\Eclectic\Transform\WrappedRowInterface;
use Quazardous\Eclectic\Transform\RowWrapperInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Util\Inflector;

/**
 * A single row wrapper.
 * 
 * Can be used directly to wrap single row/data with pseudo fields.
 * @see RowsWrapper
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
class SmartObjectWrapper implements WrappedRowInterface, RowWrapperInterface
{

    protected $_data;
    protected $_map;
    protected $_options;
    protected $_cache;

    public function __construct($data, array $map = [], array $options = [])
    {
        $this->_data = $data;
        $this->_map = $map;
        $this->_options = $options;
        
        $this->_options += [
            'use_cache' => true,
        ];
        $this->_cache = [];
    }
    
    public function getData()
    {
        return $this->_data;
    }
    
    public function getRawValue($field)
    {
        if (is_array($this->_data)) {
            if (array_key_exists($field, $this->_data)) {
                return $this->_data[$field];
            }
        } elseif (is_object($this->_data)) {
            if ($this->getAccessor()->isReadable($this->_data, $field)) {
                return $this->getAccessor()->getValue($this->_data, $field);
            }
        }
        return null;
    }
    
    public function getValue($field)
    {
        if ($this->_options['use_cache'] && array_key_exists($field, $this->_cache)) {
            return $this->_cache[$field];
        }
        $value = $this->getRawValue($field);
        if (array_key_exists($field, $this->_map)) {
            foreach ($this->_map[$field] as $map) {
                if (is_callable($map)) {
                    $value = $map($this, $field, $value);
                } else {
                    $value = $map;
                }
            }
        }
        if ($this->_options['use_cache']) {
            $this->_cache[$field] = $value;
        }
        return $value;
    }
    
    protected function valueExists($field)
    {
        if (array_key_exists($field, $this->_map)) {
            return true;
        }
        if (is_array($this->_data)) {
            return array_key_exists($field, $this->_data);
        } elseif (is_object($this->_data)) {
            return $this->getAccessor()->isReadable($this->_data, $field);
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
        return call_user_func_array([$this->_data, $name], $arguments);
    }
    
    public function addField($name, $value = null)
    {
        if (empty($this->_map[$name])) {
            $this->_map[$name] = [];
        }
        $this->_map[$name][] = $value;
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
        unset($this->_map[$name]);
    }
}