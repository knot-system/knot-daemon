<?php

if( ! $core ) exit;

# Spec: https://indieweb.org/Microsub-spec#Search

$request_type = $core->route->get('request_type');

if( $request_type != 'post' ) {
	$core->error( 'invalid_request', 'only post requests accepted', null, null, $request_type );
}

if( empty($_REQUEST['query']) ) {
	$core->error( 'invalid_request', 'query parameter must be provided' );	
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
