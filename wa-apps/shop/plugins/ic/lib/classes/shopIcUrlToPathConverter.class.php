<?php

class shopIcUrlToPathConverter
{
    private $serverRoot;

    public function __construct($serverRoot = null)
    {
        $this->serverRoot = $serverRoot ?: $_SERVER['DOCUMENT_ROOT'];
    }

    public function convert($url): string
    {
        $relPath = pathinfo(parse_url($url, PHP_URL_PATH));

        $path = str_replace('public', 'protected', $relPath['dirname']) .'.'. $relPath['extension'];

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->serverRoot . $path);
    }
}
