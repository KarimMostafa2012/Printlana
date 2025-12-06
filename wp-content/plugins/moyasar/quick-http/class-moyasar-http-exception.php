<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Moyasar_Http_Exception extends RuntimeException
{
    /**
     * @var Moyasar_Http_Response
     */
    public $response;

    public function __construct($message, $response)
    {
        parent::__construct($message, 0, null);
        $this->response = $response;
    }
}
