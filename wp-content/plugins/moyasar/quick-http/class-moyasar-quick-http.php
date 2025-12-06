<?php
if ( ! defined( 'ABSPATH' ) ) exit;
require_once 'class-moyasar-http-response.php';
require_once 'class-moyasar-connection-exception.php';
require_once 'class-moyasar-http-exception.php';
require_once 'class-moyasar-http-client-exception.php';
require_once 'class-moyasar-http-server-exception.php';

class Moyasar_Quick_Http
{
    // Config
    private $headers = array();


    public static function make()
    {
        return new static();
    }

    public function __construct()
    {
        global $wp_version;
        $this->headers['User-Agent'] = 'Moyasar Http; Woocommerce Plugin v' . MOYASAR_PAYMENT_VERSION . '; Wordpress v' . $wp_version;

    }

    public function basic_auth($username, $password = null)
    {
        $this->headers['Authorization'] = 'Basic ' . base64_encode("$username:$password");

        return $this;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     */
    public function request($method, $url, $data = array())
    {
        $is_json = is_array($data);
        $body = count($data) > 0 ? wp_json_encode($data, true) : null;
        $method = trim(strtoupper($method));

        if ($is_json) {
            $this->headers['Content-Type'] = 'application/json';
        }

        if (in_array($method, array('GET', 'HEAD'))) {
            $url = $url . $this->encode_url_params($data);
            $body = null;
        }
        // Log Request
        moyasar_logger('[Moyasar] [Http] [Request]: ' . $method . ' ' . $url , 'info', null, [
            'body' => $body,
            'headers' => $this->headers
        ]);
        $request = wp_remote_request($url, array(
            'method' => $method,
            'headers' => $this->headers,
            'body' => $body
        ));


        // Check if object type WP_Error
        if ($request instanceof WP_Error) {
            moyasar_logger('[Moyasar] [Http] [WP_Error]: HTTP Error: ' . $request->get_error_message(), 'error');
            throw new Moyasar_Connection_Exception(esc_html('HTTP Error: ' . $request->get_error_message()));
        }

        $status = $request['response']['code'];
        $headers = $request['headers'];
        $body = $request['body'];
        $response = new Moyasar_Http_Response($status, $headers, $body);

        if ($response->isServerError()) {
            moyasar_logger('[Moyasar] [Http] [ServerError]: Server Error: ' . $body, 'error');
            throw new Moyasar_Http_Server_Exception(esc_html('Server Error'), $response);
        }

        if ($response->isClientError()) {
            moyasar_logger('[Moyasar] [Http] [ClientError]: Client Error: ' . $body, 'error');
            throw new Moyasar_Http_Client_Exception(esc_html('Client Error'), $response);
        }

        return $response;
    }

    public function get($url, $params = array())
    {
        return $this->request('GET', $url, $params);
    }

    public function post($url, $data = array())
    {
        return $this->request('POST', $url, $data);
    }

    public function put($url, $data = array())
    {
        return $this->request('PUT', $url, $data);
    }

    private function encode_url_params($params = [])
    {
        if (!is_array($params) || count($params) == 0) {
            return '';
        }

        $encoded = '?';

        foreach ($params as $key => $value) {
            $encoded .= urlencode($key) . '=' . urlencode($value) . '&';
        }

        return rtrim($encoded, '&');
    }

}
