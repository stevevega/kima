<?php
/**
 * Namespaces to use
 */
use \Kima\Controller,
    \Kima\Google\UrlShortener,
    \Kima\Http\Request,
    \Kima\Payment\Paypal;

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
        var_dump(Request::getAll('module'));
        var_dump(getenv('MODULE'));
        # set title
        $this->_view->set('TITLE', 'Hola Mundo!');
        $this->_view->show('title');

        # one user example
        $id_person = Request::get('id', 112530105);
        $person = Person::get($id_person);

        $this->_view->set('id', $person->id_person, 'content');
        $this->_view->set('name', $person->name, 'content');

        # many users example
        $people = Person::get_people(10);
        $this->_view->populate('users', $people);

        # library example
        /*$shortener = new UrlShortener();
        $source = $shortener->shorten('http://www.google.com');
        $this->_view->set('source', $source, 'content');*/

        # display content
        $this->_view->show('content');
    }

    public function paypal_action()
    {
        $credentials = array(
            'username' => 'seller_1332970038_biz_api1.gmail.com',
            'password' => '1332970086',
            'signature' => 'AK4VwepUGH5sWY.MIqlpqKCT0T2pA9vdQwGcsOX9LRSl3Azy7aQkSw9v');

        $paypal = Paypal::get_instance(Paypal::DIRECT_PAYMENT, $credentials, true);

        $params = array(
                'CREDITCARDTYPE' => 'Visa',
                'ACCT' => '4737086922497822',
                'EXPDATE' => '032017',
                'FIRSTNAME' => 'Steavy',
                'LASTNAME' => 'Vega',
                'STREET' => 'Test Street',
                'CITY' => 'Miami',
                'STATE' => 'Florida',
                'ZIP' => '11111',
                'COUNTRYCODE' => 'US',
                'CURRENCYCODE' => 'USD',
                'AMT' => 10
            );

        $response = $paypal->direct_payment($params);
        $response
            ? var_dump($response)
            : var_dump($paypal->get_last_error());
    }

}