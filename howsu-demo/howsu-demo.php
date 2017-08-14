<?php
/*
Plugin Name: How'a U Demo Feature
Plugin URI: http://eighty20results.com/wordpress-plugins/
Description: Website demo for the How's U service
Version: 1.1
Requires: 4.8
Tested: 4.8.1
Author: Thomas Sjolshagen <thomas@eighty20results.com>
Author URI: http://www.eighty20results.com/thomas-sjolshagen/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: howsu-demo
*/
/**
 * Copyright (c) 2016-2017 - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Howsu_Demo;

defined( 'ABSPATH' ) or die( 'Cannot access plugin sources directly' );

if ( ! defined( 'HOWSU_DEMO_PLUGIN_URL' ) ) {
	define( 'HOWSU_DEMO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'HOWSU_DEMO_VER' ) ) {
	define( 'HOWSU_DEMO_VER', '1.1' );
}

if ( ! defined ( 'HOWSU_DEMO_DELETE_USER' ) ) {
	define( 'HOWSU_DEMO_DELETE_USER', false );
}

class howsUDemo {
	
	/**
	 * @var     howsUDemo $instance - Class instance
	 */
	private static $instance;
 
	private $urlBase = '';
	
	/**
	 * API Key for TextIt service (How's U specific)
	 *
	 * @var string
	 */
	private $key = '7964392e969ce3aa258906f8e864380c2d058841';
	
	/**
	 * Flow UUID for the telephone call demo
	 *
	 * @var string
	 */
	private $phone_flow_uuid = 'b2610257-342b-4bf4-8c3c-9dd4761b2fd3';
	
	/**
	 * Flow UUID for the SMS message demo
	 *
	 * @var string
	 */
	private $sms_flow_uuid = '4f66be54-8913-437b-8341-f43982900e51';
	
	/**
	 * howsUDemo constructor.
	 */
	private function __construct() {

		$this->urlBase = apply_filters(
			'e20r_textit_service_url_base',
			get_option( 'e20r_textit_url', 'https://api.textit.in/api/v2' )
		);
		
	}
	
	/**
	 * Transmit data to the TextIt Service
	 *
	 * @param null|array  $body
	 * @param string      $json_file
	 * @param string      $operation
	 * @param null|string $user_uuid
	 *
	 * @return array|bool|mixed|object
	 */
	public function send_to_textit_service( $body = null, $json_file = "contacts.json", $operation = 'POST', $user_uuid = null ) {
		
	    $util = Utilities::get_instance();
	    
		// BUG FIX: cURL warning during certain GET operations
		if ( 'GET' === strtoupper( $operation ) && 1 <= count( $body ) ) {
			$enc_body = ! empty( $body ) ? $body : array();
		} else {
			$enc_body = ! empty( $body ) ? json_encode( $body ) : array();
		}
		
		$request = array(
			'timeout'     => apply_filters( 'e20r_textit_service_request_timeout', 30 ),
			'httpversion' => '1.1',
			'sslverify'   => false,
			'headers'     => array(
				"Content-Type"  => "application/json",
				"Accept"        => "application/json",
				"Authorization" => "Token {$this->key}",
			),
			'body'        => $enc_body,
		);
		
		$url = "{$this->urlBase}/{$json_file}";
		
		switch ( strtolower( $operation ) ) {
			case 'get':
				$request['method'] = 'GET';
				break;
			
			case 'delete':
				$request['method'] = 'DELETE';
				break;
			
			case 'put':
				$request['method'] = 'PUT';
				break;
			
			default: // Default (most used) is POST operation
				$request['method'] = 'POST';
		}
		
		if ( $json_file == 'contacts.json' && strtolower( $operation ) != 'post' && isset( $body['urns'][0] ) ) {
			
			$urn = urlencode( $body['urns'][0] );
			$url = add_query_arg( 'urn', $urn, $url );
		}
		
		// Add the UUID for POST operations against the contacts.json API service
		if ( $json_file == 'contacts.json' && strtolower( $operation ) == 'post' && ! empty( $user_uuid ) ) {
			$url = add_query_arg( 'uuid', $user_uuid, $url );
		}
		
		$response    = wp_remote_request( $url, $request );
		$status_code = wp_remote_retrieve_response_code( $response );
  
		if ( $status_code >= 300 ) {
		 
			$msg = sprintf(
				__( "Unable to update TextIt service: %s", "e20r-textit-integration" ),
				wp_remote_retrieve_response_message( $response )
			);
			$util->log( $msg );
			$util->log( print_r( $response, true ) );
			return false;
		}
		
		$data = json_decode( wp_remote_retrieve_body( $response ) );
		
		if ( isset( $data->failed ) && ! empty( $data->failed ) ) {
			$msg = __( "Unable to update TextIt service", "e20r-textit-integration" );
			$util->log( $msg );
			return false;
		}
		
		$util->log( "Response from Service: " . print_r( $data, true ) );
		if ( $status_code <= 299 ) {
   
			if ( isset( $data ) ) {
				return $data;
			} else {
				return true;
			}
		}
		$util->log( "Unknown error when contacting TextIt service" );
		return false;
	}
	
	/**
	 * Creates or returns an instance of the howsUDemo class.
	 *
	 * @return  howsUDemo A single instance of this class.
	 */
	static public function get_instance() {
		
		if ( null === self::$instance ) {
			self::$instance = new self;
			
			self::$instance->loadHooks();
		}
		
		return self::$instance;
	}
	
	/**
	 * Process the incoming request from the How's U demo
	 *
	 * WARNING: This service is subject to potential abuse (no security features enabled to prevent misuse).
	 */
	public function howsu_demo_processor() {
		
		$util = Utilities::get_instance();
		
		$util->log( "Received from Demo page: " . print_r( $_REQUEST, true ));
		$phone = $util->get_variable( 'number' );
		$name = $util->get_variable( 'name' );
		$type = $util->get_variable( 'type' );
		$demo_user = null;
		$body = array(
			'name' => $name,
			'groups' => array( "Demo details" ),
			'urns' => array( "tel:{$phone}" ),
		);
		
		// Update contact data (for demo)
		$response = $this->send_to_textit_service( $body );
		
		$util->log( "Received from TextIT (for demo): " . print_r( $response, true ));
		
		if ( isset( $response->uuid ) ) {
			
			$demo_user = $response->uuid;
			
			if ( 'phone' === strtolower( $type ) ) {
				$util->log("Requested telephone message");
				$body = array(
					'flow' => $this->phone_flow_uuid,
					'contacts' => array( $response->uuid ),
					'urns' => array( "tel:{$phone}" )
				);
				
			} else {
				$util->log("Requested SMS message");
				$body = array(
					'flow' => $this->sms_flow_uuid,
					'contacts' => array( $response->uuid ),
					'urns' => array( "tel:{$phone}" )
				);
			}
			
			$started = $this->send_to_textit_service( $body, 'flow_starts.json', 'POST' );
			
			$util->log("Response to start of flow: " . print_r( $started, true ));
			
			if ( ! empty( $demo_user ) && ( defined( 'HOWSU_DEMO_DELETE_USER' ) && true === HOWSU_DEMO_DELETE_USER  ) ) {
				$deleted = $this->send_to_textit_service( $body, 'contacts.json', 'DELETE', $demo_user );
				$util->log("Response from delete operation: " . print_r( $deleted, true ));
			}
			
			wp_send_json_success();
		}
		
		wp_send_json_error();
	}
	
	/**
	 * Define required WordPress/Paid Memberships Pro/PDB hooks/filters being used
	 */
	public function loadHooks() {
		
		add_action( 'wp_ajax_nopriv_howsu_demo', array( $this, 'howsu_demo_processor' ) );
		add_action( 'wp_ajax_howsu_demo', array( $this, 'howsu_demo_processor' ) );
		// add_action( 'wp_enqueue_scripts', array( $this, 'load_javascript' ) );
	}
	
	/**
	 * Load Hype Generated JavaScript for the How's U demo page/pop-up
	 */
	public function load_javascript() {
		
		// wp_enqueue_script( 'howsu-hype-526-full', plugins_url('/js/howsu.hyperesources/HYPE-526.full.min.js', __FILE__ ), array( 'jquery' ), HOWSU_DEMO_VER, true );
		// wp_enqueue_script( 'howsu-hype-466-full', plugins_url('/js/howsu.hyperesources/HYPE-466.full.min.js', __FILE__ ), array( 'jquery' ), HOWSU_DEMO_VER, true );
		
		// wp_enqueue_script( 'howsu-hype-526-thin', plugins_url('/js/howsu.hyperesources/HYPE-526.thin.min.js', __FILE__ ), array( 'jquery' ), HOWSU_DEMO_VER, true );
		// wp_enqueue_script( 'howsu-hype-466-thin', plugins_url('/js/howsu.hyperesources/HYPE-466.thin.min.js', __FILE__ ), array( 'jquery' ), HOWSU_DEMO_VER, true );
	    // wp_register_script( 'howsu-demo', plugins_url('/js/howsu.hyperesources/howsuDemo_hype_generated_script.js', __FILE__ ), array( 'jquery', 'howsu-hype-526-full', 'howsu-hype-526-thin' ), HOWSU_DEMO_VER, true );
	    
	    // wp_localize_script( 'howsu-demo', 'howsu_demo', array( 'action_url' => admin_url( 'admin_ajax.php' ) ) );
	    // wp_enqueue_script( 'howsu_demo');
	}
	
	/**
	 * Autoloader class for the plugin.
	 *
	 * @param string $class_name Name of the class being attempted loaded.
	 */
	public static function __class_loader( $class_name ) {
		
		if ( false === stripos( $class_name, 'howsu')) {
			return;
		}
		
		$parts = explode( '\\', $class_name );
		$name  = strtolower( preg_replace( '/_/', '-', $parts[ ( count( $parts ) - 1 ) ] ) );
		
		$filename     = dirname( __FILE__ ) . "/classes/class.{$name}.php";
		$utils_file   = dirname( __FILE__ ) . "/utilities/class.{$name}.php";
		
		if ( file_exists( $filename ) ) {
			require_once $filename;
		}
		
		if ( file_exists( $utils_file ) ) {
			require_once $utils_file;
		}
   
	} // End of autoloader method
}

spl_autoload_register( 'Howsu_Demo\howsUDemo::__class_loader' );

add_action( 'plugins_loaded', 'Howsu_Demo\howsUDemo::get_instance' );

if ( ! class_exists( '\\PucFactory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'plugin-updates/plugin-update-checker.php' );
}

$plugin_updates = \PucFactory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/howsu-demo/metadata.json',
	__FILE__,
	'howsu-demo'
);
