<?php
/**
 * Namespaces to use
 */
use \Kima\Model;

/**
 * Person
 */
class Person extends Model
{

    /**
     * Table name
     */
    const TABLE = 'person';

    /**
     * get
     */
    public static function get($id_person)
    {
        $user = new self();
        $binds = [':id_person' => $id_person];

        $joins = [
            ['table' => 'location',
            'key' => 'id_location',
            'fields' => ['name' => 'location']],

            ['table' => 'city',
            'key' => 'id_city',
            'fields' => ['name' => 'city']]
        ];

        return $user->filter(['id_person = :id_person'])
            ->bind($binds)
            ->join($joins)
            ->fetch(['name']);
    }

    /**
     * getPeople
     */
    public static function get_people($limit = 10)
    {
        $user = new self();
        $binds = array(':name' => 'Steve');

        return $user->filter(['name = :name'])
            ->bind($binds)
            ->order(['id_person' => 'ASC'])
            ->limit($limit)
            ->fetch_all();
    }

    /**
     * update people
     */
    public function put_person()
    {
        # set fields
        $fields = array('id_person', 'name');

        return $this->put($fields);
    }


}