<?php

/*
 * Active Campaign Reference
 * https://developers.activecampaign.com/reference
 */

class SCFTAC_Active_Campaign_API {
    private $API_KEY;
    private $BASE_URL;

	/**
	 * Woo_ActiveCampaign_ActiveCampaign_API constructor.
	 *
	 * @param {string} $key
	 * @param {string} $base_url
	 */
    public function __construct($key, $base_url) {
        $this->API_KEY = $key;
        $this->BASE_URL = $base_url;
    }

	/**
	 * Get from DB
	 * Example: https://xxxxxx.api-us1.com/api/3
	 * @return string
	 */
    private function get_base_url () {
        return $this->BASE_URL;
    }

	/**
	 * @param $path
	 * @param string $method
	 * @param false $payload
	 *
	 * @return mixed
	 */
    private function request ($path, $method = "GET", $payload = false) {

        $args = array(
	        "method" => $method,
            "headers" => array(
                "Api-Token" => "$this->API_KEY"
            )
        );

        if ($method == 'POST' || $method == 'PUT') {
        	$args['headers']['Content-Type'] = 'application/json; charset=UTF-8';
        	$args['body'] = $payload;
        }

        $url = $this->get_base_url() . $path;
        $response = wp_remote_request($url, $args);
	    return json_decode(wp_remote_retrieve_body( $response ));
    }

	/**
	 * @return mixed
	 */
    public function get_fields () {
        return $this->request('/fields');
    }

	/**
	 * @param $field_id
	 *
	 * @return mixed
	 */
    public function get_field ($field_id) {
        return $this->request('/fields/'.$field_id);
    }

	/**
	 * @param $field_id
	 *
	 * @return mixed
	 */
    public function get_field_relations ($field_id) {
        return $this->request('/fields/'.$field_id.'/relations');
    }

	/**
	 * @param $email
	 *
	 * @return mixed
	 */
    public function get_contact_with_email ($email) {
        $url = '/contacts?email='.$email;
        return $this->request($url);
    }

	/**
	 * @param $contact_id
	 *
	 * @return mixed
	 */
    public function get_contact ($contact_id) {
        return $this->request('/contacts/' . $contact_id);
    }

	/**
	 * @param $contact_id
	 * @param $field_id
	 * @param $custom_field_value
	 *
	 * @return mixed
	 */
	public function create_contact_custom_field_value ($contact_id, $field_id, $custom_field_value) {
	    $payload = json_encode(array(
		    "fieldValue" => array(
			    "contact" => $contact_id,
			    "field" => $field_id,
			    "value" => $custom_field_value
		    )
	    ));

	    return $this->request('/fieldValues', 'POST', $payload);
    }

	/**
	 * @param $contact_id
	 * @param $field_id
	 * @param $field_value_id
	 * @param $value
	 *
	 * @return mixed
	 */
    public function update_contact_custom_field_value ($contact_id, $field_id, $field_value_id, $value) {

        $payload = json_encode(array(
            "fieldValue" => array(
                "contact" => $contact_id,
                "field" => $field_id,
                "value" => $value
            )
        ));

	    return $this->request('/fieldValues/' . $field_value_id, 'PUT', $payload);
    }
}
