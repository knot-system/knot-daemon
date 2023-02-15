<?php

if( ! $postamt ) exit;

$request_type = $postamt->route->get('request_type');

if( $request_type != 'post' ) {
	$postamt->error( 'invalid_request', 'only post accepted' );
}

if( ! isset($_REQUEST['channel']) ) {
	$postamt->error( 'invalid_request', 'missing channel' );
}

$channel_uid = $_REQUEST['channel'];

$channel = $postamt->channels->get( $channel_uid, true );

if( ! $channel ) {
	$postamt->error( 'invalid_request', 'channel not found' );
}

$feeds = new Feeds( $channel );

if( ! isset($_REQUEST['url']) ) {
	$postamt->error( 'invalid_request', 'missing url' );
}

$url = trim($_REQUEST['url']);

if( ! $feeds->feed_exists( false, $url ) ) {
	$postamt->error( 'invalid_request', 'this url does not exist in this channel' );
}

if( ! $feeds->remove_feed( $url ) ) {
	$postamt->error( 'internal_server_error', 'could not remove feed from channel', 500 );
}

exit;
