<?php

if( ! $postamt ) exit;

$request_type = $postamt->route->get('request_type');

if( ! isset($_REQUEST['channel']) ) {
	$postamt->error( 'invalid_request', 'missing channel' );
}

$channel_uid = $_REQUEST['channel'];

$channel = $postamt->channels->get( $channel_uid, true );

if( ! $channel ) {
	$postamt->error( 'invalid_request', 'channel not found' );
}

$feeds = new Feeds( $channel );

if( $request_type == 'get' ) {

	$feedlist = $feeds->get();

	$items = [];

	foreach( $feedlist as $feed ) {
		$items[] = $feed->get( true );
	}

	echo json_encode([
		'items' => $items
	]);

	exit;

} elseif( $request_type == 'post' ) {

	if( ! isset($_REQUEST['url']) ) {
		$postamt->error( 'invalid_request', 'missing url' );
	}

	$url = trim($_REQUEST['url']);

	if( $feeds->feed_exists( false, $url ) ) {
		$postamt->error( 'invalid_request', 'this url already exists in this channel' );
	}

	$new_feed = $feeds->create_feed( $url );

	if( ! $new_feed ) {
		$postamt->error( 'internal_server_error', 'could not add url to channel', 500 );
	}
	
	echo json_encode( $new_feed->get(true) );

	exit;

} else {
	exit;
}
