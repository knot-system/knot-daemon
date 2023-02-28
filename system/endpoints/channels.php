<?php

if( ! $postamt ) exit;

$request_type = $postamt->route->get('request_type');

$channels = $postamt->channels->get();

if( $request_type == 'get' ) {

	if( ! is_array($channels) || ! count($channels) ) {
		$postamt->error( 'internal_server_error', 'no channels found', 500 );
	}

	echo json_encode([
		'channels' => $channels
	]);

	exit;

} elseif( $request_type == 'post' ) {

	$name = false;
	if( isset($_REQUEST['name']) ) {
		$name = $_REQUEST['name'];
	}

	$uid = false;
	if( isset($_REQUEST['channel']) ) {
		
		$method = 'update';

		$uid = $_REQUEST['channel'];

	} else {
		$method = 'create';
	}

	if( isset($_REQUEST['method']) ) {
		$method = $_REQUEST['method'];
	}

	if( $method == 'create' ) {

		if( ! $name ) {
			$postamt->error( 'invalid_request', 'missing channel name', null, null, $method );
		}

		// check if channel exists;
		if( $postamt->channels->channel_exists( false, $name ) ) {
			$postamt->error( 'internal_server_error', 'a channel with this name already exists', 500, null, $name, $method );
		}

		$channel = $postamt->channels->create_channel( $name );

		if( ! $channel ) {
			$postamt->error( 'internal_server_error', 'could not create channel', 500, null, $name, $method );
		}

		echo json_encode($channel);

	} elseif( $method == 'update' ) {
		
		if( ! $name ) {
			$postamt->error( 'invalid_request', 'missing channel name' );
		}

		if( ! $uid ) {
			$postamt->error( 'invalid_request', 'missing channel uid', null, null, $name, $method );
		}

		if( ! $postamt->channels->channel_exists( $uid ) ) {
			$postamt->error( 'invalid_request', 'this channel does not exist', null, null, $uid, $name, $method );
		}		

		$new_channel = $postamt->channels->update_channel( $uid, $name );

		if( ! $new_channel ) {
			$postamt->error( 'internal_server_error', 'could not update channel', 500, null, $uid, $name, $method );
		}

		echo json_encode($new_channel);

		exit;

	} elseif( $method == 'delete' ) {

		if( ! $uid ) {
			$postamt->error( 'invalid_request', 'missing channel uid' );
		}

		if( ! $postamt->channels->channel_exists( $uid ) ) {
			$postamt->error( 'invalid_request', 'this channel does not exist', null, null, $uid, $method );
		}

		if( ! $postamt->channels->delete_channel( $uid ) ) {
			$postamt->error( 'internal_server_error', 'could not delete channel', 500, null, $uid, $method );
		}

		exit;

	} elseif( $method == 'order' ) {

		if( empty($_REQUEST['channels']) ) {
			$postamt->error( 'invalid_request', 'no channels provided', null, null, $method );
		}

		$reorder_channels = $_REQUEST['channels'];

		if( ! is_array($reorder_channels) || count($reorder_channels) < 2 ) {
			$postamt->error( 'invalid_request', 'please provide at least 2 channels', null, null, $reorder_channels, $method );
		}

		if( ! $postamt->channels->reorder( $reorder_channels ) ) {
			$postamt->error( 'internal_server_error', 'could not reorder channels', 500, null, $reorder_channels, $method );
		}

		exit;

	} else {
		$postamt->error( 'invalid_request', 'invalid method', null, null, $method );
	}

	exit;

}

exit;
