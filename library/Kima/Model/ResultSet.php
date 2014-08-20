<?php
/**
 * Kima Result Set
 * @author Steve Vega
 */
namespace Kima\Model;

/**
 * Model Resultset class
 * Stores results set data
 */
class ResultSet
{

    /**
     * Total number of rows for the query
     * @var int
     */
    public $count;

    /**
     * The query result set values
     * @var array
     */
    public $values;

}
