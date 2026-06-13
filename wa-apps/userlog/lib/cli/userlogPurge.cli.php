<?php

class userlogPurgeCli extends waCliController
{
    public function execute()
    {
        wa('userlog');
        $count = (new userlogTrashService())->purgeExpired();
        echo "Purged {$count} expired trash item(s).\n";
    }
}
