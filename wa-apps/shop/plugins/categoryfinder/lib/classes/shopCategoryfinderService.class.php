<?php

class shopCategoryfinderService
{
    const PARAM_WITHOUT_PROD = 'without_prod';

    const DUPLICATE_NONE = '';
    const DUPLICATE_NAME = 'name';
    const DUPLICATE_URL = 'url';
    const DUPLICATE_BOTH = 'both';

    const DEFAULT_URL_SIMILARITY = 85;

    const STOREFRONT_ALL = '';
    const STOREFRONT_EMPTY = 'empty';
    const STOREFRONT_FROM_SUB = 'from_sub';
    const STOREFRONT_OWN = 'own';
    const STOREFRONT_ANY = 'any';

    /**
     * @param array $filters
     * @return array<int, array>
     */
    public function getList(array $filters)
    {
        $min_depth = max(1, (int) ifset($filters, 'level', 1));
        $count_filter = ifset($filters, 'cnt', '');
        $active = ifset($filters, 'active', '');
        $redirect = ifset($filters, 'redirect', '');
        $without_prod = ifset($filters, 'without_prod', '');
        $storefront = (string) ifset($filters, 'storefront', '');
        $duplicate_mode = (string) ifset($filters, 'duplicate', '');
        $duplicate_similarity = (int) ifset($filters, 'duplicate_similarity', self::DEFAULT_URL_SIMILARITY);
        $duplicate_similarity = max(50, min(100, $duplicate_similarity));

        $categories = (new shopCategoryModel())->query(
            "SELECT id, parent_id, depth, name, url, full_url, status, count, include_sub_categories
             FROM shop_category
             WHERE depth >= i:depth AND status >= 0
             ORDER BY depth ASC, name ASC",
            array('depth' => $min_depth)
        )->fetchAll('id');

        if (!$categories) {
            return array();
        }

        $category_ids = array_keys($categories);
        $without_prod_map = $this->getWithoutProdMap($category_ids);

        $by_count = array();
        foreach ($categories as $id => $cat) {
            if (!$this->matchesActiveFilter($cat, $active)) {
                continue;
            }
            if (!$this->matchesRedirectFilter($cat, $redirect)) {
                continue;
            }
            if (!$this->matchesWithoutProdFilter($id, $without_prod_map, $without_prod)) {
                continue;
            }

            $count = (int) ifset($cat, 'count', 0);
            $by_count[$count][] = (int) $id;
        }

        $ids = $this->resolveCountFilter($by_count, $count_filter);
        if (!$ids) {
            return array();
        }

        $subtree_products_map = $this->getSubtreeProductsMap($ids);

        if ($storefront !== '') {
            $ids = array_values(array_filter($ids, function ($id) use ($categories, $subtree_products_map, $storefront) {
                return isset($categories[$id])
                    && $this->matchesStorefrontFilter($categories[$id], $subtree_products_map, $storefront);
            }));
            if (!$ids) {
                return array();
            }
        }

        $duplicate_labels = array();
        if ($duplicate_mode !== self::DUPLICATE_NONE) {
            $duplicate_labels = $this->findDuplicateMatches(
                $categories,
                $ids,
                $duplicate_mode,
                $duplicate_similarity
            );
            $ids = array_keys($duplicate_labels);
            if (!$ids) {
                return array();
            }
        }

        $rows = array();
        $public_url_prefix = $this->getCategoryPublicUrlPrefix();
        $public_url_type = $this->getCategoryPublicUrlType();
        $admin_url_base = wa()->getAppUrl('shop') . '?action=products#/products/category_id=';

        foreach ($ids as $id) {
            if (!isset($categories[$id])) {
                continue;
            }

            $cat = $categories[$id];

            $rows[] = array(
                'id' => (int) $id,
                'depth' => (int) ifset($cat, 'depth', 0),
                'count' => (int) ifset($cat, 'count', 0),
                'include_sub_categories' => !empty($cat['include_sub_categories']),
                'subtree_count' => (int) ifset($subtree_products_map, $id, 0),
                'storefront_label' => $this->getStorefrontLabel($cat, $subtree_products_map),
                'status' => (int) ifset($cat, 'status', 0),
                'name' => (string) ifset($cat, 'name', ''),
                'url' => (string) ifset($cat, 'url', ''),
                'admin_url' => $admin_url_base . $id,
                'public_url' => $this->buildCategoryPublicUrl($cat, $public_url_prefix, $public_url_type),
                'without_prod' => !empty($without_prod_map[$id]),
                'duplicate_label' => ifset($duplicate_labels, $id, ''),
                'sort_name' => $this->normalizeName(ifset($cat, 'name', '')),
            );
        }

        usort($rows, function ($a, $b) use ($duplicate_mode) {
            if ($duplicate_mode !== self::DUPLICATE_NONE) {
                $name_cmp = strcmp($a['sort_name'], $b['sort_name']);
                if ($name_cmp !== 0) {
                    return $name_cmp;
                }
            }

            if ($a['depth'] !== $b['depth']) {
                return $a['depth'] - $b['depth'];
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $rows;
    }

    /**
     * @param int $category_id
     * @param bool $without_prod
     */
    public function setWithoutProd($category_id, $without_prod)
    {
        $category_id = (int) $category_id;
        if (!$category_id) {
            return false;
        }

        $params_model = new shopCategoryParamsModel();
        $params = $params_model->get($category_id);

        if ($without_prod) {
            $params[self::PARAM_WITHOUT_PROD] = '1';
        } else {
            unset($params[self::PARAM_WITHOUT_PROD]);
        }

        return $params_model->set($category_id, $params);
    }

    /**
     * @param int[] $category_ids
     * @return array<int, bool>
     */
    protected function getWithoutProdMap(array $category_ids)
    {
        if (!$category_ids) {
            return array();
        }

        $rows = (new shopCategoryParamsModel())->query(
            "SELECT category_id, value
             FROM shop_category_params
             WHERE name = s:name AND category_id IN (i:ids)",
            array(
                'name' => self::PARAM_WITHOUT_PROD,
                'ids' => $category_ids,
            )
        )->fetchAll('category_id');

        $map = array();
        foreach ($rows as $category_id => $row) {
            $map[(int) $category_id] = !empty($row['value']) && $row['value'] !== '0';
        }

        return $map;
    }

    protected function matchesActiveFilter(array $cat, $active)
    {
        if ($active === '' || $active === null) {
            return true;
        }

        $is_active = (int) ifset($cat, 'status', 0) > 0;

        if ((string) $active === '1') {
            return $is_active;
        }
        if ((string) $active === '0') {
            return !$is_active;
        }

        return true;
    }

    protected function matchesRedirectFilter(array $cat, $redirect)
    {
        if ($redirect === '' || $redirect === null) {
            return true;
        }

        $url = (string) ifset($cat, 'url', '');
        $is_redirect = (bool) preg_match('/-r$/', $url);

        if ((string) $redirect === '1') {
            return !$is_redirect;
        }
        if ((string) $redirect === '0') {
            return $is_redirect;
        }

        return true;
    }

    /**
     * @param int[] $category_ids
     * @return array<int, int> category_id => sum of count in descendants (excluding self)
     */
    protected function getSubtreeProductsMap(array $category_ids)
    {
        if (!$category_ids) {
            return array();
        }

        $rows = (new shopCategoryModel())->query(
            "SELECT parent.id AS category_id, COALESCE(SUM(child.count), 0) AS subtree_count
             FROM shop_category parent
             INNER JOIN shop_category child
                ON child.left_key > parent.left_key
               AND child.right_key < parent.right_key
             WHERE parent.id IN (i:ids)
             GROUP BY parent.id",
            array('ids' => $category_ids)
        );

        $map = array();
        foreach ($category_ids as $id) {
            $map[(int) $id] = 0;
        }
        foreach ($rows as $row) {
            $map[(int) $row['category_id']] = (int) $row['subtree_count'];
        }

        return $map;
    }

    /**
     * @param array $cat
     * @param array<int, int> $subtree_products_map
     * @param string $storefront
     * @return bool
     */
    protected function matchesStorefrontFilter(array $cat, array $subtree_products_map, $storefront)
    {
        if ($storefront === '' || $storefront === null) {
            return true;
        }

        $id = (int) ifset($cat, 'id', 0);
        $own_count = (int) ifset($cat, 'count', 0);
        $include_sub = !empty($cat['include_sub_categories']);
        $subtree_count = (int) ifset($subtree_products_map, $id, 0);
        $visible = $this->isVisibleOnStorefront($own_count, $include_sub, $subtree_count);

        switch ($storefront) {
            case self::STOREFRONT_EMPTY:
                return !$visible;
            case self::STOREFRONT_FROM_SUB:
                return $own_count === 0 && $include_sub && $subtree_count > 0;
            case self::STOREFRONT_OWN:
                return $own_count > 0;
            case self::STOREFRONT_ANY:
                return $visible;
        }

        return true;
    }

    /**
     * @param int $own_count
     * @param bool $include_sub
     * @param int $subtree_count
     * @return bool
     */
    protected function isVisibleOnStorefront($own_count, $include_sub, $subtree_count)
    {
        if ($own_count > 0) {
            return true;
        }

        return $include_sub && $subtree_count > 0;
    }

    /**
     * @param array $cat
     * @param array<int, int> $subtree_products_map
     * @return string
     */
    protected function getStorefrontLabel(array $cat, array $subtree_products_map)
    {
        $id = (int) ifset($cat, 'id', 0);
        $own_count = (int) ifset($cat, 'count', 0);
        $include_sub = !empty($cat['include_sub_categories']);
        $subtree_count = (int) ifset($subtree_products_map, $id, 0);

        if ($own_count > 0 && $include_sub && $subtree_count > 0) {
            return 'Свои+подк.';
        }
        if ($own_count > 0) {
            return 'Свои';
        }
        if ($include_sub && $subtree_count > 0) {
            return 'Из подкат.';
        }

        return 'Пусто';
    }

    protected function matchesWithoutProdFilter($category_id, array $without_prod_map, $without_prod)
    {
        if ($without_prod === '' || $without_prod === null) {
            return true;
        }

        $flag = !empty($without_prod_map[$category_id]);

        if ((string) $without_prod === '1') {
            return $flag;
        }
        if ((string) $without_prod === '0') {
            return !$flag;
        }

        return true;
    }

    /**
     * @param array<int, int[]> $by_count
     * @param mixed $count_filter
     * @return int[]
     */
    protected function resolveCountFilter(array $by_count, $count_filter)
    {
        if ($count_filter === '' || $count_filter === null) {
            $ids = array();
            array_walk_recursive($by_count, function ($item) use (&$ids) {
                $ids[] = (int) $item;
            });

            return $ids;
        }

        if (!is_numeric($count_filter)) {
            return array();
        }

        $count = (int) $count_filter;

        return ifset($by_count, $count, array());
    }

    /**
     * @param array<int, array> $categories
     * @param int[] $ids
     * @param string $mode
     * @param int $similarity_threshold
     * @return array<int, string> category_id => match label
     */
    protected function findDuplicateMatches(array $categories, array $ids, $mode, $similarity_threshold)
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $result = array();

        $name_groups = array();
        foreach ($ids as $id) {
            if (!isset($categories[$id])) {
                continue;
            }
            $name_key = $this->normalizeName($categories[$id]['name']);
            if ($name_key === '') {
                continue;
            }
            $name_groups[$name_key][] = $id;
        }

        $name_duplicate_map = array();
        foreach ($name_groups as $group) {
            if (count($group) < 2) {
                continue;
            }
            foreach ($group as $id) {
                $name_duplicate_map[$id] = $group;
            }
        }

        $url_similarity_map = null;
        if ($mode === self::DUPLICATE_URL) {
            $url_similarity_map = $this->buildUrlSimilarityMap($categories, $ids, $similarity_threshold);
        }

        foreach ($ids as $id) {
            if (!isset($categories[$id])) {
                continue;
            }

            $labels = array();

            if ($mode === self::DUPLICATE_NAME) {
                if (empty($name_duplicate_map[$id])) {
                    continue;
                }
                foreach ($name_duplicate_map[$id] as $other_id) {
                    if ($other_id === $id) {
                        continue;
                    }
                    $labels[] = $this->formatDuplicateLabel($other_id, 'имя');
                }
            } elseif ($mode === self::DUPLICATE_URL) {
                if (empty($url_similarity_map[$id])) {
                    continue;
                }
                arsort($url_similarity_map[$id]);
                foreach ($url_similarity_map[$id] as $other_id => $percent) {
                    $labels[] = $this->formatDuplicateLabel($other_id, 'URL ' . round($percent) . '%');
                }
            } elseif ($mode === self::DUPLICATE_BOTH) {
                if (empty($name_duplicate_map[$id])) {
                    continue;
                }
                $my_url = $this->normalizeUrl(ifset($categories[$id], 'url', ''));
                foreach ($name_duplicate_map[$id] as $other_id) {
                    if ($other_id === $id) {
                        continue;
                    }
                    $other_url = $this->normalizeUrl(ifset($categories[$other_id], 'url', ''));
                    $percent = $this->getUrlSimilarity($my_url, $other_url);
                    if ($percent >= $similarity_threshold) {
                        $labels[] = $this->formatDuplicateLabel($other_id, 'имя+URL ' . round($percent) . '%');
                    }
                }
                if (!$labels) {
                    continue;
                }
            } else {
                continue;
            }

            if ($labels) {
                $result[$id] = $this->truncateDuplicateLabels($labels);
            }
        }

        return $result;
    }

    /**
     * @param array<int, array> $categories
     * @param int[] $ids
     * @param int $similarity_threshold
     * @return array<int, array<int, float>>
     */
    protected function buildUrlSimilarityMap(array $categories, array $ids, $similarity_threshold)
    {
        $map = array();
        $url_by_id = array();

        foreach ($ids as $id) {
            if (!isset($categories[$id])) {
                continue;
            }
            $url = $this->normalizeUrl(ifset($categories[$id], 'url', ''));
            if ($url === '') {
                continue;
            }
            $url_by_id[$id] = $url;
        }

        if (!$url_by_id) {
            return $map;
        }

        $exact_groups = array();
        foreach ($url_by_id as $id => $url) {
            $exact_groups[$url][] = $id;
        }
        foreach ($exact_groups as $group) {
            if (count($group) < 2) {
                continue;
            }
            $this->storeUrlSimilarityPairs($map, $group, 100.0);
        }

        if ($similarity_threshold >= 100) {
            return $map;
        }

        $len_buckets = array();
        foreach ($url_by_id as $id => $url) {
            $len_buckets[strlen($url)][] = $id;
        }

        $lengths = array_keys($len_buckets);
        sort($lengths, SORT_NUMERIC);

        foreach ($lengths as $idx => $len_a) {
            $this->compareUrlSimilarityBucket($map, $url_by_id, $len_buckets[$len_a], $similarity_threshold);

            for ($j = $idx + 1; $j < count($lengths); $j++) {
                $len_b = $lengths[$j];
                if ($len_b - $len_a > max($len_a, $len_b) * 0.5) {
                    break;
                }
                $this->compareUrlSimilarityBucketsCross(
                    $map,
                    $url_by_id,
                    $len_buckets[$len_a],
                    $len_buckets[$len_b],
                    $similarity_threshold
                );
            }
        }

        return $map;
    }

    /**
     * @param array<int, array<int, float>> $map
     * @param int[] $ids
     * @param int $similarity_threshold
     */
    protected function compareUrlSimilarityBucket(array &$map, array $url_by_id, array $ids, $similarity_threshold)
    {
        if (count($ids) < 2) {
            return;
        }

        $prefix_buckets = array();
        foreach ($ids as $id) {
            $url = $url_by_id[$id];
            $prefix = substr($url, 0, min(3, strlen($url)));
            $prefix_buckets[$prefix][] = $id;
        }

        foreach ($prefix_buckets as $group) {
            $this->compareUrlSimilarityGroup($map, $url_by_id, $group, $similarity_threshold);
        }
    }

    /**
     * @param array<int, array<int, float>> $map
     * @param int[] $ids_a
     * @param int[] $ids_b
     * @param int $similarity_threshold
     */
    protected function compareUrlSimilarityBucketsCross(
        array &$map,
        array $url_by_id,
        array $ids_a,
        array $ids_b,
        $similarity_threshold
    ) {
        if (!$ids_a || !$ids_b) {
            return;
        }

        $prefix_buckets_a = array();
        foreach ($ids_a as $id) {
            $url = $url_by_id[$id];
            $prefix = substr($url, 0, min(3, strlen($url)));
            $prefix_buckets_a[$prefix][] = $id;
        }

        foreach ($ids_b as $id_b) {
            $url_b = $url_by_id[$id_b];
            $prefix = substr($url_b, 0, min(3, strlen($url_b)));
            if (empty($prefix_buckets_a[$prefix])) {
                continue;
            }
            foreach ($prefix_buckets_a[$prefix] as $id_a) {
                if ($id_a === $id_b || isset($map[$id_a][$id_b])) {
                    continue;
                }
                $percent = $this->getUrlSimilarity($url_by_id[$id_a], $url_b);
                if ($percent >= $similarity_threshold) {
                    $map[$id_a][$id_b] = $percent;
                    $map[$id_b][$id_a] = $percent;
                }
            }
        }
    }

    /**
     * @param array<int, array<int, float>> $map
     * @param int[] $group
     * @param int $similarity_threshold
     */
    protected function compareUrlSimilarityGroup(array &$map, array $url_by_id, array $group, $similarity_threshold)
    {
        $count = count($group);
        if ($count < 2) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            $id_a = $group[$i];
            for ($j = $i + 1; $j < $count; $j++) {
                $id_b = $group[$j];
                if (isset($map[$id_a][$id_b])) {
                    continue;
                }
                $percent = $this->getUrlSimilarity($url_by_id[$id_a], $url_by_id[$id_b]);
                if ($percent >= $similarity_threshold) {
                    $map[$id_a][$id_b] = $percent;
                    $map[$id_b][$id_a] = $percent;
                }
            }
        }
    }

    /**
     * @param array<int, array<int, float>> $map
     * @param int[] $group
     * @param float $percent
     */
    protected function storeUrlSimilarityPairs(array &$map, array $group, $percent)
    {
        $count = count($group);
        for ($i = 0; $i < $count; $i++) {
            $id_a = $group[$i];
            for ($j = $i + 1; $j < $count; $j++) {
                $id_b = $group[$j];
                $map[$id_a][$id_b] = $percent;
                $map[$id_b][$id_a] = $percent;
            }
        }
    }

    /**
     * @param string $url_a
     * @param string $url_b
     * @return float
     */
    protected function getUrlSimilarity($url_a, $url_b)
    {
        if ($url_a === '' || $url_b === '') {
            return 0.0;
        }
        if ($url_a === $url_b) {
            return 100.0;
        }

        $len_a = strlen($url_a);
        $len_b = strlen($url_b);
        if (abs($len_a - $len_b) > max($len_a, $len_b) * 0.5) {
            return 0.0;
        }

        $percent = 0.0;
        similar_text($url_a, $url_b, $percent);

        return (float) $percent;
    }

    protected function normalizeName($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $name = mb_strtolower($name, 'UTF-8');
        } else {
            $name = strtolower($name);
        }

        return preg_replace('/\s+/u', ' ', $name);
    }

