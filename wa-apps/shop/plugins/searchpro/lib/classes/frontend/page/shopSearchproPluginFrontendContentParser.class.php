<?php

class shopSearchproPluginFrontendContentParser
{
	protected $entities = array(
		'h1' => array(
			'type' => 'tag',
			'tag' => 'h1',
			'quantifier' => 0
		),
		'content' => array(
			'type' => 'rest'
		)
	);

	protected function getEntities()
	{
		return $this->entities;
	}

	/**
	 * @param string $content
	 * @return shopSearchproPluginFrontendContentParserResult
	 */
	public function parse($content)
	{
		$dom = new DOMDocument();
		$doc = new DOMDocument();

		$id = uniqid('___rest-parsed-content___');
		$doc->loadHTML("<?xml encoding=\"utf-8\" ?><div id='{$id}' class='searchpro__rest-parsed-content_wrapper'>{$content}</div>");
		$node = $doc->getElementById($id);
		if($node === null) {
			$xpath = new DOMXPath($doc);
			$node = $xpath->query("//*[@id='{$id}']")->item(0);
		}
		$dom->appendChild($dom->importNode($node, true));

		$parsed_entities = array();

		$entities = $this->getEntities();
		foreach($entities as $name => $params) {
			$parsed_entities[$name] = $this->workup($dom, $params);
		}

		$result = new shopSearchproPluginFrontendContentParserResult($parsed_entities);

		return $result;
	}

	protected function workup(&$dom, $params)
	{
		$type = $params['type'];
		$entity = null;

		switch($type) {
			case 'tag':
				$tag = $params['tag'];
				$quantifier = $params['quantifier'];
				$elements = $dom->getElementsByTagName($tag);

				if($node = $elements->item($quantifier)) {
					$doc = new DOMDocument();
					$doc->appendChild($doc->importNode($node, true));

					$node->parentNode->removeChild($node);

					return $doc->saveHTML();
				}
				break;
			case 'rest':
				return $dom->saveHTML();
				break;
		}

		return $entity;
	}
}