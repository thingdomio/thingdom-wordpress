<?php

/**
 * Thingdom API PHP Wrapper
 *
 * @author     Andrew Frenz <andrew.frenz@mts.com>
 * @author     Nicholas Kreidberg <nicholas.kreidberg@mts.com>
 * @copyright  2014-2015 MTS Systems
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 * @version    1.1b (Modified specifically for integration with WordPress)
 * @link       https://github.com/thingdomio/php-thingdom
 * @website    https://thingdom.io
 */

class Thingdom
{
    const   API_URL         = 'https://api.thingdom.io/1.1/';
    const   API_ERROR       = 'error';
    const   API_SUCCESS     = 'success';

    private $apiSecret      = '2VTUBkdUDGEc3ektwAeFrNbrWDXgExtS7u6JnBkw59JDU8YANEGrNJntCE77tzSSnVnN8btEhZbqkXs49ZEc75dFspbEuR2Mn5v5';
    public  $token;
    public  $lastError      = null;

    //
    // Constructor / Public Methods
    //

    public function __construct()
    {
        if(!$this->checkCurl()) {
            throw new Exception('cURL is not enabled on this server');
        }        
    }

    public function authenticate($deviceSecret = '')
    {
        $data = array(
            'api_secret'    => $this->apiSecret,
            'device_secret' => $deviceSecret
        );

        $response = $this->postToThingdom($endpoint = "token", $data);
        $this->token = $response['application_token'];

        if(empty($deviceSecret)) {
            return $response['device_secret'];
        }

        return $deviceSecret;
    }    

    public function getThing($name, $product_type = '')
    {
        // if we don't have a token we can't look up a thing
        if(empty($this->token)) {
            $this->lastError = "Invalid API key.";
        }

        $data = array(
            'token'         => $this->token,
            'product_type'  => $product_type,
            'name'          => $name
        );

        $response = $this->postToThingdom("thing", $data);

        if(empty($response['thing_id'])) {
            // failed to look up thing, set instance flag and
            // return empty Thing object

            $thing = (object) array(
                'id'        => -1,
                'code'      => '',
                'token'     => '',
                'lastError' => '',
                'name'      => ''
            );
        } else {
            // Instantiate new Thing object and return it back to the caller
            $thing = new Thing($response['thing_id'], $response['code'], $this->token);
        }

        return $thing;
    }

    public function postToThingdom($endpoint, $data)
    {
        $ch = curl_init();

        $curl_options = array(
            CURLOPT_URL             => self::API_URL.$endpoint,
            CURLOPT_POST            => 1,
            CURLOPT_HTTPHEADER      => array('Content-Type: application/json', '', 'Accept: application/json'),
            CURLOPT_POSTFIELDS      => json_encode($data),
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_SSL_VERIFYPEER  => false
        );

        curl_setopt_array($ch, $curl_options);

        $result     = curl_exec ($ch);
        $errMsg     = curl_error($ch);
        $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        if($errMsg) {
            $response = array('response' => 'error', 'msg' => $errMsg);
        } else if($httpCode != 200) {
            $response = array('response' => 'error', 'msg' => $result);
        } else {
            $response = json_decode($result, true);
        }

        return $response;
    }

    //
    // Private Methods
    //

    private function checkCurl()
    {
        return function_exists('curl_version');
    }
}

class Thing extends Thingdom {

    public $id;
    public $code;
    public $token;
    public $lastError = null;
    protected $name;
    protected $display_name;

    //
    // Constructor / Public Methods
    //

    public function __construct($thingId, $code, $token)
    {
        $this->id = $thingId;
        $this->token = $token;
        $this->code = $code;
    }

    public function feed($category, $message)
    {
        $data = array(
            'token'         => $this->token,
            'thing_id'      => $this->id,
            'feed_category' => $category,
            'message'       => $message,
            'options'       => null
        );

        $response = parent::postToThingdom("feed", $data);

        if($response['response'] != parent::API_SUCCESS) {
            $this->lastError = $response['msg'];
        }

        return $response;
    }

    public function status($key, $value, $unit = '')
    {
        $data = array(
            'token'         => $this->token,
            'thing_id'      => $this->id,
            'status_array'  => array(
                array(
                    'name' => $key,
                    'value'=> $value,
                    'unit' => $unit
                )
            )
        );

        $response = parent::postToThingdom("status", $data);

        if($response['response'] != parent::API_SUCCESS) {
            $this->lastError = $response['msg'];
        }

        return $response;
    }

    public function statusArray($statusArray)
    {
        $data = array(
            'token'         => $this->token,
            'thing_id'      => $this->id,
            'status_array'  => $statusArray
        );

        $response = parent::postToThingdom('status', $data);

        if($response['response'] != parent::API_SUCCESS) {
            $this->lastError = $response['msg'];
        }

        return $response;
    }

}