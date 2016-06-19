<?php
namespace Quazardous\Eclectic\Transform;

/**
 * Interface for rows wrapper.
 * A row wrapper can add/map (pseudo) fields to the rows.
 *
 */
interface RowWrapperInterface
{
    /**
     * Add/map a field to the row.
     * @param string $name new or existing field
     * @param callable|mixed $value the new field callable or value
     *   the callback signature: function ($data, $field, $value) return value
     *     - data: the row data
     *     - field: the field name
     *     - value: the current value if set
     * 
     * Wrappers should be able to stack multiple callbacks.
     */
    public function addField($name, $value = null);
    
    /**
     * Same but with multiple fields at once.
     * @param callable|mixed $value $names
     * @param unknown $value
     */
    public function addFields(array $names, $value = null);
    
    /**
     * Reset the value or callbacks stack for the given field
     * @param string $name
     */
    public function resetField($name);
    public function resetFields(array $names);
}