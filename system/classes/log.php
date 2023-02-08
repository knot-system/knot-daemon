<?php

class Log {

	private $log_filepath;

	function __construct( $postamt ) {

		if( ! $postamt->config->get('logging') ) return;

		$log_filepath = $postamt->abspath.'log/';

		if( ! is_dir( $log_filepath) ) {
			mkdir( $log_filepath, 0777, true );
			if( ! is_dir( $log_filepath) ) return;
		}

		$this->log_filepath = $log_filepath;

	}

	function message( ...$messages ) {

		if( ! $this->log_filepath ) return;

		$timestamp = time();
		$date = date( 'Y-m-d H:i:s', $timestamp );

		$log_append = '['.$date."]\r\n";
		foreach( $messages as $message ) {
			$log_append .= $message."\r\n";
		}

		$log_append .= "\r\n\r\n";

		$this->write_logfile( $log_append );

	}

	function write_logfile( $log_append ) {

		$log_filename = $this->log_filepath.date('Y-m-d', time()).'.txt';

		file_put_contents( $log_filename, $log_append, FILE_APPEND );

	}

}
