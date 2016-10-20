<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 04/10/2015
 * Time: 12:27
 */

/*
 * template for displaying Customer Information
 *
 * single record template
 */
global $wpdb, $current_user;

if ( WP_DEBUG ) {
    error_log( "Loading StatusDetail data for user ID: {$current_user->ID} and record: {$_REQUEST['pdb']}" );
}

// get the template object for this record
$record = new PDb_Template($this);

$ti = e20rTextitIntegration::get_instance();
$ti->getUserRecord( $current_user->ID, true);

//var_dump($current_user->membership_level);
//var_dump($_SESSION['userDetail']);
if (!$current_user->membership_level->subscription_id) {
    $statusCode = 1;
} else {

    $status = isset($_POST['status'] ) ? intval( $_POST['status'] ) : null;
    unset ($_POST['status']);

    switch( $status ) {

        case 1:
            $ti->updateUserRecord( $current_user->ID, array('status' => 2), array('user_id' => $current_user->user_email) );
            $statusCode = 2;
            $ti->pauseTextItService( $current_user->ID );
            break;

        case 2:
            $ti->updateUserRecord( $current_user->ID, array('status' => 1), array('user_id' => $current_user->user_email) );
            $statusCode = 1;
            $ti->resumeTextItService( $current_user->ID );
            break;

        default:
            $statusCode = $record->record->service->fields->status->value;
    }
}

$user_info = $ti->getUserRecord( $current_user->ID, true );

// Show error messages/warnings
$util = e20rUtils::get_instance();
$util->display_notice();

