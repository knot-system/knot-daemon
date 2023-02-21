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
		$postamt->error( 'invalid_request', 'the before parameter is not yet implemented' ); // DEBUG
	}
	$after = false;
	if( ! empty($_REQUEST['after']) ) {
		$after = $_REQUEST['after'];
		$postamt->error( 'invalid_request', 'the after parameter is not yet implemented' ); // DEBUG
	}

	$channel = $postamt->channels->get($channel_uid, true);

	if( ! $channel ) {
		$postamt->error( 'invalid_request', 'this channel does not exist' );
	}

	$feeds = new Feeds( $channel );

	if( ! $feeds ) {
		$postamt->error( 'invalid_request', 'no feeds found in this channel' );
	}

	$items = $feeds->get_items();

	// TODO: limit $items
	// TODO: pagination

	$json = [
		'items' => $items,
		'paging' => [
			'not_implemented' => 'paging is not yet implemented'
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
