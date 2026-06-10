<?php

interface shopSearchproEntityFinderInterface
{
	/**
	 * @param string $query
	 * @param int|null $limit
	 * @return array
	 */
	public function findEntities($query, $limit = null);
}