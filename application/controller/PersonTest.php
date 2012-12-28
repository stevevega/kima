<?php
/**
 * Namespaces to use
 */
use \Kima\Controller;

/**
 * Index
 */
class PersonTest extends Controller
{

    /**
     * index
     */
    public function get()
    {
        if (!$person = Person::get(1987654321))
        {
            $person = new Person();
        }

        $fields = [
            'id_person' => 1987654321,
            'name' => 'Pizcuilo',
            'last_name' => 'Ramírez',
            'surname' => 'Arnaez',
            'id_location' => 603025,
            'genre' => 0,
            'id_expiration_date' => '2011-11-11'];

        $person->put_person($fields);
    }

}