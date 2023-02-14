<?php

class Request {

	private $user_agent;
	private $timeout;
	private $url;

	private $body;

	function __construct() {

		global $postamt;

		$this->user_agent = 'maxhaesslein/postamt/'.$postamt->version();
		$this->timeout = 10;

	}

	function set_url( $url ) {
		$this->url = $url;

		return $this;
	}


	function get_body(){

		$this->curl_request();

		if( ! $this->body ) return false;

		return $this->body;
	}

	function curl_request( $force = false ) {

		if( ! $this->url ) return false;

		if( $force || ! $this->body ) {

			$ch = curl_init( $this->url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_USERAGENT, $this->user_agent );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );

			$this->body = curl_exec( $ch );
			curl_close( $ch );
		}

	}


	function get( $url, $query = false, $headers = [] ) {

		if( $query ) {
			$query_arr = [];
			foreach( $query as $key => $value ) {
				$query_arr[] = $key.'='.$value;
			}
			$url .= '?'.implode( '&', $query_arr );
		}

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		if( is_array($headers) && count($headers) ) curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		curl_setopt( $ch, CURLOPT_USERAGENT, $this->user_agent );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );

		$response = curl_exec($ch);

		curl_close( $ch );

		return $response;
	}

	function post( $url, $query = false, $headers = [] ) {

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		if( is_array($headers) && count($headers) ) curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		curl_setopt( $ch, CURLOPT_POST, true );

		if( $query ) curl_setopt( $ch, CURLOPT_POSTFIELDS, $query );

		curl_setopt( $ch, CURLOPT_USERAGENT, $this->user_agent );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );

		$response = curl_exec($ch);

		curl_close( $ch );

		return $response;
	}

}