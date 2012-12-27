<?php
/**
 * Kima Search Solr
 * @author Steve Vega
 */
namespace Kima\Search;

use \Kima\Application,
    \Kima\Error,
    \SolrClient,
    \SolrClientException,
    \SolrIllegalArgumentException,
    \SolrInputDocument,
    \SolrQuery;

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
     * Instance
     * @var \Kima\Search\Solr
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
     * Construct
     */
    private function __construct()
    {
        // make sure solr is available
        if (!extension_loaded('solr'))
        {
            Error::set(self::ERROR_NO_SOLR);
        }
    }

    /**
     * Get the Solr instance
     * @return \Kima\Search\Solr
     */
    public static function get_instance()
    {
        isset(self::$instance) || self::$instance = new self;
        return self::$instance;
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
        if (empty($this->connection))
        {
            // get the config values to connect
            $config = Application::get_instance()->get_config();

            if (empty($config->search['solr']))
            {
                Error:set(self::ERROR_NO_CONFIG);
            }

            $options = $config->search['solr'];

            # make the connection
            $this->connect($options);
        }

        return $this->connection;
    }

    /**
     * Fetch values from the Solr index
     * @param string $query
     * @param array $fields
     * @return SolrQueryResponse
     */
    public function fetch(array $fields = [], $query_string = '*:*')
    {
        $query = new SolrQuery();
        $query->setQuery((string)$query_string);

        if (!empty($this->start))
        {
            $query->setStart($this->start);
        }

        if (!empty($this->rows))
        {
            $query->setRows($this->rows);
        }

        foreach ($fields as $field)
        {
            $query->addField($field);
        }

        $connection = $this->get_connection();

        try
        {
            $response = $connection->query($query);
        }
        catch (SolrClientException $e)
        {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        }

        return $response->getResponse()->response;
    }

    /**
     * Puts one or many documents to the Solr index
     * @param array | object
     * @return SolrUpdateResponse
     */
    public function put($documents)
    {
        $docs = [];

        if (!is_array($documents) && !is_object($documents))
        {
            Error::set(self::ERROR_INVALID_DOCUMENT);
        }

        if (is_array($documents))
        {
            foreach($documents as $document)
            {
                if (!is_object($document))
                {
                    Error::set(self::ERROR_INVALID_DOCUMENT);
                }

                $docs[] = $this->get_solr_document($document);
            }
        }
        else
        {
            $docs[] = $this->get_solr_document($documents);
        }

        $connection = $this->get_connection();

        try
        {
            $connection->addDocuments($docs);
            $response = $connection->commit();
        }
        catch (SolrClientException $e)
        {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        }
        catch(SolrIllegalArgumentException $e)
        {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        }

        return $response->getResponse();
    }

    /**
     * Deletes a list of values from the index
     */
    public function delete(array $ids)
    {
        $connection = $this->get_connection();

        try
        {
            foreach($ids as $id)
            {
                $response = $connection->deleteById($id);
            }

            $response = $connection->commit();
        }
        catch (SolrClientException $e)
        {
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
        try
        {
            $response = $connection->optimize();
        }
        catch (SolrClientException $e)
        {
            Error::set(sprintf(self::ERROR_SOLR_CLIENT, $e->getMessage()));
        }

        return $response;
    }

    /**
     * Gets a Solr document based on a regular object
     * @param mixed $document
     * @return SolrUpdateResponse
     */
    private function get_solr_document($document)
    {
        $solr_doc = new SolrInputDocument();

        // use reflection to set the document values
        foreach(get_object_vars($document) as $prop => $value)
        {
           if (is_array($value))
           {
               foreach ($value as $v)
               {
                   $solr_doc->addField($prop, $v);
               }
           }
           else
           {
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
        $limit = (int)$limit;
        $page = (int)$page;

        if ($limit > 0)
        {
            $this->rows = $limit;
            $this->start = $page > 0 ? $limit * ($page - 1) : 0;
        }

        return $this;
    }

}