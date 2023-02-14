<?php


function get_postamt_version( $abspath ){
	return trim(file_get_contents($abspath.'system/version.txt'));
}


function url( $path = '', $trailing_slash = true ) {
	global $postamt;
	
	$path = $postamt->baseurl.$path;

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


function read_folder( $folderpath, $recursive = false ) {

	global $postamt;

	$files = [];

	if( ! is_dir( $folderpath ) ) {
		$postamt->debug( $folderpath.' is no directory' );
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
		$postamt->debug( 'could not open dir', $folderpath );
		return array();
	}

	return $files;
}
