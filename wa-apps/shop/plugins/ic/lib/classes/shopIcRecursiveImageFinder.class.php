<?php

class shopIcRecursiveImageFinder
{
    protected $iterator;
    protected $items = [];
    protected $path = "";

    public function __construct($absolutePath)
    {
        $this->path = $absolutePath;

        $this->iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path));

        foreach ($this->iterator as $item) {

            if ($this->isImg($item)) {
                $this->items[] = $item;
            }
        }
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getAllImagesPath(): array
    {
        $paths = [];

        foreach ($this->items as $item) {
            $rawPath = $item->getPathname();

            $paths[] = $this->normalizePath($rawPath);
        }

        return $paths;
    }

    public function getSize(): int
    {
        $size = 0;

        foreach ($this->items as $item) {
            $size += $item->getSize();
        }

        return $size;
    }

    public function getCount(): int
    {
        return count($this->items);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    private function isImg(\SplFileInfo $file): bool
    {
        return $file->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $file->getFilename());
    }

    private function normalizePath($rawPath): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rawPath);
    }
}
