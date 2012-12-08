<?php
/**
 * Namespaces to use
 */
use \Kima\Controller,
    \Kima\Payment\Paypal\DirectPayment;

/**
 * Paypal
 */
class Paypal extends Controller
{

    public function get()
    {
        $credentials = array(
            'username' => 'seller_1332970038_biz_api1.gmail.com',
            'password' => '1332970086',
            'signature' => 'AK4VwepUGH5sWY.MIqlpqKCT0T2pA9vdQwGcsOX9LRSl3Azy7aQkSw9v');

        $direct_payment = new DirectPayment($credentials, true);

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

        $response = $direct_payment->request($params);
        $response
            ? var_dump($response)
            : var_dump($direct_payment->get_last_error());
    }

}