<?php

class SCFTAC_Database {

	/**
	 * @param $query
	 *
	 * @return array|object|null
	 */
	private function wp_query ($query) {
		global $wpdb;
		return $wpdb->get_results($query);
	}

	/**
	 * @param $string
	 * @param $startString
	 *
	 * @return bool
	 */
	function startsWith($string, $startString) {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    /**
     * @param $post_id
     *
     * @return array
     */
    function get_order_meta ($post_id) {
        $sql = "SELECT meta_key, meta_value FROM wp_postmeta WHERE post_id='$post_id';";
        $meta_results = $this->wp_query($sql);
        $order_meta = array();
	    foreach ($meta_results as $meta_row) {
		    $key = $meta_row->meta_key;
		    $has_no_underscore = !$this->startsWith($key, '_');
		    $not_invalid_field = in_array($key, array('is_vat_exempt'));
		    $has_no_active_campaign_key = !$this->startsWith($key, 'activecampaign_for_woocommerce');
		    if ($has_no_underscore && $has_no_active_campaign_key && !$not_invalid_field) {
			    $order_meta[$key] = $meta_row->meta_value;
		    }
	    }
        return $order_meta;
    }

	/**
	 * @return array
	 */
    function get_customer_emails () {
        $sql = "SELECT user_email FROM wp_users;";
        $result = $this->wp_query($sql);
        $customers = array();

        foreach ($result as $customer) {
            array_push($customers, $customer);
        }
        return $customers;
    }

	/**
	 * @param $customers
	 *
	 * @return array
	 */
    function get_customers_with_orders ($customers) {
        $customers_with_orders = array();
        foreach ($customers as $customer) {
            $email = $customer->user_email;
            $sql = "SELECT * FROM wp_postmeta WHERE meta_value='$email' AND meta_key='_billing_email';";
            $result = $this->wp_query($sql);
            $order_ids = array();
            foreach ($result as $meta) {
                array_push($order_ids, $meta->post_id);
            }
            $customer->order_ids = $order_ids;
            if (sizeof($order_ids) > 0) {
                array_push($customers_with_orders, $customer);
            }
        }
        return $customers_with_orders;
    }

	/**
	 * @return array
	 */
    function get_customers_with_meta () {
        $customers = $this->get_customer_emails();
        $customers_with_orders = $this->get_customers_with_orders($customers);

        for ($i = 0; $i < count($customers_with_orders); $i++) {
            $orders_meta = array();
            $customer = $customers_with_orders[$i];
            foreach ($customer->order_ids as $order_id) {
                $order_meta = $this->get_order_meta($order_id);
                $order_meta['post_id'] = $order_id;
                array_push($orders_meta, $order_meta);
            }
            $customers_with_orders[$i]->orders = $orders_meta;
        }
        return $customers_with_orders;
    }
}