    protected function normalizeUrl($url)
    {
        return trim(strtolower((string) $url));
    }

    protected function formatDuplicateLabel($category_id, $reason)
    {
        return (int) $category_id . ' (' . $reason . ')';
    }

    protected function truncateDuplicateLabels(array $labels)
    {
        $labels = array_values(array_unique($labels));
        if (count($labels) > 5) {
            return implode(', ', array_slice($labels, 0, 5)) . '…';
        }

        return implode(', ', $labels);
    }

    /** @var string|null */
    protected $category_public_url_prefix = null;

    /** @var int|null */
    protected $category_public_url_type = null;

    /**
     * @return string
     */
    protected function getCategoryPublicUrlPrefix()
    {
        if ($this->category_public_url_prefix !== null) {
            return $this->category_public_url_prefix;
        }

        $route_info = $this->getStorefrontRoute();
        if (!$route_info) {
            $this->category_public_url_prefix = '';
            $this->category_public_url_type = 0;

            return '';
        }

        list($domain, $route) = $route_info;
        wa()->getRouting()->setRoute($route, $domain);

        $this->category_public_url_type = (int) ifset($route, 'url_type', 0);
        $sample = wa()->getRouteUrl('/frontend/category', array(
            'category_url' => '__slug__',
        ), true, $domain, $route['url']);

        $this->category_public_url_prefix = preg_replace('/__slug__\/?$/', '', $sample);

        return $this->category_public_url_prefix;
    }

