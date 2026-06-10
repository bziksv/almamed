<?php


class shopSeoWaTableModel extends waModel
{
	public function __construct($table, $type = null, $writable = false)
	{
		$this->table = $table;
		parent::__construct($type, $writable);

		if (method_exists(get_class($this), 'clearMetadataCache'))
		{
			$this->clearMetadataCache();
		}
		else
		{
			$this->_oldClearMetadataCache();
		}
	}

	/**
	 * Update and return description of table columns (like getMetadata()), bypassing all caches.
	 * @return array
	 */
	public function _oldClearMetadataCache()
	{
		$runtime_cache = new waRuntimeCache('db/'.$this->table);
		$runtime_cache->delete();
		$cache = new waSystemCache('db/'.$this->table);
		$cache->delete();
		$this->fields = null;
		return $this->getMetadata();
	}
}