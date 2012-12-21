<?php
/**
 * Namespaces to use
 */
use \Kima\Model;

/**
 * Things
 */
class Things extends Model
{

    /**
     * DB engine
     */
    const DB_ENGINE = 'mongo';

    /**
     * Collection name
     */
    const TABLE = 'things';


    /**
     * Set mongo as default engine for this model
     */
    protected $db_engine = 'mongo';

    /**
     * get
     */
    public static function get()
    {
        $things = new self();

        $things = $things->filter([])
            ->limit(5)
            ->fetch();

        return $things;
    }

    /**
     * update
     */
    public function put_thing()
    {
        $this->filter(['name' => 'testing'])
            ->put(['name' => 'testing']);
    }

}