    /**
     * @return int
     */
    protected function getCategoryPublicUrlType()
    {
        if ($this->category_public_url_type === null) {
            $this->getCategoryPublicUrlPrefix();
        }

        return (int) $this->category_public_url_type;
    }

    /**
     * @param array $cat
     * @param string $prefix
     * @param int $url_type
     * @return string
     */
    protected function buildCategoryPublicUrl(array $cat, $prefix, $url_type)
    {
        if ($prefix === '') {
            return '';
        }

        $category_url = ($url_type === 1) ? ifset($cat, 'url', '') : ifset($cat, 'full_url', '');
        if ($category_url === '') {
            return '';
        }

        return $prefix . $category_url . '/';
    }

    /**
     * @param array $cat
     * @return string
     */
    protected function getCategoryPublicUrl(array $cat)
    {
        return $this->buildCategoryPublicUrl(
            $cat,
            $this->getCategoryPublicUrlPrefix(),
            $this->getCategoryPublicUrlType()
        );
    }

    /**
     * @return array{0:string,1:array}|null
     */
    protected function getStorefrontRoute()
    {
        foreach (wa()->getRouting()->getByApp('shop') as $domain => $routes) {
            foreach ($routes as $route) {
                if (!empty($route['private'])) {
                    continue;
                }

                return array($domain, $route);
            }
        }

        return null;
    }
}
