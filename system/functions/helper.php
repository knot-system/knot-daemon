<?php

// update: 2023-03-15


function get_system_version( $abspath ){
	return trim(file_get_contents($abspath.'system/version.txt'));
}


function url( $path = '', $trailing_slash = true ) {
	global $core;
	
	$path = $core->baseurl.$path;

	if( $trailing_slash ) {
		$path = trailing_slash_it($path);
	}
	
	return $path;
}


function trailing_slash_it( $string ){
	// add a slash at the end, if there isn't already one ..

	$string = preg_replace( '/\/*$/', '', $string );
	$string .= '/';

	return $string;
}


function un_trailing_slash_it( $string ) {
	// remove slash at the end

	$string = preg_replace( '/\/*$/', '', $string );

	return $string;
}


function sanitize_string_for_url( $string ) {

	// Entferne alle nicht druckbaren ASCII-Zeichen
	$string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);

	$string = mb_strtolower($string);

	$string = str_replace(array("ä", "ö", "ü", "ß"), array("ae", "oe", "ue", "ss"), $string);

	// Ersetze Sonderzeichen durch '-'
	$string = preg_replace('/[^\p{L}\p{N}]+/u', '-', $string);

	$string = trim($string, '-');
	
	return $string;
}


function sanitize_folder_name( $string ) {

	$string = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '-', $string);
	$string = mb_ereg_replace("([\.]{1,})", '-', $string);

	return $string;
}


function get_hash( $input ) {
	// NOTE: this hash is for data validation, NOT cryptography!
	// DO NOT USE FOR CRYPTOGRAPHIC PURPOSES


	// TODO: check if we want to create the hash like this
	$hash = hash( 'tiger128,3', $input );

	return $hash;
}


function streamline_date( $input ) {

	$date = strtotime($input);

	if( $date === false ) {
		global $core;
		$core->debug( 'could not convert date', $input );
	}

	$return = date('c', $date);

	return $return;
}


function read_folder( $folderpath, $recursive = false ) {

	global $core;

	$files = [];

	if( ! is_dir( $folderpath ) ) {
		$core->debug( $folderpath.' is no directory' );
		return array();
	}

	$filename = false;
	if( $handle = opendir($folderpath) ){
		while( false !== ($file = readdir($handle)) ){
			if( substr($file,0,1) == '.' ) continue; // skip hidden files, ./ and ../

			if( is_dir($folderpath.$file) ) {

				if( $recursive ) {
					$files = array_merge( $files, read_folder($folderpath.$file.'/', $recursive));
				}

				continue;
			}

			$files[] = $folderpath.$file;

		}
		closedir($handle);
	} else {
		$core->debug( 'could not open dir', $folderpath );
		return array();
	}

	return $files;
}


function refresh_feed_items( $active_feeds ){

	if( empty($active_feeds) ) return false;

	// TODO: build some type of queue, so we don't loop over _all_ active feeds every time, but split it up into multiple requests to cron

	foreach( $active_feeds as $active_feed ) {
		$active_feed->refresh_posts();
		$active_feed->cleanup_posts();
	}

	return true;
}
