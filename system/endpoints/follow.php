<?php

if( ! $core ) exit;

$request_type = $core->route->get('request_type');

if( ! isset($_REQUEST['channel']) ) {
	$core->error( 'invalid_request', 'missing channel' );
}

$channel_uid = $_REQUEST['channel'];

$channel = $core->channels->get( $channel_uid, true );

if( ! $channel ) {
	$core->error( 'invalid_request', 'channel not found', null, null, $channel_uid );
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
		$core->error( 'invalid_request', 'missing url' );
	}

	$url = trim($_REQUEST['url']);

	if( $feeds->feed_exists( false, $url ) ) {
		$core->error( 'invalid_request', 'this url already exists in this channel' );
	}

	$new_feed = $feeds->create_feed( $url );

	if( ! $new_feed ) {
		$core->error( 'internal_server_error', 'could not add url to channel', null, null, $url );
	}

	$new_feed->refresh_posts();
	
	echo json_encode( $new_feed->get(true) );

	exit;

} else {
	exit;
}
