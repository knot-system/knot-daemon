<?php

// update: 2023-03-22


class Config {

	public $config = array();

	function __construct() {

		global $core;

		// load default config
		$this->load_config_file( $core->abspath.'system/config.php' );

		// NOTE: the file theme/{themename}/config.php may be loaded here; but because at this time we don't have the correct themename yet, this happens in system/classes/theme.php; after the theme config.php gets loaded, it gets overwritten by the local config again.
		
		// overwrite with custom local config
		$this->load_config_file( $core->abspath.'config.php' );

	}


	function load_config_file( $config_file ) {

		global $core;

		if( ! file_exists($config_file) ) {
			$core->debug( 'config file not found', $config_file );
			exit;
		}

		$config = include( $config_file );

		$this->config = array_merge( $this->config, $config );

		return $this;
	}
	

	function get( $option = false, $fallback = false ) {

		if( $option ) {
			if( ! array_key_exists( $option, $this->config ) ) {
				return $fallback;
			}
			return $this->config[$option];
		}

		// TODO: check if we want to allow to return all config options
		return $this->config;
	}

};
