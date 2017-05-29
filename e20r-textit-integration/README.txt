=== Eighty/20 Results - TextIt/PMPro Service interface ===
Contributors: sjolshag
Tags: TextIt, memberships, paid memberships pro
Requires at least: 4.7
Tested up to: 4.7.5
Stable tag: 2.0.2

Eighty/20 Results - TextIt/PMPro Service interface

== Description ==
This plugin requires the Paid Memberships Pro plugin by Stranger Studios, LLC.
This plugin requires the Participants Database plugin

Integration between Paid Memberships Pro/Participants Database and the TextIt service. Allows register/cancel
operations plus pause/resume of TextIt services based on membership events or member action.

=== Filters ===

`e20r-textit-available-service-options` = Update/change the Service options for the TextIt/HowsU service (returns array)
`e20r_textit_update_everlive_service` = Whether to update the everlive service (returns boolean, false by default)
`e20r_textit_admin_email_addr` = Array of email addresses to send administrator email messages to (default is WordPress admin mail)
`e20r_textit_service_request_timeout` = The timeout value for AJAX service updates/requests
`e20r_textit_service_url_base` = The URL to the upstream TextIt API server to connect to
`textit_pdb_page_list` = The array of PDB pages being used/defined on the system for the checkout/management side of the user account
`e20r_textit_timewindow_variable_names` = The array of variables used on textit for the time windows specified in the user service
`e20r_textit_contact_column_map` = Maps PDB field names and TextIt fields/data
`e20r_textit_flow_settings_array` = Flow/Group settings for the available TextIt/HowsU services (Deprecated: Use options/settings page)
`e20r_textit_membership_levels` = Array of level names where the user needs to be subscribed/added to the TextIt service

== Installation ==

1. Upload the `e20r-textit-integration` directory to the `/wp-content/plugins/` directory of your site.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Changelog ==

== 2.0.2 ==

* BUG/FIX: Didn't include CSS in build

== 2.0.1 ==

* BUG/FIX: Didn't return fields from TextIt Service

== 2.0 ==

* BUG/FIX: Don't attempt to load User Service information record for user if the table doesn't exist
* BUG/FIX: Alignment issue for table(s) on checkout page
* BUG/FIX: Updated the DB record before knowing if the service update was successful
* ENHANCEMENT/FIX: Numerous updates to support v2 of the TextIt API
* ENHANCEMENT/FIX: Add mapping of PDB to TextIt User Fields on Settings -> HowsU Settings page
* ENHANCEMENT/FIX: Verify user's registration status on TextIt service before attempting to update/change data for them
* ENHANCEMENT/FIX: URN needs an array value (not the old text value) to work
* ENHANCEMENT/FIX: Better handling of UUIDs and Groups
* ENHANCEMENT/FIX: Dynamic mapping of user data fields to include in contact updates on TextIt service
* ENHANCEMENT/FIX: Block/Unblock user when pausing/resuming service
* ENHANCEMENT/FIX: Remove user data on TextIt service when blocking TextIt service
* ENHANCEMENT/FIX: Use URN info to update specific contact info upstream (avoids warnings/error messages)
* ENHANCEMENT/FIX: Updated error handling for updateTextItService() method
* ENHANCEMENT/FIX: Use more descriptive settings/options array name
* ENHANCEMENT/FIX: Style for Payment Info (Credit Card)
* ENHANCEMENT: Rename the loadOptions() member function to loadSettings()
* ENHANCEMENT: Save the options/settings for the HowsU/TextIt Integration
* ENHANCEMENT: Allow admin to specify the source table for the user's service information records
* ENHANCEMENT: Use AJAX to force refresh of the groups/settings
* ENHANCEMENT: Cache TextIt Groups/Flows on local server to minimize API requests
* ENHANCEMENT: Load Option page JavaScript and CSS when loading the option page (only)
* ENHANCEMENT: Add styling for the Options/Settings page
* ENHANCEMENT: Add options page w/default values matching existing configuration
* ENHANCEMENT: Adding version string to Stylesheet for Option page
* ENHANCEMENT: Allow admin to force a fetch of existing flows/groups on the TextIt API server
* ENHANCEMENT: Place the refresh flow/group button more prominently on the page
* ENHANCEMENT: Updated license per GPL requirements
* ENHANCEMENT: Redirect to login if user isn't logged in
* ENHANCEMENT: Cache all upstream data we use (force reload from Settings page if needed).
* ENHANCEMENT: Update TextIt API key if needed via Settings page
* ENHANCEMENT: Update TextIt User Fields -> Participants DB maps on Settings page

== 1.1.4 ==

* ENH/BUG: Add the edit-my-details page stub as valid pdb page

== 1.1.3 ==

* BUG: Fix debug output

== 1.1.2 ==

* ENH/BUG: Didn't always handle empty data properly in shortcodes

== 1.1.1 ==

* ENH: Fix typo

== 1.1 ==

* ENH: Languages stub
* ENH: Initial commit of license functionality.
* ENH: Add welcome message shortcode

== 1.0 ==

* Initial release

