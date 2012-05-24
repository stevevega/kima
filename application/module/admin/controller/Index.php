<?php
/**
 * Namespaces to use
 */
use \Kima\Controller;

/**
 * Index
 */
class Index extends Controller
{

    /**
     * index
     */
    public function index_action()
    {
        # display content
        $this->_view->show('content');
    }

}