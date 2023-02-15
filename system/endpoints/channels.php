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

		if( isset($_REQUEST['method']) ) {
			$method = $_REQUEST['method'];
		}

	} else {
		$method = 'create';
	}


	if( $method == 'create' ) {

		if( ! $name ) {
			$postamt->error( 'invalid_request', 'missing channel name' );
		}

		// check if channel exists;
		if( $postamt->channels->channel_exists( false, $name ) ) {
			$postamt->error( 'internal_server_error', 'a channel with this name already exists', 500 );
		}

		$channel = $postamt->channels->create_channel( $name );

		if( ! $channel ) {
			$postamt->error( 'internal_server_error', 'could not create channel', 500 );
		}

		echo json_encode($channel);

	} elseif( $method == 'update' ) {
		
		if( ! $name ) {
			$postamt->error( 'invalid_request', 'missing channel name' );
		}

		if( ! $uid ) {
			$postamt->error( 'invalid_request', 'missing channel uid' );
		}

		if( ! $postamt->channels->channel_exists( $uid ) ) {
			$postamt->error( 'invalid_request', 'this channel does not exist' );
		}		

		$new_channel = $postamt->channels->update_channel( $uid, $name );

		if( ! $new_channel ) {
			$postamt->error( 'internal_server_error', 'could not update channel', 500 );
		}

		echo json_encode($new_channel);

		exit;

	} elseif( $method == 'delete' ) {

		if( ! $uid ) {
			$postamt->error( 'invalid_request', 'missing channel uid' );
		}

		if( ! $postamt->channels->channel_exists( $uid ) ) {
			$postamt->error( 'invalid_request', 'this channel does not exist' );
		}

		if( ! $postamt->channels->delete_channel( $uid ) ) {
			$postamt->error( 'internal_server_error', 'could not delete channel', 500 );
		}

		exit;

	} elseif( $method == 'order' ) {

		// TODO
		$postamt->error( 'not_implemented', 'this method is not yet implemented' );

	} else {
		$postamt->error( 'invalid_request', 'invalid method' );
	}

	exit;

}

exit;
