<?php

class Feedsearch {

	private $query;
	private $results = [];

	function __construct( $query ) {

		$this->query = $query;

		$this->search();

	}

	function search() {

		$query = $this->query;

		$url = $this->normalize_url($query);

		if( ! $url ) return $this;

		$request = new Request( $url );

		$request->curl_request();

		$status_code = $request->get_status_code();

		if( $status_code != 200 ) {
			global $postamt;
			$postamt->error( 'bad_request', 'the page returned an invalid status_code', 400, true, $status_code, $url, $query );
		}

		$headers = $request->get_headers();
		$body = $request->get_body();

		if( ! $body ) {
			global $postamt;
			$postamt->error( 'bad_request', 'the page did not return an HTML body', 400, true, $status_code, $url, $query );
		}

		$dom = new Dom( $body );

		$feeds = $dom->find( 'link', 'alternate' );

		if( ! $feeds ) return $this;

		if( ! is_array($feeds) ) $feeds = array($feeds);

		$this->results = $feeds;

		return $this;
	}

	function get_results() {
		return $this->results;
	}

	function normalize_url( $url ) {
		return normalize_url( $url );
	}


	function build_url( $parsed_url ) {
		return build_url( $parsed_url );
	}

}
