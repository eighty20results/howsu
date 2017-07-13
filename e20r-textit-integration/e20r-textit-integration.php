<?php
/*
Plugin Name: E20R HowsU/Text-It Messaging Service integration
Plugin URI: http://eighty20results.com/wordpress-plugins/e20r-textit-integration/
Description: howsu.today website integration for the textit.in SMS/Voice messaging service
Version: 2.0.4
Requires: 4.7
Tested: 4.7.5
Author: Thomas Sjolshagen <thomas@eighty20results.com>
Author URI: http://www.eighty20results.com/thomas-sjolshagen/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: e20r-textit-integration
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
defined( 'ABSPATH' ) or die( 'Cannot access plugin sources directly' );

if ( ! defined( 'HOWSU_PLUGIN_URL' ) ) {
	define( 'HOWSU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'E20RTEXTIT_VER' ) ) {
	define( 'E20RTEXTIT_VER', '2.0.4' );
}

class e20rTextitIntegration {
	
	/**
	 * @var     e20rTextitIntegration $instance - Class instance
	 */
	private static $instance;
	
	/**
	 * @var     e20rUtils $util -  Various utility functions
	 */
	private $util;
	
	/**
	 * @var     stdClass $userRecord
	 */
	private $userRecord = null;
	
	/**
	 * @var string $table
	 */
	private $table = null;
	
	private $urlBase = '';
	
	private $HTTPCommand = '';
	
	private $key = array();
	
	private $everliveAppId = null;
	
	private $everliveUrl = null;
	
	private $pdb_page_list = array();
	
	private $flow_settings = array();
	
	private $settings;
	
	private $settings_name = 'e20r_textit_options';
	
	/**
	 * e20rTextitIntegration constructor.
	 */
	private function __construct() {
		
		// TODO: Use options page for TextIt integration to get/save key
		$this->key = $this->loadSettings( 'textit_key' );
		
		// TODO: Use options page for TextIt service URL
		$this->urlBase = apply_filters(
			'e20r_textit_service_url_base',
			get_option( 'e20r_textit_url', 'https://api.textit.in/api/v2' )
		);
		
		$this->everliveAppId = get_option( 'textit_everlive', "eButuc0AhfMCXdz5" );
		$this->everliveUrl   = "http://api.everlive.com/v1/{$this->everliveAppId}/";
		
		if ( empty( $this->pdb_page_list ) ) {
			$this->pdb_page_list = array(
				'upgrade-service-detail',
				'service-details',
				'contact-details',
				'customer-details',
				'customer-detail',
				'edit-service',
				'edit-contact-1-2',
				'customer-detail-2',
				'edit-third-contact',
				'edit-forth-contact',
				'view-my-details',
				'edit-my-details',
				'order-confirmation',
				'third-contact',
				'view-first-contact',
				'view-status-detail',
			);
		}
		
		$this->pdb_page_list = apply_filters( 'textit_pdb_page_list', $this->pdb_page_list );
	}
	
	/**
	 * Debug page for data from TextIt service
	 *
	 * @return string
	 */
	public function listFlows() {
		
		$flows  = $this->getFlows();
		$groups = $this->getGroups();
		
		$deleted = $this->updateTextItService( array( 'urns' => array( 'tel:+16037859780' ) ), 'contacts.json', 'DELETE' );
		$contact = $this->updateTextItService( null, 'contacts.json', 'GET' );
		
		$active = array();
		$grps   = array();
		
		foreach ( $flows as $flow ) {
			
			$data             = new stdClass();
			$data->uuid       = $flow->uuid;
			$data->name       = $flow->name;
			$data->created_on = $flow->created_on;
			
			$active[] = $data;
		}
		
		foreach ( $groups as $group ) {
			
			$grps[] = $group;
			
		}
		
		$string = '';
		// $string .= '<pre> Flows: ' . print_r( $active, true ) . '</pre>';
		// $string .= '<pre> Groups: ' . print_r( $grps, true ) . '</pre>';
		$string .= '<pre> Contacts: ' . print_r( $contact, true ) . '</pre>';
		
		return $string;
	}
	
	/**
	 * Checkout handler for TextIt (register the user with the service)
	 *
	 * @param int         $user_id
	 * @param MemberOrder $order
	 *
	 * @return bool
	 */
	public function checkoutComplete( $user_id, $order ) {
		
		$gateways_with_pending_status = apply_filters( 'pmpro_gateways_with_pending_status', array(
			'paypalstandard',
			'twocheckout',
			'gourl',
		) );
		
		unset( $_SESSION['pmpro_level_id'] );
		
		$howsu_level_map = apply_filters( 'e20r_textit_howsu_level_map', $this->defaultLevelMap( array() ) );
		
		// Wait for the membership level to be OK.
		if ( in_array( pmpro_getGateway(), $gateways_with_pending_status ) ) {
			
			$msg = __( "Account not ready. We're waiting for membership payment status from gateway.", "e20r-textit-integration" );
			$this->util->set_notice( $msg, 'notice' );
			$this->util->log( $msg );
			wp_redirect( pmpro_url( 'levels' ) );
		}
		
		$user                = get_user_by( 'ID', $user_id );
		$current_level_descr = isset( $howsu_level_map[ $order->membership_id ] ) ? $howsu_level_map[ $order->membership_id ] : $order->membership_id;
		
		$this->util->log( "Signing {$user_id} up for: {$current_level_descr}" );
		
		if ( ! in_array( $current_level_descr, apply_filters( 'e20r_textit_membership_levels', array(
			'standard',
			'premium',
			'premiumplus',
		) ) )
		) {
			
			if ( false === $this->registerWithTextIt( $user_id ) ) {
				
				$msg = __( "Unable to register the user with the TextIt Service. Please contact the webmaster!", "e20r-textit-integration" );
				$this->util->set_notice( $msg, 'error' );
				$this->util->log( $msg );
				
				return false;
			}
		}
		
		if ( false === $this->updateUserRecord( $user_id, array( 'onetimefee' => true ), array( 'user_id' => $user->user_email ) ) ) {
			
			$msg = __( "Please contact the webmaster. There was a problem recording your one-time setup fee payment", "e20r-textit-integration" );
			$this->util->set_notice( $msg, 'warning' );
			$this->util->log( $msg );
			
			return false;
		}
	}
	
	/**
	 * Validate whether the specified URN is active and
	 *
	 * @param      $user_info
	 * @param null $group_list
	 *
	 * @return bool
	 */
	public function isUserRegistered( $user_info, $group_list = null ) {
		
		if ( empty( $group_list ) ) {
			$role_name  = $this->_getRoleName( $user_info->service_level );
			$group_list = $this->_setGroupInfo( $role_name );
		}
		
		$body = array(
			// 'groups' =>  ! empty( $group_list ) ? $group_list : array(),
			'urns' => array( "tel:{$user_info->service_number}" ),
		);
		
		$status = $this->updateTextItService( $body, 'contacts.json', 'GET' );
		
		$this->util->log( "Returned status for {$user_info->service_number}: " . print_r( $status, true ) );
		
		return ( isset( $status->results ) && ! empty( $status->results ) );
	}
	
	/**
	 * Register the specified user ID to the TextIt Service API
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function registerWithTextIt( $user_id ) {
		
		$groups = 0;
		
		$user      = new WP_User( $user_id );
		$user_info = $this->getUserRecord( $user_id, true );
		
		if ( empty( $user_info ) ) {
			
			$msg = sprintf( __( "Unable to locate user data for %s", "e20r-textit-integration" ), $user->display_name );
			
			$this->util->set_notice( $msg, 'error' );
			$this->util->log( $msg );
			
			return false;
		}
		
		$this->util->log( "User record for {$user_id} is: " . print_r( $user_info, true ) );
		
		// Generate the correct role name & set it for the specified user.
		$role_name = $this->_getRoleName( $user_info->service_level );
		$user->set_role( $role_name );
		
		$this->util->log( "Configured {$role_name} role for user {$user->user_email}" );
		
		// get the service configuration
		$flow_config = $this->_getFlowConfig( $user_info->service_type );
		
		// Save the membership number (service number) and other metadata for the user.
		update_user_meta( $user_id, 'member_number', $user_info->service_number );
		update_user_meta( $user_id, 'first_name', $user_info->first_name );
		update_user_meta( $user_id, 'last_name', $user_info->last_name );
		update_user_meta( $user_id, 'country', $user_info->country );
		
		$group_list = $this->_setGroupInfo( $role_name );
		
		$this->util->log( "TextIt Group info: " . print_r( $group_list, true ) );
		
		if ( empty( $user_info->service_number ) ) {
			$msg = sprintf( __( "Cannot start TextIt Service for %s: Err-InvalidServiceNumber", "e20r-textit-integration" ), $user->display_name );
			$this->util->set_notice( $msg, 'error' );
			$this->util->log( $msg );
		}
		
		$textit_record = $this->configureTextItRecord( $user_info, $group_list, $flow_config );
		
		$this->util->log( "Loading user info to TextIt Service: " . print_r( $textit_record['fields'], true ) );
		
		$is_registered = $this->isUserRegistered( $user_info, $group_list );
		
		$this->util->log( "User is registered? " . ( isset( $is_registered->results ) && ! empty( $is_registered->results ) ? 'Yes' : 'No' ) );
		
		// Add new user record to the TextIt Service
		if ( ! empty( $is_registered ) || ( empty( $is_registered ) && ( false !== ( $data = $this->updateTextItService( $textit_record ) ) ) ) ) {
			
			$this->util->log( "Added user info during checkout for {$user_id}" );
			
			$u_record = array( 'textitid' => $data->uuid, 'status' => 1, 'onetimefee' => 1 );
			$where    = array( 'id' => $user_info->id );
			
			// Save the TextIt User UUID (contact record UUID)
			update_user_meta( $user_id, 'e20r_textit_contact_uuid', $data->uuid );
			
			// Update the status for the user record
			if ( false === ( $user_info = $this->updateUserRecord( $user_id, $u_record, $where ) ) ) {
				
				$msg = __( "Unable to update local DB record after subscribing to TextIt service", "e20r-textit-integration" );
				$this->util->set_notice( $msg, 'error' );
				$this->util->log( $msg );
				
				return false;
			}
			
			$urn_info = array( "tel: {$user_info->service_number}" );
			
			$this->util->log( "Updated user record with successful TextIt record load. Now sending welcome message to {$user_info->textitid}" );
			
			if ( false !== ( $response = $this->sendMessage( 'welcomemessage', $urn_info ) ) ) {
				
				$this->util->log( "Welcome message sent" );
				
				$flow_config = $this->_getFlowConfig( 'welcomemessage' );
				
				$msg = array(
					"action"   => 'remove',
					"group"    => $flow_config['group_uuid'],
					"contacts" => ! empty( $user_info->textitid ) ? array( $user_info->textitid ) : array(),
				);
				
				$response = $this->updateTextItService( $msg, 'contact_actions.json' );
				$this->util->log( "Response from final attempt to update TextIt: " . print_r( $response, true ) );
			}
		} else {
			
			$msg = __( "Unable to subscribe you to the TextIt service", "e20r-textit-integration" );
			$this->util->set_notice( $msg, "error" );
			
			// Send email to the admin when something goes wrong.
			$admin_email = apply_filters( 'e20r_textit_admin_email_addr', array( get_option( 'admin_email' ) ) );
			$recipients  = implode( ',', $admin_email );
			$message     = "Error subscribing user {$user_info->first_name} {$user_info->last_name} with record ID {$user_info->id} to the {$user_info->service_type} TextIt service";
			$subject     = "HowsU: New TextIt Subscription failure";
			
			$this->util->log( $message );
			
			// Send the admin an email notice
			wp_mail( $recipients, $subject, $message );
			
			// Set the group for the user to request assistance
			$groups = $this->_getGroupUUIDsFromName( array( 'UserAssistance' ) );
			
			$urn_type = strtolower( $user_info->type );
			$urn_info = array( "tel: {$user_info->service_number}" );
			
			// Retry sending the welcome message?
			$msg = array(
				'name'   => "{$user_info->first_name} {$user_info->last_name}",
				'groups' => ! empty( $groups ) ? $groups : array(),
				'urns'   => $urn_info,
			);
			
			$data = $this->updateTextItService( $msg );
			
			if ( false === $data ) {
				$msg = __( "Failed to send alert message", "e20r-textit-integration" );
				$this->util->log( $msg );
				$this->util->set_notice( $msg, 'error' );
				
				return false;
			}
			
			if ( false === $this->updateUserRecord( $user_id, array(
					'textitid'   => $data->uuid,
					'status'     => 1,
					'onetimefee' => 1,
				), array( 'id' => $user_info->id ) )
			) {
				$msg = __( "Failed to update the user's database record after welcome message retry", "e20r-textit-integration" );
				$this->util->log( $msg );
				$this->util->set_notice( $msg, 'error' );
    
				return false;
			}
			
			if ( false === $this->sendMessage( 'welcomemessage', $urn_info ) ) {
				$msg = __( "Failed at second attempt to send welcome message", "e20r-textit-integration" );
				$this->util->set_notice( $msg, 'error' );
				$this->util->log( $msg );
				
				return false;
			}
		}
	}
	
	/**
	 * Using the HowsU User Data record, configure the Contact info for the TextIt Service
	 *
	 * @param array $user_info
	 * @param array $group_list
	 * @param array $flow_config
	 *
	 * @return array
	 */
	private function configureTextItRecord( $user_info, $group_list, $flow_config ) {
		
		$field_config = $this->loadSettings( 'field_map' );
		
		$textit_record = array(
			'name'   => "{$user_info->first_name} {$user_info->last_name}",
			'groups' => ! empty( $group_list ) ? $group_list : array(),
			'urns'   => array( "tel:{$user_info->service_number}" ),
			'fields' => array(),
		);
		
		foreach ( $field_config as $tField => $dbField ) {
			
			/** Safely load the flowtype info and configure it for all registered users when processing them */
			if ( $field_config[ $tField ] == 'default' ) {
				
				$this->util->log( "Field {$tField} needs to use default value" );
				
				if ( 'flowtype' === $tField ) {
					$this->util->log( "Configuring for default flow type/Service type!" );
					$services                           = $this->availableServices();
					$type_key                           = $this->_process_text( $user_info->service_type );
					$textit_record['fields'][ $tField ] = $services[ $type_key ]['type'];
				} else {
					$textit_record['fields'][ $tField ] = $this->defaultFieldValue( $tField );
				}
			} else {
				
				if ( isset( $user_info->{$dbField} ) ) {
					$textit_record['fields'][ $tField ] = $user_info->{$dbField};
				}
			}
		}
		
		return $textit_record;
	}
	
	/**
	 * Transmit a TextIt Message to the contact(s)
	 *
	 * @param $flow_type
	 * @param $who
	 *
	 * @return bool
	 */
	public function sendMessage( $flow_type, $who ) {
		
		$flow_settings = $this->_getFlowConfig( $flow_type );
		// $who           = ! is_array( $who ) ? array( "tel: {$who}" ) : $who;
		
		$msg = array(
			'flow' => $flow_settings['flow_id'],
			'urns' => $who,
		);
		
		$data = $this->updateTextItService( $msg, 'flow_starts.json', 'POST' );
		
		if ( $data === false ) {
			
			$msg = __( "Unable to start the {$flow_type} flow for the new user!", "e20r-textit-integration" );
			$this->util->set_notice( $msg, 'error' );
			$this->util->log( $msg );
			
			return false;
			
		} else {
			return $data;
		}
	}
	
	/**
	 * Stop the TextIt Service for the specific user ID
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function pauseTextItService( $user_id ) {
		
		$user_info  = $this->getUserRecord( $user_id );
		$group_name = $this->_getRoleName( $user_info->service_level );
		$group_list = $this->_setGroupInfo( $group_name );
		
		$data = array(
			'contacts' => array( "tel:{$user_info->service_number}" ),
//			'group'  => $group_list,
			'action'   => 'block',
		);
		
		return $this->updateTextItService( $data, 'contact_actions.json', 'POST' );
	}
	
	/**
	 * Restart/activate the TextIt Service for the specific user ID
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function resumeTextItService( $user_id ) {
		
		$user_info = $this->getUserRecord( $user_id );
		$status    = false;
		
		if ( ! empty( $user_info ) ) {
			
			$group_name    = $this->_getRoleName( $user_info->service_level );
			$group_list    = $this->_setGroupInfo( $group_name );
			$flow_config   = $this->_getFlowConfig( $user_info->service_type );
			$textit_record = $this->configureTextItRecord( $user_info, $group_list, $flow_config );
			$user_uuid     = get_user_meta( $user_id, 'e20r_textit_contact_uuid', true );
			
			$this->util->log( "Including user info for TextIt Service: " . print_r( $textit_record['fields'], true ) );
			
			$data = array(
				'urns' => array( "tel:{$user_info->service_number}" ),
			);
			
			$contact_status = $this->updateTextItService( $data, 'contacts.json', 'GET', $user_uuid );
			
			if ( ! empty( $contact_status ) ) {
				$this->util->log( "Returned status from check: " . print_r( $contact_status->results[0]->blocked, true ) );
				
				if ( true === $contact_status->results[0]->blocked || true === $contact_status->results[0]->stopped ) {
					$this->util->log( "User {$user_id} is blocked/stopped on TextIt Service. Need to unblock before resuming!" );
					
					$data = array(
						'contacts' => array( "tel:{$user_info->service_number}" ),
						'action'   => 'unblock',
					);
					
					$status = $this->updateTextItService( $data, 'contact_actions.json', 'POST' );
				}
			}
			
			$data = array(
				'urns'   => array( "tel:{$user_info->service_number}" ),
				'groups' => $group_list,
				'fields' => $textit_record['fields'],
			);
			
			$status = $this->updateTextItService( $data, 'contacts.json', 'POST', $user_uuid );
			
			if ( false !== ( $response = $this->sendMessage( 'welcomemessage', array( "tel:{$user_info->service_number}" ) ) ) ) {
				
				$this->util->log( "Welcome message sent" );
				
				$flow_config = $this->_getFlowConfig( 'welcomemessage' );
				
				$msg = array(
					"action"   => 'remove',
					"group"    => $flow_config['group_uuid'],
					"contacts" => ! empty( $user_info->textitid ) ? array( $user_info->textitid ) : array(),
				);
				
				$response = $this->updateTextItService( $msg, 'contact_actions.json' );
				$this->util->log( "Response from final attempt to update TextIt: " . print_r( $response, true ) );
			}
		}
		
		return $status;
	}
	
	public function ajaxPauseService() {
		
		// TODO: Add NONCE handling
		$service_number = $this->util->_get_variable( 'snu', null );
		wp_verify_nonce( 'e20r-pdb-nonce', 'e20r_pdb_update' );
	}
	
	public function ajaxResumeService() {
		
		// TODO: Add NONCE handling
		wp_verify_nonce( 'e20r-pdb-nonce', 'e20r_pdb_update' );
	}
	
	/**
	 * Update the PDB database for a user via AJAX call(s).
	 */
	public function ajaxUpdateDatabase() {
		
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'User not logged in' );
		}
		
		wp_verify_nonce( 'e20r-pdb-nonce', 'e20r_pdb_update' );
		
		if ( WP_DEBUG ) {
			error_log( "Preparing to update database via AJAX call" );
		}
		
		global $current_user;
		
		$column         = $this->util->_get_variable( 'col', null );
		$value          = $this->util->_get_variable( 'val', null );
		$record_id      = $this->util->_get_variable( 'pdb', null );
		$service_number = $this->util->_get_variable( 'sn', null );
		$nonce          = $this->util->_get_variable( 'nonce', null );
		
		if ( ! empty( $column ) && ! empty( $record_id ) && ! empty( $service_number ) ) {
			
			if ( false === ( $record = $this->updateUserRecord( $current_user->ID, array( $column => $value ), array( 'id' => $record_id ) ) ) ) {
				
				wp_send_json_error( "Error updating record # {$record_id} for {$current_user->display_name}" );
				
			} else {
				
				$msg = array(
					'urns' => array( "tel:{$service_number}" ),
				);
				
				$timewindow_vars = apply_filters( 'e20r_textit_timewindow_variable_names', array(
					'time_window',
					'time_window_2',
					'time_window_3',
				) );
				
				if ( in_array( $column, $timewindow_vars ) ) {
					
					$groups = $this->_setGroupInfo( $record->service_type );
					$time   = substr( $value, 0, 2 );
					
					switch ( $column ) {
						case 'time_window':
							$groups[0] = "{$record->service_level}{$time}";
							break;
						case 'time_window_2':
							$groups[1] = "{$record->service_level}{$time}";
							break;
						
						default:
							$groups[2] = "{$record->service_level}{$time}";
					}
					
					$msg['groups'] = $this->_getGroupUUIDsFromName( $groups );
				} else {
					
					$column        = $this->_mapTextItColumns( $column );
					$msg['fields'] = array( $column => $value );
				}
				
				if ( false === $this->updateTextItService( $msg ) ) {
					
					wp_send_json_error( "Error updating TextIt service for {$current_user->display_name}" );
				}
			}
		} else {
			wp_send_json_error( "Error: Missing information from update request" );
		}
		
		wp_send_json_success();
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
	public function updateTextItService( $body = null, $json_file = "contacts.json", $operation = 'POST', $user_uuid = null ) {
		
		$request = array(
			'timeout'     => apply_filters( 'e20r_textit_service_request_timeout', 30 ),
			'httpversion' => '1.1',
			'sslverify'   => false,
			'headers'     => array(
				"Content-Type"  => "application/json",
				"Accept"        => "application/json",
				"Authorization" => "Token {$this->key}",
			),
			'body'        => ! empty( $body ) ? json_encode( $body ) : array(),
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
		
		$this->util->log( "Body content: " . print_r( $body, true ) );
		
		if ( $json_file == 'contacts.json' && strtolower( $operation ) != 'post' && isset( $body['urns'][0] ) ) {
			
			$urn = urlencode( $body['urns'][0] );
			$this->util->log( "URN Info: {$urn} vs " . print_r( $body, true ) );
			$url = add_query_arg( 'urn', $urn, $url );
		}
		
		$this->util->log( "User data: UUID => {$user_uuid} -> " . print_r( $body, true ) );
		
		// Add the UUID for POST operations against the contacts.json API service
		if ( $json_file == 'contacts.json' && strtolower( $operation ) == 'post' && ! empty( $user_uuid ) ) {
			$this->util->log( "Adding User's UUID to URL " );
			$url = add_query_arg( 'uuid', $user_uuid, $url );
		}
		
		$this->util->log( "Sending {$request['method']} to {$url}: " . print_r( $request, true ) );
		
		$response    = wp_remote_request( $url, $request );
		$status_code = wp_remote_retrieve_response_code( $response );
		
		$this->util->log( "Status Code: {$status_code}" );
		
		if ( $status_code >= 400 ) {
			$this->util->log( "Raw Response from TextIt API Servers: " . print_r( $response, true ) );
		}
		
		if ( $status_code >= 300 ) {
			
			$msg = sprintf(
				__( "Unable to update TextIt service: %s", "e20r-textit-integration" ),
				wp_remote_retrieve_response_message( $response )
			);
			
			$this->util->set_notice( $msg, 'warning' );
			$this->util->log( $msg );
			
			return false;
		}
		
		$data = json_decode( wp_remote_retrieve_body( $response ) );
		
		if ( isset( $data->failed ) && ! empty( $data->failed ) ) {
			$msg = __( "Unable to update TextIt service", "e20r-textit-integration" );
			$this->util->set_notice( $msg, 'error' );
			$this->util->log( $msg );
			
			return false;
		}
		
		// $this->util->log( "Response from Service: " . print_r( $data, true ) );
		if ( $status_code <= 299 ) {
			
			$this->util->log( "Returned data for {$json_file} {$operation} operation: " . print_r( $data, true ) );
			
			if ( isset( $data ) ) {
				return $data;
			} else {
				return true;
			}
		}
		
		$this->util->set_notice( __( "Unknown error: Please report this to the website administrator", "e20r-textit-integration" ) );
		
		return false;
	}
	
	/**
	 * Load User Contact Fields from TextIt Service
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	private function getTextItFields( $force = false ) {
		
		$fields = get_option( 'e20r_textit_fields', false );
		
		if ( $force === true || empty( $fields ) ) {
			
			$data   = $this->updateTextItService( null, 'fields.json', 'GET' );
			$fields = isset( $data->results ) ? $data->results : array();
			
			if ( ! empty( $fields ) ) {
				update_option( 'e20r_textit_fields', $fields, false );
			}
		}
		
		return $fields;
	}
	
	/**
	 * Load User Database Fields for HowsU DB record
	 *
	 * @return array
	 */
	private function getDBFields() {
		
		global $wpdb;
		$tableName = $this->loadSettings( 'user_database' );
		$fields    = array();
		
		if ( ! empty( $tableName ) ) {
			$sql = "DESCRIBE {$wpdb->prefix}{$tableName}";
			
			$tableInfo = $wpdb->get_results( $sql );
			
			if ( ! empty( $tableInfo ) ) {
				foreach ( $tableInfo as $row ) {
					$fields[ $row->Field ] = $row->Type;
				}
			}
		}
		
		ksort( $fields );
		
		return $fields;
	}
	
	/**
	 * Get all TextIt groups for the specific group/role the (new?) user has been assigned/chosen
	 *
	 * @param string $group_name
	 * @param null   $var
	 * @param null   $value
	 *
	 * @return array
	 */
	private function _setGroupInfo( $group_name, $var = null, $value = null ) {
		
		$var_name = apply_filters( 'e20r_textit_timewindow_variable_names', array(
				'time_window',
				'time_window_2',
				'time_window_3',
			)
		);
		
		switch ( $group_name ) {
			case 'protector':
				$entries = 1;
				break;
			
			case 'guardian':
				$entries = 2;
				break;
			
			case 'guardianangel':
			case 'weekend':
			case 'weekendspecial':
				
				$entries = 3;
				break;
			
			default:
				$entries = 0;
		}
		
		$groups = array();
		
		for ( $i = 0; $i < $entries; $i ++ ) {
			
			if ( $var == $var_name[ $i ] ) {
				$time = $value;
			} else {
				$time = substr( $this->userRecord->{$var_name[ $i ]}, 0, 2 );
			}
			
			if ( false !== stripos( $group_name, 'weekend' ) ) {
				$group_name = 'weekend';
			}
			
			$groups[] = "{$group_name}{$time}";
		}
		
		$groups = $this->_getGroupUUIDsFromName( $groups );
		
		return $groups;
	}
	
	/**
	 * Select the UUID value(s) for the specified TextIt group(s)
	 *
	 * @param array $groups
	 *
	 * @return array
	 */
	private function _getGroupUUIDsFromName( $groups = array() ) {
		
		$cached_groups = $this->getGroups();
		$group_uuids   = array();
		
		$this->util->log( "Convert Group names to UUIDs for v2 of the TextIt API to use" );
		
		foreach ( $groups as $name ) {
			$group_uuids[] = $cached_groups[ $name ]->uuid;
		}
		
		return $group_uuids;
	}
	
	/**
	 * Return the Flow Configuration for the service type
	 *
	 * @param $type
	 *
	 * @return mixed
	 */
	private function _getFlowConfig( $type ) {
		
		$this->flow_settings = apply_filters( 'e20r_textit_flow_settings_array', $this->loadSettings( 'service_mappings' ) );
		
		$flow_type = $this->_process_text( $type );
		
		return $this->flow_settings[ $flow_type ];
	}
	
	/**
	 * Validating the returned values from the Settings API page on save/submit
	 *
	 * @param array $input Changed values from the settings page
	 *
	 * @return array Validated array
	 *
	 * @since  1.0
	 * @access public
	 */
	public function validateSettings( $input ) {
		
		if ( isset( $input['service_mappings'] ) ) {
			return $input;
		}
		
		
		// Base our settings off of the defaults
		$this->settings = $this->defaultSettings();
		
		// Update the database table to use for the service
		if ( isset( $input['user_database'] ) && ! empty( $input['user_database'] ) ) {
			$this->settings['user_database'] = $input['user_database'];
		}
		
		// Update the TextIt API Key setting
		if ( isset( $input['textit_key'] ) && ! empty( $input['textit_key'] ) ) {
			$this->settings['textit_key'] = $input['textit_key'];
		}
		
		// Process all defined services & set their flow & group IDs (if applicable)
		foreach ( $input['servicekey'] as $key => $name ) {
			
			$this->settings['service_mappings'][ $name ]['flow_id']    = ( empty( $input['flow_id'][ $key ] ) ? '' : $input['flow_id'][ $key ] );
			$this->settings['service_mappings'][ $name ]['group_uuid'] = ( empty( $input['group_uuid'][ $key ] ) ? '' : $input['group_uuid'][ $key ] );
		}
		
		if ( WP_DEBUG ) {
			
			$this->util->log( "Input from Settings API: " . print_r( $input, true ) );
			
			// return $this->settings;
		}
		
		if ( ! is_array( $this->settings['field_map'] ) ) {
			$this->settings['field_map'] = array();
		}
		
		foreach ( $input['field_map'] as $key => $db_field_name ) {
			
			if ( $db_field_name != '-1' ) {
				$this->settings['field_map'][ $key ] = $db_field_name;
			} else if ( $db_field_name == '-1' && isset( $this->settings['field_map'][ $key ] ) ) {
				unset( $this->settings['field_map'][ $key ] );
			}
		}
		
		// Validated & updated settings
		return $this->settings;
	}
	
	/**
	 * Configure the default settings for this plugin
	 *
	 * @return array
	 */
	private function defaultSettings() {
		
		$services = apply_filters( 'e20r_textit_available_service_options', array() );
		
		$default_service_mappings = array();
		
		foreach ( $services as $service => $config ) {
			$default_service_mappings[ $service ] = array(
				'flow_id'    => '',
				'group_uuid' => '',
				'type'       => $config['type'],
				'label'      => $config['label'],
			);
			
			// From preexisting configuration
			switch ( $config['type'] ) {
				case 'TEL':
					$default_service_mappings[ $service ]['group_uuid'] = '607571ed-125c-432c-a2dc-ebf93539357a';
					$default_service_mappings[ $service ]['flow_id']    = '81b71a38-aec2-4f71-adb1-cfecd3b4d5ba';
					break;
				case 'SMS':
					$default_service_mappings[ $service ]['group_uuid'] = '607571ed-125c-432c-a2dc-ebf93539357a';
					$default_service_mappings[ $service ]['flow_id']    = '';
					break;
				case 'FBM':
					$default_service_mappings[ $service ]['group_uuid'] = '607571ed-125c-432c-a2dc-ebf93539357a';
					$default_service_mappings[ $service ]['flow_id']    = '';
					break;
				default:
					if ( $service === 'welcomemessage' ) {
					    //63c42285-f938-4528-9274-40419028d5db
						$default_service_mappings[ $service ]['group_uuid'] = '607571ed-125c-432c-a2dc-ebf93539357a';
						$default_service_mappings[ $service ]['flow_id']    = '63c42285-f938-4528-9274-40419028d5db';
					}
			}
		}
		
		$settings = array(
			'user_database'    => 'participants_database',
			'textit_key'       => '7964392e969ce3aa258906f8e864380c2d058841',
			'field_map'        => $this->defaultFieldMap(),
			'service_mappings' => $default_service_mappings,
		);
		
		return $settings;
	}
	
	/**
	 * Adds the HowsU Settings (in the WordPress backend)
	 */
	public function loadAdminSettingsPage() {
		add_options_page(
			__( "HowsU Settings", "e20r-textit-integration" ),
			__( "HowsU Settings", "e20r-textit-integration" ),
			'manage_options',
			'e20r-textit',
			array( $this, 'howsuSettingsPage' )
		);
	}
	
	/**
	 * Generates the Settings API compliant option page
	 */
	public function howsuSettingsPage() {
		?>
        <div class="e20r-textit-settings">
            <div class="wrap">
                <h2 class="e20r-textit-settings"><?php _e( 'HowsU Settings', "e20r-textit-integration" ); ?></h2>
                <p class="e20r-textit-settings">
					<?php _e( "Configure TextIt services for HowsU", "e20r-textit-integration" ); ?>
                </p>
                <p class="e20r-textit-refresh">
                    <button class="e20r-textit-fetch button-primary"><?php _e( 'Refresh Groups/Flows from TextIt Server' ); ?></button>
                </p>
                <form method="post" action="options.php">
					<?php settings_fields( 'e20r_textit_options' ); ?>
					<?php do_settings_sections( 'e20r-textit' ); ?>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>"/>
                    </p>
                </form>

            </div>
        </div>
		<?php
	}
	
	/**
	 * Register Settings fields
	 */
	public function registerSettingsPage() {
		
		register_setting( "e20r_textit_options", $this->settings_name, array( $this, 'validateSettings' ) );
		
		add_settings_section(
			'e20r_textit_db',
			__( "Database", "e20r-textit-integration" ),
			array( $this, 'renderDBSelection' ),
			'e20r-textit'
		);
		
		add_settings_field(
			'e20r_textit_flows',
			__( "User data source", "e20r-textit-integration" ),
			array( $this, 'renderDBSelect' ),
			'e20r-textit',
			'e20r_textit_db',
			array( 'option_name' => 'user_database' )
		);
		
		add_settings_section(
			'e20r_textit_flowmap',
			__( "Service Settings", 'e20r-textit-integration' ),
			array( $this, 'renderFlowMapSection' ),
			'e20r-textit'
		);
		
		add_settings_field(
			'e20r_textit_key',
			__( "TextIt API", "e20r-textit-integration" ),
			array( $this, 'renderKeyField' ),
			'e20r-textit',
			'e20r_textit_flowmap',
			array( 'option_name' => 'textit_key' )
		);
		
		add_settings_field(
			'e20r_textit_flows',
			__( "Configuration", "e20r-textit-integration" ),
			array( $this, 'renderFlowMaps' ),
			'e20r-textit',
			'e20r_textit_flowmap',
			array( 'option_name' => 'service_mappings' )
		);
		
		add_settings_section(
			'e20r_textit_dbmap',
			__( "TextIt Fields to User Data Field Map", 'e20r-textit-integration' ),
			array( $this, 'renderDataMapSection' ),
			'e20r-textit'
		);
		
		add_settings_field(
			'e20r_textit_dbfields',
			__( "Field Mapping", "e20r-textit-integration" ),
			array( $this, 'renderDataFieldMap' ),
			'e20r-textit',
			'e20r_textit_dbmap',
			array( 'option_name' => 'field_map' )
		);
		
	}
	
	/**
	 * Descriptive text for the TextIt/HowsU User Record map
	 */
	public function renderDataMapSection() {
		?>
        <p class="e20r-textit-settings-text">
			<?php // _e( "Map TextIt User fields to local DB user fields", "e20r-textit-integration" ); ?>
        </p>
		<?php
	}
	
	/**
	 * Generate the settings fields for TextIt Flow and Group for each HowsU Service
	 *
	 * @param array $settings
	 */
	public function renderDataFieldMap( $settings ) {
		
		
		$TextItFields = $this->getTextItFields();
		$dbFields     = $this->getDBFields();
		
		// $this->util->log( "TextIt Data: " . print_r( $TextItFields, true ) );
		// $this->util->log( "DB Info: " . print_r( $dbFields, true ) );
		
		?>
        <div class="e20r-textit-service">
            <div class="e20r-textit-service-row">
                <label for="e20r_textit_db_maps-field_map"><?php _e( "HowsU Record to TextIt Contact Field Maps", "e20r-textit-integration" ) ?></label>
                <table class="e20r_textit_db_maps">
                    <thead>
                    <tr class="e20r_textit_db_map_header">
                        <th class="e20r_textit_db_map_field">
							<?php _e( "Local User Data Field", "e20r-textit-integration" ); ?>
                        </th>
                        <th class="e20r_textit_db_map_field">
							<?php _e( "TextIt Service Contact Field", "e20r-textit-integration" ); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					foreach ( $TextItFields as $field ) {
						?>
                        <tr class="e20r_textit_db_map_row">
                            <td class="e20r_textit_db_map_field">
                                <label for="e20r_textit_dbmap-<?php esc_attr_e( $field->key ); ?>"><?php esc_attr_e( $field->label ); ?>
                                    (<?php esc_attr_e( $field->value_type ); ?>)</label>
                            </td>
                            <td class="e20r_textit_db_map_field">
								<?php echo $this->renderDBFieldList( $settings['option_name'], $field->key, $dbFields ); ?>
                            </td>
                        </tr>
						<?php
					}
					?>
                    </tbody>
                </table>
            </div>
        </div>
		
		<?php
	}
	
	/**
	 * Render map settings for TextIt Contact fields -> HowsU User Database Record values
	 *
	 * @param string $option_name
	 * @param string $key
	 * @param array  $dbFields
	 *
	 * @return string
	 */
	private function renderDBFieldList( $option_name, $key, $dbFields ) {
		
		$field_map = $this->loadSettings( $option_name );
		
		if ( empty( $field_map ) ) {
			$field_map = $this->defaultFieldMap();
		}
		
		/**
		 * Field Map: array( $key => $dbField_key, )
		 */
		$html = '';
		// $html .= sprintf( '<input type="hidden" value="%1$s" name="%2$s" />', $key, "{$this->settings_name}[{$option_name}][]" );
		$html .= sprintf( '<select class="e20r_textit_dbmap" id="%1$s" name="%2$s">', "e20r_textit_dbmap-{$key}", "{$this->settings_name}[{$option_name}][$key]" );
		$html .= sprintf( '  <option value="%1$s" %2$s>%3$s</option>', '-1', ( isset( $field_map[ $key ] ) ? selected( $field_map[ $key ], '-1', false ) : null ), __( 'Ignore', 'e20r-textit-integration' ) );
		$html .= sprintf( '  <option value="%1$s" %2$s>%3$s</option>', 'default', ( isset( $field_map[ $key ] ) ? selected( $field_map[ $key ], 'default', false ) : null ), __( 'Default', 'e20r-textit-integration' ) );
		foreach ( $dbFields as $field => $type ) {
			$html .= sprintf( '   <option value="%1$s" %2$s>%3$s</option>', $field, ( isset( $field_map[ $key ] ) ? selected( $field_map[ $key ], $field, false ) : null ), esc_attr( $field ) . " ( " . esc_attr( $type ) . " )" );
		}
		$html .= sprintf( '</select>' );
		
		return $html;
	}
	
	/**
	 * Default values for the TextIt Contact fields -> HowsU User Info record
	 *
	 * @return array
	 */
	private function defaultFieldMap() {
		
		return array(
			'flowtype'       => 'flow',
			'elapsed_time'   => 'elapse_time',
			'firstname'      => 'first_name',
			'lastname'       => 'last_name',
			'address'        => 'address',
			'city'           => 'city',
			'postcode'       => 'zip',
			'telephone'      => 'phone',
			'contact1name'   => 'full_name_c1',
			'contact1phone'  => 'contact_number_c1',
			'contact1email'  => 'email_c1',
			'contact2name'   => 'full_name_2',
			'contact2phone2' => 'contact_number_2_2',
			'contact2email'  => 'email',
		);
	}
	
	/**
	 * Seelct the default value for a specific database/TextIt Contact field
	 *
	 * @param string $field_name
	 *
	 * @return mixed
	 */
	private function defaultFieldValue( $field_name ) {
		
		$value = null;
		
		switch ( $field_name ) {
			case 'flowtype':
				$value = 'flow';
				break;
		}
		
		return $value;
	}
	
	/**
	 * Map TextIt Contact fields to Participants Database field
	 *
	 * @param string $column
	 *
	 * @return int|string
	 */
	private function _mapTextItColumns( $column ) {
		
		$textit_cols = $this->loadSettings( 'field_map' );
		$textit_cols = apply_filters( 'e20r_textit_contact_column_map', $textit_cols );
		
		foreach ( $textit_cols as $col => $key ) {
			
			if ( $column === $key ) {
				$column = $col;
			}
		}
		
		return $column;
	}
	
	/**
	 * TextIt Service API Access key
	 *
	 * @param array $settings
	 */
	public function renderKeyField( $settings ) {
		$key = $this->loadSettings( $settings['option_name'] );
		
		if ( empty( $key ) ) {
			$opts = $this->defaultSettings();
			$key  = $opts[ $settings['option_name'] ];
		}
		
		?>
        <div class="e20r-textit-service">
            <div class="e20r-textit-service-row">
                <label for="e20r_textit_service-textit_key"><?php _e( "Key", "e20r-textit-integration" ) ?></label>
                <input id="e20r_textit_service-textit_key" type="password"
                       name="<?php esc_attr_e( $this->settings_name ); ?>[<?php esc_html_e( $settings['option_name'] ); ?>]"
                       about="<?php _e( "Enter the secret key for TextIt API v2 access", "e20r-textit-integration" ); ?>"
                       value="<?php esc_attr_e( $key ); ?>"/>
            </div>
        </div>
		<?php
		
	}
	
	/**
	 * Generate listing of user Database settings for HowsU/TextIt service
	 *
	 * @param array $settings
	 */
	public function renderDBSelect( $settings ) {
		
		$user_db_table = $this->loadSettings( $settings['option_name'] );
		$tables        = $this->getDBTables();
		?>
        <div class="e20r-textit-service">
            <div class="e20r-textit-service-row">
                <label for="e20r_textit_service-flow-user_database"><?php _e( "HowsU user records", "e20r-textit-integration" ) ?></label>
                <select name="<?php esc_attr_e( $this->settings_name ); ?>[<?php esc_html_e( $settings['option_name'] ); ?>]"
                        id="e20r_textit_service-flow-user_database">
                    <option value="" <?php selected( '', $user_db_table ) ?>><?php _e( "Not Configured", "e20r-textit-integration" ); ?></option>
					<?php
					foreach ( $tables as $table_name ) { ?>
                        <option value="<?php esc_attr_e( $table_name ); ?>" <?php selected( $table_name, $user_db_table ); ?>><?php esc_attr_e( $table_name ); ?></option>
						<?php
					}
					?>
                </select>
            </div>
        </div>
		<?php
	}
	
	/**
	 * Description for the Flow/Group settings by service
	 */
	public function renderFlowMapSection() {
		?>
        <p class="e20r-textit-settings-text">
			<?php // _e( "Configure Flow and Group for services", "e20r-textit-integration" ); ?>
        </p>
		<?php
	}
	
	/**
	 * Description for Database settings
	 */
	public function renderDBSelection() {
		?>
        <p class="e20r-textit-settings-text">
        </p>
		<?php
		
	}
	
	/**
	 * Service specific settings for the Settings page
	 *
	 * @param $settings
	 */
	public function renderFlowMaps( $settings ) {
		
		$services = $this->loadSettings( $settings['option_name'] );
		
		$this->util->log( "Found " . count( $services ) . " configurable services for {$settings['option_name']}" );
		
		if ( ! empty( $services ) ) {
			
			$upstream = $this->getFlows( false );
			$groups   = $this->getGroups( false );
			
			foreach ( $services as $s_key => $config ) {
				$this->renderServiceEntry( $s_key, $config, $upstream, $groups );
			}
		}
	}
	
	/**
	 * Generate the input fields for the specific TextIt/HowsU setting
	 *
	 * @param string $serviceKey
	 * @param array  $settings
	 * @param array  $upstream_flows
	 * @param array  $groups
	 */
	private function renderServiceEntry( $serviceKey, $settings, $upstream_flows, $groups ) {
		
		printf( '<div class="e20r-textit-service">' );
		printf( '    <h3 class="e20r-textit-service-name">%1$s</h3>', $settings['label'] );
		printf( '    <input type="hidden" name="%1$s" value="%2$s">', "{$this->settings_name}[servicekey][]", $serviceKey );
		printf( '    <div class="e20r-textit-service-row">' );
		printf( '        <label for="e20r_textit_service-flow-%1$s">%2$s</label>', $serviceKey, __( "Flow", "e20r-textit-integration" ) );
		printf( '        <select name="%1$s" id="e20r_textit_service-flow-%2$s">', "{$this->settings_name}[flow_id][]", $serviceKey );
		printf( '            <option value="">%1$s</option>', __( "None", "e20r-textit-integration" ) );
		foreach ( $upstream_flows as $flow ) {
			printf( '            <option value="%1$s" %2$s>%3$s (%4$s)</option>', $flow->uuid, selected( $flow->uuid, $settings['flow_id'] ), $flow->name, date_i18n( 'Y-m-d', strtotime( $flow->created_on, current_time( 'timestamp' ) ) ) );
		}
		printf( '        </select>' );
		printf( '    </div>' );
		
		printf( '    <div class="e20r-textit-service-row">' );
		printf( '        <label for="e20r_textit_service-group-%1$s">%2$s</label>', $serviceKey, __( "Group", "e20r-textit-integration" ) );
		printf( '        <select name="%1$s" id="e20r_textit_service-group-%2$s">', "{$this->settings_name}[group_uuid][]", $serviceKey );
		printf( '            <option value="">%1$s</option>', __( "None", "e20r-textit-integration" ) );
		foreach ( $groups as $group ) {
			printf( '            <option value="%1$s" %2$s>%3$s</option>', $group->uuid, selected( $group->uuid, $settings['group_uuid'] ), $group->name );
		}
		printf( '        </select>' );
		printf( '    </div>' );
		
		printf( '</div>' );
	}
	
	/**
	 * Filter returns the currently defined list of services
	 *
	 * @param array $serviceList
	 *
	 * @return array
	 */
	public function availableServices( $serviceList = array() ) {
		
		$serviceList['telephonecall']     = array(
			'label' => __( "Telephone Call - IVR (Flow: TEL)", "e20r-textit-integration" ),
			'type'  => 'TEL',
		);
		$serviceList['smstext']           = array(
			'label' => __( "SMS/Text Message (Flow: SMS)", "e20r-textit-integration" ),
			'type'  => 'SMS',
		);
		$serviceList['facebookmessenger'] = array(
			'label' => __( "Facebook Messenger (Flow: FBM)", "e20r-textit-integration" ),
			'type'  => 'FBM',
		);
		$serviceList['welcomemessage']    = array(
			'label' => __( "Welcome Message (Flow: welcomemessage)", "e20r-textit-integration" ),
			'type'  => '',
		);
		$serviceList['twitter']           = array(
			'label' => __( "Twitter Message (Flow: TWIT)", "e20r-textit-integration" ),
			'type'  => 'TWIT',
		);
		
		$serviceList['telegram']           = array(
			'label' => __( "Telegram (Flow: GRAM)", "e20r-textit-integration" ),
			'type'  => 'GRAM',
		);
		
		// $serviceList['twitter'] = __( "Twitter Message", "e20r-textit-integration" );
		
		return $serviceList;
	}
	
	/**
	 * Connect with TextIt API server & refresh the group and flow list on this server
	 */
	public function ajaxRefreshTextit() {
		
		if ( WP_DEBUG ) {
			error_log( "Running action: " . print_r( $_REQUEST, true ) );
		}
		
		$this->getFlows( true );
		$this->getGroups( true );
		$this->getTextItFields( true );
	}
	
	/**
	 * Load settings/options for the plugin
	 *
	 * @param $option_name
	 *
	 * @return bool|mixed
	 */
	public function loadSettings( $option_name ) {
		
		$this->settings = get_option( "{$this->settings_name}", false );
		
		if ( empty( $this->settings ) ) {
			$this->settings = $this->defaultSettings();
		}
		
		if ( isset( $this->settings[ $option_name ] ) && ! empty( $this->settings[ $option_name ] ) ) {
			
			return $this->settings[ $option_name ];
		}
		
		return false;
	}
	
	/**
	 * Load and return all flows from the upstream TextIt server
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	private function getFlows( $force = false ) {
		
		$active = get_option( 'e20r_textit_flows', false );
		
		if ( true === $force || empty( $active ) ) {
			
			$this->util->log( "Forcing load from TextIt Service for Flows" );
			
			$results = $this->updateTextItService( array(), 'flows.json', 'GET' );
			$flows   = isset( $results->results ) ? $results->results : array();
			
			$active = array();
			
			foreach ( $flows as $flow ) {
				
				$data             = new stdClass();
				$data->uuid       = $flow->uuid;
				$data->name       = $flow->name;
				$data->created_on = $flow->created_on;
				
				$active[] = $data;
			}
			
			if ( ! empty( $active ) ) {
				update_option( 'e20r_textit_flows', $active, false );
			}
		}
		
		if ( false === $active ) {
			$active = array();
		}
		$this->util->log( ( "Have " . count( $active ) . " flows" ) );
		
		return $active;
	}
	
	/**
	 * Load and return all groups from the upstream TextIt server
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	private function getGroups( $force = false ) {
		
		$groups = get_option( 'e20r_textit_groups', false );
		
		if ( true === $force || empty( $groups ) ) {
			
			$this->util->log( "Forcing load from TextIt Service for Groups" );
			
			$data  = $this->updateTextItService( array(), 'groups.json', 'GET' );
			$glist = isset( $data->results ) ? $data->results : array();
			
			$groups = array();
			
			foreach ( $glist as $group ) {
				$groups[ $group->name ] = $group;
			}
			
			if ( ! empty( $groups ) ) {
				update_option( 'e20r_textit_groups', $groups, false );
			}
		}
		
		$this->util->log( "Have " . count( $groups ) . " groups" );
		
		return $groups;
	}
	
	/**
	 * Return a list of table names in the DB for this WordPress instance
	 *
	 * @return array
	 */
	private function getDBTables() {
		
		global $wpdb;
		
		$list        = $wpdb->get_results( 'SHOW TABLES' );
		$table_list  = array();
		$column_name = "Tables_in_" . DB_NAME;
		
		foreach ( $list as $record ) {
			$table_name   = preg_replace( "/{$wpdb->prefix}/", '', $record->{$column_name} );
			$table_list[] = $table_name;
		}
		
		return $table_list;
	}
	
	/**
	 * Use HowsU Service Level info to select the WordPress role for the user
	 *
	 * @param string $service_level
	 *
	 * @return string
	 */
	private function _getRoleName( $service_level ) {
		
		$role = $this->_process_text( $service_level );
		
		if ( false !== stripos( $role, 'weekend' ) ) {
			$role = 'weekend';
		}
		
		return $role;
	}
	
	/**
	 * Strip whitespace from strings
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function _process_text( $text ) {
		
		return strtolower( preg_replace( '/\s/', '', $text ) );
	}
	
	/**
	 * Map of Service Levels/Membership Levels used by HowsU service
	 *
	 * @param array $map
	 *
	 * @return array
	 */
	public function defaultLevelMap( $map = array() ) {
		
		if ( empty( $map ) ) {
			$map = array(
				10 => 'standard',
				11 => 'premium',
				12 => 'premiumplus',
			);
		}
		
		return $map;
	}
	
	/**
	 * Map user
	 * @return array
	 */
	public function membershipLevelMap() {
		
		$map = array();
		
		if ( function_exists( 'pmpro_getAllLevels' ) ) {
			
			$levels = pmpro_getAllLevels( true, true );
			
			foreach ( $levels as $id => $level ) {
				
				$lname = $this->_process_text( $level->name );
				
				if ( false !== strpos( $lname, 'weekend' ) ) {
					$lname = 'weekend';
				}
				
				$map[ $this->_process_text( $level->name ) ] = array(
					'id'   => $id,
					'name' => $level->name,
					'key'  => $lname,
				);
			}
			
		}
		
		return $map;
	}
	
	/**
	 * Get HowsU user data record from the specified Database Table (default is Participants Database user data table)
	 *
	 * @param int  $user_id
	 * @param bool $force
	 *
	 * @return mixed
	 */
	public function getUserRecord( $user_id, $force = false ) {
		
		global $wpdb;
		
		if ( ! is_user_logged_in() ) {
			return false;
		}
		
		$user           = get_user_by( "ID", $user_id );
		$user->textitid = get_user_meta( $user_id, 'e20r_textit_contact_uuid', true );
		
		// Make sure the table is configured
		if ( empty( $this->table ) ) {
			$this->setParticipantsTable( $this->loadSettings( 'user_database' ) );
		}
		
		if ( WP_DEBUG ) {
			error_log( "Loading user info for {$user->user_email}... " );
		}
		
		if ( ! empty( $this->table ) ) {
			
			$sql = $wpdb->prepare(
				"
		  SELECT ud.* 
		  FROM {$this->table} AS ud
		  WHERE ud.user_id = %s
		  ORDER BY ud.id DESC",
				$user->user_email
			);
			
			$record = $wpdb->get_row( $sql );
			
			if ( ! empty( $record ) ) {
				
				if ( WP_DEBUG ) {
					error_log( "Found data for user..." . print_r( $record->id, true ) );
				}
				
				// $_SESSION['TextIt_UserDetail'] = $record;
				$this->userRecord = $record;
				
				if ( ! empty( $user->textitid ) ) {
					$this->userRecord->textitid = $user->textitid;
				}
				
				return $record;
			} else {
				if ( WP_DEBUG ) {
					error_log( "No data found for user {$user_id}" );
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Save the HowsU User data record to the database
	 *
	 * @param int   $user_id
	 * @param array $record
	 * @param array $where
	 *
	 * @return bool|mixed
	 */
	public function updateUserRecord( $user_id, $record = array(), $where = array() ) {
		
		global $wpdb;
		
		if ( WP_DEBUG ) {
			error_log( "Updating {$this->table} record for {$user_id}: " . print_r( $record, true ) . print_r( $where, true ) );
		}
		
		// Process $record
		if ( false !== $wpdb->update( $this->table, $record, $where ) ) {
			
			// Clear the transient since the DB user record was updated.
			delete_transient( "textit_user_{$user_id}" );
			
			$new = $this->getUserRecord( $user_id, true );
			
			if ( WP_DEBUG ) {
				error_log( "Retrieved data after update: " . print_r( $new, true ) );
			}
			
			return $new;
		}
		
		return false;
	}
	
	/**
	 * Delete the specified HowsU User Database record
	 *
	 * @param int   $user_id
	 * @param array $where
	 *
	 * @return bool
	 */
	private function _deleteUserRecord( $user_id, $where ) {
		
		global $wpdb;
		
		if ( WP_DEBUG ) {
			error_log( "Deleting {$this->table} record for {$user_id}: " . print_r( $where, true ) );
		}
		
		// Process $record
		if ( false !== $wpdb->delete( $this->table, $where ) ) {
			
			// Clear the transient since the DB user record was updated.
			delete_transient( "textit_user_{$user_id}" );
			
			$new = $this->getUserRecord( $user_id, true );
			
			if ( empty( $new ) ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Cancel the membership & any HowsU/TextIt services.
	 *
	 * @param int       $current_level_id
	 * @param int       $user_id
	 * @param int| null $old_level_id
	 *
	 * @return bool
	 */
	public function cancelMembership( $current_level_id, $user_id, $old_level_id = null ) {
		
		// We're cancelling the membership level for this user_id
		if ( 0 == $current_level_id ) {
			
			$user_info = $this->getUserRecord( $user_id );
			
			// Update the TextIt Service
			$msg = array(
				'urns' => array( "tel:{$user_info->service_number}" ),
			);
			
			if ( false === ( $response = $this->updateTextItService( $msg, 'contacts.json', 'DELETE', $user_info->textitid ) ) ) {
				$msg = __( "Unable to unsubscribe user from TextIt service", "e20r-textit-integration" );
				$this->util->set_notice( $msg, "error" );
				$this->util->log( $msg );
				
				return false;
			}
			
			$where = array( 'id' => $user_info->id );
			
			if ( false === $this->_deleteUserRecord( $user_id, $where ) ) {
				$msg = __( "Unable to update your database record. Please report this to the webmaster!", 'e20r-textit-integration' );
				$this->util->set_notice( $msg, 'error' );
				$this->util->log( $msg );
				
				return false;
			}
			
			if ( WP_DEBUG ) {
				error_log( "Resetting the member's role to that of a subscriber" );
			}
			
			wp_update_user( array( 'ID' => $user_id, 'role' => 'subscriber' ) );
			
			if ( true === apply_filters( 'e20r_textit_update_everlive_service', false ) ) {
				$this->updateEverliveService( 'removeUser', $user_info->id );
			}
		}
	}
	
	/**
	 * Show the Membership ID (Shortcode handler)
	 *
	 * @param array $atts
	 *
	 * @return null|string
	 */
	public function displayMembershipId( $atts = array() ) {
		
		global $current_user;
		
		$a = shortcode_atts( array(
			'user_id' => null,
		), $atts );
		
		if ( empty( $user_id ) ) {
			$user = $current_user;
		} else {
			$user = new WP_User( $user_id );
		}
		
		if ( empty( $this->userRecord ) || false !== strcmp( $this->userRecord->user_id, $user->user_email ) ) {
			$this->getUserRecord( $user->ID );
		}
		
		if ( empty( $this->userRecord->id ) ) {
			return null;
		}
		
		$edit_link = add_query_arg( 'pdb', $this->userRecord->id, home_url( '/view-my-details/' ) );
		ob_start();
		?>
        <div class="pdb-membership-id">
            <p>
                <strong><?php _e( "Membership ID", "e20r-textit-integration" ); ?>
                    :</strong> <?php esc_attr_e( $current_user->member_number ); ?>
            </p>
            <p>
                <a href="<?php echo $edit_link; ?>" class="dt-btn-m dt-btn ripple" target="_self"
                   style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
					<span class="text-wrap"
                          style="position: relative; z-index: 2;"><?php _e( "Update/Edit Personal Details", "e20r-textit-integration" ); ?></span>
                </a>
            </p>
        </div>
		<?php
		
		return ob_get_clean();
	}
	
	/**
	 * Display Welcome message on HowsU Membership page
	 *
	 * @param array $attrs
	 *
	 * @return string
	 */
	public function displayWelcomeMessage( $attrs = array() ) {
		
		global $current_user;
		
		$firstname = get_user_meta( $current_user->ID, 'first_name', true );
		
		ob_start();
		?>
        <div class="e20r-textit-welcom-message">
            <p> <?php if ( ! empty( $firstname ) ) { ?>
                    <strong><?php printf( __( "Thank you, %s for signing up!", "e20r-textit-integration" ), esc_attr( $firstname ) ); ?></strong>
				<?php } else { ?>
                    <strong><?php printf( __( "Thank you, for signing up!", "e20r-textit-integration" ), esc_attr( $firstname ) ); ?></strong>
				<?php } ?>
            </p>
            <p>
				<?php _e( "You may complete your registration or update/change any of the information by visiting the link provided in the welcome acknowledgement email.", "e20r-textit-integration" ); ?>
            </p>
        </div>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * Creates or returns an instance of the e20rTextitIntegration class.
	 *
	 * @return  e20rTextitIntegration A single instance of this class.
	 */
	static public function get_instance() {
		
		if ( null === self::$instance ) {
			self::$instance = new self;
			
			self::$instance->loadHooks();
		}
		
		return self::$instance;
	}
	
	/**
	 * Verify presence of and select the HowsU User Database table
	 *
	 * @param string $name
	 */
	public function setParticipantsTable( $name ) {
		
		global $wpdb;
		
		$table_name = "{$wpdb->prefix}{$name}";
		
		// Make sure the table exists...
		$rows = $wpdb->query( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
		
		if ( ! empty( $rows ) ) {
			$this->table = $table_name;
		} else {
			
			$this->util->set_notice(
				sprintf(
					__( "A table with that name (%s) was not found in this database!", "textit" ),
					"{$wpdb->prefix}{$name}"
				),
				"error"
			);
		}
	}
	
	/**
	 * If the member's payment fails, we'll cancel their membership and clean up after them.
	 *
	 * @param   MemberOrder $old_order
	 */
	public function paymentFailed( $old_order ) {
		
	    if ( ! function_exists( 'pmpro_changeMembershipLevel' ) ) {
	        $this->util->log("Error: PMPro is no longer active on the site!");
	        $this->util->set_notice( __("Paid Memberships Pro is missing or inactive. Please activate Paid Memberships Pro!", "e20r-textit-integraion"), 'error' );
	        return;
        }
        
		if ( false === pmpro_changeMembershipLevel( false, $old_order->user_id ) ) {
			
			$admin_email = apply_filters( 'e20r_textit_admin_email_addr', array( get_option( 'admin_email' ) ) );
			$recipients  = implode( ',', $admin_email );
			
			$user_info = $this->getUserRecord( $old_order->user_id );
			$message   = "Error cancelling membership (ID: $old_order->membership_id) for user {$user_info->first_name} {$user_info->last_name} with record ID {$user_info->id} for the {$user_info->service_type} TextIt service";
			$subject   = "HowsU: TextIt Unsubscribe failure";
			
			wp_mail( $recipients, $subject, $message );
		}
	}
	
	public function hub_pdbUpdateID( $record ) {
		
		global $current_user;
		
		if ( is_user_logged_in() ) {
			
			$record['user_id'] = $current_user->user_email;
			$record['u_id']    = $current_user->ID;
		}
		
		return $record;
	}
	
	public function setSessionVars( $session_vars = array() ) {
		
		$_SESSION['TextIt_UserDetail'] = $this->userRecord;
	}
	
	public function restoreSessionVars( $vars, $order ) {
		
		if ( empty( $this->userRecord ) && ! empty( $_SESSION['TextIt_UserDetail'] ) ) {
			
			$this->userRecord = $_SESSION['TextIt_UserDetail'];
			unset( $_SESSION['TextIt_UserDetail'] );
		}
		
		return $vars;
	}
	
	static public function getPDBRecord( $user = null ) {
		
		$class = self::get_instance();
		global $current_user;
		
		if ( is_null( $user ) && is_user_logged_in() && ! empty( $current_user->ID ) ) {
			
			// Nothing received, but the user is logged in
			$user = $current_user;
		} else if ( is_numeric( $user ) && ! empty( $user ) ) {
			// is a User ID;
			$user = new WP_User( $user );
		}
		
		if ( false !== $userRecord = $class->getUserRecord( $user->ID ) ) {
			return $userRecord;
		}
		
		return false;
	}
	
	/**
	 * Load the user's PDB info to session & memory during login.
	 *
	 * @param string $username The user's login name
	 *
	 * @return bool
	 */
	public function loadUserDBInfo( $username ) {
		
		global $wpdb;
		
		// Grab the last record for this user ID (WP_User->login_name)
		$user_info = $wpdb->get_row(
			$wpdb->prepare( "
				SELECT * 
				FROM {$this->table} 
				WHERE user_id = %s
				ORDER BY id DESC
				LIMIT 1", $username )
		);
		
		if ( ! empty ( $user_info ) ) {
			
			$this->userRecord              = $user_info;
			$_SESSION['TextIt_UserDetail'] = $this->userRecord;
			
			return true;
		}
		
		$this->util->set_notice( sprintf( __( "Unable to load database record for %s", "e20r-textit-integration" ), $username ), 'error' );
		
		return false;
	}
	
	
	/**
	 * Remove user info from memory/sessions when they log out
	 */
	public function unloadUserDBInfo() {
		
		// Just to make sure!
		unset( $_SESSION['TextIt_UserDetail'] );
		$this->userRecord = null;
	}
	
	/**
	 * Update the user's record on the EverLive Service (Dormant serice for HowsU)
	 *
	 * @param $action
	 * @param $id
	 *
	 * @return bool
	 */
	public function updateEverliveService( $action, $id ) {
		
		$request = array(
			'timeout'     => 30,
			'httpversion' => '1.1',
			'body'        => array( 'email' => $id ),
		);
		
		$url = "{$this->everliveUrl}/functions/{$action}/";
		
		$response = wp_remote_post( $url, $request );
		
		if ( WP_DEBUG ) {
			error_log( "Returned value: " . print_r( $response, true ) );
		}
		
		if ( is_wp_error( $response ) ) {
			$error_messages = $response->get_error_messages();
			$this->util->set_notice(
				sprintf(
					__( "Unable to update the Everlive service: %s", "e20r-textit-integration" ),
					( is_array( $error_messages ) ? explode( ' - ', $error_messages ) : $error_messages )
				)
			);
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Redirect to the membership level comparison page (rather than the membership-levels page).
	 */
	function avoidLevelsPage() {
		
		if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
			$this->util->log("Error: PMPro is no longer active on the site!");
			$this->util->set_notice( __("Paid Memberships Pro is missing or inactive. Please activate Paid Memberships Pro!", "e20r-textit-integraion"), 'error' );
			return;
		}
		
		global $current_user;
		global $post;
		
		$has_pdb = $this->util->_get_variable( 'pdb', false );
		
		if ( is_user_logged_in() && pmpro_getMembershipLevelForUser() && is_page( $this->pdb_page_list ) && false === $has_pdb ) {
			
			$record = $this->getUserRecord( $current_user->ID, true );
			
			if ( ! empty( $record ) ) {
				
				$redirect_url = add_query_arg( 'pdb', $record->id, site_url( "/{$post->post_name}/" ) );
				
				if ( WP_DEBUG ) {
					error_log( "Redirecting with PDB ID: {$record->id} to {$redirect_url}" );
				}
				
				wp_redirect( $redirect_url );
				exit;
			}
		}
		
		if ( is_page( 'membership-levels' ) ) {
			wp_redirect( home_url( '/membership-account/choose-your-membership-level/' ) );
		}
	}
	
	/**
	 * Load the TextIt Integration CSS and JavaScript resources
	 */
	public function loadScriptStyle() {
		
		// PDB template pages:
		global $post;
		global $current_user;
		
		$record = $this->getUserRecord( $current_user->ID );
		
		if ( ! empty( $record ) ) {
			
			wp_register_script( 'e20r-textit-integration', plugins_url( 'js/e20r-textit-integration.js', __FILE__ ), array( 'jquery' ), E20RTEXTIT_VER, true );
			
			wp_localize_script( 'e20r-textit-integration', 'e20rTextIt', array(
					'everlive'   => array(
						'url' => $this->everliveUrl,
					),
					'userRecord' => $record,
				)
			);
			
			wp_enqueue_script( 'e20r-textit-integration' );
		}
		
		// Load the PDB template page(s)
		if ( ! empty( $post->post_name ) && in_array( $post->post_name, $this->pdb_page_list ) ) {
			
			if ( WP_DEBUG ) {
				error_log( "Loading: {$post->post_name}" );
			}
			
			wp_enqueue_script( 'pdb-telcodes', plugins_url( 'js/e20r-textit-country-codes.js', __FILE__ ), array( 'jquery' ), E20RTEXTIT_VER, true );
			
			wp_register_script( 'e20r-pdb-js', plugins_url( 'js/e20r-pdb-integration.js', __FILE__ ), array(
				'jquery',
				'pdb-telcodes',
			), E20RTEXTIT_VER, true );
			wp_localize_script( 'e20r-pdb-js', 'textIt',
				array(
					'userDetail' => $this->getUserRecord( $current_user->ID ),
					'settings'   => array(
						'timeout' => apply_filters( 'e20r_textit_service_request_timeout', 5000 ),
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
					),
				)
			);
			
			wp_enqueue_script( 'e20r-pdb-js' );
			
		}
	}
	
	/**
	 * Redirect a user to the Register page on login if not a valid/active user
	 *
	 * @param string $redirect_to
	 *
	 * @return string
	 */
	public function loginRedirectHandler( $redirect_to ) {
		
		if ( ! function_exists( 'pmpro_url' ) ) {
			$this->util->log("Error: PMPro is no longer active on the site!");
			$this->util->set_notice( __("Paid Memberships Pro is missing or inactive. Please activate Paid Memberships Pro!", "e20r-textit-integraion"), 'error' );
			return $redirect_to;
		}
		
		//is there a user to check?
		global $current_user;
		
		if ( ! is_user_logged_in() || is_admin() ) {
			return $redirect_to;
		}
		
		$user_info = $this->getUserRecord( $current_user->ID );
		
		if ( is_array( $current_user->roles ) ) {
			
			// Ignore if admin
			if ( ! current_user_can( 'manage_options' ) ) {
				
				if ( ! empty( $user_info ) ) {
					$redirect_to = add_query_arg( 'pid', $user_info->id, pmpro_url( 'account' ) );
				} else {
					$redirect_to = site_url( '/register/' );
				}
			}
		}
		
		return $redirect_to;
		
	}
	
	/**
	 * Define required WordPress/Paid Memberships Pro/PDB hooks/filters being used
	 */
	public function loadHooks() {
		
		$this->util = e20rUtils::get_instance();
		$this->setParticipantsTable( $this->loadSettings( 'user_database' ) );
		
		// Action hooks
		add_action( 'pmpro_paypalexpress_session_vars', array( $this, 'setSessionVars' ), 10, 1 );
		add_action( 'pmpro_checkout_confirmed', array( $this, 'restoreSessionVars' ), 10, 2 );
		add_action( 'pmpro_subscription_payment_failed', array( $this, 'paymentFailed' ), 10, 1 );
		
		add_action( 'pmpro_after_checkout', array( $this, 'checkoutComplete' ), 10, 2 );
		add_action( 'pmpro_after_change_membership_level', array( $this, 'cancelMembership' ), 10, 3 );
//		add_action( 'wp_login', array( $this, 'loadUserDBInfo' ), 10, 1 );
//		add_action( 'wp_logout', array( $this, 'unloadUserDBInfo' ), 10, 1 );
		
		add_action( 'pdb-before_submit_update', array( $this, 'hub_pdbUpdateID' ), 10, 1 );
		add_action( 'pdb-before_submit_signup', array( $this, 'hub_pdbUpdateID' ), 10, 1 );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'loadScriptStyle' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'loadScriptStyle' ), 10, 1 );
		
		add_action( 'template_redirect', array( $this, 'avoidLevelsPage' ) );
		
		add_action( 'wp_ajax_e20r_pdb_update', array( $this, 'ajaxUpdateDatabase' ) );
		add_action( 'wp_ajax_e20r_pause_service', array( $this, 'ajaxPauseService' ) );
		add_action( 'wp_ajax_e20r_start_service', array( $this, 'ajaxResumeService' ) );
		add_action( 'wp_ajax_e20r_refresh_textit', array( $this, 'ajaxRefreshTextit' ) );
		
		add_action( 'admin_menu', array( $this, 'loadAdminSettingsPage' ), 10 );
		
		// add_action( 'admin_init', array( $this, 'registerSettingsPage' ), 10 );
		
		if ( ! empty ( $GLOBALS['pagenow'] )
		     && ( 'options-general.php' === $GLOBALS['pagenow']
		          || 'options.php' === $GLOBALS['pagenow']
		     )
		) {
			add_action( 'admin_init', array( $this, 'registerSettingsPage' ), 10 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueueOptionStyles' ) );
		}
		
		add_shortcode( 'list_flows', array( $this, 'listFlows' ) );
		
		// add_action( 'init', array( $this, 'loadHelpers' ) );
		
		// Filters
		add_filter( 'login_redirect', array( $this, 'loginRedirectHandler' ), 10, 1 );
		add_filter( 'e20r_textit_available_service_options', array( $this, 'availableServices' ), 5, 1 );
		
		// Shortcodes
		add_shortcode( 'pdb_membership_id', array( $this, 'displayMembershipId' ) );
		add_shortcode( 'e20r_textit_welcome', array( $this, 'displayWelcomeMessage' ) );
	}
	
	public function loadHelpers() {
	
	}
	
	/**
	 * Styles for the Settings page (/wp-admin/)
	 */
	public function enqueueOptionStyles() {
		wp_enqueue_style( 'e20r-textit-options', plugins_url( 'css/e20r-textit-admin-options.css', __FILE__ ), null, E20RTEXTIT_VER );
		wp_enqueue_script( 'e20r-textit-options', plugins_url( 'js/e20r-textit-options.js', __FILE__ ), array( 'jquery' ), E20RTEXTIT_VER );
	}
	
	/**
	 * Plugin activation function: Configures required WordPress roles
	 */
	static public function configureRoles() {
		
		if ( null === get_role( 'protector' ) ) {
			
			$caps = array(
				'read'              => true, // true allows the user to read posts
				'edit_posts'        => false, // Allows user to edit their own posts
				'edit_pages'        => false, // Allows user to edit pages
				'edit_others_posts' => false, // Allows user to edit others posts not just their own
				'create_posts'      => false, // Allows user to create new posts
				'manage_categories' => false, // Allows user to manage post categories
				'publish_posts'     => false, // Allows the user to publish, otherwise posts stays in draft mode
				'edit_themes'       => false, // false denies this capability. User can’t edit your theme
				'install_plugins'   => false, // User cant add new plugins
				'update_plugin'     => false, // User can’t update any plugins
				'update_core'       => false // user cant perform core updates
			);
			
			$role_defs = array(
				'protector'     => array(
					'label' => __( 'Protector', 'e20r-textit-integration' ),
					'caps'  => $caps,
				),
				'guardian'      => array(
					'label' => __( 'Guardian', 'e20r-textit-integration' ),
					'caps'  => $caps,
				),
				'guardianangel' => array(
					'label' => __( 'Guardian Angel', 'e20r-textit-integration' ),
					'caps'  => $caps,
				),
				'weekend'       => array(
					'label' => __( 'Weekender', 'e20r-textit-integration' ),
					'caps'  => $caps,
				),
				'standard'      => array(
					'label' => __( 'Standard', 'e20r-textit-integration' ),
					'caps'  => $caps,
				),
				'premium'       => array(
					'label' => __( 'Premium', 'e20r-textit-integration' ),
					'caps'  => $caps,
				),
				'premiumplus'   => array(
					'label' => __( 'Premium Plus', 'e20r-textit-integration' ),
					'caps'  => $caps,
				),
			);
			
			foreach ( $role_defs as $role => $info ) {
				
				if ( false === add_role( $role, $info['label'], $info['caps'] ) ) {
					
					trigger_error( __( "Error: Unable to define {$info['label']} role", "e20r-textit-integration" ), E_USER_ERROR );
				}
			}
		}
	}
	
	/**
	 * Autoloader class for the plugin.
	 *
	 * @param string $class_name Name of the class being attempted loaded.
	 */
	public static function __class_loader( $class_name ) {
		
		$plugin_classes = array(
			'e20rutils',
			'e20rtextitintegration',
			'howsu',
			'howsuviews',
		);
		
		// $plugin_classes = $this->util->autoloader_list( $classes );
		
		if ( in_array( strtolower( $class_name ), $plugin_classes ) && ! class_exists( $class_name ) ) {
			
			$name = strtolower( $class_name );
			
			$filename     = dirname( __FILE__ ) . "/classes/class.{$name}.php";
			$utils_file   = dirname( __FILE__ ) . "/utilities/class.{$name}.php";
			$license_file = dirname( __FILE__ ) . "/license/class.{$name}.php";
			
			if ( file_exists( $filename ) ) {
				require_once $filename;
			}
			
			if ( file_exists( $utils_file ) ) {
				require_once $utils_file;
			}
			
			if ( file_exists( $license_file ) ) {
				require_once $license_file;
			}
			
		}
	} // End of autoloader method
}

spl_autoload_register( 'e20rTextitIntegration::__class_loader' );

add_action( 'plugins_loaded', 'e20rTextitIntegration::get_instance' );

register_activation_hook( __FILE__, 'e20rTextitIntegration::configureRoles' );

if ( ! class_exists( '\\PucFactory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'plugin-updates/plugin-update-checker.php' );
}

$plugin_updates = \PucFactory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/e20r-textit-integration/metadata.json',
	__FILE__,
	'e20r-textit-integration'
);
