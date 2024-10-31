<?php

require('db.php');
require('active-campaign.php');

class SCFTAC_Admin_Page_Controller {

    // Admin Page Slug
    private $admin_page_slug = 'send-custom-fields-from-woocommerce-to-active-campaign';

    // Option Field Names
    private $api_key_option_field_name = 'woo_commerce_active_campaign_custom_fields_api_key';
    private $base_url_option_field_name = 'woo_commerce_active_campaign_custom_fields_base_url';
    private $custom_field_mapping_option_name = 'woo_commerce_active_campaign_custom_field_name_to_field_id_mapping';

	/**
	 * @return int[]
	 */
    function get_custom_field_mapping () {
        $data = get_option($this->custom_field_mapping_option_name);
        if (isset($data)) {
            return $data;
        }
	    return array();
    }

	/**
	 *
	 */
    function migrate_custom_fields () {

        $database = new SCFTAC_Database();

        // Find all users on Active Campaign that are on WooCommerce
        $customers = $database->get_customers_with_meta();
        $api_key = get_option($this->api_key_option_field_name);
        $base_url = get_option($this->base_url_option_field_name);
        $active_campaign = new SCFTAC_Active_Campaign_API($api_key, $base_url);

        echo "<br/>";
        echo "<table class=\"wp-list-table widefat fixed striped pages\">";
        echo "<thead>
		<tr>
		<th>Email</th>
		<th>Active Campaign User ID</th>
		<th>Custom Field Key</th>
		<th>Active Campaign Custom Field ID</th>
		<th>Custom Field Value</th>
		</tr>
	</thead>";
        $custom_field_mapping = $this->get_custom_field_mapping();
        foreach ($customers as $cus) {
            $customer_email = $cus->user_email;
            $ac_contact = $active_campaign->get_contact_with_email($customer_email);
            $ac_user_id = $ac_contact->contacts[0]->id;
            foreach ($cus->orders as $order) {
                foreach ($order as $custom_field_name => $custom_field_value) {
                    $custom_field_exists_on_active_campaign = array_key_exists($custom_field_name, $custom_field_mapping);
                    if ($custom_field_exists_on_active_campaign) {
                        echo "<tr>";
                        echo "<td>$customer_email</td>";
                        echo "<td>$ac_user_id</td>";
                        echo "<td>$custom_field_name</td>";
                        $custom_field_id = $custom_field_mapping[$custom_field_name];
                        echo "<td>$custom_field_id</td>";
                        echo "<td>$custom_field_value</td>";
                        // Based on custom meta values from each order of each customer,
                        // update the customer on ActiveCampaign with those properties
                        $active_campaign->create_contact_custom_field_value($ac_user_id, $custom_field_id, $custom_field_value);
                        echo "</tr>";
                    }
                }
            }
        }
        echo "</table>";
        echo "<p>Completed run!</p>";
    }

    function debug ($data) {
        ?>
        <pre>
            <?php print_r($data);?>
        </pre>
        <?php
    }

