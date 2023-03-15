<?php

if( ! $core ) exit;

$request_type = $core->route->get('request_type');

if( $request_type != 'post' ) {
	$core->error( 'invalid_request', 'only post requests accepted', null, null, $request_type );
}

if( ! isset($_REQUEST['channel']) ) {
	$core->error( 'invalid_request', 'missing channel' );
}

$channel_uid = $_REQUEST['channel'];

$channel = $core->channels->get( $channel_uid, true );

if( ! $channel ) {
	$core->error( 'invalid_request', 'channel not found', null, null, $channel_uid );
}

$feeds = new Feeds( $channel );

if( ! isset($_REQUEST['url']) ) {
	$core->error( 'invalid_request', 'missing url', null, null, $channel_uid, $channel );
}

$url = trim($_REQUEST['url']);

if( ! $feeds->feed_exists( false, $url ) ) {
	$core->error( 'invalid_request', 'this url does not exist in this channel', null, null, $url, $channel_uid, $channel );
}

if( ! $feeds->remove_feed( $url ) ) {
	$core->error( 'internal_server_error', 'could not remove feed from channel', 500, null, $url, $channel_uid, $channel );
}

exit;
