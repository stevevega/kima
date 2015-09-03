<?php
/**
 * Kima Search Solr
 * @author Steve Vega
 */
namespace Kima\Search;

use Kima\Prime\App;
use Kima\Error;
use SolrClient;
use SolrClientException;
use SolrIllegalArgumentException;
use SolrInputDocument;
use SolrQuery;

/**
 * Solr
 * Enables interaction with the Solr search engine
 */
class Solr
{

    /**
     * Error messages
     */
    const ERROR_NO_SOLR = 'Solr extension is not available on this server';
    const ERROR_INVALID_DOCUMENT = 'Solr document must be an object or an array of objects';
    const ERROR_NO_CONFIG = 'Empty Solr config in application ini';
    const ERROR_SOLR_CLIENT = 'Solr client exception: "%s"';

    /**
     * Query order values
     */
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    /**
     * Instance
     * Array of the instances with different cores of Solr
     * @var array
     */
    private static $instance;

    /**
     * connection
     * @var SolrClient
     */
    private $connection;

    /**
     * Start
     * @var int
     */
    private $start;

    /**
     * Rows
     * @var int
     */
    private $rows;

    /**
     * The core of the current Solr instance
     * @var string
     */
    private $core;

    /**
     * An array of arrays with the fields to be used for sorting
     * e.g.
     * [
     *     'name' => SolrQuery:ORDER_ASC,
     *     'type' => SolrQuery:ORDER_DESC,
     * ]
     * @var [type]
     */
    private $sort_fields;

    /**
     * An array of field names that are used to facet a search by fields
     * @var array
     */
    private $facet_fields;

    /**
     * An array of field names that are used to facet a search by query
     * @var array
     */
    private $facet_queries;

    /**
     * Defines the default min value that a faceted field should return
     * @var integer
     */
    private $facet_min_count = 1;

    private $highlight_fields = [];
    /**
     * Construct
     * @param string $core
     */
    private function __construct($core)
    {
        // make sure solr is available
        if (!extension_loaded('solr')) {
            Error::set(self::ERROR_NO_SOLR);
        }

        // set the core
        $this->core = $core;
    }

    /**
     * Get the Solr instance
     * @param  string            $core
     * @return \Kima\Search\Solr
     */
    public static function get_instance($core)
    {
        $core = (string) $core;

        isset(self::$instance[$core]) || self::$instance[$core] = new self($core);

        return self::$instance[$core];
    }

    /**
     * Creates a new Solr connection
     * @param array $options
     */
    public function connect(array $options)
    {
        $this->connection = new SolrClient($options);

        return $this->connection;
    }

    /**
     * Gets a Solr Client instance
     * @return SolrClient
     */
    public function get_connection()
    {
        if (empty($this->connection)) {
            // get the config values to connect
            $config = App::get_instance()->get_config();

            if (empty($config->search['solr'][$this->core])) {
                Error::set(self::ERROR_NO_CONFIG);
            }

            $options = $config->search['solr'][$this->core];

            # make the connection
            $this->connect($options);
        }

        return $this->connection;
    }

    /**
     * Fetch values from the Solr index
     * @param  array             $fields
     * @param  string            $query_string
     * @param  array             $params       any additional params required
     * @return SolrQueryResponse
     */
    public function fetch(array $fields = [], $query_string = '*:*', array $params = [])
    {
        $query = new SolrQuery();
        $query->setQuery((string) $query_string);

        if (!empty($params)) {
            // add each extra param to be added to the solr query
            foreach ($params as $param => $value) {
                if (is_array($value)) {
                    // in the case of params to be included multiple times, such as fq,
                    // an array can be sent for each of the values
                    foreach ($value as $inner_value) {
                        $query->addParam((string) $param, (string) $inner_value);
                    }
                } else {
                    // simple append single values
                    $query->addParam((string) $param, (string) $value);
                }
            }
        }

        if (!empty($this->start)) {
            $query->setStart($this->start);
        }

        if (isset($this->rows)) {
            $query->setRows($this->rows);
        }

        foreach ($fields as $field) {
            $query->addField($field);
        }

        // add the sort fields to the query
        if (!empty($this->sort_fields)) {
            foreach ($this->sort_fields as $sort_field => $sort_order) {
                $query->addSortField($sort_field, $sort_order);
            }
        }

        // turn on faceting and add any field for it
        if (!empty($this->facet_fields)) {
            $query->setFacet(true);
            // ignore results with 0 as those are not useful
            $query->setFacetMinCount($this->facet_min_count);
            foreach ($this->facet_fields as $facet) {
                $query->addFacetField($facet);
            }
        }

        // turn on faceting and add any query for it
        if (!empty($this->facet_queries)) {
            $query->setFacet(true);
            foreach ($this->facet_queries as $facet) {
                $query->addFacetQuery($facet);
            }
        }

        if (!empty($this->highlight_fields)) {
            $query->setHighlight(true);
            foreach ($this->highlight_fields as $hl_field) {
                $query->addHighlightField($hl_field);
            }
        }

        $connection = $this->get_connection();

        try {
            $response = $connection->query($query);
        } catch (SolrClientException $e) {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        }

        return $response->getResponse();
    }

