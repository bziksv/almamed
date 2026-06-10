<?php

/**
 * Class shopPlugmeinBOM
 * based BOM CLEANER by Emrah Gunduz
 */
class shopPlugmeinBOM
{
    public static function find($types = [])
    {
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);
        @ob_end_clean();
        // We do not want the script to stop working for long processes
        set_time_limit(0);
        ob_implicit_flush(1);

        /**
         * Detect if we are runnning under Windows
         */
        define('WIN', strncasecmp(PHP_OS, 'WIN', 3) === 0);
        /**
         * Current version
         */
        define('VERSION', 0.55);
        /**
         * The folder script resides under...
         */
        define('ROOT', waConfig::get('wa_path_root'));

        /*
         * Check if we are running in a terminal or called from web
         */
        /**
         * Keeps the answers of file extension questions.
         * Called from terminal.
         * @global array $types
         * @param string $t
         */
        function keepTrackOfTypes($t)
        {
            global $types;
            $handle = fopen('php://stdin', 'rb');
            $line = fgets($handle);
            if (trim($line) === 'yes' || trim($line) === 'y') {
                $types[] = $t;
            }
        }

        /**
         * Keeps the answers of file extension questions.
         * Called from web.
         * @global array $types
         * @internal param string $t
         */
        function keepTrackOfTypesString()
        {
            global $types;
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            $fs = explode(",", trim($line));
            foreach ($fs as $f) {
                if ($f != '') {
                    $types[] = trim(str_ireplace('.', '', $f));
                }
            }
        }

        /**
         * Checks if the string has BOM
         * @param string $string
         * @return bool
         */
        function hasBom($string)
        {
            return (strpos($string, pack('CCC', 0xef, 0xbb, 0xbf)) === 0);
        }

        // Running from web
        if (isset($_GET[ 'action' ]) && $_GET[ 'action' ] == 'run') {
            $types = filter_var_array($_POST[ 'ext' ], FILTER_SANITIZE_STRING);
            $others = filter_var($_POST[ 'types' ], FILTER_SANITIZE_STRING);
            if ($others != '') {
                $fs = explode(',', trim($others));
                foreach ($fs as $f) {
                    if (strlen($f)) {
                        $types[] = trim(str_ireplace('.', '', $f));
                    }
                }
            }
            //cleanFilesHtml();
        }
    }
}
