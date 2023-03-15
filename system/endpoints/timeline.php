<?php

if( ! $core ) exit;

$request_type = $core->route->get('request_type');

if( $request_type == 'get' ) {

	if( empty($_REQUEST['channel']) ) {
		$core->error( 'invalid_request', 'missing channel name' );
	}

	$channel_uid = $_REQUEST['channel'];

	$channel = $core->channels->get($channel_uid, true);

	if( ! $channel ) {
		$core->error( 'invalid_request', 'this channel does not exist', null, null, $channel_uid );
	}

	$feeds = new Feeds( $channel );

	if( ! $feeds ) {
		$core->error( 'invalid_request', 'no feeds found in this channel', null, null, $channel_uid, $channel );
	}


	$refresh_on_connect = $core->config->get('refresh_on_connect');
	if( $refresh_on_connect ) {
		$active_feeds = $feeds->get( false, true );
		refresh_feed_items( $active_feeds );
	}
	

	$before = false;
	if( ! empty($_REQUEST['before']) ) {
		$before = $_REQUEST['before'];
	}

	$after = false;
	if( ! empty($_REQUEST['after']) ) {
		$after = $_REQUEST['after'];
	}

	$limit = $core->config->get( 'item_limit_count' );
	if( ! empty($_REQUEST['limit']) ) {
		$limit = (int) $_REQUEST['limit'];
	}


	list( 'before' => $next_before, 'after' => $next_after, 'items' => $items ) = $feeds->get_items( $before, $after, $limit );

	$json = [
		'items' => array_values($items), // remove keys from $items
		'paging' => [
			'before' => $next_before,
			'after' => $next_after
		]
	];

	echo json_encode($json);
	exit;

} elseif( $request_type == 'post' ) {

	$core->error( 'not_implemented', 'this endpoint is not yet implemented' );

	exit;

} else {
	exit;
}