    /**
     * Puts one or many documents to the Solr index
     * @param array | object
     * @return SolrUpdateResponse
     */
    public function put($documents)
    {
        $docs = [];

        if (!is_array($documents) && !is_object($documents)) {
            Error::set(self::ERROR_INVALID_DOCUMENT);
        }

        if (is_array($documents)) {
            foreach ($documents as $document) {
                if (!is_object($document)) {
                    Error::set(self::ERROR_INVALID_DOCUMENT);
                }

                $docs[] = $this->get_solr_document($document);
            }
        } else {
            $docs[] = $this->get_solr_document($documents);
        }

        $connection = $this->get_connection();

        try {
            $connection->addDocuments($docs);
            // $response = $connection->commit();
            $response = $this->commit();
        } catch (SolrClientException $e) {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        } catch (SolrIllegalArgumentException $e) {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        }

        // return $response->getResponse();
        return $response;
    }

    /**
     * Deletes all documents matching the given query
     * @param  string             $query The query; example "*:*" (This will erase the entire index)
     * @return SolrUpdateResponse on success and throws a SolrClientException on failure
     */
    public function delete_by_query($query_string)
    {
        $connection = $this->get_connection();

        try {
            $response = $connection->deleteByQuery($query_string);
            // $response = $connection->commit();
            $response = $this->commit();
        } catch (SolrClientException $e) {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        }

        // return $response->getResponse();
        return $response;
    }

    /**
     * Deletes a list of values from the index
     */
    public function delete(array $ids)
    {
        $connection = $this->get_connection();

        try {
            foreach ($ids as $id) {
                $response = $connection->deleteById($id);
            }

            $response = $connection->commit();
        } catch (SolrClientException $e) {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        }

        return $response->getResponse();
    }

    /**
     * Optimize the Solr index
     */
    public function optimize()
    {
        $connection = $this->get_connection();
        try {
            $response = $connection->optimize();
        } catch (SolrClientException $e) {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        }

        return $response;
    }

    /**
     * Gets a Solr document based on a regular object
     * @param  mixed              $document
     * @return SolrUpdateResponse
     */
    private function get_solr_document($document)
    {
        $solr_doc = new SolrInputDocument();

        // use reflection to set the document values
        foreach (get_object_vars($document) as $prop => $value) {
           if (is_array($value)) {
               foreach ($value as $v) {
                   $solr_doc->addField($prop, $v);
               }
           } else {
               $solr_doc->addField($prop, $value);
           }
        }

        return $solr_doc;
    }

    /**
     * Limits the results number to fetch
     */
    public function limit($limit, $page = 0)
    {
        $limit = (int) $limit;
        $page = (int) $page;

        $this->rows = $limit;

        if ($limit > 0) {
            $this->start = $page > 0 ? $limit * ($page - 1) : 0;
        }

        return $this;
    }

    /**
     * Sets what fields to use for ordering the query results
     * @param  array $sort_fields contains the fields and (optionally) the order
     *                            ['name', 'type' => Solr::DESC]
     * @return Solr  reference to this
     */
    public function order(array $sort_fields = [])
    {
        $this->sort_fields = [];

        foreach ($sort_fields as $key => $value) {
            $has_order = !is_numeric($key);

            $field = $has_order ? $key : $value;

            // default to ASC if the value is not found
            $order = $has_order && self::ORDER_DESC === $value
                ? SolrQuery::ORDER_DESC
                : SolrQuery::ORDER_ASC;

            $this->sort_fields[$field] = $order;
        }

        return $this;
    }

    /**
     * Sets the fields that will be used to generate the facet fields
     * @param array $facet_fields array of field names
     */
    public function facet_fields($facet_fields = [])
    {
        $this->facet_fields = $facet_fields;

        return $this;
    }

    /**
     * Sets the fields that will be used to generate the facet queries
     * @param array $facet_fields array of query to be used to generate the facets
     */
    public function facet_queries($facet_queries = [])
    {
        $this->facet_queries = $facet_queries;

        return $this;
    }

    /**
     * Sets the default min value to be returned in a faceted field, set the default
     * in one as 0 is generally not useful for display purposes
     * @param  integer $count
     * @return $this
     */
    public function facet_min_count($count = 1)
    {
        $this->facet_min_count = (int) $count;

        return $this;
    }

    /**
     * Sets the fields that will be hightlighted in the Solr query
     * @param  array $highlight_fields all the field names to be highlighted
     * @return this
     */
    public function highlight_fields(array $highlight_fields = [])
    {
        $this->highlight_fields = $highlight_fields;

        return $this;
    }

    /**
     * Alternative commit function while the bug 'waiteflush' is fixed
     * @See https://bugs.php.net/bug.php?id=62332
     * @return
     */
    private function commit()
    {
        $config = App::get_instance()->get_config();
        $response = false;
        if (empty($config->search['solr'][$this->core])) {
            Error:set(self::ERROR_NO_CONFIG);
        } else {
            $solrConfig = $config->search['solr'][$this->core];
            $solrAddress = $solrConfig['hostname'] . ':' . $solrConfig['port'] . '/' .$solrConfig['path'];
            $url = 'http://' . $solrAddress . '/update?commit=true';

            // Open curl session
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);

            // timeout after 10 ms, tried with 100 ms but it seems some of the
            // calls were not correctly routed
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10);

            // this allows curl to ignore the signals and correctly timeout on MS
            // "If libcurl is built to use the standard system name resolver, that
            // portion of the transfer will still use full-second resolution for timeouts
            // with a minimum timeout allowed of one second."
            // http://nl3.php.net/manual/en/function.curl-setopt.php#104597
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

            // Stop printing the result on the screen
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            //execute post, this will return false as we are not waiting for the response
            $response = curl_exec($ch);

            //close connection
            curl_close($ch);
        }

        return $response;
    }

}
