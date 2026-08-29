<?php
/*
Plugin Name: AlfaPay Connect for WooCommerce
Description: Woocommerce payment gateway for bank alfalah.
Version: 1.2
Requires PHP: 7.4
Author: Alfa Payment Gateway
Author URI: https://www.bankalfalah.com/
Text Domain: bank-alfalah
*/

// Prevent direct access to the file
defined( 'ABSPATH' ) or exit;

/**
 * Log Alfa Payment Gateway errors to the WooCommerce logs
 * (WooCommerce > Status > Logs, source "bank-alfalah") so
 * failed handshake/IPN requests are visible instead of silent.
 *
 * @since 1.1
 * @param string $message
 */
if ( ! function_exists( 'bank_alfalah_log' ) ) {
    function bank_alfalah_log( $message ) {
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->error( $message, array( 'source' => 'bank-alfalah' ) );
        } else {
            error_log( '[bank-alfalah] ' . $message );
        }
    }
}

/**
 * Final Class for Alfa Payment Gateway
 * Woocommerce Payment Gateway
 * 
 */
if ( ! class_exists( 'WoocommerceBankAlfalah' ) ){
    
    final class WoocommerceBankAlfalah{
        
        /**
         * 
         * Plugin Version
         * Plugin Text Domain
         * 
         */
        public $version   = '1.1';
		public $slug      = 'bank-alfalah';
        
        /**
        * Not allowed
        * @since 1.0
        * @version 1.0
        */
        public function __clone(){
            _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version );
        }
        
        /**
        * Not allowed
        * @since 1.0
        * @version 1.0
        */
        public function __wakeup(){
            _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version );
        }
        
        /**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		public function define( $name, $value, $definable = true ){
			if ( ! defined( $name ) )
				define( $name, $value );
			elseif ( ! $definable && defined( $name ) )
				_doing_it_wrong( 'WoocommerceBankAlfalah->define()', 'Could not define: ' . $name . ' as it is already defined somewhere else!', BAF_WOO_VERSION );
		}
        
        /**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ){
			if ( file_exists( $required_file ) )
				require_once $required_file;
			else
				_doing_it_wrong( 'WoocommerceBankAlfalah->file()', 'Requested file ' . $required_file . ' not found.', BAF_WOO_VERSION );
		}
        
        /**
        * Construct
        * @since 1.0
        * @version 1.0
        */
        public function __construct(){
            $this->define_constants();
            $this->wordpress();
            $this->includes();
        }
        
        /**
		 * Define Constants
		 * First, we start with defining all requires constants if they are not defined already.
		 * @since 1.0
		 * @version 1.0
		 */
		private function define_constants(){

            $this->define( 'BAF_WOO_GATEWAY_ID', 'bank_alfalah_gateway' );

			/**
             * Here we define all plugin dir paths
             */
            $this->define( 'BAF_WOO_VERSION', $this->version );
            $this->define( 'BAF_WOO_TEXT_DOMAIN', $this->slug );
			$this->define( 'BAF_WOO_THIS', __FILE__, false );
            $this->define( 'BAF_WOO_BASE', plugin_basename( BAF_WOO_THIS ) );
			$this->define( 'BAF_WOO_ROOT_DIR', plugin_dir_path( BAF_WOO_THIS ), false );
			$this->define( 'BAF_WOO_INCLUDES', BAF_WOO_ROOT_DIR . 'includes/', false );
            $this->define( 'BAF_WOO_ADMIN', BAF_WOO_ROOT_DIR . 'admin/', false );
            /**
             * Here we define all plugin urls
             */
            $this->define( 'BAF_WOO_URL', plugin_dir_url(__FILE__), false );
            $this->define( 'BAF_WOO_ASSETS', BAF_WOO_URL . 'assets/', false );
            $this->define( 'BAF_WOO_JS', BAF_WOO_ASSETS . 'js/', false );
            $this->define( 'BAF_WOO_CSS', BAF_WOO_ASSETS . 'css/', false );
            $this->define( 'BAF_WOO_IMG', BAF_WOO_ASSETS . 'img/', false );
            /**
             * Here we define all order status
             */
            $this->define( 'BAF_WOO_TX_SUCCESS', 'Paid', false );
            $this->define( 'BAF_WOO_TX_FAILED', 'Failed', false );
            $this->define( 'BAF_WOO_TX_SESSIONENDED', 'SessionEnded', false );

		}
        
        /**
		 * Include Plugin Files
		 * @since 1.0
		 * @version 1.0
		 */
		public function includes(){
            $this->file( BAF_WOO_ADMIN . 'admin.php' );
            $this->file( BAF_WOO_INCLUDES . 'woocommerce.php' );
		}
        
        /**
		 * WordPress
		 * Next we hook into WordPress
		 * @since 1.0
		 * @version 1.0
		 */
		public function wordpress() {
            add_action( 'admin_init', array( $this , 'bank_alfalah_check_woocommerce_plugin' ) );
            add_action( 'wp_enqueue_scripts', array( $this , 'bank_alfalah_enqueue_style' ) );
			add_action( 'init', array( $this, 'bank_alfalah_load_textdomain' ), 5 );
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__) , array( $this, 'bank_alfalah_plugin_links' ), 10, 4 );
            add_filter( 'plugin_row_meta', array( $this, 'bank_alfalah_description_links' ), 10, 2 );
            add_action( 'admin_enqueue_scripts', array( $this, 'payment_scripts' ) );
        }
        
        /**
		 * Plugin Check Woocommerce Active or Install
		 * @since 1.0
		 * @version 1.0
		 */
        public function bank_alfalah_check_woocommerce_plugin() {
            
	        if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                deactivate_plugins( BAF_WOO_BASE );
                if ( isset( $_GET['activate'] ) )
                    unset( $_GET['activate'] );
            
                wp_die( '<b>'.__( 'Alfa Payment Gateway Woo Payment Gateway Plugin ', BAF_WOO_TEXT_DOMAIN ).'</b> '.__('requires you to install & activate', BAF_WOO_TEXT_DOMAIN ).'<b> '.__( 'WooCommerce Plugin', BAF_WOO_TEXT_DOMAIN ).'</b> '.__( 'before activating it!', BAF_WOO_TEXT_DOMAIN ).'<br><br><a href="javascript:history.back()"><< '.__( 'Go Back To Plugins Page', BAF_WOO_TEXT_DOMAIN ).'</a>' ); 
	        }
        }
        
        /**
		 * Plugin add little style
		 * @since 1.0
		 * @version 1.0
		 */
        public function bank_alfalah_enqueue_style() {
            if(is_checkout()){
                wp_enqueue_script( 'bank_alfalah', BAF_WOO_JS . 'script.js' , array( 'jquery' ), BAF_WOO_VERSION, true );
                wp_enqueue_style( 'bank_alfalah', BAF_WOO_CSS . 'view.css' );
            }
        }
        
        /**
    	 * Enqueue Script for the gateway settings page only
    	 * (was previously loading on every wp-admin screen).
    	 */
        public function payment_scripts( $hook ) {
            // Only load on the WooCommerce settings screen, and only when
            // viewing this gateway's section, to avoid shipping this script
            // and its localized nonce to every admin page.
            if ( 'woocommerce_page_wc-settings' !== $hook ) {
                return;
            }
            if ( ! isset( $_GET['section'] ) || 'bank_alfalah_gateway' !== $_GET['section'] ) {
                return;
            }
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            wp_enqueue_script( 'bank_alfalah', BAF_WOO_JS . 'script.js' , array( 'jquery' ), BAF_WOO_VERSION, true );
            wp_localize_script( 'bank_alfalah', 'bank_alfalah_AuthToken', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'bank_alfalah_test_connection' ),
            ) );
            wp_enqueue_style( 'bank_alfalah', BAF_WOO_CSS . 'view.css' );
        }
        
        /**
		 * Load Plugin Textdomain
		 * @since 1.0
		 * @version 1.0
		 */
		public function bank_alfalah_load_textdomain(){
			$locale = apply_filters( 'plugin_locale', get_locale(), BAF_WOO_TEXT_DOMAIN );
			load_textdomain( BAF_WOO_TEXT_DOMAIN , WP_LANG_DIR . '/'. BAF_WOO_TEXT_DOMAIN .'/wc-' . $locale . '.mo' );
			load_plugin_textdomain( BAF_WOO_TEXT_DOMAIN , false, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
		}
        
        /**
		 * Plugin Links
		 * @since 1.0
		 * @version 1.0
		 */
		public function bank_alfalah_plugin_links( $actions, $plugin_file, $plugin_data, $context ){
			$actions['_settings'] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bank_alfalah_gateway' ) . '" >' . __( 'Settings', BAF_WOO_TEXT_DOMAIN ) . '</a>';
			ksort( $actions );
			return $actions;
		}
        
        /**
		 * Plugin Description Links
		 * @since 1.0
		 * @version 1.0
		 */
		public function bank_alfalah_description_links( $links, $file ){
			if ( $file != BAF_WOO_BASE ) return $links;
			
			$links[] = '<a href="'. BAF_WOO_URL .'documentation" target="_blank">Documentation</a>';
            return $links;
		}
    }
    new WoocommerceBankAlfalah();
}
