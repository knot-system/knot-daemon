<?php


class Route {

	public $route;

	function __construct( $core ) {

		if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$request_type = 'post';
		} else if( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
			$request_type = 'get';
		} else {
			$core->error( 'invalid_request', 'unknown request method', null, null, $_SERVER['REQUEST_METHOD'] );
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

				$core->error( 'invalid_request', 'unknown action', null, null, $action );

			}

		} else {

			$core->error( 'invalid_request', 'no action provided' );

		}


		$scope_valid = $core->session->check_scope( $required_scopes );
		if( ! $scope_valid ) {
			$core->error( 'insufficient_scope', 'The scope of this token does not meet the requirements for this request', 403, null, $required_scopes );
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
