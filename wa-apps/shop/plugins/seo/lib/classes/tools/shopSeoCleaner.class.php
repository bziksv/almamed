<?php


class shopSeoCleaner
{
	private $root_path;
	private $available_files;

	public function __construct()
	{
		$class = get_class($this);
		$classpath = array_map('mb_strtolower', preg_split('/(?=[A-Z])/', $class, -1));

		if (count($classpath) == 3)
		{
			list($app, $plugin) = $classpath;
			$this->root_path = wa()->getAppPath("plugins/{$plugin}", $app);
		}
		elseif (count($classpath) == 2)
		{
			list($app) = $classpath;
			$this->root_path = wa()->getAppPath(null, $app);
		}

		if (!isset($this->root_path))
		{
			return;
		}

		if (file_exists($this->root_path . '/.build') || file_exists($this->root_path . '/.git') || file_exists($this->root_path . '/.build.php'))
		{
			return;
		}

		$all_files_file = "{$this->root_path}/lib/config/all_files.php";

		if (file_exists($all_files_file))
		{
			$this->available_files = include($all_files_file);
		}
	}

	public function clean()
	{
		if (!isset($this->available_files))
		{
			return;
		}

		$files = $this->scanDir($this->root_path);

		foreach ($files as $file)
		{
			if (!in_array(mb_substr($file, mb_strlen($this->root_path) + 1), $this->available_files))
			{
				if (file_exists($file))
				{
					waFiles::delete($file);
				}
			}
		}
	}

	private function scanDir($dir)
	{
		$files = array_diff(scandir($dir), array('.', '..'));
		$result = array();

		foreach ($files as $file)
		{
			$file = "{$dir}/{$file}";
			$result[] = $file;

			if (is_dir($file))
			{
				$result = array_merge($result, $this->scanDir($file));
			}
		}

		return $result;
	}
}