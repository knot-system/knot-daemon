<?php

class Route {

	public $route;

	function __construct( $postamt ) {

		$request = $_SERVER['REQUEST_URI'];
		$request = preg_replace( '/^'.preg_quote($postamt->basefolder, '/').'/', '', $request );

		$query_string = false;

		$request = explode( '?', $request );
		if( count($request) > 1 ) $query_string = $request[1];
		$request = $request[0];

		$request = explode( '/', $request );

		$pagination = 0;


		if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$request_type = 'post';
		} else if( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
			$request_type = 'get';
		} else {
			$postamt->error( 'invalid_request', 'unknown request method' );
		}

		$required_scopes = [ 'read' ];

		if( isset($_REQUEST['action']) ) {
			if( $_REQUEST['action'] == 'channels' ) {
				// TODO
				$required_scopes[] = 'channels';
			} elseif( $_REQUEST['action'] == 'search' ) {
				// TODO
			} elseif( $_REQUEST['action'] == 'preview' ) {
				// TODO
			} elseif( $_REQUEST['action'] == 'follow' ) {
				// TODO
				$required_scopes[] = 'follow';
			} elseif( $_REQUEST['action'] == 'unfollow' ) {
				// TODO
				$required_scopes[] = 'follow';
			} elseif( $_REQUEST['action'] == 'timeline' ) {
				// TODO
			} elseif( $_REQUEST['action'] == 'mute' ) {
				// TODO
				$required_scopes[] = 'mute';
			} elseif( $_REQUEST['action'] == 'unmute' ) {
				// TODO
				$required_scopes[] = 'mute';
			} elseif( $_REQUEST['action'] == 'block' ) {
				// TODO
				$required_scopes[] = 'block';
			} elseif( $_REQUEST['action'] == 'unblock' ) {
				// TODO
				$required_scopes[] = 'block';
			} else {
				$postamt->error( 'invalid_request', 'unknown action' );
			}
		} else {
			$postamt->error( 'invalid_request', 'no action provided' );
		}

		$this->route = array(
			'endpoint' => 'index',
			'required_scopes' => $required_scopes,
		);
		

		return $this;
	}

	function get( $name = false ) {

		if( $name ) {

			if( ! is_array($this->route) ) return false;

			if( ! array_key_exists($name, $this->route) ) return false;

			return $this->route[$name];
		}

		return $this->route;
	}
	
}
