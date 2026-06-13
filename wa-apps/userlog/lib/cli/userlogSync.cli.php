<?php

class userlogSyncCli extends waCliController
{
    public function execute()
    {
        wa('userlog');
        $full = waRequest::param('full');
        if ($full) {
            $asm = new waAppSettingsModel();
            $asm->set('userlog', 'wa_log_shop_last_id', 0);
            $asm->set('userlog', 'wa_log_blog_last_id', 0);
            $total = 0;
            do {
                $before = (int) (new waModel())->query('SELECT COUNT(*) FROM userlog_event')->fetchField();
                userlogLogger::syncFromWaLog(true);
                $after = (int) (new waModel())->query('SELECT COUNT(*) FROM userlog_event')->fetchField();
                $added = $after - $before;
                $total += $added;
                echo "Imported {$added} events (total {$after})\n";
            } while ($added >= 500);
            echo "Full sync complete. Added {$total} events.\n";
            return;
        }

        userlogLogger::syncForDisplay();
        echo "Display sync complete.\n";
    }
}
