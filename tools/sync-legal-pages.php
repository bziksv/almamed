<?php
/**
 * Создаёт/обновляет юридические страницы магазина из wa-data/public/shop/legal-pages/.
 *
 * Локально и на prod:
 *   php tools/sync-legal-pages.php
 */
require dirname(__DIR__) . '/wa-config/SystemConfig.class.php';

$config = new SystemConfig('cli');
waSystem::getInstance(null, $config);
wa()->setActive('shop');
wa('shop');

$content_dir = dirname(__DIR__) . '/wa-data/public/shop/legal-pages';

$page_model = new shopPageModel();

$targets = $page_model->query(
    'SELECT DISTINCT domain, route FROM ' . $page_model->getTableName() . ' WHERE status = 1 ORDER BY domain, route'
)->fetchAll();

if (!$targets) {
    fwrite(STDERR, "No published shop pages found for domain/route reference.\n");
    exit(1);
}

$pages = array(
    array(
        'url' => 'pravovye-dokumenty-almamed/',
        'name' => 'Правовые документы',
        'title' => 'Правовые документы АльмаМед',
        'file' => 'index.html',
        'description' => 'Юридические документы интернет-магазина АльмаМед: политика персональных данных, cookie, рекомендательные технологии.',
    ),
    array(
        'url' => 'soglasie-na-obrabotku-pd/',
        'name' => 'Согласие на обработку персональных данных',
        'title' => 'Согласие на обработку персональных данных — АльмаМед',
        'file' => 'consent.html',
        'parent_url' => 'pravovye-dokumenty-almamed/',
        'description' => 'Согласие на обработку персональных данных интернет-магазина АльмаМед.',
    ),
    array(
        'url' => 'politika-personalnyh-dannyh/',
        'name' => 'Политика обработки персональных данных',
        'title' => 'Политика обработки персональных данных — АльмаМед',
        'file' => 'personal-data.html',
        'parent_url' => 'pravovye-dokumenty-almamed/',
        'description' => 'Политика обработки персональных данных ООО «Альмамед».',
    ),
    array(
        'url' => 'politika-cookie/',
        'name' => 'Политика использования cookie-файлов',
        'title' => 'Политика cookie — АльмаМед',
        'file' => 'cookie.html',
        'parent_url' => 'pravovye-dokumenty-almamed/',
        'description' => 'Политика использования cookie-файлов на сайте almamed.su.',
    ),
    array(
        'url' => 'rekomendatelnye-tehnologii/',
        'name' => 'Правила применения рекомендательных технологий',
        'title' => 'Рекомендательные технологии — АльмаМед',
        'file' => 'recommendation.html',
        'parent_url' => 'pravovye-dokumenty-almamed/',
        'description' => 'Правила применения рекомендательных технологий на сайте almamed.su.',
    ),
);

function read_legal_content($content_dir, $file)
{
    $path = rtrim($content_dir, '/') . '/' . $file;
    if (!is_readable($path)) {
        throw new waException('Legal content file not found: ' . $path);
    }

    return trim(file_get_contents($path));
}

function find_page(shopPageModel $page_model, array $reference, $url, $parent_id = null)
{
    $sql = 'SELECT * FROM ' . $page_model->getTableName() . '
        WHERE domain = s:domain AND route = s:route AND url = s:url';
    $params = array(
        'domain' => $reference['domain'],
        'route' => $reference['route'],
        'url' => $url,
    );
    if ($parent_id) {
        $sql .= ' AND parent_id = i:parent_id';
        $params['parent_id'] = $parent_id;
    } else {
        $sql .= ' AND parent_id IS NULL';
    }

    return $page_model->query($sql, $params)->fetch();
}

function upsert_page(shopPageModel $page_model, array $reference, array $def, $parent_id = null)
{
    $existing = find_page($page_model, $reference, $def['url'], $parent_id);

    $data = array(
        'parent_id' => $parent_id,
        'domain' => $reference['domain'],
        'route' => $reference['route'],
        'name' => $def['name'],
        'title' => $def['title'],
        'url' => $def['url'],
        'content' => $def['content'],
        'status' => 1,
    );

    if (!empty($def['description'])) {
        $data['description'] = $def['description'];
    }

    if ($existing) {
        $page_model->update($existing['id'], $data);
        $id = $existing['id'];
        $action = 'updated';
    } else {
        $id = $page_model->add($data);
        $action = 'created';
    }

    $page = $page_model->getById($id);
    if ($parent_id) {
        $parent = $page_model->getById($parent_id);
        $full_url = rtrim($parent['full_url'], '/') . '/' . ltrim($def['url'], '/');
    } else {
        $full_url = $def['url'];
    }
    $page_model->updateById($id, array('full_url' => $full_url));

    return array($id, $action, $full_url);
}

echo "Content dir: {$content_dir}\n";

foreach ($pages as &$def) {
    $def['content'] = read_legal_content($content_dir, $def['file']);
}
unset($def);

foreach ($targets as $reference) {
    echo "\nDomain: {$reference['domain']}, route: {$reference['route']}\n";

    $parent_id = null;
    $ids = array();

    foreach ($pages as $def) {
        $pid = !empty($def['parent_url']) ? ifset($ids, $def['parent_url'], $parent_id) : null;
        list($id, $action, $full_url) = upsert_page($page_model, $reference, $def, $pid);
        $ids[$def['url']] = $id;
        if (empty($def['parent_url'])) {
            $parent_id = $id;
        }
        echo strtoupper($action) . " #{$id} /{$full_url}\n";
    }
}

$cache_path = wa()->getConfig()->getPath('cache') . '/apps/shop/frontend_page';
if (file_exists($cache_path)) {
    waFiles::delete($cache_path, true);
}

echo "\nDone. Clear wa-cache if pages do not appear immediately.\n";
