<?php

class shopProductbrandsPluginFrontendBrandsAction extends shopFrontendAction
{
    const BRANDS_PER_PAGE = 120;

    public function execute()
    {
        if ($t = wa()->getSetting('template_brands', '', array('shop', 'productbrands'))) {
            $t = preg_replace('/\{\$b\.name\|truncate:\d+:"[^"]*"\}/', '{$b.name|escape}', $t);
            $t = str_replace('<a href="{$b.url}">', '<a href="{$b.url}" title="{$b.name|escape}">', $t);
            $t = preg_replace(
                '/<div class="name-brand">\s*\{\$b\.name\|escape\}\s*<\/div>/',
                '<div class="name-brand"><span class="name-brand__text">{$b.name|escape}</span></div>',
                $t
            );
            $t = preg_replace(
                '/\s*data-src="\{\$wa_url\}wa-data\/public\/shop\/brands\/\{\$b\.id\}\/\{\$b\.id\}\{\$b\.image\}"/',
                '',
                $t
            );
            $t = str_replace(
                'src="/wa-data/public/shop/brands/{$b.id}/{$b.id}{$b.image}"',
                'src="{$wa_url}wa-data/public/shop/brands/{$b.id}/{$b.id}{$b.image}" loading="lazy"',
                $t
            );
            $t = str_replace('alt="{$b.name}"', 'alt="{$b.name|escape}"', $t);
            $template = 'string:'.$t;
        } else {
            $template = 'file:'.wa()->getAppPath('plugins/productbrands/templates/', 'shop').'frontendBrands.html';
        }

        $per_page = self::BRANDS_PER_PAGE;
        $total = shopProductbrandsPlugin::getBrandsCount();
        $pages_count = $per_page ? (int) ceil($total / $per_page) : 1;
        $page = waRequest::get('page', 1, 'int');
        if ($page < 1) {
            $page = 1;
        }
        if ($pages_count && $page > $pages_count) {
            $page = $pages_count;
        }
        $brands = shopProductbrandsPlugin::getBrandsPage(
            ($page - 1) * $per_page,
            $per_page
        );

        $this->view->assign('brands', $brands);
        $this->view->assign('brands_total', $total);
        $this->view->assign('brands_page', $page);
        $this->view->assign('brands_pages_count', $pages_count);

        $plugin = wa('shop')->getPlugin('productbrands');

        $title = $plugin->getSettings('brands_name');
        if (!$title) {
            $title = _w('Brands');
        }

        $this->setThemeTemplate('page.html');

        $this->getResponse()->addCss('plugins/productbrands/css/brands-page.css', 'shop');

        $content = $this->view->fetch($template);
        if ($pages_count > 1) {
            $content .= $this->renderPagination($page, $pages_count);
        }

        $this->view->assign('page', array(
            'id' => 'brands',
            'title' => $title,
            'name' => $title,
            'content' => $content
        ));

        $this->getResponse()->setTitle($title);

        if ($tmp = $plugin->getSettings('brands_meta_description')) {
            $this->getResponse()->setMeta('description', $tmp);
        }
        if ($tmp = $plugin->getSettings('brands_meta_keywords')) {
            $this->getResponse()->setMeta('keywords', $tmp);
        }

        waSystem::popActivePlugin();
    }

    protected function renderPagination($page, $pages_count)
    {
        $base_url = wa()->getRouteUrl('shop/frontend/brands');
        $html = '<div class="block paging-nav brands-pagination">';
        $html .= '<ul>';

        if ($page > 1) {
            $html .= '<li><a href="'.htmlspecialchars($base_url.($page > 2 ? '?page='.($page - 1) : '')).'">&larr;</a></li>';
        }

        $window = 5;
        $start = max(1, $page - $window);
        $end = min($pages_count, $page + $window);
        if ($start > 1) {
            $html .= '<li><a href="'.htmlspecialchars($base_url).'">1</a></li>';
            if ($start > 2) {
                $html .= '<li><span class="brands-pagination__dots">…</span></li>';
            }
        }
        for ($p = $start; $p <= $end; $p++) {
            $href = $base_url.($p > 1 ? '?page='.$p : '');
            if ($p == $page) {
                $html .= '<li class="selected"><a href="'.htmlspecialchars($href).'">'.$p.'</a></li>';
            } else {
                $html .= '<li><a href="'.htmlspecialchars($href).'">'.$p.'</a></li>';
            }
        }
        if ($end < $pages_count) {
            if ($end < $pages_count - 1) {
                $html .= '<li><span class="brands-pagination__dots">…</span></li>';
            }
            $html .= '<li><a href="'.htmlspecialchars($base_url.'?page='.$pages_count).'">'.$pages_count.'</a></li>';
        }

        if ($page < $pages_count) {
            $html .= '<li><a href="'.htmlspecialchars($base_url.'?page='.($page + 1)).'">&rarr;</a></li>';
        }

        $html .= '</ul></div>';

        return $html;
    }
}