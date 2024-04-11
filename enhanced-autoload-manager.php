<?php
/*
Plugin Name: Enhanced Autoload Manager
Version: 1.1
Description: Manages autoloaded data in the WordPress database, allowing for individual deletion or disabling of autoload entries.
Author: Your Name
Author URI: https://yourwebsite.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: enhanced-autoload-manager
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Enhanced_Autoload_Manager {
    
    function __construct() {
        // Add the menu item under Tools
        add_action( 'admin_menu', [ $this, 'add_menu_item' ] );
        // Handle actions for deleting and disabling autoloads
        add_action( 'admin_init', [ $this, 'handle_actions' ] );
    }

    // Add the menu item under Tools
    function add_menu_item() {
        add_submenu_page( 'tools.php', 'Enhanced Autoload Manager', 'Enhanced Autoload Manager', 'manage_options', 'enhanced-autoload-manager', [ $this, 'display_page' ] );
    }

    // Display the plugin page
    function display_page() {
        global $wpdb;

        // Get the top 20 autoloads
        $autoloads = $wpdb->get_results( "SELECT option_name, LENGTH(option_value) AS option_size FROM {$wpdb->options} WHERE autoload = 'yes' ORDER BY option_size DESC LIMIT 20" );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Enhanced Autoload Manager', 'enhanced-autoload-manager' ); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Option Name', 'enhanced-autoload-manager' ); ?></th>
                        <th><?php echo esc_html__( 'Size (Bytes)', 'enhanced-autoload-manager' ); ?></th>
                        <th><?php echo esc_html__( 'Actions', 'enhanced-autoload-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $autoloads as $autoload ) : ?>
                        <tr>
                            <td><?php echo esc_html( $autoload->option_name ); ?></td>
                            <td><?php echo esc_html( $autoload->option_size ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'tools.php?page=enhanced-autoload-manager&action=delete&option_name=' . urlencode( $autoload->option_name ) ), 'delete_autoload_' . $autoload->option_name ) ); ?>" class="button button-secondary"><?php echo esc_html__( 'Delete', 'enhanced-autoload-manager' ); ?></a>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'tools.php?page=enhanced-autoload-manager&action=disable&option_name=' . urlencode( $autoload->option_name ) ), 'disable_autoload_' . $autoload->option_name ) ); ?>" class="button button-secondary"><?php echo esc_html__( 'Disable Autoload', 'enhanced-autoload-manager' ); ?></a>
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
        global $wpdb;

        if ( isset( $_GET['page'], $_GET['action'], $_GET['option_name'], $_GET['_wpnonce'] ) && $_GET['page'] === 'enhanced-autoload-manager' ) {
            $option_name = urldecode( $_GET['option_name'] );
            $action = $_GET['action'];

            if ( wp_verify_nonce( $_GET['_wpnonce'], $action . '_autoload_' . $option_name ) ) {
                if ( $action === 'delete' ) {
                    $wpdb->delete( $wpdb->options, [ 'option_name' => $option_name ] );
                } elseif ( $action === 'disable' ) {
                    $wpdb->update( $wpdb->options, [ 'autoload' => 'no' ], [ 'option_name' => $option_name ] );
                }
                wp_redirect( admin_url( 'tools.php?page=enhanced-autoload-manager' ) );
                exit;
            }
        }
    }
}

// Instantiate the class
new Enhanced_Autoload_Manager;