	/**
	 *
	 */
    function render_admin_settings_page () {
        $controller = new SCFTAC_Admin_Page_Controller();
        $form_post_path = admin_url('options-general.php?page='. $controller->admin_page_slug);
        ?>
        <div class="wrap">
            <h2>Active Campaign Integration</h2>
            <h3>Store Credentials</h3>
            <p>To find this information on obtaining your Active Campaign API key and base URL, you can go <a href="https://help.activecampaign.com/hc/en-us/articles/207317590-Getting-started-with-the-API#how-to-obtain-your-activecampaign-api-url-and-key" target="_blank">here</a>.</p>
            <form method="post" action="<?php echo $form_post_path;?>">
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="ac_api_key">API Key</label><br/>
                        </th>
                        <td>
                            <input type="text" name="ac_api_key" required class="regular-text" value="<?php echo esc_html(get_option($controller->api_key_option_field_name)); ?>">
                            <p class="description">Example: https://XXXXXXXXXX.api-us1.com/api/</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ac_base_url">API Base URL</label>
                        </th>
                        <td>
                            <input type="url" name="ac_base_url" required class="regular-text" value="<?php echo esc_url(get_option($controller->base_url_option_field_name)); ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div>
                    <?php submit_button('Save Settings'); ?>
                </div>
            </form>

            <br />
            <hr />
            <h3>Manage Fields</h3>

            <p>Here we can pass the keys that  <strong>must match exactly</strong> for WooCommerce custom values to Active Campaign, as long as the Active Campaign custom fields have the exact same field names.</p>
            <p>Whenever a WooCommerce order is completed these fields will be looked up on the order and sent to Active Campaign.</p>

                <h4>Remove Fields</h4>
                <table class="wp-list-table widefat fixed striped pages">
                    <thead>
                    <tr>
                        <th>Active Campaign Custom Field Name</th>
                        <th>Active Campaign Custom Field ID</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $custom_fields = $controller->get_custom_field_mapping();

                    if ($custom_fields) {
	                    foreach($custom_fields as $ac_custom_field_name => $ac_custom_field_id) {
		                    ?>
                            <tr>
                                <td><?php echo esc_html($ac_custom_field_name); ?></td>
                                <td><?php echo esc_html($ac_custom_field_id); ?></td>
                                <td>
                                    <form method="post" action="<?php echo $form_post_path;?>">
                                        <input type="hidden" name="delete_key" value="<?php echo esc_attr($ac_custom_field_name); ?>">
                                        <button class="button-secondary">Remove</button>
                                    </form>
                                </td>
                            </tr>
		                    <?php
	                    }
                    }
                    ?>
                    </tbody>
                </table>
                <br />
            <form method="post" action="<?php echo $form_post_path;?>">
                <h4>Add Fields</h4>
                <div>
                    <label for="ac_api_key">
                        <strong>Custom Field Name</strong><br/>
                        <input type="text" name="ac_custom_field_name">
                    </label>
                </div>
                <br />
                <div>
                    <label for="ac_base_url">
                        <strong>Custom Field ID</strong><br/>
                        <input type="number" name="ac_custom_field_id">
                    </label>
                </div>
                <br />
                <button class="button-secondary">Add Field</button>
            </form>

            <br />
            <hr />

            <h3>Run Field Migration</h3>
            <p>This script will:</p>
            <ol>
                <li>Find all customers in your WooCommerce installation by email</li>
                <li>Find all the orders that those customers have purchased</li>
                <li>Find the custom fields attached to those orders</li>
                <li>Match them against the <strong>Active Campaign Custom Field Name</strong> and <strong>Active Campaign Custom Field ID</strong> stored in the section above</li>
                <li>Apply the custom fields values to the ActiveCampaign contact</li>
            </ol>
            <form method="post" action="<?php echo $form_post_path;?>">
                <input type="hidden" name="migrate_data" value="true">
                <?php submit_button('Run Custom Field Migration'); ?>
            </form>
        </div>
        <?php
        // Run Migrate Data
        $is_migrating_data = sanitize_text_field($_POST['migrate_data']);
        if ($is_migrating_data == 'true') {
            $controller->migrate_custom_fields();
            return;
        }

        // Add Configuration
        $ac_api_key = sanitize_key($_POST['ac_api_key']);
        $ac_base_url = esc_url($_POST['ac_base_url']);
        if ($ac_api_key != '' && $ac_base_url != '') {
            update_option( $controller->api_key_option_field_name, $ac_api_key );
            update_option( $controller->base_url_option_field_name, $ac_base_url );
            wp_redirect($form_post_path);
            return;
        }

        // Add Custom Field
	    $ac_custom_field_name = sanitize_text_field($_POST['ac_custom_field_name']);
	    $ac_custom_field_id = intval($_POST['ac_custom_field_id']);
	    if ($ac_custom_field_name != '' && $ac_custom_field_id != null) {
		    $mapping = $controller->get_custom_field_mapping();
		    $mapping[$ac_custom_field_name] = $ac_custom_field_id;
		    update_option( $controller->custom_field_mapping_option_name, $mapping );
		    wp_redirect($form_post_path);
		    return;
        }

	    // Delete Custom Field
	    $delete_key = sanitize_text_field($_POST['delete_key']);
	    if ($delete_key != '') {
		    $mapping = $controller->get_custom_field_mapping();
		    unset($mapping[$delete_key]);
		    update_option( $controller->custom_field_mapping_option_name, $mapping);
		    wp_redirect($form_post_path);
		    return;
	    }
    }

    /**
     * https://developer.wordpress.org/reference/functions/add_options_page/
     */
    function add_menu_item () {
        $item_title = 'Send Custom Fields from WooCommerce To Active Campaign';
        $page_title = $item_title;
        $menu_title = $item_title;
        $controller = new SCFTAC_Admin_Page_Controller();
        add_options_page( $page_title, $menu_title, 'manage_options', $controller->admin_page_slug, array('SCFTAC_Admin_Page_Controller', 'render_admin_settings_page') );
    }
}