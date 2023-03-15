<?php

// update: 2023-03-15

class IndieAuth {

	public $request;

	function __construct(){

	}


	function normalize_url( $url ) {
		return normalize_url($url, false);
	}


	function build_url( $parsed_url ) {
		return build_url($parsed_url);
	}


	function discover_endpoint( $name, $url ) {

		if( ! $this->url_is_valid($url) ) return false;

		$body = $this->request()->set_url($url)->curl_request()->get_body();

		if( ! $body ) return false;

		$dom = new Dom( $body );

		$endpoint = $dom->find( 'link', $name );

		if( ! $endpoint ) return false;

		return $endpoint;
	}


	function url_is_valid( $url ) {

		$url = parse_url( $url );

		if( ! $url ) return false;
		if( ! array_key_exists('scheme', $url) ) return false;
		if( ! in_array($url['scheme'], array('http','https')) ) return false;
		if( ! array_key_exists('host', $url) ) return false;
		
		return true;
	}


	function request(){
		if( ! $this->request ) {
			$this->request = new Request();
		}

		return $this->request;
	}

	
}
