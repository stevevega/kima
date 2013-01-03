<?php
/**
 * Solr example
 */
use \Kima\Controller,
    \Kima\Search\Solr as KimaSolr;

/**
 * Solr
 */
class Solr extends Controller
{

    /**
     * Get
     */
    public function get()
    {
        // set test doc
        $doc = new stdClass();
        $doc->id = '123456';
        $doc->name = 'Kima Test';
        $doc->manu = 'Kima';
        $doc->cat = ['test', 'kima'];
        $doc->features = ['testing', '123', '456'];
        $doc->price = 10;
        $doc->popularity = 10;

        $doc2 = new stdClass();
        $doc2->id = '56789';
        $doc2->name = 'Kima Test 2';
        $doc2->manu = 'Kima';
        $doc2->cat = ['test', 'kima'];
        $doc2->features = ['testing', '654', '321'];
        $doc2->price = 20;
        $doc2->popularity = 9;

        // get solr instance and put the doc
        $solr = KimaSolr::get_instance('kima');
        $solr->put([$doc, $doc2]);

        var_dump($solr->limit(2, 1)->fetch(['id', 'name'], '*', 'name:Kima*'));
    }

}