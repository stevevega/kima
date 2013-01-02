<?php
/**
 * Namespaces to use
 */
use \Kima\Application,
    \Kima\Controller,
    \Kima\Http\Request,
    \Kima\Google\UrlShortener,
    \MongoClient,
    \Kima\Logger;

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
        $this->view->set('TITLE', 'Hola Mundo!');
        $this->view->show('title');

        # one user example
        $id_person = Request::get_all('id_person', 106300624);
        $person = Person::get($id_person);

        $this->view->set('id_person', $person->id_person, 'content');
        $this->view->set('name', $person->name, 'content');

        # many users example
        $people = Person::get_people(10);
        $this->view->populate('people', $people);

        # set required scripts/styles
        $this->view->script('/js/main.js');
        $this->view->style('/css/main.css');

        # display content
        $this->view->show('content');
    }

}