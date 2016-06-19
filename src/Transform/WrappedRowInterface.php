<?php
namespace Quazardous\Eclectic\Transform;

/**
 * Interface for a wrapped row.
 *
 */
interface WrappedRowInterface
{
    /**
     * A multipe rows wrapper needs to create new rows.
     * @param mixed $data row data or object
     * @param array $map the fields map
     * @param array $options
     * @see RowsWrapper
     */
    public function __construct($data, array $map = [], array $options = []);
}