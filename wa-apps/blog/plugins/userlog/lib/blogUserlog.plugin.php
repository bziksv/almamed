<?php

class blogUserlogPlugin extends blogPlugin
{
    /** @var int[] */
    protected static $logged_posts = array();

    /** @var bool */
    protected static $logging_suspended = false;

    public static function setLoggingSuspended($flag)
    {
        self::$logging_suspended = (bool) $flag;
    }

    protected function ensureUserlogReady()
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }
        if (!wa()->appExists('userlog')) {
            return $ready = false;
        }
        if (!waSystem::isLoaded('userlog')) {
            wa('userlog');
        }
        return $ready = class_exists('userlogHelper');
    }

    public function postPresave($data)
    {
        $this->preparePostSave($data);
    }

    public function postPrepublish($data)
    {
        $this->preparePostSave($data);
    }

    public function postSave($data)
    {
        $this->finalizePostSave($data);
    }

    public function postPublish($data)
    {
        $this->finalizePostSave($data);
    }

    public function postPredelete($post_ids)
    {
        if (self::$logging_suspended || !$this->ensureUserlogReady() || !$post_ids) {
            return;
        }
        $post_ids = array_map('intval', (array) $post_ids);
        $post_model = new blogPostModel();
        foreach ($post_ids as $post_id) {
            if (!$post_id || !empty(self::$logged_posts[$post_id])) {
                continue;
            }
            $snapshot = blogUserlogPostSnapshot::captureForLog($post_id);
            if (!$snapshot) {
                continue;
            }
            $title = ifset($snapshot, 'post', 'title', '#'.$post_id);
            userlogLogger::log(array(
                'app_id'       => 'blog',
                'action'       => 'post.delete',
                'entity_type'  => 'post',
                'entity_id'    => $post_id,
                'entity_name'  => $title,
                'summary'      => 'Удалена запись «'.$title.'»',
                'before_data'  => $snapshot,
                'can_rollback' => 1,
            ));
            self::$logged_posts[$post_id] = true;
        }
    }

    protected function preparePostSave($data)
    {
        if (self::$logging_suspended || !$this->ensureUserlogReady()) {
            return;
        }
        $post_id = $this->resolvePostId($data);
        if (!$post_id || userlogLogger::hasPostBefore($post_id)) {
            return;
        }
        $snapshot = blogUserlogPostSnapshot::captureForLog($post_id);
        if ($snapshot) {
            userlogLogger::rememberPostBefore($post_id, $snapshot);
        }
    }

    protected function finalizePostSave($data)
    {
        if (self::$logging_suspended || !$this->ensureUserlogReady()) {
            return;
        }
        $post_id = $this->resolvePostId($data);
        if (!$post_id || !empty(self::$logged_posts[$post_id])) {
            return;
        }

        $before = userlogLogger::pullPostBefore($post_id);
        $after_snapshot = blogUserlogPostSnapshot::captureForLog($post_id);
        if (!$after_snapshot) {
            return;
        }

        $after = ifset($after_snapshot, 'post', array());
        $title = ifset($after, 'title', '#'.$post_id);

        if ($before) {
            $diff = userlogHelper::formatDiff(
                blogUserlogPostSnapshot::flattenForDiff($before),
                blogUserlogPostSnapshot::flattenForDiff($after_snapshot),
                'post'
            );
            if (!$diff) {
                return;
            }
            if ($this->hasRecentDuplicateEvent($post_id, $diff)) {
                return;
            }
            userlogLogger::log(array(
                'app_id'       => 'blog',
                'action'       => 'post.update',
                'entity_type'  => 'post',
                'entity_id'    => $post_id,
                'entity_name'  => $title,
                'summary'      => $this->buildUpdateSummary($title, $diff),
                'before_data'  => $before,
                'after_data'   => $after_snapshot,
                'can_rollback' => 1,
            ));
        } else {
            userlogLogger::log(array(
                'app_id'       => 'blog',
                'action'       => 'post.create',
                'entity_type'  => 'post',
                'entity_id'    => $post_id,
                'entity_name'  => $title,
                'summary'      => 'Создана запись «'.$title.'»',
                'after_data'   => $after_snapshot,
                'can_rollback' => 0,
            ));
        }

        self::$logged_posts[$post_id] = true;
    }

    protected function resolvePostId($data)
    {
        if (!is_array($data)) {
            return 0;
        }
        return (int) ifset($data, 'id', 0);
    }

    protected function buildUpdateSummary($title, array $diff)
    {
        if (!$diff) {
            return 'Изменена запись «'.$title.'»';
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        return 'Изменена «'.$title.'» — '.implode('; ', $parts);
    }

    protected function hasRecentDuplicateEvent($post_id, array $diff)
    {
        if (!$diff) {
            return false;
        }
        $model = new userlogEventModel();
        $recent = $model->query(
            "SELECT summary FROM userlog_event
             WHERE entity_id = i:id AND action = 'post.update' AND app_id = 'blog'
               AND datetime >= s:since
             ORDER BY id DESC LIMIT 3",
            array(
                'id'    => (int) $post_id,
                'since' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
            )
        )->fetchAll(null, true);
        if (!$recent) {
            return false;
        }
        $parts = array();
        foreach (array_slice($diff, 0, 5) as $line) {
            $parts[] = $line['label'].': '.$line['before'].' → '.$line['after'];
        }
        $needle = implode('; ', $parts);
        foreach ($recent as $summary) {
            if ($summary && strpos($summary, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
}
