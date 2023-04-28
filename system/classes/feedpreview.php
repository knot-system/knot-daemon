<?php


// TODO: merge this into the Feed class

class FeedPreview {

	private $url;
	private $request;
	private $type;

	function __construct( $url ) {

		$this->url = $url;

		$request = new Request($url);
		$request->curl_request();

		$this->request = $request;

		$status_code = $this->request->get_status_code();

		if( $status_code == '400' ) return false;

		$headers = $this->request->get_headers();

		if( empty($headers['content-type']) ) {
			return false;
		}

		$content_type = strtolower($headers['content-type']);


		if( str_contains($content_type, 'application/rss+xml') || str_contains($content_type, 'application/xml') || str_contains($content_type, 'text/xml') ) {

			$this->type = 'rss';

		} elseif( str_contains($content_type, 'application/atom+xml') ) {

			$this->type = 'atom';

		} elseif( str_contains($content_type, 'application/json') ) {
			
			$this->type = 'json';
			
		}

	}



	function is_valid_feed(){

		if( $this->type ) return true;
		else return false;

	}


	function get_info() {

		# see https://indieweb.org/Microsub-spec#Search

		$info = [
			'type' => 'feed',
			'url' => $this->url
		];

		$body = $this->request->get_body();

		$title = false;
		$description = false;
		$image = false;

		if( $this->type == 'json' ) {

			$json = json_decode($body);

			if( ! empty($json->title) ) $title = $json->title;
			if( ! empty($json->description) ) $description = $json->description;

			if( ! empty($json->image) ) {
				$image = $json->image;
			} elseif( ! empty($json->photo) ) {
				$image = $json->photo;
			}

			if( is_object($title) ) $title = json_encode($title);
			if( is_object($description) ) $description = json_encode($description);

		} elseif( $this->type == 'rss' || $this->type == 'atom' ) {

			$rss = @simplexml_load_string( $body );

			if( $rss !== false ) {

				if( $rss->title ) {
					$title = $rss->title;
				} elseif( $rss->channel->title ) {
					$title = $rss->channel->title;
				}
				if( $title ) $title = $title->__toString();

				if( $rss->channel->description ) {
					$description = $rss->channel->description;
				}
				if( $description ) $description = $description->__toString();

				if( $rss->channel->image ) {
					if( $rss->channel->image->link ) {
						$image = $rss->channel->image->url;
					} else {
						$image = $rss->channel->image;
					}

					if( $image ) $image = $image->__toString();
				}

			}

		}

		if( $description ) $info['description'] = trim($description);
		if( $image ) $info['photo'] = trim($image);
		if( $title ) $info['name'] = trim($title);

		return $info;
	}

}
