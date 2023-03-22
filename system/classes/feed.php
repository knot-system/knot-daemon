<?php


// Spec: https://indieweb.org/Microsub-spec#Following

class Feed {

	private $id;
	private $path;
	private $info;
	private $posts;
	private $file;

	function __construct( $path ) {

		$file = new File( $path.'_feed.txt' );

		$this->file = $file;

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
			// if available, use $redirect_url
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
			// if available, and $url fails, use $original_url
			$request = new Request( $original_url );
			$request->curl_request( true );
			$status_code = $request->get_status_code();
			// TODO: maybe update $url with new location; see Feeds->create_feed()
		}

		if( $status_code != 200 ) {
			$this->import_error( 'invalid status code', $status_code );
			return false;
		}

		$headers = $request->get_headers();

		if( empty($headers['content-type']) ) {
			$this->import_error( 'no content-type' );
			return false;
		}

		$content_type = strtolower($headers['content-type']);

		$body = $request->get_body();

		if( str_contains($content_type, 'application/rss+xml') || str_contains($content_type, 'application/atom+xml') || str_contains($content_type, 'application/xml') || str_contains($content_type, 'text/xml') ) {
			// handle rss or atom feed

			$this->import_posts_rss( $body );

		} elseif( str_contains($content_type, 'application/json') ) {
			// handle json feed

			$this->import_posts_json( $body );

		} else {
			$this->import_error( 'invalid content-type', $content_type );
			return false;
		}

		
		// update feed information
		$feed_preview = new FeedPreview($url);
		$new_info = $feed_preview->get_info();
		$old_file_info = $this->file->get();
		$new_feed_info = array_merge( $old_file_info, $new_info );
		$this->file->create( $new_feed_info );


