<?php

class Request {

	private $user_agent;
	private $timeout;
	private $url;

	private $http_status_code;
	private $output;

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

		if( ! $this->output ) return false;

		return $this->output;
	}

	function curl_request( $force = false, $header = false, $nobody = false, $http_status_code = false, $followlocation = true ) {

		if( ! $this->url ) return false;

		if( $force || ! $this->output ) {

			$ch = curl_init( $this->url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

			if( $header ) curl_setopt( $ch, CURLOPT_HEADER, true );
			if( $nobody ) curl_setopt( $ch, CURLOPT_NOBODY, true );
			
			curl_setopt( $ch, CURLOPT_USERAGENT, $this->user_agent );
			
			if( $followlocation ) curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

			curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );

			$output = curl_exec( $ch );

			if( $http_status_code ) {
				$this->http_status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			}

			$this->output = $output;

			curl_close( $ch );
		}

		return $this;
	}


	function get_status_code() {
		return $this->http_status_code;
	}


	function get_output() {
		return $this->output;
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
