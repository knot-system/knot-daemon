<?php

// NOTE: in system/classes/core.php there is also the 'refresh_cache()' function
// that takes care of deleting old, obsolete cache files

class Cache {

	private $cache_folder = 'cache/';
	private $cache_file;

	public $type;
	public $name;
	public $hash;
	public $cache_file_name;
	public $filesize;
	public $lifetime;
	
	function __construct( $type, $input, $use_hash = false, $lifetime = false ) {

		// TODO: add config option to disable cache
		// TODO: add method to force a cache refresh?

		global $postamt;

		if( ! $type && ! $input ) return;

		if( $type == 'image' || $type == 'image-preview' ) {
			$this->cache_folder .= 'images/';
		} elseif( $type == 'link' ) {
			$this->cache_folder .= 'link-previews/';
		} elseif( $type == 'session' ) {
			$this->cache_folder .= 'sessions/';
		} else {
			$this->cache_folder .= $type.'/';
		}

		$this->check_cache_folder();

		$this->type = $type;

		if( $use_hash ) {

			$this->hash = $input;

		} else {

			$this->name = $input;

			$this->hash = get_hash( $this->name );

		}

		if( $lifetime ) { // lifetime is in seconds
			$this->lifetime = $lifetime;
		} else {
			$this->lifetime = $postamt->config->get( 'cache_lifetime' );
		}

		$this->cache_file_name = $this->get_file_name();

		$this->cache_file = $this->cache_folder.$this->cache_file_name;

	}


	function get_file_name(){

		global $postamt;

		$hash = $this->hash;

		$folderpath = $postamt->abspath.$this->cache_folder;

		$files = read_folder( $folderpath, false, false );

		foreach( $files as $filename ) {
			if( str_starts_with($filename, $hash) ) {
				return $filename;
			}
		}

		// no file yet, create new name:
		
		$target_timestamp = time() + $this->lifetime;

		$filename = $this->hash.'_'.$target_timestamp;

		return $filename;
	}


	function get_data() {
		if( ! file_exists($this->cache_file) ) return false;

		if( isset($_GET['refresh']) ) return false; // force a refresh

		$this->filesize = filesize($this->cache_file);

		$cache_content = file_get_contents($this->cache_file);

		return $cache_content;
	}


	function add_data( $data ) {
		global $postamt;

		if( ! file_put_contents( $postamt->abspath.$this->cache_file, $data ) ) {
			$postamt->debug( 'could not create cache file', $this->cache_file );
		}

		return $this;
	}


	function remove() {
		if( ! file_exists($this->cache_file) ) return;

		unlink($this->cache_file);
	}


	function touch() {
		if( ! file_exists($this->cache_file) ) return $this;

		touch( $this->cache_file );

		return $this;
	}


	private function check_cache_folder(){
		global $postamt;

		if( is_dir($postamt->abspath.$this->cache_folder) ) return;

		if( mkdir( $postamt->abspath.$this->cache_folder, 0777, true ) === false ) {
			$postamt->debug( 'could not create cache dir', $this->cache_folder );
		}

		return $this;
	}


	function clear_cache_folder(){
		// this function clears out old cache files.

		global $postamt;

		$lifetime = $postamt->config->get( 'cache_lifetime' );

		$folderpath = $postamt->abspath.'cache/';

		$files = read_folder( $folderpath, true );

		$current_timestamp = time();

		foreach( $files as $file ) {

			$file_explode = explode( '_', $file );
			$expire_timestamp = (int) end($file_explode);

			if( $expire_timestamp < $current_timestamp ) { // cachefile too old
				@unlink($file); // delete old cache file; fail silently
			}

		}
	}


};
