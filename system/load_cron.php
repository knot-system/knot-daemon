<?php

$abspath = realpath(dirname(__FILE__)).'/';
$abspath = preg_replace( '/system\/$/', '', $abspath );


if( ! file_exists($abspath.'config.php') || ! file_exists($abspath.'.htaccess') ) {
	echo 'error, invalid config';
	exit;
}


include_once( $abspath.'system/functions.php' );
include_once( $abspath.'system/classes.php' );

$postamt = new Postamt( true );

if( empty($_GET['secret']) ) {
	echo 'please provide a secret';
	exit;
}

$secret = $_GET['secret'];

$secret_option = $postamt->config->get('cron_secret');

if( $secret != $secret_option ) {
	echo 'wrong secret';
	exit;
}

// TODO: cron stuff ...

exit;
