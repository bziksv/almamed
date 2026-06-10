<?php

class shopIcCsvReader implements shopIcUsedFiles
{
    protected $file;
    protected $handle;

    public function __construct($filePath, $skipHeader = true)
    {
        $this->file = $filePath;
        $this->handle = fopen($this->file, 'r');
        if ($this->handle === false) {
            throw new Exception('Could not open the file: ' . $this->file);
        }

        if ($skipHeader) {
            $this->getNextRow();
        }
    }

    public function getNextRow()
    {
        if (($data = fgetcsv($this->handle, 1000, ",")) !== false) {
            return $data;
        }
        return null;
    }

    public function getAll(): array
    {
        $items = [];

        while (($row = $this->getNextRow()) !== null) {
            $items[] = $row[0];
        }

        return $items;
    }

    public function __destruct()
    {
        if ($this->handle) {
            fclose($this->handle);
        }
    }
}
