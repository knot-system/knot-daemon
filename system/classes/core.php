<?php

class Postamt {

	// TODO: check, if we want those variables to be public:

	public $version;

	public $abspath;
	public $basefolder;
	public $baseurl;

	public $config;
	public $log;

	public $session;

	public $route;

	public $channels;
	
	function __construct() {

		global $postamt;
		$postamt = $this;

		$abspath = realpath(dirname(__FILE__)).'/';
		$abspath = preg_replace( '/system\/classes\/$/', '', $abspath );
		$this->abspath = $abspath;

		$basefolder = str_replace( 'index.php', '', $_SERVER['PHP_SELF']);
		$this->basefolder = $basefolder;

		if( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ) $baseurl = 'https://';
		else $baseurl = 'http://';
		$baseurl .= $_SERVER['HTTP_HOST'];
		$baseurl .= $basefolder;
		$this->baseurl = $baseurl;


		$this->version = get_postamt_version( $abspath );


		$this->config = new Config( $this );
		$this->log = new Log( $this );

	}

	function setup() {

		$this->session = new Session( $this );
			
		$this->route = new Route( $this );

		$this->refresh_cache();

		$this->channels = new Channels( $this );

		return $this;
	}

	function error( $error, $description, $status_code = 400 ) {

		http_response_code( $status_code );

		echo json_encode([
			'error' => $error,
			'error_description' => $description
		]);

		exit;
	}

	function debug( ...$messages ) {

		if( $this->config->get('logging') ) {
			$this->log->message( ...$messages );
		}

		if( $this->config->get('debug') ) {
			echo json_encode( [ 'error' => $messages ] );
		}

	}

	function include( $file_path, $args = array() ) {

		$postamt = $this;

		$full_file_path = $this->abspath.$file_path;

		if( ! file_exists($full_file_path) ) {
			$this->debug( 'include not found' );
			exit;
		}

		include( $full_file_path );

	}

	function version() {
		return $this->version;
	}


	function refresh_cache() {
		
		$postamt = $this;

		$cache = new Cache( false, false );

		$cache->clear_cache_folder();

	}

}
