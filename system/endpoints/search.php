<?php

if( ! $postamt ) exit;

# Spec: https://indieweb.org/Microsub-spec#Search

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

foreach( $results as $feed_url ) {

	$feed = new FeedPreview( $feed_url );

	$feeds[] = $feed->get_info();
}

$json = [
	'results' => $feeds
];

echo json_encode($json);
