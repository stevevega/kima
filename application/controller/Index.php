<?php
/**
 * Namespaces to use
 */
use \Kima\Application,
    \Kima\Controller,
    \Kima\Http\Request,
    \Kima\Google\UrlShortener;

/**
 * Index
 */
class Index extends Controller
{

    /**
     * get test
     */
    public function get($params)
    {
        # set title
        $this->_view->set('TITLE', 'Hola Mundo!');
        $this->_view->show('title');

        # one user example
        $id_person = Request::get_all('id_person', 106300624);
        $person = Person::get($id_person);

        $this->_view->set('id_person', $person->id_person, 'content');
        $this->_view->set('name', $person->name, 'content');

        # many users example
        $people = Person::get_people(10);
        $this->_view->populate('people', $people);

        # set required scripts/styles
        $this->_view->script('/js/main.js');
        $this->_view->style('/css/main.css');

        # display content
        $this->_view->show('content');
    }

}