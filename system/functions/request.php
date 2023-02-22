<?php


function request_post( $url, $headers = array() ){

	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $ch, CURLOPT_USERAGENT, get_user_agent() );
	$response = array();
	parse_str( curl_exec($ch), $response );
	curl_close( $ch );

	return $response;
}


function get_remote_json( $url, $headers = array() ) {

	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $ch, CURLOPT_USERAGENT, get_user_agent() );
	$response = curl_exec($ch);
	curl_close( $ch );

	$json = json_decode($response);

	return $json;
}


function request_get_remote( $url, $headers = array() ) {

	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_USERAGENT, get_user_agent() );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	$response = curl_exec( $ch );
	curl_close( $ch );

	return $response;
}


function get_user_agent(){

	global $postamt;
	if( $postamt ) {
		$version = $postamt->version();
	} else {
		global $abspath;
		$version = get_postamt_version( $abspath );
	}

	return 'maxhaesslein/postamt/'.$version;
}
