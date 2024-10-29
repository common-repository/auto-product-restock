<?php
   /*
   Plugin Name: Auto Product Restock
   Plugin URI: 
   description: Automatically restock products daily. Set which products, a time, and a restock amount.
   Version: 1.01
   Author: Nerdy WP
   Author URI: https://www.nerdywp.com
   */



// Display Fields
add_action('woocommerce_product_options_stock_status', 'nwp_apr_woocommerce_product_custom_fields');
function nwp_apr_woocommerce_product_custom_fields()
{
    global $woocommerce, $post;
    echo '<div class="nwp_apr_option_metas">';
    woocommerce_wp_checkbox( array(
		'id'      => 'nwp_apr_restock_this_product',
		'value'   => get_post_meta( get_the_ID(), 'nwp_apr_restock_this_product', true ),
		'label'   => 'Restock Daily?' ,
		'desc_tip' => true,
		'description' => 'Check this to automatically restock daily.',
	) );

    woocommerce_wp_text_input(
        array(
            'id' => 'nwp_apr_restock_amount',
            'placeholder' => '0',
            'label' => __('Restock Quantity', 'woocommerce'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            ),
            'desc_tip' => true,
		'description' => 'This is the amount it will restock to everyday. It will be exactly this number.',
        )
    );

    woocommerce_wp_select( array(
		'id'          => 'nwp_apr_restock_time',
		'value'       => get_post_meta( get_the_ID(), 'nwp_apr_restock_time', true ),
		'label'       => 'Restock @ what time?',
		'options'     => array( '00' => '12am', '01' => '1am', '02' => '2am', '03' => '3am', '04' => '4am', '05' => '5am', '06' => '6am', '07' => '7am', '08' => '8am', '09' => '9am', '10' => '10am', '11' => '11am', '12' => '12pm', '13' => '1pm', '14' => '2pm', '15' => '3pm', '16' => '4pm', '17' => '5pm', '18' => '6pm', '19' => '7pm', '20' => '8pm', '21' => '9pm', '22' => '10pm', '23' => '11pm'),
		'desc_tip' => true,
		'description' => 'Choose when it should restock daily. Current system time: '.current_time('mysql').'. Change timezones in Wordpress general settings.',
	) );

    echo '</div>';

}

// Save Fields
add_action('woocommerce_process_product_meta', 'nwp_apr_woocommerce_product_custom_fields_save');
function nwp_apr_woocommerce_product_custom_fields_save($post_id)
{
    $nwp_apr_restock_this_product = sanitize_text_field($_POST['nwp_apr_restock_this_product']);
    if (!empty($nwp_apr_restock_this_product)){
        update_post_meta($post_id, 'nwp_apr_restock_this_product', esc_attr($nwp_apr_restock_this_product));
    } else {
		delete_post_meta( $post_id, 'nwp_apr_restock_this_product' );
	}

    $nwp_apr_restock_amount = sanitize_text_field($_POST['nwp_apr_restock_amount']);
    if (!empty($nwp_apr_restock_amount)){
        update_post_meta($post_id, 'nwp_apr_restock_amount', esc_attr($nwp_apr_restock_amount));
    } else {
		delete_post_meta( $post_id, 'nwp_apr_restock_amount' );
	}

    $nwp_apr_restock_time = sanitize_text_field($_POST['nwp_apr_restock_time']);
    if (!empty($nwp_apr_restock_time)){
        update_post_meta($post_id, 'nwp_apr_restock_time', esc_attr($nwp_apr_restock_time));
    } else {
		delete_post_meta( $post_id, 'nwp_apr_restock_time' );
	}

}

// set the cron
add_action( 'init', 'nwp_apr_register_restock_event');
function nwp_apr_register_restock_event() {
    // Make sure this event hasn't been scheduled
    if( !wp_next_scheduled( 'nwp_apr_reset_stock_daily' ) ) {
        // Schedule the event
        wp_schedule_event( time(), 'hourly', 'nwp_apr_reset_stock_daily' );
    }
}   

//stock reset cron
add_action('init', 'nwp_apr_reset_stock_daily');
function nwp_apr_reset_stock_daily() {

	//get all products set to restock
	$args = array(
	    "post_type" => "product",
	    'post_status' => 'publish',
    	'posts_per_page' => -1,
	    "meta_query" => array(
	    	'relation' => 'AND',
	        array(
	            "key"     => "nwp_apr_restock_this_product",
	            "value" => "yes",
	            "compare" => "=",
	        ),
	        array(
	            "key"     => "_manage_stock",
	            "value" => "yes",
	            "compare" => "=",
	        ),
	    ),
	);
	$the_query = new WP_Query( $args );
	if( $the_query->have_posts() ) {
	    while( $the_query->have_posts() ) {

	        $the_query->the_post();

	        $product_id = get_the_ID();

	        $hour = get_post_meta( $product_id , 'nwp_apr_restock_time', true );
	        $amount = get_post_meta( $product_id , 'nwp_apr_restock_amount', true );

	        $current_hour = current_time('H');
	        
	        if ($hour == $current_hour) {
	        	$product = new WC_Product($product_id);
			    if($product->get_total_stock() < $amount) {
			        $product->set_stock($amount);
			    }
	        }
	    }

	    // Very Important
	    wp_reset_postdata();
	}

}

//add some JS to make things fancier
add_action('admin_enqueue_scripts', 'nwp_apr_enqueue_js');
function nwp_apr_enqueue_js($hook) {
    // Only add to the post.php admin page.
    if ('post.php' !== $hook) {
        return;
    }
    wp_enqueue_script('nwp_apr_scripts', plugin_dir_url(__FILE__) . '/apr.js');
}


//clear the cron if the plugin is deactivated
function nwp_apr_deactivation() {
    wp_clear_scheduled_hook("nwp_apr_reset_stock_daily");
}
register_deactivation_hook( __FILE__, 'nwp_apr_deactivation' );