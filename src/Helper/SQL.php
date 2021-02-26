<?php

namespace Quazardous\Eclectic\Helper;

use Doctrine\DBAL\Connection;

class SQL
{
    /**
     * Ugly custom WHERE builder.
     * 
     * @param string $field the full field name
     * @param array|mixed $criteria the where description (see comments)
     * @param string $key the field basename in the criteria
     * @param array $parameters the bind params values
     * @param string $operator default operator
     * @param boolean $expand expand array
     * @return boolean|string
     */
    public static function getCriteriaWhere($field, $criteria, $key, &$parameters, $operator = '=', $expand = true)
    {
        if (!is_array($criteria)) {
            $criteria = [$key => $criteria];
        }
        if (!array_key_exists($key, $criteria)) {
            return false;
        }
        // handle IS (NOT) NULL
        if (is_null($criteria[$key])) {
            if (in_array($operator, ['<>', 'NOT IN'])) {
                return "$field IS NOT NULL";
            }
            return "$field IS NULL";
        }
        // handle complex conditions
        // A "complex" condition is an array starting with an operator => value couple.
        // ie: 'foo' => ['<>' => 100] means foo <> 100
        // You can have many conditions sparated by AND/OR
        // ie: 'foo' => ['>' => 100, 'AND', '<' => 200] means foo > 100 AND foo < 200
        // params will be filled with incremental subkeys
        if (is_array($criteria[$key])) {
            list($op) = each($criteria[$key]);
            $op = strtoupper($op);
            if (in_array($op, ['LIKE', '<', '>', '<>', '<=', '>=', '=', 'IN', 'NOT IN'])) {
                $i = 1;
                $where = [];
                foreach ($criteria[$key] as $op => $value) {
                    if (is_int($op) && in_array(strtoupper($value), ['AND', 'OR'])) {
                        $where[] = strtoupper($value);
                    } elseif (in_array(strtoupper($op), ['LIKE', '<', '>', '<>', '<=', '>=', '=', 'IN', 'NOT IN'])) {
                        $subkey = $key.$i;
                        $where[] = self::getCriteriaWhere($field, [$subkey => $value], $subkey, $parameters, $op, $expand);
                        ++$i;
                    }
                }

                return implode(' ', $where);
            }
        }

        // Dealing with multiple values criteria
        // multiple values are always transformed in (NOT) IN (...) + IS (NOT) NULL condition
        if (is_array($criteria[$key])) {
            $values = [];
            $isNull = false;
            foreach ($criteria[$key] as $v) {
                if (is_null($v)) {
                    $isNull = true;
                } else {
                    $values[] = $v;
                }
            }
            $where = [];
            // if there are values left we add a IN () or NOT IN () condition
            if (!empty($values)) {
                // we put the values in the params array
                if ($expand) {
                    $i = 0;
                    $ks = [];
                    foreach ($values as $v) {
                        $i++;
                        $k = $key . '_' . $i;
                        $ks[] = ':' . $k;
                        $parameters[$k] = $v;
                    }
                    $where[] = "$field ".(in_array($operator, ['<>', 'NOT IN']) ? 'NOT IN' : 'IN')." (".implode(',', $ks).")";
                } else {
                    $parameters[$key] = $values;
                    $where[] = "$field ".(in_array($operator, ['<>', 'NOT IN']) ? 'NOT IN' : 'IN')." (:$key)";
                }
            }
            // if we have found a NULL value we add IS (NOT) NULL condition
            if ($isNull) {
                $where[] = "$field ".(in_array($operator, ['<>', 'NOT IN']) ? 'IS NOT' : 'IS').' NULL';
            }

            return implode(in_array($operator, ['<>', 'NOT IN']) ? ' AND ' : ' OR ', $where);
        }

        // we put the value in the params array
        $parameters[$key] = $criteria[$key];

        // basic condition
        return "$field $operator :$key";
    }

    /**
     * Prepare a sql for PDO with parameters not supported as it by PDO (ie \DateTime or array).
     * Parameters must be by name not by position (ie ':param').
     *
     * @param string $sql
     * @param array  $parameters
     */
    public static function preparePDO(&$sql, &$parameters)
    {
        $tmp = $parameters;
        foreach ($tmp as $k => $v) {
            if ($v instanceof \DateTime) {
                $parameters[$k] = $v->format('Y-m-d H:i:s');
            } elseif (is_array($v)) {
                // cas du IN

                $i = 0;
                $ph = [];
                foreach ($v as $vv) {
                    ++$i;
                    $parameters[$k.$i] = $vv;
                    $ph[] = ':'.$k.$i;
                }
                $v = implode(', ', $v);
                $sql = str_replace(":$k", implode(', ', $ph), $sql);

                unset($parameters[$k]);
            } else {
                $parameters[$k] = $v;
            }
        }
    }
    
    /**
     * Prepare and execute a sql or a a PDO query builder.
     * 
     * @param string|\Doctrine\DBAL\Query\QueryBuilder $sql
     * @param array $parameters
     * 
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public static function executePDO(Connection $db, $sql, $parameters, $execute = true)
    {
        if ($sql instanceof \Doctrine\DBAL\Query\QueryBuilder) {
            $sql = $sql->getSQL();
        }

        SQL::preparePDO($sql, $parameters);
        
        $stmt = $db->prepare($sql);
        
        if ($execute) {
            $stmt->execute($parameters);
        }
        
        return $stmt;
    }
    
}
