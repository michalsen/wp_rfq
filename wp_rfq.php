<?php
/*
Plugin Name: WP RFQ
Plugin URI: http://localhost
Description: RFQ w/o the commerce overhead
Version:     1
Author: Eric L. Michalsen
Author URI:
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


function register_user() {
    if ( !$_SESSION[ 'wprfq']  ) {
        session_start();
        if (strlen($_SESSION[ 'wprfq' ]) < 1) {
            $_SESSION[ 'wprfq' ] = time();
        }
    }
}

add_action('init', 'register_user');

// ADD TO CART JS
function wprfq_js_call() {
    wp_enqueue_script( 'script-name', plugins_url() . '/wp_rfq/assets/js/wp_rfq.js', array('jquery'), '1.0.0', true );
    wp_localize_script( 'script-name', 'rfqAjax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'security' => wp_create_nonce( 'rfq-special-string' )
    ));
}
add_action( 'wp_enqueue_scripts', 'wprfq_js_call' );



function wprfq_action_callback() {
    if ($_POST['method'] == 'add') {
        check_ajax_referer('rfq-special-string', 'security');
        $whatever = intval($_POST['whatever']);
        $proData = getData($whatever);
        echo $proData;
        die();
    }
    if ($_POST['method'] == 'delete') {
        check_ajax_referer('rfq-special-string', 'security');
        $delete = intval($_POST['data']);
        $proData = deleteData($delete);
        print createrfqtable();
        die();
    }
}

add_action( 'wp_ajax_wprfq', 'wprfq_action_callback' );
add_action( 'wp_ajax_nopriv_wprfq', 'wprfq_action_callback' );

// DELETE ITEM FROM RFQ
function deleteData($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'wprfq';
    $wpdb->query(" DELETE FROM $table WHERE product=" . $_REQUEST['product'] . " AND SESSION = ". $_REQUEST['user']);
    $posts = $wpdb->get_results("SELECT DISTINCT(product) FROM $table WHERE session =" . $_SESSION[ 'wprfq' ] . " ORDER BY time DESC");
    // print count($posts);
}

// ADD ITEM TO RFQ
function getData($data) {
    date_default_timezone_set('US/Eastern');
    global $wpdb;
    $table = $wpdb->prefix . 'wprfq';
    $wpdb->insert($table, array(
                    'time' => date('Y-m-d H:i:s'),
                    'session' => $_REQUEST['user'],
                    'product' => $_REQUEST['product'],
                ));

    $posts = $wpdb->get_results("SELECT DISTINCT(product) FROM $table WHERE session =" . $_SESSION[ 'wprfq' ]);
    $return = ['count' => count($posts), 'form' => createrfqtable()];
    print json_encode($return);
}


// ADD TO CART SHORTCODE
function wprfqadd() {
    $nonce = wp_create_nonce("wprfq_nonce");
    print '<button id="wprfq_button"
                   session="' . $_SESSION[ 'wprfq' ] . '"
                   value="' . get_post()->ID . '"
                   data-nonce="' . $nonce . '">Request Quote</button>';
}
add_shortcode('wprfq', 'wprfqadd');

// CART TOTAL SHORTCODE
function wprfqcart() {
    global $wpdb;
    $table = $wpdb->prefix . 'wprfq';
    $posts = $wpdb->get_results("SELECT DISTINCT(product) FROM $table WHERE session =" . $_SESSION[ 'wprfq' ] . " ORDER BY time DESC");
    print '<span class="wprfqcart">' . count($posts) . '</span>';
}
add_shortcode('wprfqcart', 'wprfqcart');

// CART DETAILS SHORTCODE
function wprfqcartdetail() {
    print createrfqtable();
}
add_shortcode('wprfqcartdetails', 'wprfqcartdetail');

function createrfqtable(){
    global $wpdb;
    $table = $wpdb->prefix . 'wprfq';
    $posts = $wpdb->get_results("SELECT distinct(product) FROM $table WHERE session =" . $_SESSION[ 'wprfq' ]);

    $requestform = '<div class="wprfqform">';
    $requestform .= '<br>Items Requested for Quote: (' . count($posts) . ')<br>';
//    $table = '<table border="1" id="rfqtable">';
    $nonce = wp_create_nonce("wprfq_nonce");
    $table = '<table border="1" id="rfqtable">';
    foreach ($posts as $key => $value) {

        // HERE BE DRAGONS!!!
        // that ACF Field is from my initial install
        $post = get_post($value->product);
        $product = get_field_object('field_5f85adab8ec38', $post->ID);

        $table .= '<tr><td>';
        $nonce = wp_create_nonce("wprfq_nonce");
        $table .= '<button
                           id="cartdelete"
                           session="' . $_SESSION[ 'wprfq' ] . '"
                           value="' . $post->ID . '"
                           data-nonce="' . $nonce . '">X</button>';

        $table .= '</td><td>';
        $table .= $post->post_title;
        $table .= '</td><td>';
        $table .= $product['value'];
        $table .= '</td></tr>';
    }
    $table .= '</table>';
    $table .= '</div>';
    // $return = json_encode(['count' => count($posts), 'table' => $table]);
    // $table .= '<table>';
    return $requestform . $table;
}

// Plugin install/uninstall hooks
register_activation_hook( __FILE__, 'wprfq_install' );
register_deactivation_hook( __FILE__, 'wprfq_remove' );

/**
 *  wprfq DB Table
 */
function wprfq_install () {
    global $wpdb;
    global $jal_db_version;
    $table_name = $wpdb->prefix . 'wprfq';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            session int(12),
            product int(8),
            PRIMARY KEY  (id)
          ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    add_option( 'jal_db_version', $jal_db_version );
}

/**
 *  wprfq REMOVE DB Table
 */
function wprfqq_remove () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wprfq';
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
}
