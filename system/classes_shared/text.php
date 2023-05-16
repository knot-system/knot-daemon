<?php

// update: 2023-05-16


class Text {

	public $content;
	public $links;

	function __construct( $text ) {
		$this->content = $text;

		$this->match_links();

		return $this;
	}


	function cleanup( $hide_anchors = false ) {
		return $this->remove_html_elements()->auto_a( $hide_anchors )->auto_p();
	}


	function remove_html_elements() {
		global $core;

		$allowed_html_elements = $core->config->get('allowed_html_elements');

		$this->content = strip_tags( $this->content, $allowed_html_elements );

		return $this;
	}


	function auto_p() {

		$text = $this->content;

		// this is based on the wpautop function from WordPress (but very much simplified). Thanks WordPress!
		// https://developer.wordpress.org/reference/functions/wpautop/
		// TODO: we may want to rewrite this based on our use case

		if ( trim( $text ) === '' ) {
			return $this;
		}

		// Change multiple <br>'s into two line breaks, which will turn into paragraphs.
		$text = preg_replace( '|<br\s*/?>\s*<br\s*/?>|', "\n\n", $text );

		// Standardize newline characters to "\n".
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );

		// Remove more than two contiguous line breaks.
		$text = preg_replace( "/\n\n+/", "\n\n", $text );

		// Split up the contents into an array of strings, separated by double line breaks.
		$paragraphs = preg_split( '/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY );

		// Reset $text prior to rebuilding.
		$text = '';

		// Rebuild the content as a string, wrapping every bit with a <p>.
		foreach ( $paragraphs as $paragraph ) {
			$text .= '<p>' . trim( $paragraph, "\n" ) . "</p>\n";
		}

		// Normalize <br>
		$text = str_replace( array( '<br>', '<br/>' ), '<br>', $text );

		// Replace any new line characters that aren't preceded by a <br> with a <br>.
		$text = preg_replace( '|(?<!<br>)\s*\n|', "<br>\n", $text );

		// If a <br> tag is before a subset of opening or closing block tags, remove it.
		$text = preg_replace( "|\n</p>$|", '</p>', $text );

		// If there is a <br> tag after a </p> tag, remove it:
		$text = preg_replace( "/\<\/p\>\<br\>/", '</p>', $text );

		// remove brs inside <ul> and <ol> tags
		$text = preg_replace( "/\<ul\>\<br\>/", '<ul>', $text );
		$text = preg_replace( "/\<\/ul\>\<br\>/", '</ul>', $text );
		$text = preg_replace( "/\<ol\>\<br\>/", '<ol>', $text );
		$text = preg_replace( "/\<\/ol\>\<br\>/", '</ol>', $text );
		$text = preg_replace( "/\<br\>\<li\>/", '<li>', $text );
		$text = preg_replace( "/\<\/li\>\<br\>/", '</li>', $text );

		$this->content = $text;

		return $this;
	}


	function auto_a( $hide_anchors = false ) {

		if( trim($this->content) == '' ) return $this;

		global $core;

		$regexp = $this->get_link_regex_pattern( true );

		$replace = '<a class="inline-link" href="$1://$2.$3$4" target="_blank" rel="noopener" title="$1://$2.$3$4">$2.$3$4</a>';

		$add_footnote_to_links = $core->config->get('add_footnote_to_links');

		if( $hide_anchors ) $add_footnote_to_links = false;

		if( function_exists('doing_feed') && doing_feed() ) $add_footnote_to_links = false;

		if( $add_footnote_to_links ) {
			$replace .= '<sup class="footnote"><a href="#$2.$3$4">*</a></sup>';
		}

		$this->content = preg_replace( $regexp, $replace, $this->content );

		// remove trailing slash from link preview text:
		$this->content = str_replace( '/</a>', '</a>', $this->content );

		return $this;
	}


	function get() {
		return $this->content;
	}


	function match_links() {

		$regexp = $this->get_link_regex_pattern();

		$links = array();

		if( preg_match_all( $regexp, $this->content, $matches ) ) {

			foreach( $matches[0] as $link ) {
				$links[] = $link;
			}

		}

		$links = array_map( 'trim', $links );
		$links = array_filter($links); // remove empty elements

		$this->links = $links;

		return $this;
	}


	function get_link_preview() {

		if( ! count($this->links ) ) return '';

		$html = '<ol class="link-preview-list">
		';
		foreach( $this->links as $link_url ) {

			$link = new Link( $link_url );
			$link_id = $link->id;

			$link_info = $link->get_preview();

			$classes = array( 'link-preview' );

			$max_age = 60*60*6; // we currently refresh links after 6 hours - TODO: finetune this value

			if( empty($link_info['last_refresh']) || time()-$link_info['last_refresh'] > $max_age ) {

				$classes[] = 'link-preview-needs-refresh';

				global $core;
				if( ! isset($core->is_link_refreshing) ) {
					// NOTE: we refresh only on link for every request, because this can take a few seconds,
					// depending on the url and how fast the other server is.
					// by default, the link refresh also happens async via js, so all the links that don't get
					// refreshed with this request, should be done by the time this page refreshes again.
					// this is just a fallback, if js is not active, or doesn't get executed, or is removed by the theme
					$core->is_link_refreshing = true;
					$link_info = $link->get_info()->get_preview();
				}
				
			}

			$html .= '			<li>
				<a id="'.$link_id.'" class="'.implode(' ', $classes).'" name="'.$link->short_url.'" href="'.$link->url.'" target="_blank" rel="noopener" data-preview-hash="'.$link_info['preview_html_hash'].'">'.$link_info['preview_html'].'</span></a>
			</li>
			';
		}
		$html .= '		</ol>';

		return $html;
	}


	private function get_link_regex_pattern( $output = false ){
		// TODO: maybe we also want to support gopher:// or other protocols?

		$exclude_attributes = '(?<!src=[\"\'])'; // ignore src attributes
		if( $output ) $exclude_attributes .= '(?<!href=[\"\'])'; // ignore href as well, for links that are already valid HTML

		$scheme = '(http|https)\:\/\/'; // http:// or https://
		$domain = '([a-zA-Z0-9\-\.]+)'; // domain (with subdomain)
		$tld = '([a-zA-Z]+)';
		$path = '(\/[^\r\n\t\f\v \<\"\']*)*'; // path (with query); no linebreak, space or <

		$pattern = '/'.$exclude_attributes.$scheme.$domain.'\.'.$tld.$path.'/mix';

		return $pattern;
	}


}
