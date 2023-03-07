<?php

if( ! $postamt ) exit;

$request_type = $postamt->route->get('request_type');

if( $request_type != 'post' ) {
	$postamt->error( 'invalid_request', 'only post requests accepted', null, null, $request_type );
}

if( empty($_REQUEST['query']) ) {
	$postamt->error( 'invalid_request', 'query parameter must be provided' );	
}

$query = $_REQUEST['query'];

$search = new Feedsearch( $query );
$results = $search->get_results();

$feeds = [];

foreach( $results as $result ) {
	$feeds[] = [
		'type' => 'feed',
		'url' => $result
	];
	// TODO: add things like name, description, photo, author .. see https://indieweb.org/Microsub-spec#Search
}

$json = [
	'results' => $feeds
];

echo json_encode($json);
