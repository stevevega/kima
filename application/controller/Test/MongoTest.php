<?php
namespace Test;

/**
 * Namespaces to use
 */
use \Kima\Application,
    \Kima\Controller,
    \Kima\Http\Request,
    \Kima\Google\UrlShortener,
    \MongoClient,
    \Kima\Database\Mongo as MongoDb,
    \Test\Things;

/**e
 * Mongo
 */
class MongoTest extends Controller
{

    /**
     * get test
     */
    public function get($params)
    {
        $things = Things::get();

        echo $things->_id;
        $things->put(['test' => 'Steve', 'last_name' => 'Vega']);
        $things->filter(['test' => 'test'])->delete();

        $things = new Things();
        $things->filter(['test' => 'Steve'])->put(['test' => null]);
    }

}