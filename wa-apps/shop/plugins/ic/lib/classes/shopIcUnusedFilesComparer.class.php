<?php

class shopIcUnusedFilesComparer
{
    protected $used_files = [];
    public function __construct(shopIcUsedFiles $files)
    {
        $urlToPathConverter = new shopIcUrlToPathConverter();

        foreach ($files->getAll() as $item) {
            $path = $urlToPathConverter->convert($item);
            $this->used_files[$path] = true;
        }
    }

    public function getToDeleteFiles($local_files = []): array
    {
        $unused_files = [];

        foreach ($local_files as $item) {
            if ($this->used_files[$item] == null) {
                $unused_files[] = $item;
            }
        }

        return $unused_files;
    }
}
