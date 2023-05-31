<?php


class IndieAuth {

	private $indieauth_metadata = NULL;
	private $requests = [];

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

		if( $name != 'indieauth-metadata' && $this->indieauth_metadata === NULL ) {

			$this->indieauth_metadata = false;

			$indieauth_metadata = $this->discover_endpoint( 'indieauth-metadata', $url );
			if( $indieauth_metadata ) {

				$request = $this->request($indieauth_metadata);
				$body = $request->get_body();

				if( $body ) {
					$json = json_decode($body, true);
					if( is_array($json) ) {
						$this->indieauth_metadata = $json;
					}
				}
			}
		}

		if( $this->indieauth_metadata ) {
			$metadata = $this->get_metadata( $name );
			if( $metadata ) return $metadata;
		}

		$request = $this->request( $url );

		$headers = $request->get_headers();
		if( ! empty($headers['link']) ) {
			// endpoint provided via http 'link' header
			$links = explode(',', $headers['link']);
			$links = array_map( 'trim', $links );

			foreach( $links as $link ) {
				if( preg_match( '/\<(.*?)\>.*?rel="'.$name.'"/i', $link, $matches ) ) {
					return( $matches[1] );
				}
			}
		}

		// endpoint may be provided via <link rel=".." href=".."> metatag

		$body = $request->get_body();

		if( ! $body ) return false;

		$dom = new Dom( $body );

		$endpoints = $dom->find_elements( 'link' )->filter_elements( 'rel', $name )->return_elements( 'href' );

		if( empty($endpoints) ) return false;

		$endpoint = $endpoints[0];

		if( ! $endpoint ) return false;

		return $endpoint;
	}


	function get_metadata( $endpoint ) {
		if( ! $this->indieauth_metadata ) return false;

		if( ! array_key_exists( $endpoint, $this->indieauth_metadata ) ) return false;

		return $this->indieauth_metadata[$endpoint];
	}


	function url_is_valid( $url ) {

		$url = parse_url( $url );

		if( ! $url ) return false;
		if( ! array_key_exists('scheme', $url) ) return false;
		if( ! in_array($url['scheme'], array('http','https')) ) return false;
		if( ! array_key_exists('host', $url) ) return false;
		
		return true;
	}


	function request( $url ){

		if( ! array_key_exists( $url, $this->requests ) ) {
			$this->requests[$url] = new Request($url);
			$this->requests[$url]->curl_request();
		}

		return $this->requests[$url];
	}

	
}
