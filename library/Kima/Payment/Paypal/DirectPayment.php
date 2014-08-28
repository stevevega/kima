<?php
/**
 * Yeah Namespace
 */
namespace Kima\Payment\Paypal;

/**
 * Namespaces to use
 */

/**
 * Paypal Direct Payment
 */
class DirectPayment extends APaypal
{

    /**
     * API method name
     */
    const METHOD = 'DoDirectPayment';

    /**
     * API method required fields
     */
    private $_required_fields = array(
        'ACCT',             // Credit card number
        'EXPDATE',          // Expiration date in MMYYYY format
        'FIRSTNAME',        // Credit card fisrt name
        'LASTNAME',         // Credit card last name
        'STREET',           // Address street
        'CITY',             // Address city
        'STATE',            // Address state check https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_StateandProvinceCodes
        'ZIP',              // Address ZIP
        'COUNTRYCODE',      // Country, check https://cms.paypal.com/es/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_soap_country_codes
        'AMT'               // Item cost
    );

    /**
     * Makes an API request
     * @see https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_r_DoDirectPayment
     * @param array $params
     */
    public function request($params)
    {
        $this->_validate_required_fields($params, $this->_required_fields, self::METHOD);

        $response = $this->api_request(self::METHOD, $params);

        if ('SUCCESS' == $response['ACK'] || 'SUCCESSWITHWARNING' == $response['ACK']) {
            return $response;
        }

        $this->set_error_response($response, self::METHOD);

        return false;
    }

}
