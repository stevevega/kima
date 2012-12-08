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
     * get
     */
    public static function get($id_person)
    {
        $user = new self();

        $fields = array('id_person', 'name');
        $binds = array(':id_person' => $id_person);

        return $user->fields($fields)
            ->filter('id_person=:id_person')
            ->bind($binds)
            ->fetch();
    }

    /**
     * getPeople
     */
    public static function get_people($limit = 10)
    {
        $user = new self();
        $binds = array(':name' => 'Steve');

        return $user->filter('name=:name')
            ->bind($binds)
            ->order('id_person')
            ->limit($limit)
            ->fetch(true);
    }

    /**
     * update people
     */
    public function update()
    {
        # set fields
        $fields = array('id_person', 'name');

        return $this->fields($fields)
            ->save();
    }


}