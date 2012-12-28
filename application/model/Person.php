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

        $joins = [
            ['table' => 'location',
            'key' => 'id_location',
            'fields' => ['name' => 'location']],

            ['table' => 'city',
            'key' => 'id_city',
            'fields' => ['name' => 'city']]
        ];

        return $user->filter(['id_person' => $id_person])
            ->join($joins)
            ->fetch(['name']);
    }

    /**
     * getPeople
     */
    public static function get_people($limit = 10)
    {
        $user = new self();

        $filters = [
            '$or' => [
                [
                'name' => 'Steve',
                'last_name' => ['$ne' => 'Cascante'],
                'id_person' =>
                    ['$lte' => 108650325,
                    '$gte' => 106560217,
                    '$in' => [107440358, 108200569, 108440350, 108520971],
                    '$exists' => true]],
                ['name' => 'Jose'],
                ['last_name' => 'Castro']]];

        /*$filters = ['name' => 'Steve',
            '$or' => [
                ['last_name' => ['$in' => ['Castro', 'Cascante']]],
                ['surname' => 'Rodriguez']]];*/

        return $user->filter($filters)
            ->order(['id_person' => 'ASC'])
            ->limit($limit)
            ->fetch_all();
    }

    /**
     * update people
     * @param array $fields
     */
    public function put_person(array $fields)
    {
        return $this->put($fields);
    }


}