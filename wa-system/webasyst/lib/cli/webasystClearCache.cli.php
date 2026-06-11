<?php

class webasystClearCacheCli extends waCliController
{
    public function execute()
    {
        $errors = array();
        $path_cache = waConfig::get('wa_path_cache');

        if (!waSystemConfig::systemOption('cache_versioning')) {
            foreach (waFiles::listdir($path_cache) as $path) {
                $path = $path_cache.'/'.$path;
                if (!is_dir($path)) {
                    continue;
                }
                try {
                    waFiles::delete($path);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        if (!wa()->getConfig()->clearCache()) {
            $errors[] = 'waSystemConfig::clearCache() returned false';
        }

        if ($errors) {
            fwrite(STDERR, "Cache cleared with errors:\n".implode("\n", $errors)."\n");
            exit(1);
        }

        echo "Cache cleared\n";
    }
}
