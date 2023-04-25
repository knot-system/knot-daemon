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

		// check, if $url is already a valid feed
		$feedPreview = new FeedPreview( $url );
		if( $feedPreview->is_valid_feed() ) {
			$this->results = [$url];
			return $this;
		}

		// if not, check the html to find linked feeds:

		$request = new Request( $url );

		$request->curl_request();

		$status_code = $request->get_status_code();

		if( $status_code != 200 ) {
			global $core;
			$core->error( 'bad_request', 'the page returned an invalid status_code', 400, true, $status_code, $url, $query );
		}

		$headers = $request->get_headers();
		$body = $request->get_body();

		if( ! $body ) {
			global $core;
			$core->error( 'bad_request', 'the page did not return an HTML body', 400, true, $status_code, $url, $query );
		}

		$dom = new Dom( $body );

		$feeds = $dom->find( 'link', 'alternate' );
		// TODO: only consider type="application/rss+xml" and type="application/json" (and maybe other) -- if we consider all rel="alternate", we also get things like /wp-json/ urls

		if( ! $feeds ) return $this;

		if( ! is_array($feeds) ) $feeds = array($feeds);

		$cleaned_feeds = [];
		foreach( $feeds as $feed ) {

			$cleaned_feed = $feed;

			if( str_starts_with($cleaned_feed, '//') ) {
				$cleaned_feed = 'http:'.$cleaned_feed;
			} elseif( ! str_starts_with($cleaned_feed, 'http') ) {
				$cleaned_feed = $url.ltrim($cleaned_feed, '/');
			}

			$cleaned_feeds[] = $cleaned_feed;
		}

		$this->results = $cleaned_feeds;

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
