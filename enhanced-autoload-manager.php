<?php
/*
Plugin Name: Enhanced Autoload Manager
Version: 1.3
Description: Manages autoloaded data in the WordPress database, allowing for individual deletion or disabling of autoload entries.
Author: Rai Ansar
Author URI: https://raiansar.com
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: enhanced-autoload-manager
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Enhanced_Autoload_Manager {
    
    function __construct() {
        // Add the menu item under Tools
        add_action( 'admin_menu', [ $this, 'add_menu_item' ] );
        // Handle actions for deleting and disabling autoloads
        add_action( 'admin_init', [ $this, 'handle_actions' ] );
        // Enqueue custom styles
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_custom_styles' ] );
    }

    // Enqueue custom styles
    function enqueue_custom_styles() {
        wp_enqueue_style( 'enhanced-autoload-manager-css', plugins_url( 'styles.css', __FILE__ ) );
    }

    // Add the menu item under Tools
    function add_menu_item() {
        add_submenu_page( 'tools.php', 'Enhanced Autoload Manager', 'Enhanced Autoload Manager', 'manage_options', 'enhanced-autoload-manager', [ $this, 'display_page' ] );
    }

    // Display the plugin page
    function display_page() {
        global $wpdb;

        // Get the total autoload size in MBs
        $total_autoload_size = $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'");
        $total_autoload_size_mb = round($total_autoload_size / 1024 / 1024, 2);

        // Get the top 20 autoloads
        $autoloads = $wpdb->get_results("SELECT option_name, LENGTH(option_value) AS option_size FROM {$wpdb->options} WHERE autoload = 'yes' ORDER BY option_size DESC LIMIT 20");

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Enhanced Autoload Manager', 'enhanced-autoload-manager' ); ?></h1>
            <h2><?php echo sprintf(esc_html__('Total Autoload Size: %s MB', 'enhanced-autoload-manager'), esc_html($total_autoload_size_mb)); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Option Name', 'enhanced-autoload-manager' ); ?></th>
                        <th><?php echo esc_html__( 'Size', 'enhanced-autoload-manager' ); ?></th>
                        <th><?php echo esc_html__( 'Actions', 'enhanced-autoload-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $autoloads as $autoload ) :
                        $size_kb = round( $autoload->option_size / 1024, 2 );
                        $size_display = $size_kb < 1024 ? $size_kb . ' KB' : round( $size_kb / 1024, 2 ) . ' MB';
                    ?>
                        <tr>
                            <td><?php echo esc_html( $autoload->option_name ); ?></td>
                            <td><?php echo esc_html( $size_display ); ?></td>
                            <td>
                                <!-- The Nonce field is now displayed here -->
                                <?php $delete_nonce = wp_create_nonce('delete_autoload_' . $autoload->option_name); ?>
                                <?php $disable_nonce = wp_create_nonce('disable_autoload_' . $autoload->option_name); ?>
                                <a href="<?php echo esc_url( admin_url( 'tools.php?page=enhanced-autoload-manager&action=delete&option_name=' . urlencode( $autoload->option_name ) . '&_wpnonce=' . $delete_nonce ) ); ?>" class="eal-button eal-button-delete"><?php echo esc_html__( 'Delete', 'enhanced-autoload-manager' ); ?></a>
                                <a href="<?php echo esc_url( admin_url( 'tools.php?page=enhanced-autoload-manager&action=disable&option_name=' . urlencode( $autoload->option_name ) . '&_wpnonce=' . $disable_nonce ) ); ?>" class="eal-button eal-button-disable"><?php echo esc_html__( 'Disable', 'enhanced-autoload-manager' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // Handle the actions for deleting and disabling autoloads
    function handle_actions() {
        if ( ! isset( $_GET['page'], $_GET['_wpnonce'], $_GET['action'], $_GET['option_name'] ) || $_GET['page'] !== 'enhanced-autoload-manager' ) {
            return;
        }

        $action = sanitize_text_field( $_GET['action'] );
        $nonce_action = $action . '_autoload_' . sanitize_text_field( $_GET['option_name'] );

        if ( ! wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {
            wp_die( 'Nonce verification failed, action not allowed.', 'Nonce Verification Failed', array( 'response' => 403 ) );
        }

        // Sanitize and decode option_name after nonce verification
        $option_name = sanitize_text_field( urldecode( $_GET['option_name'] ) );

        global $wpdb;
        if ( $action === 'delete' ) {
            $wpdb->delete( $wpdb->options, [ 'option_name' => $option_name ] );
        } elseif ( $action === 'disable' ) {
            $wpdb->update( $wpdb->options, [ 'autoload' => 'no' ], [ 'option_name' => $option_name ] );
        }

        wp_redirect( admin_url( 'tools.php?page=enhanced-autoload-manager' ) );
        exit;
    }
}

// Instantiate the class
new Enhanced_Autoload_Manager;
