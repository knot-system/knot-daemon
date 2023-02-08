<?php

$abspath = realpath(dirname(__FILE__)).'/';
$abspath = preg_replace( '/system\/$/', '', $abspath );


if( ! file_exists($abspath.'config.php')
 || ! file_exists($abspath.'.htaccess')
 || isset($_GET['setup'])
) {
	// run the setup if we are missing required files
	include_once( $abspath.'system/setup.php' );
} elseif( isset($_GET['update'])
 && ( file_exists($abspath.'update') || file_exists($abspath.'update.txt') )
) {
	// run the update if we request it
	include_once( $abspath.'system/update.php' );
	exit;
}


header("Content-type: application/json");


include_once( $abspath.'system/functions.php' );
include_once( $abspath.'system/classes.php' );


$postamt = new Postamt();


// here we gooo


$endpoint = $postamt->route->get('endpoint');
if( ! file_exists( $postamt->abspath.'system/endpoints/'.$endpoint.'.php') ){
	$postamt->debug( 'endpoint not found!', $endpoint );
	exit;
}

$postamt->include( 'system/endpoints/'.$endpoint.'.php' );