?>
    <div class="wrap <?php echo $this->wrap_class ?>">
        <?php wp_nonce_field('e20r_pdb_update', 'e20r-pdb-nonce'); ?>
        <table width="100%">
            <tr>
                <td width="50%"><strong>Service Status:</strong></td>
                <form action="" method="post">
                    <?php if ($statusCode == 1) { ?>
                        <td width="25%"><img src="<?php echo get_stylesheet_directory_uri()  . '/images/greenDot.jpg' ?>" width="25"
                                             height="25"/></td><input
                            type="hidden" name="status" value="1"/>
                        <td width="25%">
                        <?php if ($current_user->membership_level->subscription_id && $statusCode) { ?>
                            <button type="submit" class="e20r-pdb-pause-button" style="width: 150px !important">Pause</button>
                        <?php } ?>
                        </td>
                    <?php } else { ?>
                        <td width="25%"><img src="<?php echo get_stylesheet_directory_uri()  . '/images/redDot.jpg'; ?>" width="25" height="25"/>
                        </td>
                        <td width="25%"><input type="hidden" name="status" value="2"/>
                        <?php if ($current_user->membership_level->subscription_id && $statusCode) { ?>
                            <button type="submit" class="e20r-pdb-pause-button" style="width: 150px !important">Start</button>
                        <?php } ?>
                        </td><?php } ?>
                </form>
            </tr>
            <tr>
                <td width="40%"><strong>Service Level:</strong></td>
                <td><?php if ( isset( $current_user->membership_level->subscription_id ) ) {
                        echo $record->record->service->fields->service_level->value;
                    } else {
                        echo 'Free';
                    } ?></td>
                <td><a class="button" style="background-color: #193356!important;"
                       href="/?page_id=428&pdb=<?php echo $user_info->id ?>" name="level">Change</a></td>
            </tr>
            <?php if ($current_user->membership_level->subscription_id) { ?>
                <?php if ($record->record->service->fields->service_level->value == "weekend") { ?>
                    <tr>
                        <td width="40%"><strong>Time Window:</strong></td>
                        <td><?php echo $record->record->service->fields->time_window->value ?></td>
                        <td><a class="button" href="/?page_id=563&pdb=<?php echo $user_info->id ?>"
                               name="level">Change</a>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($record->record->service->fields->service_level->value == "guardianangel") { ?>
                    <tr>
                        <td width="40%"><strong>Time Window:</strong></td>
                        <td><?php echo $record->record->service->fields->time_window->value ?></td>
                        <td><a class="button" href="<?php echo add_query_arg( 'pdb', $user_info->id, site_url( '/membership-upgrade/' ) ); ?>"
                               name="level">Change</a>
                        </td>
                    </tr>
                    <tr>
                        <td width="40%"><strong>Time Window (Second):</strong></td>
                        <td><?php echo $record->record->service->fields->time_window_2->value ?></td>
                        <td><a class="button" href="<?php echo add_query_arg( 'pdb', $user_info->id, site_url( '/membership-upgrade/' ) ); ?>"
                               name="level">Change</a>
                        </td>
                    </tr>
                    <tr>
                        <td width="40%"><strong>Time Window (Third):</strong></td>
                        <td><?php echo $record->record->service->fields->time_window_3->value ?></td>
                        <td><a class="button" href="<?php echo add_query_arg( 'pdb', $user_info->id, site_url( '/membership-upgrade/' ) ); ?>"
                               name="level">Change</a>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($record->record->service->fields->service_level->value == "guardian") { ?>
                    <tr>
                        <td width="40%"><strong>Time Window:</strong></td>
                        <td><?php echo $record->record->service->fields->time_window->value ?></td>
                        <td><a class="button" href="<?php echo add_query_arg( 'pdb', $user_info->id, site_url( '/membership-upgrade/' ) ); ?>"
                               name="level">Change</a>
                        </td>
                    </tr>
                    <tr>
                        <td width="40%"><strong>Time Window (Second):</strong></td>
                        <td><?php echo $record->record->service->fields->time_window_2->value ?></td>
                        <td><a class="button" href="<?php echo add_query_arg( 'pdb', $user_info->id, site_url( '/membership-upgrade/' ) ); ?>"
                               name="level">Change</a>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($record->record->service->fields->service_level->value == "protector") { ?>
                    <tr>
                        <td width="40%"><strong>Time Window:</strong></td>
                        <td><?php echo $record->record->service->fields->time_window->value ?></td>
                        <td><a class="button" href="<?php echo add_query_arg( 'pdb', $user_info->id, site_url( '/membership-upgrade/' ) ); ?>"
                               name="level">Change</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } ?>
        </table>
    </div>
<?php

/*
function stopService($wpdb)
{
    $groupArray = array();
    $myrows = $wpdb->get_results("SELECT * FROM xwdi_participants_database WHERE Id = " . $_REQUEST['pdb']);

    $group1 = $myrows[0]->service_level . substr($myrows[0]->time_window, 0, 2);
    $group2 = $myrows[0]->service_level . substr($myrows[0]->time_window_2, 0, 2);
    $group3 = $myrows[0]->service_level . substr($myrows[0]->time_window_3, 0, 2);
    $groupArray = array($group1, $group2, $group3);

    $data = array(
        'urns' => 'tel:' . $myrows[0]->service_number,
        'groups' => []
    );
    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => json_encode($data),
            'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n" . "Authorization: Token 7964392e969ce3aa258906f8e864380c2d058841\r\n"
        )
    );

    $url = 'https://api.textit.in/api/v1/contacts.json';
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);
    //print_r($result);
    return $response;
}

function startService($wpdb)
{
    $groupArray = array();
    $myrows = $wpdb->get_results("SELECT * FROM xwdi_participants_database WHERE Id = " . $_GET['pdb']);
    $level = '';

    switch($myrows[0]->service_level) {
        case "Protector":
            $level = 'protector';
            break;
        case "Guardian":
            $level = 'guardian';
            break;
        case "Guardian Angel":
            $level = 'guardianangel';
            break;
        case "Weekend":
            $level = 'weekend';
            break;
    }

    $group1 = $level . substr($myrows[0]->time_window, 0, 2);
    $group2 = $level . substr($myrows[0]->time_window_2, 0, 2);
    $group3 = $level . substr($myrows[0]->time_window_3, 0, 2);
    $groupArray = array($group1, $group2, $group3);
    $data = array(
        'urns' => 'tel:' . $myrows[0]->service_number,
        'groups' => $groupArray
    );
    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => json_encode($data),
            'header' => "Content-Type: application/json\r\n" . "Accept: application/json\r\n" . "Authorization: Token 7964392e969ce3aa258906f8e864380c2d058841\r\n"
        )
    );

    $url = 'https://api.textit.in/api/v1/contacts.json';
    //print_r($data);
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);
    //print_r($result);
    return $response;
}

*/
?>