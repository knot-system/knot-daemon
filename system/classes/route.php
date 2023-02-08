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

		
		$this->route = array(
			'endpoint' => 'index'
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