		return $this;
	}


	function import_posts_rss( $body ) {

		$rss = simplexml_load_string( $body );

		if( $rss === false ) {
			$this->import_error( 'rss: xml error', $body );
			return false;
		}

		if( $rss->channel->item ) {
			$items = $rss->channel->item;
		} elseif( $rss->entry ) {
			$items = $rss->entry;
		} elseif( $rss->item ) {
			$items = $rss->item;
		} else {
			$this->import_error( 'rss: no items found', $rss, $body );
			return false;
		}

		$feed_title = false;
		if( $rss->title ) {
			$feed_title = $rss->title;
		} elseif( $rss->channel->title ) {
			$feed_title = $rss->channel->title;
		}
		if( $feed_title ) $feed_title = $feed_title->__toString();

		$author_link = false;
		$feed_link = false;
		if( $rss->link ) {
			$author_link = $rss->link;
			if( isset($author_link['href']) ) $author_link = $author_link['href'];
		} elseif( $rss->channel->link ) {
			$author_link = $rss->channel->link;
		}
		if( $author_link ) {
			$author_link = $author_link->__toString();
			$feed_link = $author_link;
		}

		foreach( $items as $rss_item ) {

			$title = $rss_item->title;
			if( $title ) {
				$title = $title->__toString();
			} else {
				$title = false;
			}

			$link = $rss_item->link;
			if( $link ) {
				if( $link['href'] ) {
					$link = $link['href'];
				}
				$link = $link->__toString();
			} else {
				$link = false;
			}

			$guid = false;
			if( $rss_item->guid ) {
				$guid = $rss_item->guid;
			} elseif( $rss_item->id ) {
				$id = $rss_item->id;
			}
			if( $guid ) {
				$guid = $guid->__toString();
			} else {
				$guid = false;
			}

			$content = false;
			if( $rss_item->description ) {
				$content = $rss_item->description;
			} elseif( $rss_item->content ) {
				$content = $rss_item->content;
			}
			if( $content ) {
				$content = $content->__toString();
			} else {
				$content = false;
			}

			$pubDate = false;
			if( $rss_item->pubDate ) {
				$pubDate = $rss_item->pubDate;
			} elseif( $rss_item->updated ) {
				$pubDate = $rss_item->updated;
			}
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

			$author_name = $rss_item->author;
			if( $author_name ) {
				if( $author_name->name ) {
					$author_name = $author_name->name;
				}
				$author_name = $author_name->__toString();
			} else {
				$author_name = false;
			}
			
			$item = [
				'id' => $guid,
				'permalink' => $link,
				'title' => $title,
				'content_html' => $content,
				'date_published' => $pubDate,
				'image' => $image_url,
				'author_name' => $author_name,
				'author_link' => $author_link,
				'feed_title' => $feed_title,
				'feed_link' => $feed_link,
			];

			$this->import_item($item);

		}

		return true;
	}

	function import_posts_json( $body ) {
		
		$json = json_decode($body, true);

		if( $json === NULL ) {
			$this->import_error( 'invalid json', $body );
			return false; 
		}

		if( empty($json['items']) ) {
			$this->import_error( 'json: no items found', $json );
			return false;
		}

		$feed_title = false;
		if( ! empty($json['title']) ) {
			$feed_title = $json['title'];
		}

		$author_link = false;
		$feed_link = false;
		if( ! empty($json['home_page_url']) ) {
			$author_link = $json['home_page_url'];
			$feed_link = $json['home_page_url'];
		}

		$authors = false;
		if( ! empty($json['authors']) ) {
			$authors = $json['authors'];
		}

		foreach( $json['items'] as $item ) {

			if( empty($item['permalink']) && ! empty($item['url']) ) {
				$item['permalink'] = $item['url'];
			}

			if( $feed_title ) $item['feed_title'] = $feed_title;
			if( $feed_link ) $item['feed_link'] = $feed_link;
			if( $author_link ) $item['author_link'] = $author_link;
			if( $authors ) $item['authors'] = $authors;

			$this->import_item( $item );
		}

		return true;
	}


	function import_item( $item ) {

		if( empty($item['permalink']) ) {
			$this->import_error( 'item has no permalink', $item );
			return false;
		}

		$permalink = $item['permalink'];

		if( ! empty($item['id']) ) {
			$id = $item['id'];
			$internal_id = $id;
		} else {
			$id = get_hash( $permalink );
			$internal_id = $id;
		}

		$internal_id = get_hash( $this->path.$id ); // create a unique id for this item in this feed in this channel
		

		// check if item exists already

		$post_path = $this->path.'item-'.$internal_id.'.txt';
		$file = new File( $post_path );

		$updating = false;
		if( $file->exists() ) {
			global $core;
			$force_refresh = $core->config->get('force_refresh_posts');
			if( ! $force_refresh ) {
				return true;
			}

			$updating = true;
		}

		$title = false;
		if( ! empty($item['title']) ) {
			$title = $item['title'];
		}

		$content_html = false;
		if( ! empty($item['content_html']) ) {
			$content_html = $item['content_html'];
		}

		$content_text = false;
		if( ! empty($item['content_text']) ) {
			$content_text = $item['content_text'];
		} elseif( $content_html ) {
			$content_text = strip_tags( $content_html );
		}

		if( ! $content_html && ! $content_text && ! $title ) {
			$this->import_error( 'item '.$id.' has no title nor content', $item );
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

		$author_name = false;
		if( ! empty($item['author_name']) ) {
			$author_name = $item['author_name'];
		} elseif( ! empty($item['feed_title']) ) {
			$author_name = $item['feed_title'];
		}

		$author_link = false;
		if( ! empty($item['author_link']) ) {
			$author_link = $item['author_link'];
		}

		if( ! empty($item['authors']) ) {

			$author = $item['authors'];
			
			// if multiple authors are provided, fall back to the first
			//if( is_array($author) ) $author = $author[0];

			if( ! empty($author['name']) ) {
				$author_name = $author['name'];
			}
			if( ! empty($author['url']) ) { // TODO: check if this is the correct field name
				$author_link = $author['url'];
			}

		}

		if( ! $date_published ) {
			$date_published = date('c', time()); // fallback, if no date is set
			$this->import_error( 'item '.$internal_id.' ('.$id.') has no published date, fall back to current date', $item );
		}

		$feed_title = false;
		if( ! empty($item['feed_title']) ) $feed_title = $item['feed_title'];

		$feed_link = false;
		if( ! empty($item['feed_link']) ) $feed_link = $item['feed_link'];


		$date_published = streamline_date($date_published);
		if( $date_modified ) $date_modified = streamline_date($date_modified);


		if( ! $date_modified ) $date_modified = $date_published; // fallback, if no modified date is set

		$categories = false;
		if( ! empty($item['categories']) ) {
			$categories = $item['categories'];
		} elseif( ! empty($item['tags']) ) {
			$categories = $item['tags'];
		}


		// TODO: if $updating is true, keep old read state


		$post = [
			'id' => $id,
			'internal_id' => $internal_id,
			'permalink' => $permalink,
			'title' => $title,
			'content_html' => $content_html,
			'content_text' => $content_text,
			'date_published' => $date_published,
			'date_modified' => $date_modified,
			'author_name' => $author_name,
			'author_link' => $author_link,
			'feed_title' => $feed_title,
			'feed_link' => $feed_link,
			'category' => json_encode($categories),
			'image' => $image,
			'_raw' => json_encode($item)
		];

		$file->create( $post );

		return true;
	}


	function get_posts() {

		if( $this->posts ) return $posts;

		$posts = [];

		$posts_folder = new Folder( $this->path );

		$files = $posts_folder->get_content();

		foreach( $files as $filename ) {
			if( $filename == '_feed.txt' ) continue;

			if( ! str_starts_with( strtolower($filename), 'item-' ) ) continue;

			$filepath = $this->path.$filename;

			$post = $this->get_post( $filepath );

			$sort_id = $post['_sort_id'];
			unset($post['_sort_id']);
			
			$posts[$sort_id] = $post;
		}

		krsort($posts);

		$this->posts = $posts;

		return $posts;
	}


	function get_post( $filepath ) {

		if( ! file_exists($filepath) ) return false;

		$file = new File( $filepath );

		$file_content = $file->get();

		if( ! empty($file_content['date_modified']) ) {
			$date = $file_content['date_modified'];
		} else {
			$date = $file_content['date_published'];
		}

		$sort_id = $date.'-'.$file_content['internal_id'];

		$post = [
			'_sort_id' => $sort_id,
			'_id' => $file_content['internal_id'],
			'type' => 'entry',
			'uid' => $file_content['id'],
			'name' => $file_content['title'],
			'published' => $date,
			'url' => $file_content['permalink'],
			'content' => [
				'text' => $file_content['content_text'],
				'html' => $file_content['content_html']
			],
		];

		// TODO: remove <script> tags and other things that could be harmful from $post['content']['html']

		if( ! empty($file_content['author_name']) ) {

			$author_link = false;
			if( ! empty($file_content['author_link']) ) {
				$author_link = $file_content['author_link'];
			}

			$post['author'] = [
				'type' => 'card',
				'name' => $file_content['author_name'],
				'url' => $author_link,
				// 'photo' => '', // TODO
			];
		}

		if( ! empty($file_content['image']) ) {
			if( ! is_array($file_content['image']) ) $file_content['image'] = array($file_content['image']);
			$post['photo'] = $file_content['image'];
		}

		if( ! empty($file_content['category']) ) {
			$post['category'] = json_decode($file_content['category']);
		}

		# for _source see https://indieweb.org/Microsub-spec#Indicating_Item_Source_Proposal	
		$source = false;
		if( $file_content['feed_title'] ) {
			$source = [
				'_id' => $this->id,
				'name' => $file_content['feed_title']
			];
			if( ! empty($file_content['feed_link']) ) $source['url'] = $file_content['feed_link'];
		}
		if( $source ) $post['_source'] = $source;

		// TODO: set $post['_is_read']	

		return $post;
	}


	function import_error( ...$messages ) {

		$id = $this->id;
		$path = $this->path;

		$first_message = 'feed import error in '.$id.' ('.$path.')';
		array_unshift( $messages, $first_message );

		global $core;
		$core->log->message( $messages );

		// TODO: disable this feed if it fails multiple times

	}


	function cleanup_posts() {
		// TODO: delete posts, that are older than a specific threshold; make the threshold a config option
		// (also check for this threshold when importing, and don't import posts that are older)

		return $this;
	}


}
