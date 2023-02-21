<?php

// Spec: https://indieweb.org/Microsub-spec#Following

class Feed {

	private $id;
	private $path;
	private $info;

	function __construct( $path ) {

		$file = new File( $path.'_feed.txt' );

		$file_content = $file->get();

		if( ! $file_content ) return false;

		$this->path = $path;

		$this->id = $file_content['_id'];

		$this->info = $file_content;

	}


	function get_id() {
		return $this->id;
	}

	function get_path() {
		return $this->path;
	}


	function get_info( $key ) {
		if( ! array_key_exists($key, $this->info) ) return false;

		return $this->info[$key];
	}

	function get( $cleanup = false ) {

		$feed_info = $this->info;

		if( $cleanup ) {
			unset($feed_info['_id']);
			unset($feed_info['_path']);
			unset($feed_info['_original_url']);
			unset($feed_info['_redirect_url']);
		}

		return $feed_info;
	}


	function has_url( $url ) {
		if( $this->info['url'] == $url ) return true;

		if( ! empty($this->info['_original_url']) && $this->info['_original_url'] == $url ) return true;

		if( ! empty($this->info['_redirect_url']) && $this->info['_redirect_url'] == $url ) return true;

		return false; 
	}


	function refresh_posts() {

		$url = $this->get_info( 'url' );
		$redirect_url = $this->get_info( '_redirect_url' );
		$original_url = $this->get_info( '_original_url' );

		$request = false;
		$status_code = false;
		if( $redirect_url ) {
			// if available, use _redirect_url
			$request = new Request( $redirect_url );
			$request->curl_request( false );
			$status_code = $request->get_status_code();
		}

		if( $status_code != 200 ) {
			$request = new Request( $url );
			$request->curl_request( false );
			$status_code = $request->get_status_code();
			// TODO: if $redirect_url is set, update with new location; see Feeds->create_feed()
		}

		if( $status_code != 200 ) {
			// if available, and $url fails, us _original_url
			$request = new Request( $original_url );
			$request->curl_request( false );
			$status_code = $request->get_status_code();
			// TODO: maybe update $url with new location; see Feeds->create_feed()
		}

		if( $status_code != 200 ) {
			$this->import_error( 'invalid status code' );
			return false;
		}

		$headers = $request->get_headers();

		if( empty($headers['content-type']) ) {
			$this->import_error( 'no content-type' );
			return false;
		}

		$content_type = strtolower($headers['content-type']);

		$body = $request->get_body();

		if( str_contains($content_type, 'application/rss+xml') || str_contains($content_type, 'application/xml') ) {
			// handle rss feed

			$this->import_posts_rss( $body );

		} elseif( str_contains($content_type, 'application/json') ) {
			// handle json feed

			$this->import_posts_json( $body );

		} else {
			$this->import_error( 'invalid content-type' );
			return false;
		}

		return $this;
	}


	function import_posts_rss( $body ) {
		// TODO: import rss feed
	}

	function import_posts_json( $body ) {
		// TODO: import json feed

		$json = json_decode($body, true);

		if( $json === NULL ) {
			$this->import_error( 'invalid json' );
			return false; 
		}

		var_dump($json);

	}


	function import_error( $message ) {

		$id = $this->id;
		$path = $this->path;

		// TODO: log this
		// TODO: disable this feed if it fails multiple times

	}


	function cleanup_posts() {
		// TODO: delete posts, that are older than a specific threshold; make the threshold a config option

		return $this;
	}


}
