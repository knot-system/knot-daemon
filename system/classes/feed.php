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

		if( ! empty($info['_original_url']) && $info['_original_url'] == $url ) return true;

		if( ! empty($info['_redirect_url']) && $info['_redirect_url'] == $url ) return true;

		return false; 
	}


	function refresh_posts() {
		// TODO: get new posts

		// if available, use _redirect_url, if that fails update the _redirect_url by querying the _url
		// if available, and $url fails, us _original_url to update the $url

		var_dump('refresh_posts');

		return $this;
	}


	function cleanup_posts() {
		// TODO: delete posts, that are older than a specific threshold; make the threshold a config option

		return $this;
	}


}
