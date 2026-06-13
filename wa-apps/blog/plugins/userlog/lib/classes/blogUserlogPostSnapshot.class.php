<?php

class blogUserlogPostSnapshot
{
    protected static $status_labels = array(
        blogPostModel::STATUS_DRAFT     => 'Черновик',
        blogPostModel::STATUS_PUBLISHED => 'Опубликован',
        blogPostModel::STATUS_SCHEDULED => 'Запланирован',
        blogPostModel::STATUS_DEADLINE  => 'Дедлайн',
    );

    public static function captureForLog($post_id)
    {
        $post_id = (int) $post_id;
        if (!$post_id) {
            return null;
        }

        $post_model = new blogPostModel();
        $post = $post_model->getById($post_id);
        if (!$post) {
            return null;
        }

        $params_model = new blogPostParamsModel();
        $params = $params_model->getByField('post_id', $post_id, true);

        $blog_name = '';
        if (!empty($post['blog_id'])) {
            $blog = (new blogBlogModel())->getById($post['blog_id']);
            $blog_name = ifset($blog, 'name', '');
        }

        return array(
            'post'      => self::trimPost($post),
            'params'    => $params ?: array(),
            'blog_name' => $blog_name,
            'captured_at' => date('Y-m-d H:i:s'),
        );
    }

    public static function flattenForDiff(array $snapshot)
    {
        $post = ifset($snapshot, 'post', array());
        $flat = array(
            'title'              => ifset($post, 'title', ''),
            'status'             => self::formatStatus(ifset($post, 'status', '')),
            'blog'               => ifset($snapshot, 'blog_name', ifset($post, 'blog_id', '')),
            'url'                => ifset($post, 'url', ''),
            'datetime'           => ifset($post, 'datetime', ''),
            'text'               => self::textExcerpt(ifset($post, 'text', '')),
            'meta_title'         => ifset($post, 'meta_title', ''),
            'meta_keywords'      => ifset($post, 'meta_keywords', ''),
            'meta_description'   => ifset($post, 'meta_description', ''),
            'comments_allowed'   => !empty($post['comments_allowed']) ? 'да' : 'нет',
        );
        return $flat;
    }

    protected static function trimPost(array $post)
    {
        return $post;
    }

    protected static function textExcerpt($text)
    {
        return userlogHelper::plainTextForDisplay((string) $text, 160);
    }

    protected static function formatStatus($status)
    {
        return ifset(self::$status_labels, $status, (string) $status);
    }

    public static function prepareForRestore(array $snapshot, $post_id)
    {
        $post_id = (int) $post_id;
        if (!empty($snapshot['post']) && is_array($snapshot['post'])) {
            $snapshot['post']['id'] = $post_id;
        }
        return $snapshot;
    }

    /**
     * @return int post id
     */
    public static function restore(array $snapshot, $post_id)
    {
        wa('blog');
        $post_id = (int) $post_id;
        $post_data = ifset($snapshot, 'post', array());
        if (!$post_id || !$post_data) {
            throw new waException('Пустой снимок записи');
        }

        $model = new blogPostModel();
        $current = $model->getById($post_id);
        if (!$current) {
            throw new waException('Запись не найдена');
        }

        $partial = ifset($snapshot, '_partial_restore', array());
        $fields = array(
            'title', 'text', 'status', 'blog_id', 'url', 'datetime',
            'meta_title', 'meta_keywords', 'meta_description', 'comments_allowed',
            'text_before_cut', 'cut_link_label', 'contact_id', 'album_id', 'album_link_type',
        );

        $update = array();
        if ($partial) {
            foreach ((array) $partial as $field) {
                if (array_key_exists($field, $post_data)) {
                    $update[$field] = $post_data[$field];
                }
            }
        } else {
            foreach ($fields as $field) {
                if (array_key_exists($field, $post_data)) {
                    $update[$field] = $post_data[$field];
                }
            }
        }

        if (!$update) {
            throw new waException('Нет полей для восстановления');
        }

        $model->updateItem($post_id, array_merge($current, $update));
        return $post_id;
    }
}
