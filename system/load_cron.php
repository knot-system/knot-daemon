<?php

$abspath = realpath(dirname(__FILE__)).'/';
$abspath = preg_replace( '/system\/$/', '', $abspath );


if( ! file_exists($abspath.'config.php') || ! file_exists($abspath.'.htaccess') ) {
	echo 'error, invalid config';
	exit;
}


include_once( $abspath.'system/functions.php' );
include_once( $abspath.'system/classes.php' );

$core = new Core();

if( empty($_GET['secret']) ) {
	$core->error( 'invalid_request', 'missing secret', 400, false );
}

$secret = $_GET['secret'];

$secret_option = get_config('cron_secret');

if( $secret != $secret_option ) {
	$core->error( 'invalid_request', 'invalid secret', 400, false );
	exit;
}


// NOTE: use cron.php?me=.. to only refresh a specific user; use the foldername as the me parameter; this can be combined with the channel parameter, cron.php?me=..&channel=..
$me = false;
if( ! empty($_GET['me']) ) {
	$me = $_GET['me'];
	$me = str_replace( array('/', '\\', '.'), '', $me );
}

// NOTE: use cron.php?channel=.. to only refresh a specific channel; use the part after the foldername '[0-9]_' for the channel parameter (for example 'cron.php?channel=notifications' for the default Notifications channel); this can be combined with the me parameter, cron.php?me=..&channel=..
$channel = false;
if( ! empty($_GET['channel']) ) {
	$channel = $_GET['channel'];
}





if( $me ) {
	$me_folder_path = trailing_slash_it($core->abspath.'content/'.$me);

	if( ! is_dir( $me_folder_path ) ) {
		// user does not exist
		return false;
	}

	$userfolders_obj = new Folder( $me_folder_path );

	$userfolders = [
		$me => [
			'name' => $me,
			'path' => $me_folder_path
		]
	];

} else {
	$userfolders_obj = new Folder( $core->abspath.'content/' );
	$userfolders = $userfolders_obj->get_subfolders();	
}


if( empty($userfolders) ) {
	// no users exist yet
	return false;
}


$active_feeds = [];

foreach( $userfolders as $userfolder ) {

	$channels_obj = new Channels( $userfolder['path'] );

	if( $channel ) {
		$channels_obj->filter_channel( $channel );
	}

	$active_feeds = array_merge( $active_feeds, $channels_obj->get_active_feeds() );

}


refresh_feed_items( $active_feeds );


exit;
