<?php

class userlogConfig extends waAppConfig
{
    const TRASH_RETENTION_DAYS = 365;

    public function explainLogs($logs)
    {
        return $logs;
    }
}
