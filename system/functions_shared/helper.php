<?php

// update: 2023-05-19


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


function get_class_attribute( $classes ) {

	if( ! is_array( $classes ) ) $classes = explode( ' ', $classes );

	$classes = array_unique( $classes ); // remove double class names
	$classes = array_filter( $classes ); // remove empty class names

	if( ! count($classes) ) return '';

	return ' class="'.implode( ' ', $classes ).'"';
}


function sanitize_string_for_url( $string ) {

	// remove non-printable ASCII
	$string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);

	$string = mb_strtolower($string);

	$string = str_replace(array("ä", "ö", "ü", "ß"), array("ae", "oe", "ue", "ss"), $string);

	// replace special characters with '-'
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


function read_folder( $folderpath, $recursive = false, $return_folderpath = true ) {

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
					$files = array_merge( $files, read_folder($folderpath.$file.'/', $recursive, $return_folderpath));
				}

				continue;
			}

			if( $return_folderpath ) $files[] = $folderpath.$file;
			else $files[] = $file;

		}
		closedir($handle);
	} else {
		$core->debug( 'could not open dir', $folderpath );
		return array();
	}

	return $files;
}


function get_config( $option, $fallback = false ) {
	global $core;

	return $core->config->get( $option, $fallback );
}
