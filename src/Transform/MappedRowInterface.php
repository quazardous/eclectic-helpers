<?php
namespace Quazardous\Eclectic\Transform;

/**
 * Interface for a mapped row.
 *
 */
interface MappedRowInterface
{
    /**
     * A multipe rows mapper needs to create new rows.
     * @param mixed $data row data or object
     * @param array $map the fields map
     * @param array $options
     * @see RowsMapper
     */
    public function __construct($data, array $map = [], array $options = []);
}