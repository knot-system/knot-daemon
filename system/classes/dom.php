<?php

// Core Version: 0.1.0

class Dom {

	private $document;

	function __construct( $html ) {
		$doc = new DOMDocument();
		@$doc->loadHTML($html);

		$this->document = $doc;
	}

	function find( $find_tagname, $find_rel ) {

		$nodes = $this->document->getElementsByTagName($find_tagname);

		$values = [];

		foreach( $nodes as $node ) {
			$rel = $node->getAttribute('rel');

			if( $rel == $find_rel ) {
				$href = $node->getAttribute('href');
				$values[] = $href;
			}
			
		}

		if( ! count($values) ) return false;
		
		if( count($values) == 1 ) return $values[0];

		return $values;
	}

}