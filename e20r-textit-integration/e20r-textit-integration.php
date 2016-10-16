<?php
/*
Plugin Name: E20R HowsU/Text-It Messaging Service integration
Plugin URI: http://eighty20results.com/wordpress-plugins/e20r-textit-integration/
Description: howsu.today website integration for the textit.in SMS/Voice messaging service
Version: 1.1.2
Requires: 4.5.3
Tested: 4.6.1
Author: Thomas Sjolshagen <thomas@eighty20results.com>
Author URI: http://www.eighty20results.com/thomas-sjolshagen/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: e20rtextit
*/

defined( 'ABSPATH' ) or die( 'Cannot access plugin sources directly' );

if ( ! defined( 'HOWSU_PLUGIN_URL' ) ) {
	define( 'HOWSU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'E20RTEXTIT_VER' ) ) {
	define( 'E20RTEXTIT_VER', '1.1.2' );
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

	/**
	 * e20rTextitIntegration constructor.
	 */
	private function __construct() {

		// TODO: Use options page for TextIt integration to get/save key
		$this->key = get_option( 'e20r_textit_key', "7964392e969ce3aa258906f8e864380c2d058841" );

		// TODO: Use options page for TextIt service URL
		$this->urlBase = apply_filters(
			'e20r_textit_service_url_base',
			get_option( 'e20r_textit_url', 'https://api.textit.in/api/v1' )
		);

		$this->everliveAppId = get_option( 'textit_everlive', "eButuc0AhfMCXdz5" );
		$this->everliveUrl   = "http://api.everlive.com/v1/{$this->everliveAppId}/";

		if ( empty( $this->pdb_page_list ) ) {
			$this->pdb_page_list = array(
				'upgrade-service-detail',
				'service-details',
				'contact-details',
				'customer-detail',
				'edit-service',
				'edit-contact-1-2',
				'customer-detail-2',
				'edit-third-contact',
				'edit-forth-contact',
				'view-my-details',
				'order-confirmation',
				'third-contact',
				'view-first-contact',
				'view-status-detail',
			);
		}

		$this->pdb_page_list = apply_filters( 'textit_pdb_page_list', $this->pdb_page_list );
		add_action( 'plugins_loaded', array( $this, 'loadHooks' ) );
		// add_action( 'init', array( $this, 'listFlows' ) );
	}

	public function listFlows() {

		if ( WP_DEBUG ) {
			error_log( "Getting flow info from TextIt service" );
		}

		$data = $this->updateTextItService( array(), 'flows.json', 'GET' );

		if ( WP_DEBUG ) {
			error_log( "Flow Info: " . print_r( $data, true ) );
		}
	}

	/**
	 * Checkout handler for TextIt (register the user with the service)
	 *
	 * @param int $user_id
	 * @param MemberOrder $order
	 *
	 * @return bool
	 */
	public function checkoutComplete( $user_id, $order ) {

		$gateways_with_pending_status = apply_filters( 'pmpro_gateways_with_pending_status', array(
			'paypalstandard',
			'twocheckout',
			'gourl'
		) );

		unset( $_SESSION['pmpro_level_id'] );

		$howsu_level_map = apply_filters( 'e20r_textit_howsu_level_map', $this->defaultLevelMap( array() ) );

		// Wait for the membership level to be OK.
		if ( in_array( pmpro_getGateway(), $gateways_with_pending_status ) ) {

			$this->util->set_notice( __( "Account not ready. We're waiting for membership payment status from gateway.", "e20rtextit" ), 'notice' );
			wp_redirect( pmpro_url( 'levels' ) );
		}

		$user                = get_user_by( 'ID', $user_id );
		$current_level_descr = isset( $howsu_level_map[ $order->membership_id ] ) ? $howsu_level_map[ $order->membership_id ] : $order->membership_id;

		if ( WP_DEBUG ) {
			error_log( "Signing up for: {$current_level_descr}" );
		}

		if ( ! in_array( $current_level_descr, array( 'standard', 'premium', 'premiumplus' ) ) ) {

			if ( false === $this->registerWithTextIt( $user_id ) ) {
				$this->util->set_notice( __( "Unable to register the user with the TextIt Service. Please contact the webmaster!", "e20rtextit" ), 'error' );

				return false;
			}
		}

		if ( false === $this->updateUserRecord( $user_id, array( 'onetimefee' => true ), array( 'user_id' => $user->user_email ) ) ) {

			$this->util->set_notice( __( "Please contact the webmaster. There was a problem recording your one-time setup fee payment", "e20rtextit" ), 'warning' );

			return false;
		}
	}

	public function registerWithTextIt( $user_id ) {

		$groups = 0;

		$user      = new WP_User( $user_id );
		$user_info = $this->getUserRecord( $user_id, true );

		if ( empty( $user_info ) ) {
			$this->util->set_notice( sprintf( __( "Unable to locate user data for %s", "e20rtextit" ), $user->display_name ), 'error' );

			return false;
		}

		if ( WP_DEBUG ) {
			error_log( "User record for {$user_id} is: " . print_r( $user_info, true ) );
		}

		// Generate the correct role name & set it for the specified user.
		$role_name = $this->_getRoleName( $user_info->service_level );
		$user->set_role( $role_name );

		if ( WP_DEBUG ) {
			error_log( "Configured {$role_name} role for user {$user->user_email}" );
		}

		// get the service configuration
		$flow_config = $this->_getFlowConfig( $user_info->service_type );

		// Save the membership number (service number) and other metadata for the user.
		update_user_meta( $user_id, 'member_number', $user_info->service_number );
		update_user_meta( $user_id, 'first_name', $user_info->first_name );
		update_user_meta( $user_id, 'last_name', $user_info->last_name );
		update_user_meta( $user_id, 'country', $user_info->country );

		$group_list = $this->_setGroupInfo( $role_name );

		if ( WP_DEBUG ) {
			error_log( "TextIt Group info: " . print_r( $group_list, true ) );
		}

		if ( empty( $user_info->service_number) ) {
			$this->util->set_notice( sprintf( __( "Cannot start TextIt Service for %s: Err-InvalidServiceNumber", "e20rtextit" ), $user->display_name ), 'error' );
		}

		$textit_record = array(
			'name'   => "{$user_info->first_name} {$user_info->last_name}",
			'groups' => ! empty( $group_list ) ? $group_list : array(),
			'urns'   => array( "tel:{$user_info->service_number}" ),
			'fields' => array(
				'flow Type'       => $flow_config['type'],
				'firstname'       => $user_info->first_name,
				'lastname'        => $user_info->last_name,
				'address'         => $user_info->address,
				'city'            => $user_info->city,
				'postcode'        => $user_info->zip,
				'telephone'       => $user_info->phone,
				'age range'       => ! empty( $user_info->age_range ) ? $user_info->age_range : '0',
				'contact 1 name'  => ! empty( $user_info->full_name_c1 ) ? $user_info->full_name_c1 : '0',
				'contact 1 phone' => ! empty( $user_info->contact_number_c1 ) ? $user_info->contact_number_c1 : '0',
				'contact 1 email' => ! empty( $user_info->email_c1 ) ? $user_info->email_c1 : '0',
				'contact 2 name'  => ! empty( $user_info->full_name_2 ) ? $user_info->full_name_2 : '0',
				'contact 2 phone' => ! empty( $user_info->contact_number_2_2 ) ? $user_info->contact_number_2_2 : '0',
				'contact 2 email' => ! empty( $user_info->email_c2 ) ? $user_info->email_c2 : '0',
				'elapsed time'    => ! empty( $user_info->elapse_time ) ? $user_info->elapse_time : '0',
			)
		);

		if ( WP_DEBUG ) {
			error_log( "Loading user info to TextIt Service" );
		}

		// Add new user record to the TextIt Service
		if ( false !== ( $data = $this->updateTextItService( $textit_record ) ) ) {

			$u_record = array( 'textitid' => $data->uuid, 'status' => 1, 'onetimefee' => 1 );
			$where    = array( 'id' => $user_info->id );

			// Update the status for the user record
			if ( false === ( $user_info = $this->updateUserRecord( $user_id, $u_record, $where ) ) ) {

				$this->util->set_notice( __( "Unable to update local DB record after subscribing to TextIt service", "e20rtextit" ), 'error' );

				return false;
			}

			if ( WP_DEBUG ) {
				error_log( "Updated user record with successful TextIt record load. Now sending welcome message to {$user_info->textitid}" );
			}

			if ( false !== ( $response = $this->sendMessage( 'welcomemessage', $user_info->textitid ) ) ) {

				if ( WP_DEBUG ) {
					error_log( "Welcome message sent" );
				}

				$flow_config = $this->_getFlowConfig( 'welcomemessage' );

				$msg = array(
					"action"     => 'remove',
					"group_uuid" => $flow_config['group_uuid'],
					"contacts"   => ! empty( $user_info->textitid ) ? $user_info->textitid : array(),
				);

				$response = $this->updateTextItService( $msg, 'contact_actions.json' );

				if ( WP_DEBUG ) {
					error_log( "Response from final attempt to update TextIt: " . print_r( $response, true ) );
				}
			}

		} else {

			$this->util->set_notice( __( "Unable to subscribe you to the TextIt service", "e20rtextit" ), "error" );

			// Send email to the admin when something goes wrong.
			$admin_email = apply_filters( 'e20r_textit_admin_email_addr', array( get_option( 'admin_email' ) ) );
			$recipients  = implode( ',', $admin_email );
			$message     = "Error subscribing user {$user_info->first_name} {$user_info->last_name} with record ID {$user_info->id} to the {$user_info->service_type} TextIt service";
			$subject     = "HowsU: New TextIt Subscription failure";

			// Send the admin an email notice
			wp_mail( $recipients, $subject, $message );

			if ( empty( $groups ) ) {
				$groups = array( 'UserAssistance' );
			}

			// Retry sending the welcome message?
			$msg = array(
				'name'   => "{$user_info->first_name} {$user_info->last_name}",
				'groups' => ! empty( $groups ) ? $groups : array(),
				'urns'   => "tel:{$user_info->service_number}",
			);

			$data = $this->updateTextItService( $msg );

			if ( false === $data ) {
				$this->util->set_notice( __( "Failed to send alert message", "e20rtextit" ), 'error' );

				return false;
			}

			if ( false === $this->updateUserRecord( $user_id, array(
					'textitid'   => $data->uuid,
					'status'     => 1,
					'onetimefee' => 1
				), array( 'id' => $user_info->id ) )
			) {
				$this->util->set_notice( __( "Failed to update the user's database record after welcome message retry", "e20rtextit" ), 'error' );

				return false;
			}

			if ( false === $this->sendMessage( 'welcomemessage', $user_info->textitid ) ) {
				$this->util->set_notice( __( "Failed at second attempt to send welcome message", "e20rtextit" ), 'error' );

				return false;
			}
		}
	}

	public function sendMessage( $flow_type, $who ) {

		$flow_settings = $this->_getFlowConfig( $flow_type );

		$msg = array(
			'flow_uuid' => $flow_settings['flow_id'],
			'contacts'  => ! empty( $who ) ? $who : array(),
		);

		$data = $this->updateTextItService( $msg, 'runs.json' );

		if ( $data === false ) {

			$this->util->set_notice( __( "Unable to send the welcome message to the new user!", "e20rtextit" ), 'error' );

			return false;

		} else {
			return $data;
		}
	}

	public function pauseTextItService( $user_id ) {

		$user_info = $this->getUserRecord( $user_id );

		/*

		$groupArray = array();

		$group1 = $user_info->service_level . substr($user_info->time_window, 0, 2);
		$group2 = $user_info->service_level . substr($user_info->time_window_2, 0, 2);
		$group3 = $user_info->service_level . substr($user_info->time_window_3, 0, 2);

		$groupArray = array($group1, $group2, $group3);
		*/

		$data = array(
			'urns'   => "tel:{$user_info->service_number}",
			'groups' => array(),
		);

		return $this->updateTextItService( $data );
	}

	public function resumeTextItService( $user_id ) {

		$groupArray = array();
		$user_info  = $this->getUserRecord( $user_id );

		$group_name = $this->_getRoleName( $user_info->service_level );
		$group_list = $this->_setGroupInfo( $group_name );

		/*
		$group1 = $level . substr( $user_info->time_window, 0, 2 );
		$group2 = $level . substr( $user_info->time_window_2, 0, 2 );
		$group3 = $level . substr( $user_info->time_window_3, 0, 2 );

		$groupArray = array( $group1, $group2, $group3 );
		*/

		$data = array(
			'urns'   => "tel:{$user_info->service_number}",
			'groups' => $group_list,
		);

		return $this->updateTextItService( $data );
	}

	public function ajaxPauseService() {

		// TODO: Add NONCE handling
		$service_number = $this->util->_get_variable( 'snu', null );
		wp_verify_nonce( 'e20r-pdb-nonce', 'e20r_pdb_update');
	}

	public function ajaxResumeService() {

		// TODO: Add NONCE handling
		wp_verify_nonce( 'e20r-pdb-nonce', 'e20r_pdb_update');
	}

	/**
	 * Update the PDB database for a user via AJAX call(s).
	 */
	public function ajaxUpdateDatabase() {

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'User not logged in' );
		}

		wp_verify_nonce( 'e20r-pdb-nonce', 'e20r_pdb_update');

		if ( WP_DEBUG ) {
			error_log( "Preparing to update database via AJAX call" );
		}
		// TODO: Add nonce check(s).

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
					'urns' => "tel:{$service_number}",
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

					$msg['groups'] = $groups;
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

	public function updateTextItService( $body, $json_file = "contacts.json", $operation = 'POST' ) {

		$request = array(
			'timeout'     => apply_filters( 'e20r_textit_service_request_timeout', 30 ),
			'httpversion' => '1.1',
			'sslverify'   => false,
			'headers'     => array(
				"Content-Type"  => "application/json",
				"Accept"        => "application/json",
				"Authorization" => "Token {$this->key}"
			),
			'body'        => json_encode( $body )
		);


		$url = "{$this->urlBase}/{$json_file}";

		if ( WP_DEBUG ) {
			error_log( "Sending to {$url}: " . print_r( $request, true ) );
		}

		switch ( strtolower( $operation ) ) {
			case 'get':
				$response = wp_remote_get( $url, $request );
				break;

			case 'delete':
				$request['method'] = 'DELETE';
				$response          = wp_remote_request( $url, $request );
				break;

			case 'put':
				$request['method'] = 'PUT';
				$response          = wp_remote_request( $url, $request );

			default: // Default (most used) is POST operation
				$response = wp_remote_post( $url, $request );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( WP_DEBUG ) {
			error_log( "Returned value: " . print_r( $data, true ) );
		}

		if ( is_wp_error( $response ) ) {

			$this->util->set_notice(
				sprintf(
					__( "Unable to update TextIt service: %s", "e20rtextit" ),
					explode( ' - ', $response->get_error_messages() )
				)
			);

			return false;
		}

		if ( isset( $data->failed ) && ! empty( $data->failed ) ) {

			$this->util->set_notice( __( "Unable to update TextIt service: %s", "e20rtextit" ), 'error' );

			return false;
		}

		return $data;
	}

	private function _mapTextItColumns( $column ) {

		$textit_cols = apply_filters( 'e20r_textit_contact_column_map', array(
			'flow Type'         => 'flow',
			'elapsed time'      => 'Elapsed',
			'firstname'         => 'first_name',
			'lastname'          => 'last_name',
			'address'           => 'address',
			'city'              => 'city',
			'postcode'          => 'zip',
			'telephone'         => 'phone',
			'contact 1 name'    => 'full_name_c1',
			'contact 1 phone'   => 'contact_number_c1',
			'contact 1 email'   => 'email_c1',
			'contact 2 name'    => 'full_name_2',
			'contact 2 phone 2' => 'contact_number_2_2',
			'contact 2 email'   => 'email'
		) );

		foreach ( $textit_cols as $col => $key ) {

			if ( $column === $key ) {
				$column = $col;
			}
		}

		return $column;
	}

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

		$group = array();

		for ( $i = 0; $i < $entries; $i ++ ) {

			if ( $var == $var_name[ $i ] ) {
				$time = $value;
			} else {
				$time = substr( $this->userRecord->{$var_name[ $i ]}, 0, 2 );
			}

			if ( false !== stripos( $group_name, 'weekend' ) ) {
				$group_name = 'weekend';
			}

			$group[] = "{$group_name}{$time}";
		}

		return $group;
	}

	private function _getFlowConfig( $type ) {

		$this->flow_settings = apply_filters( 'e20r_textit_flow_settings_array', $this->_defaultFlowSettings() );

		$flow_type = $this->_process_text( $type );

		return $this->flow_settings[ $flow_type ];
	}

	private function _defaultFlowSettings( $settings = array() ) {

		$settings['telephonecall'] = array(
			'type'       => 'TEL',
			'flow_id'    => '81b71a38-aec2-4f71-adb1-cfecd3b4d5ba',
			'group_uuid' => '607571ed-125c-432c-a2dc-ebf93539357a',
		);

		$settings['smstext'] = array(
			'type'       => 'SMS',
			'flow_id'    => '',
			'group_uuid' => '607571ed-125c-432c-a2dc-ebf93539357a',
		);

		$settings['facebookmessenger'] = array(
			'type'       => 'FBM',
			'flow_id'    => '',
			'group_uuid' => '607571ed-125c-432c-a2dc-ebf93539357a',
		);

		$settings['welcomemessage'] = array(
			'type'       => '',
			'flow_id'    => '63c42285-f938-4528-9274-40419028d5db',
			'group_uuid' => '607571ed-125c-432c-a2dc-ebf93539357a',
		);

		/*
		$settings = array(
			'default'   => '79e42ec6-ba35-41e8-bd0b-0a8fd44cc657'
		);
		*/

		return $settings;
	}

	private function _getRoleName( $service_level ) {

		$role = $this->_process_text( $service_level );

		if ( false !== stripos( $role, 'weekend' ) ) {
			$role = 'weekend';
		}

		return $role;
	}

	public function _process_text( $text ) {

		return strtolower( preg_replace( '/\s/', '', $text ) );
	}

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

	public function membershipLevelMap() {

		$levels = pmpro_getAllLevels( true, true );
		$map    = array();

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

		return $map;
	}

	public function getUserRecord( $user_id, $force = false ) {

		global $wpdb;

		if ( !is_user_logged_in() ) {
			return false;
		}

		$user = get_user_by( "ID", $user_id );

		// Make sure the table is configured
		if ( empty( $this->table ) ) {
			$this->setParticipantsTable( 'participants_database' );
		}

		if ( WP_DEBUG ) {
			error_log( "Loading user info for {$user->user_email}... " );
		}


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
			return $record;
		} else {
			if (WP_DEBUG) {
				error_log("No data found for user {$user_id}");
			}
		}

		return false;
	}

	public function updateUserRecord( $user_id, $record = array(), $where = array() ) {

		global $wpdb;

		if ( WP_DEBUG ) {
			error_log( "Updating {$this->table} record for {$user_id}: " . print_r( $record, true ) . print_r( $where ) );
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

	private function _deleteUserRecord( $user_id, $where ) {

		global $wpdb;

		if ( WP_DEBUG ) {
			error_log( "Deleting {$this->table} record for {$user_id}: " .  print_r( $where ) );
		}

		// Process $record
		if ( false !== $wpdb->delete( $this->table, $where ) ) {

			// Clear the transient since the DB user record was updated.
			delete_transient( "textit_user_{$user_id}" );

			$new = $this->getUserRecord( $user_id, true );

			if (empty( $new ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cancel the membership & any HowsU/TextIt services.
	 *
	 * @param int $current_level_id
	 * @param int $user_id
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
				'urns'   => "tel:{$user_info->service_number}",
				'groups' => array(),
			);

			if ( false === ( $response = $this->updateTextItService( $msg ) ) ) {

				$this->util->set_notice( __( "Unable to unsubscribe user from TextIt service", "e20rtextit" ), "error" );

				return false;
			}

			$where = array( 'id' => $user_info->id );

			if ( false === $this->_deleteUserRecord( $user_id, $where ) ) {

				$this->util->set_notice( __( "Unable to update your database record. Please report this to the webmaster!", 'e20rtextit' ), 'error' );

				return false;
			}

			if ( WP_DEBUG ) {
				error_log( "Resetting the member's role to that of a subscriber" );
			}

			wp_update_user( array( 'ID' => $user_id, 'role' => 'subscriber' ) );

			if ( true === apply_filters('e20r_textit_update_everlive_service', false ) ) {
				$this->updateEverliveService( 'removeUser', $user_info->id );
			}
		}
	}

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
			$this->loadUserDBInfo( $user->user_email );
		}

		if ( empty( $this->userRecord->id ) ) {
			return null;
		}

		$edit_link = add_query_arg( 'pdb', $this->userRecord->id, home_url( '/view-my-details/' ) );
		ob_start();
		?>
		<div class="pdb-membership-id">
			<p>
				<strong><?php _e( "Membership ID", "e20rtextit" ); ?>
					:</strong> <?php esc_attr_e( $current_user->member_number ); ?>
			</p>
			<p>
				<a href="<?php echo $edit_link; ?>" class="dt-btn-m dt-btn ripple" target="_self"
				   style="-webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
					<span class="text-wrap"
					      style="position: relative; z-index: 2;"><?php _e( "Update/Edit Personal Details", "e20rtextit" ); ?></span>
				</a>
			</p>
		</div>
		<?php

		return ob_get_clean();
	}

	public function displayWelcomeMessage( $attrs = array() ) {

		global $current_user;

		$firstname = get_user_meta($current_user->ID, 'first_name', true);

		ob_start();
		?>
		<div class="e20r-textit-welcom-message">
			<p> <?php if (!empty( $firstname ) ) { ?>
				<strong><?php printf( __( "Thank you, %s for signing up!", "e20rtextit" ), esc_attr( $firstname ) ); ?></strong>
			<?php } else { ?>
				<strong><?php printf( __( "Thank you, for signing up!", "e20rtextit" ), esc_attr( $firstname ) ); ?></strong>
			<?php } ?>
			</p>
			<p>
				<?php _e("You may complete your registration or update/change any of the information by visiting the link provided in the welcome acknowledgement email.", "e20rtextit"); ?>
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
	static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

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
		} elseif ( is_numeric( $user ) && ! empty( $user ) ) {
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

		$this->util->set_notice( sprintf( __( "Unable to load database record for %s", "e20rtextit" ), $username ), 'error' );

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


	public function updateEverliveService( $action, $id ) {

		$request = array(
			'timeout'     => 30,
			'httpversion' => '1.1',
			'body'        => array( 'email' => $id )
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
					__( "Unable to update the Everlive service: %s", "e20rtextit" ),
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
				'pdb-telcodes'
			), E20RTEXTIT_VER, true );
			wp_localize_script( 'e20r-pdb-js', 'textIt',
				array(
					'userDetail'   => $this->getUserRecord( $current_user->ID ),
					'settings'     => array(
						'timeout' => apply_filters( 'e20r_textit_service_request_timeout', 5000 ),
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
					)
				)
			);

			wp_enqueue_script( 'e20r-pdb-js' );

		}
	}

	/*
	public function old_updateTextit( $snu, $col, $val, $wpdb ) {
//  *********************   Load new contact onto TextIt ***********************************

		$groupArray = array();
		$myrows     = $wpdb->get_results( "SELECT * FROM xwdi_participants_database WHERE Id = " . $_POST['pdb'] );

		$_SESSION['TextIt_UserDetail'] = $myrows;

		if ( $col == 'time_window' || $col == 'time_window_2' || $col == 'time_window_3' ) {

			if ( $col == "time_window" ) {
				$group1     = $myrows[0]->service_level . substr( $val, 0, 2 );
				$group2     = $myrows[0]->service_level . substr( $myrows[0]->time_window_2, 0, 2 );
				$group3     = $myrows[0]->service_level . substr( $myrows[0]->time_window_3, 0, 2 );
				$groupArray = array( $group1, $group2, $group3 );

			} elseif ( $col == "time_window_2" ) {
				$group1     = $myrows[0]->service_level . substr( $myrows[0]->time_window, 0, 2 );
				$group2     = $myrows[0]->service_level . substr( $val, 0, 2 );
				$group3     = $myrows[0]->service_level . substr( $myrows[0]->time_window_3, 0, 2 );
				$groupArray = array( $group1, $group2, $group3 );

			} else {
				$group1     = $myrows[0]->service_level . substr( $myrows[0]->time_window, 0, 2 );
				$group2     = $myrows[0]->service_level . substr( $myrows[0]->time_window_2, 0, 2 );
				$group3     = $myrows[0]->service_level . substr( $val, 0, 2 );
				$groupArray = array( $group1, $group2, $group3 );
			}

			$data = array(
				'urns'   => 'tel:' . $snu,
				'groups' => $groupArray
			);

		} else {

			$map = array(
				'flow Type'         => 'flow',
				'elapsed time'      => 'Elapsed',
				'firstname'         => 'first_name',
				'lastname'          => 'last_name',
				'address'           => 'address',
				'city'              => 'city',
				'postcode'          => 'zip',
				'telephone'         => 'phone',
				'contact 1 name'    => 'full_name_c1',
				'contact 1 phone'   => 'contact_number_c1',
				'contact 1 email'   => 'email_c1',
				'contact 2 name'    => 'full_name_2',
				'contact 2 phone 2' => 'contact_number_2_2',
				'contact 2 email'   => 'email'
			);

			foreach ( $map as $field => $value ) {
				if ( $col == $value ) {
					$col = $field;
				}
			}

			$data = array(
				'urns'   => 'tel:' . $snu,
				'fields' => array(
					$col => $val
				)
			);
		}

		return $this->updateTextItService( $data );

	}
*/
	public function loginRedirectHandler( $redirect_to ) {

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

	public function loadHooks() {

		$this->util = e20rUtils::get_instance();
		$this->setParticipantsTable( get_option( 'e20r_textit_table', 'participants_database' ) );

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

		// add_action( 'init', array( $this, 'loadHelpers' ) );

		// Filters
		add_filter( 'login_redirect', array( $this, 'loginRedirectHandler' ), 10, 1 );

		// Shortcodes
		add_shortcode( 'pdb_membership_id', array( $this, 'displayMembershipId' ) );
		add_shortcode( 'e20r_textit_welcome', array( $this, 'displayWelcomeMessage' ) );
	}

	public function loadHelpers() {

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
					'label' => __( 'Protector', 'e20rtextit' ),
					'caps'  => $caps
				),
				'guardian'      => array(
					'label' => __( 'Guardian', 'e20rtextit' ),
					'caps'  => $caps
				),
				'guardianangel' => array(
					'label' => __( 'Guardian Angel', 'e20rtextit' ),
					'caps'  => $caps,
				),
				'weekend'       => array(
					'label' => __( 'Weekender', 'e20rtextit' ),
					'caps'  => $caps,
				),
				'standard'      => array(
					'label' => __( 'Standard', 'e20rtextit' ),
					'caps'  => $caps,
				),
				'premium'       => array(
					'label' => __( 'Premium', 'e20rtextit' ),
					'caps'  => $caps,
				),
				'premiumplus'   => array(
					'label' => __( 'Premium Plus', 'e20rtextit' ),
					'caps'  => $caps,
				)
			);

			foreach ( $role_defs as $role => $info ) {

				if ( false === add_role( $role, $info['label'], $info['caps'] ) ) {

					trigger_error( __( "Error: Unable to define {$info['label']} role", "e20rtextit" ), E_USER_ERROR );
				}
			}
		}
	}

	/**
	 * Autoloader class for the plugin.
	 *
	 * @param string $class_name Name of the class being attempted loaded.
	 */
	public function __class_loader( $class_name ) {

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

spl_autoload_register( array( e20rTextitIntegration::get_instance(), '__class_loader' ) );
register_activation_hook( __FILE__, 'e20rTextitIntegration::configureRoles' );

if ( ! class_exists( '\\PucFactory' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'plugin-updates/plugin-update-checker.php' );
}

$plugin_updates = \PucFactory::buildUpdateChecker(
	'https://eighty20results.com/protected-content/e20r-textit-integration/metadata.json',
	__FILE__,
	'e20r-textit-integration'
);
