<?php

// update: 2023-05-16


class Dom {

	private $document;

	private $elements = [];

	function __construct( $html ) {

		$doc = new DOMDocument();

		if( $html ) {
			@$doc->loadHTML($html);
		}

		$this->document = $doc;
	}

	function find_elements( $tagname ) {

		$nodes = $this->document->getElementsByTagName($tagname);

		$elements = [];

		foreach( $nodes as $node ) {
			$elements[] = $node;
		}

		$this->elements = $elements;

		return $this;
	}

	function filter_elements( $filter_attribute, $filter_value ) {

		if( ! count($this->elements) ) {
			return $this;
		}

		$elements = [];

		foreach( $this->elements as $element ) {
			$attribute = $element->getAttribute( $filter_attribute );

			if( $attribute == $filter_value ) {
				$elements[] = $element;
			}
		}

		$this->elements = $elements;

		return $this;
	}

	function return_elements( $attribute = false ) {

		if( ! count($this->elements) ) return [];

		$values = [];

		foreach( $this->elements as $element ) {
			if( $attribute ) {
				$value = $element->getAttribute($attribute);
			} else {
				$value = $element->textContent;
			}

			if( empty($value) ) continue;

			$values[] = $value;
		}

		return $values;
	}

}