<?php

if( ! $postamt ) exit;

$request_type = $postamt->route->get('request_type');

if( $request_type == 'get' ) {

	if( empty($_REQUEST['channel']) ) {
		$postamt->error( 'invalid_request', 'missing channel name' );
	}

	$channel_uid = $_REQUEST['channel'];
		
	$before = false;
	if( ! empty($_REQUEST['before']) ) {
		$before = $_REQUEST['before'];
	}
	$after = false;
	if( ! empty($_REQUEST['after']) ) {
		$after = $_REQUEST['after'];
	}

	$channel = $postamt->channels->get($channel_uid, true);

	if( ! $channel ) {
		$postamt->error( 'invalid_request', 'this channel does not exist' );
	}

	$feeds = new Feeds( $channel );

	if( ! $feeds ) {
		$postamt->error( 'invalid_request', 'no feeds found in this channel' );
	}

	list( 'before' => $next_before, 'after' => $next_after, 'items' => $items ) = $feeds->get_items( $before, $after );

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

	$postamt->error( 'not_implemented', 'this endpoint is not yet implemented' );

	exit;

} else {
	exit;
}
