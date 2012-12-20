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
    public function get()
    {
        # display content
        $this->view->show('content');
    }

}