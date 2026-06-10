<?php

class shopIcFileDeleter
{
    public function delete($path): bool
    {
        if (!file_exists($path)) {
            return true;
        }

        if (is_file($path) && is_writable($path)) {
            return waFiles::delete($path, true);
        }

        return false;
    }

    public function deleteDir($dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (waFiles::delete($dir, true)) {
            return true;
        }

        return false;
    }

    public function deleteSubDirs($parentDir): array
    {
        $result = [
            'deleted' => 0,
            'failed' => [],
        ];

        $items = waFiles::listdir($parentDir);
        foreach ($items as $item) {
            $subDir = implode(DIRECTORY_SEPARATOR, [$parentDir, $item]);
            if (is_dir($subDir)) {
                if ($this->deleteDir($subDir)) {
                    $result['deleted']++;
                } else {
                    $result['failed'][] = $subDir;
                }
            }
        }

        return $result;
    }

    public function deleteList($paths): array
    {
        $result = [
            'deleted' => 0,
            'failed' => [],
        ];

        foreach ($paths as $path) {
            if ($this->delete($path)) {
                $result['deleted']++;
            } else {
                $result['failed'][] = $path;
            }
        }

        return $result;
    }
}