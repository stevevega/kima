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
     * @var int
     */
    public $id_person;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $last_name;

    /**
     * get
     */
    public static function get($id_person)
    {
        $user = new self();

        $fields = array('id_person' => 'id_person', 'name');

        return $user->fields($fields)
            ->filter('id_person=' . $id_person)
            ->fetch();
    }

    /**
     * getPeople
     */
    public static function get_people($limit = 10)
    {
        $user = new self();

        return $user->order('id_person')
            ->limit($limit)
            ->fetch(true);
    }

    /**
     * update people
     */
    public function update()
    {
        # set fields
        $fields = array('id', 'name');

        return $this->fields($fields)
            ->save();
    }

}