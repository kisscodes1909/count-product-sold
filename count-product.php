<?php
/*
Plugin Name: Count product
Description: This plugin counts the total product sold. It's will run between 7:00 and 15:00. Use this shortcode [count_product_sold] to show on frontend.
Author: Huu Nguyen Dac
Version: 1.0
License: GPL
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: count-product
*/

define('count_product_dir', plugin_dir_path(__FILE__));
define('count_product_url', plugin_dir_url(__FILE__));

class count_product {
	function __construct() {
		//register activation function
		register_activation_hook(__FILE__, array($this, 'plugin_activate'));
		//register deactivation function
		register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));

		// This function used to setup cron.
		add_action('init', array($this, 'count_product_set_cron'));

		// Daily Increase Visitor and sale
		add_action('count_product', array($this, 'update_product_sold'));
		// See http://codex.wordpress.org/Plugin_API/Filter_Reference/cron_schedules
		add_filter( 'cron_schedules', array($this, 'every_90s_cron_schedule') );

		add_action('wp_footer', array($this, 'js_virtual_count_product'));

		add_shortcode('count_product_sold', array($this, 'count_product_output_html'));
						
	}

	/*
		We will check cron job is running
	*/
	function is_cron_running() {
		return wp_next_scheduled('count_product') && get_option('hn_total_product_sold');
	}

	function count_product_output_html() {
		ob_start();
		?>
				<div id="product-sold-modal">
					<h1 id="total-product-sold"><?php echo get_option('hn_total_product_sold'); ?></h1>
				</div>
		<?php	
		$html = ob_get_contents();
		ob_end_clean();
		wp_reset_postdata();
		return $html;			
	}

	function js_virtual_count_product() {
		if( $this->is_cron_running() ) {
			?>
			<script type="text/javascript">
				let totalProduct = document.getElementById('total-product-sold').innerHTML;
				setInterval( ()=> {
					totalProduct++;
					document.getElementById('total-product-sold').innerHTML = totalProduct;
				}, 90000);
			</script>
			<?php
		}
	}

	function every_90s_cron_schedule( $schedules ) {
	    $schedules['every_90s'] = array(
	        'interval' => 90, // 1 week in seconds
	        'display'  => __( 'Every 90s' ),
	    );

	   	$schedules['every_150s'] = array(
	        'interval' => 150, // 1 week in seconds
	        'display'  => __( 'Every 150s' ),
	    );
	 
	    return $schedules;
	}	

	function count_product_set_cron() {
		//The count product sold bettween 7:00 and 15:00. Out of time, we will delete the cron
		if( current_time('timestamp', 0) > strtotime('7:00:00')  && current_time('timestamp', 0) < strtotime('23:00:00', time()) ) {
			if( ! wp_next_scheduled('count_product')) {
				wp_schedule_event(time(), 'every_90s', 'count_product' );
			}
		} else {
			wp_clear_scheduled_hook('count_product');
		}
	}

	function plugin_activate() {
		if( !get_option('hn_total_product_sold') ) {
			update_option( 'hn_total_product_sold', 700000);
		}
	}

	function plugin_deactivate() {
		wp_clear_scheduled_hook('count_product');
	}

	function update_product_sold() {
		$total_product = get_option('hn_total_product_sold');
		$total_product++;
		update_option('hn_total_product_sold', $total_product);
	}

}

new count_product();



