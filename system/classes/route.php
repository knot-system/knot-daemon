<?php

class Route {

	public $route;

	function __construct( $postamt ) {

		if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$request_type = 'post';
		} else if( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
			$request_type = 'get';
		} else {
			$postamt->error( 'invalid_request', 'unknown request method' );
		}


		$required_scopes = [ 'read' ];

		if( isset($_REQUEST['action']) ) {

			$action = $_REQUEST['action'];

			if( $action == 'channels' ) {

				if( $request_type == 'post' ) {
					$required_scopes[] = 'channels';
				}

			} elseif( $action == 'search' ) {

				//

			} elseif( $action == 'preview' ) {

				//

			} elseif( $action == 'follow' ) {

				$required_scopes[] = 'follow';

			} elseif( $action == 'unfollow' ) {

				$required_scopes[] = 'follow';

			} elseif( $action == 'timeline' ) {

				//

			} elseif( $action == 'mute' ) {

				$required_scopes[] = 'mute';

			} elseif( $action == 'unmute' ) {

				$required_scopes[] = 'mute';

			} elseif( $action == 'block' ) {

				$required_scopes[] = 'block';

			} elseif( $action == 'unblock' ) {

				$required_scopes[] = 'block';

			} else {

				$postamt->error( 'invalid_request', 'unknown action' );

			}

		} else {

			$postamt->error( 'invalid_request', 'no action provided' );

		}


		$scope_valid = $postamt->session->check_scope( $required_scopes );
		if( ! $scope_valid ) {
			$postamt->error( 'insufficient_scope', 'The scope of this token does not meet the requirements for this request', 403 );
		}
		

		$this->route = array(
			'endpoint' => $action,
			'required_scopes' => $required_scopes,
			'request_type' => $request_type
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
