<?php

$abspath = realpath(dirname(__FILE__)).'/';
$abspath = preg_replace( '/system\/$/', '', $abspath );


if( ! file_exists($abspath.'config.php') || ! file_exists($abspath.'.htaccess') ) {
	echo 'error, invalid config';
	exit;
}


include_once( $abspath.'system/functions.php' );
include_once( $abspath.'system/classes.php' );

$postamt = new Postamt( true );

if( empty($_GET['secret']) ) {
	$postamt->error( 'invalid_request', 'missing secret', 400, false );
}

$secret = $_GET['secret'];

$secret_option = $postamt->config->get('cron_secret');

if( $secret != $secret_option ) {
	$postamt->error( 'invalid_request', 'invalid secret', 400, false );
	exit;
}



$active_feeds = []; // NOTE: these are all the active feeds of _all_ users on this system


// TODO: add a filter parameter, to only refresh a specific user
$userfolders_obj = new Folder( $postamt->abspath.'content/' );
$userfolders = $userfolders_obj->get_subfolders();

if( empty($userfolders) ) {
	// no users exist yet
	exit;
}


foreach( $userfolders as $userfolder ) {

	// TODO: check, when the last user login was; if it was a long time, reduce the frequency of updates

	$channels_obj = new Channels( $postamt, $userfolder['path'] );

	$channels = $channels_obj->get( false, true );

	if( empty($channels) ) continue;

	foreach( $channels as $channel ) {

		$feeds_obj = new Feeds( $channel );
		$feeds = $feeds_obj->get( false, true );

		if( empty($feeds) ) continue;

		foreach( $feeds as $feed ) {
			$active_feeds[] = $feed;
		}

	}

}

if( empty($active_feeds) ) exit;


// TODO: build some type of queue, so we don't loop over _all_ active feeds every time, but split it up into multiple requests to cron

foreach( $active_feeds as $active_feed ) {
	$active_feed->refresh_posts();
	$active_feed->cleanup_posts();
}


exit;
