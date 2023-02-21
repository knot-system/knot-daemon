<?php

// Spec: https://indieweb.org/Microsub-spec#Following

class Feed {

	private $id;
	private $path;
	private $info;

	function __construct( $path ) {

		$file = new File( $path.'_feed.txt' );

		$file_content = $file->get();

		if( ! $file_content ) return false;

		$this->path = $path;

		$this->id = $file_content['_id'];

		$this->info = $file_content;

	}


	function get_id() {
		return $this->id;
	}

	function get_path() {
		return $this->path;
	}


	function get_info( $key ) {
		if( ! array_key_exists($key, $this->info) ) return false;

		return $this->info[$key];
	}

	function get( $cleanup = false ) {

		$feed_info = $this->info;

		if( $cleanup ) {
			unset($feed_info['_id']);
			unset($feed_info['_path']);
			unset($feed_info['_original_url']);
			unset($feed_info['_redirect_url']);
		}

		return $feed_info;
	}


	function has_url( $url ) {
		if( $this->info['url'] == $url ) return true;

		if( ! empty($this->info['_original_url']) && $this->info['_original_url'] == $url ) return true;

		if( ! empty($this->info['_redirect_url']) && $this->info['_redirect_url'] == $url ) return true;

		return false; 
	}


	function refresh_posts() {

		$url = $this->get_info( 'url' );
		$redirect_url = $this->get_info( '_redirect_url' );
		$original_url = $this->get_info( '_original_url' );

		$request = false;
		$status_code = false;
		if( $redirect_url ) {
			// if available, use _redirect_url
			$request = new Request( $redirect_url );
			$request->curl_request( true );
			$status_code = $request->get_status_code();
		}

		if( $status_code != 200 ) {
			$request = new Request( $url );
			$request->curl_request( true );
			$status_code = $request->get_status_code();
			// TODO: if $redirect_url is set, update with new location; see Feeds->create_feed()
		}

		if( $status_code != 200 ) {
			// if available, and $url fails, us _original_url
			$request = new Request( $original_url );
			$request->curl_request( true );
			$status_code = $request->get_status_code();
			// TODO: maybe update $url with new location; see Feeds->create_feed()
		}

		if( $status_code != 200 ) {
			$this->import_error( 'invalid status code' );
			return false;
		}

		$headers = $request->get_headers();

		if( empty($headers['content-type']) ) {
			$this->import_error( 'no content-type' );
			return false;
		}

		$content_type = strtolower($headers['content-type']);

		$body = $request->get_body();

		if( str_contains($content_type, 'application/rss+xml') || str_contains($content_type, 'application/atom+xml') || str_contains($content_type, 'application/xml') ) {
			// handle rss or atom feed

			$this->import_posts_rss( $body );

		} elseif( str_contains($content_type, 'application/json') ) {
			// handle json feed

			$this->import_posts_json( $body );

		} else {
			$this->import_error( 'invalid content-type' );
			return false;
		}

		return $this;
	}


	function import_posts_rss( $body ) {

		// TODO: check compatibility with atom

		$rss = simplexml_load_string( $body );

		foreach( $rss->channel->item as $rss_item ) {

			$title = $rss_item->title;
			if( $title ) {
				$title = $title->__toString();
			} else {
				$title = false;
			}

			$link = $rss_item->link;
			if( $link ) {
				$link = $link->__toString();
			} else {
				$link = false;
			}

			$guid = $rss_item->guid;
			if( $guid ) {
				$guid = $guid->__toString();
			} else {
				$guid = false;
			}

			$description = $rss_item->description;
			if( $description ) {
				$description = $description->__toString();
			} else {
				$description = false;
			}

			$pubDate = $rss_item->pubDate;
			if( $pubDate ) {
				$pubDate = $pubDate->__toString();
			} else {
				$pubDate = false;
			}

			$image_url = $rss_item->enclosure->url;
			if( $image_url ) {
				$image_url = $image_url->__toString();
			} else {
				$image_url = false;
			}

			$author = $rss_item->author;
			if( $author ) {
				$author = $author->__toString();
			} else {
				$author = false;
			}
			
			$item = [
				'id' => $guid,
				'permalink' => $link,
				'title' => $title,
				'content_html' => $description,
				'date_published' => $pubDate,
				'image' => $image_url,
				'author' => $author
			];

			$this->import_item($item);

		}

		return true;
	}

	function import_posts_json( $body ) {
		
		$json = json_decode($body, true);

		if( $json === NULL ) {
			$this->import_error( 'invalid json' );
			return false; 
		}

		if( empty($json['items']) ) {
			$this->import_error( 'json: no items found' );
			return false;
		}

		foreach( $json['items'] as $item ) {

			if( empty($item['permalink']) && ! empty($item['url']) ) {
				$item['permalink'] = $item['url'];
			}

			$this->import_item( $item );
		}

		return true;
	}


	function import_item( $item ) {

		if( empty($item['permalink']) ) {
			$this->import_error( 'item has no permalink' );
			return false;
		}

		$permalink = $item['permalink'];

		if( ! empty($item['id']) ) {
			$id = $item['id'];
			$internal_id = get_hash($id);
		} else {
			$id = get_hash( $permalink );
			$internal_id = $id;
		}

		// check if item exists already

		$post_path = $this->path.'item-'.$internal_id.'.txt';
		$file = new File( $post_path );

		if( $file->exists() ) {
			// TODO: maybe try to refresh content?
			return true;
		}

		$title = false;
		if( ! empty($item['title']) ) {
			$title = $item['title'];
		}

		$content_html = false;
		if( ! empty($item['content_html']) ) {
			$content_html = $item['content_html'];
			// TODO: remove 'script' tags and other things that could be harmful?
		}

		$content_text = false;
		if( ! empty($item['content_text']) ) {
			$content_text = $item['content_text'];
		} elseif( $content_html ) {
			$content_text = strip_tags( $content_html );
		}

		if( ! $content_html && ! $content_text && ! $title ) {
			$this->import_error( 'item '.$id.' has no title nor content' );
		}

		$date_published = false;
		if( ! empty($item['date_published']) ) {
			$date_published = $item['date_published'];
		}

		$date_modified = false;
		if( ! empty($item['date_modified']) ) {
			$date_modified = $item['date_modified'];
		}

		$image = false;
		if( ! empty($item['image']) ) {
			$image = $item['image'];
		}

		$author = false;
		if( ! empty($item['author']) ) {
			$author = $item['author'];
		}

		$post = [
			'id' => $id,
			'internal_id' => $internal_id,
			'title' => $title,
			'content_html' => $content_html,
			'content_text' => $content_text,
			'date_published' => $date_published,
			'date_modified' => $date_modified,
			'author' => $author,
			'image' => $image,
			'_raw' => json_encode($item)
		];

		$file->create( $post );

		return true;
	}


	function import_error( $message ) {

		$id = $this->id;
		$path = $this->path;

		$message = 'feed import error in '.$id.' ('.$path.'): '.$message;

		global $postamt;
		$postamt->log->message( $message );

		// TODO: disable this feed if it fails multiple times

	}


	function cleanup_posts() {
		// TODO: delete posts, that are older than a specific threshold; make the threshold a config option

		return $this;
	}


}
