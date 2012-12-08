<?php
/**
 * Namespaces to use
 */
use \Kima\Application,
    \Kima\Cache as KimaCache,
    \Kima\Controller;

/**
 * Cache
 */
class Cache extends Controller
{

    public function get()
    {
        $config = Application::get_config()->cache;
        $cache = KimaCache::get_instance('default', $config);
        var_dump($cache->get_type());

        var_dump($cache->get('test'));
        $cache->set('test', 'Hola Mundo', 10);
        var_dump($cache->get('test'));
    }

}