<?php

// update: 2023-05-19


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

		global $core;

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
			$this->lifetime = get_config( 'cache_lifetime' );
		}

		$this->cache_file_name = $this->get_file_name();
		$this->cache_file = $this->cache_folder.$this->cache_file_name;

	}


	function get_file_name( $skip_existing = false ){

		global $core;

		$hash = $this->hash;

		if( ! $skip_existing ) {
			$folderpath = $core->abspath.$this->cache_folder;
			$files = read_folder( $folderpath, false, false );
			foreach( $files as $filename ) {
				if( str_starts_with($filename, $hash) ) {

					$current_timestamp = time();
					$expire_timestamp = $this->get_expire_timestamp( $filename );
					if( $expire_timestamp < $current_timestamp ) { // cachefile too old
						@unlink($folderpath.$file); // delete old cache file; fail silently
						break;
					}

					return $filename;
				}
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
		global $core;

		if( ! file_put_contents( $core->abspath.$this->cache_file, $data ) ) {
			$core->debug( 'could not create cache file', $this->cache_file );
		}

		return $this;
	}


	function remove() {
		if( ! file_exists($this->cache_file) ) return;

		unlink($this->cache_file);
	}


	function refresh_lifetime() {
		if( ! file_exists($this->cache_file) ) return $this;

		$old_filename = $this->get_file_name();
		$new_filename = $this->get_file_name(true);
		if( rename( $this->cache_folder.$old_filename, $this->cache_folder.$new_filename ) ) {
			$this->cache_file_name = $new_filename;
			$this->cache_file = $this->cache_folder.$this->cache_file_name;
		}

		return $this;
	}


	function get_remote_file( $url ) {

		$url = html_entity_decode($url);

		$ch = curl_init( $url );
		$fp = fopen( $this->cache_file, 'wb' );
		curl_setopt( $ch, CURLOPT_FILE, $fp );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_USERAGENT, get_user_agent() );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_exec( $ch );
		curl_close( $ch );
		fclose( $fp );

		return $this;
	}


	private function check_cache_folder(){
		global $core;

		if( is_dir($core->abspath.$this->cache_folder) ) return;

		if( mkdir( $core->abspath.$this->cache_folder, 0774, true ) === false ) {
			$core->debug( 'could not create cache dir', $this->cache_folder );
		}

		return $this;
	}


	function clear_cache_folder(){
		// this function clears out old cache files.

		global $core;

		$folderpath = $core->abspath.'cache/';

		$files = read_folder( $folderpath, true );

		$current_timestamp = time();

		foreach( $files as $file ) {

			$expire_timestamp = $this->get_expire_timestamp( $file );

			if( $expire_timestamp < $current_timestamp ) { // cachefile too old
				@unlink($file); // delete old cache file; fail silently
			}

		}
	}


	function get_expire_timestamp( $file ) {
		$file_explode = explode( '_', $file );
		$expire_timestamp = (int) end($file_explode);

		return $expire_timestamp;
	}


};
