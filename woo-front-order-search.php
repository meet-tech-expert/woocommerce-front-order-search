<?php
/*
Plugin Name: Woo Front Order Search
Plugin URI: #
Description: Display search form and result for order.
Version: 1.0.0
Author: Rinkesh
Author URI: https://github.com/meet-tech-expert
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Domain Path: /languages
Text Domain: woo-front-order-search
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
define( 'PLUGIN_WFOS_VERSION', '1.0.0' );

add_action('wp_ajax_nopriv_woo_front_search_order', 'woo_front_search_order_action');
add_action('wp_ajax_woo_front_search_order', 'woo_front_search_order_action');

function woo_front_search_order_action(){
    //print_r($_POST);
    $order_id = trim($_POST['data']['order_id']);
    $order_id = str_replace("#",'',$order_id);
    $order = wc_get_order( $order_id );
    //var_dump($order);
    if(!$order)wp_send_json_error('注文が見つかりません。有効な注文IDを入力してください' );
    
    $ship_type = ''; $tracking_number='';
    
    // Iterating through order shipping items
	foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
		// Get the data in an unprotected array
		$shipping_data = $shipping_item_obj->get_data();
		$shipping_data_id  = $shipping_data['id'];
		if( $shipping_data['instance_id'] == '5' || $shipping_data['instance_id'] == '6' ){
			$ship_type = 'DHL';
		}else{
			$ship_type = 'JP';
		}
	}
	$tracking_number = woo_front_order_tracking($order->get_id());
	$status = $order->get_status();
	if($status == "processing"){
	    $status = "発送準備中";
	}else if($status == "completed"){
	    $status = "発送済み";
	}else if($status == "partially-shipped"){
	    $status = "商品の一部を発送 ";
	}else{
	    $status = ucfirst($status);
	}
    $data = array(
        'order_id'      => $order->get_id(),
        'en_status'     => $order->get_status(),
        'status'        => $status,
        'shipping'      => $ship_type,
        'tracking_number' => (!is_null($tracking_number))?$tracking_number:'',
    );
    wp_send_json_success( $data);
}
function woo_front_order_tracking( $order_id ){
		global $wpdb;
		$sql = "SELECT GROUP_CONCAT(DISTINCT meta_value SEPARATOR ',') AS tracking  FROM `ko5Dj_postmeta` WHERE `post_id` = $order_id AND `meta_key` LIKE '%tracking_item%'";
		$result = $wpdb->get_row($sql);
		return $result->tracking;
}
add_action( 'wp_footer', 'woo_front_order_footer' );
function woo_front_order_footer(){
	global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'woo-order-search-form') ) {
	echo '<script type="text/javascript" src="'.plugin_dir_url( __FILE__ ) . 'assets/js/woo-front-order-search.js'.'"></script>';
	}
}
add_action( 'wp_enqueue_scripts', 'woo_front_order_search_enqueue_scripts' );
function woo_front_order_search_enqueue_scripts(){
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'woo-order-search-form') ) {
    wp_register_style( 'woo-front-order-search', plugin_dir_url( __FILE__ ) . 'assets/css/woo-front-order-search.css', array(), '', 'all' );
    }    
}
add_shortcode( 'woo-order-search-form', 'woo_front_order_search_func' );
function woo_front_order_search_func( $atts ) {
    ob_start();
    $id = (isset($atts['id']) && $atts['id']!="")?$atts['id']:false;
    ?>
    <div class="woo_front_order_search" id="wfo-<?php echo $id;?>" data-id="<?php echo $id;?>">
        <div class="header">
            <h2>ご注文番号(＃からはじまる6桁以上の数字)を入力してください</h2>
        </div>
        <div class="search-form">
            <div class="search-div">
                <input type="number" class="search-input" placeholder="#は入力しないでください" />
            </div>
            <div class="button-div">
                <button type="button" class="search-btn" >ご注文情報を確認する</button>
            </div>
        </div>
        <div class="woo_front_order_response" class="response hide"><span></span></div>
    </div>
    <span> ※Macをお使いのお客様はChromeにてお調べお願いします。
        Safariのブラウザーでは正常に作動しない事例が報告されています。</span>
  
	<?php 
	wp_enqueue_style( 'woo-front-order-search' );
	//wp_enqueue_script( 'woo-front-order-search' );
    $data = ob_get_contents();
	ob_end_clean();
	return $data;
}

add_shortcode( 'woo-order-search-result', 'woo_front_order_result_func' );
function woo_front_order_result_func( $atts ) {
    ob_start();
    $id = (isset($atts['id']) && $atts['id']!="")?$atts['id']:false;
    ?>
    <div class="woo_front_order_table hide" id="wft-<?php echo $id;?>" data-id="<?php echo $id;?>">
         <table class="display_table">
             <tbody>
                 <tr><td>ご注文番号</td><td class="order_id-td"></td></tr>
                 <tr><td>ご注文状況</td><td class="order_status-td"></td></tr>
                 <tr><td>お荷物のトラッキング情報</td>
                 <td class="tracking-td"></td>
                 </tr>
             </tbody>
         </table>
    </div>
	<?php 
	//wp_enqueue_style( 'woo-front-order-search' );
	//wp_enqueue_script( 'woo-front-order-search' );
    $data = ob_get_contents();
	ob_end_clean();
	return $data;
}

register_activation_hook( __FILE__, 'activate_wfos' );
function activate_wfos(){
    /**
	* Check if WooCommerce is active
	**/
	if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )  ) {
		
		// Deactivate the plugin
		deactivate_plugins(__FILE__);
		
		// Throw an error in the wordpress admin console
		$error_message = __('This plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugins to be active!', 'woocommerce');
		die($error_message);
		
	}
}
?>
