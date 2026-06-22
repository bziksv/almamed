<?php

class shopCategoryfinderPlugin extends shopPlugin
{
    /**
     * @event backend_menu
     * @return array
     */
    public function backendMenu()
    {
        if (!wa()->getUser()->getRights('shop', 'settings')) {
            return array();
        }

        $this->addJs('js/menu-tab.js?v=' . $this->getVersion());
        $this->addJs('js/admin.js?v=' . $this->getVersion());

        $selected = waRequest::get('plugin') === 'categoryfinder' ? 'selected' : 'no-tab';

        return array(
            'core_li' => '<li class="' . $selected . ' categoryfinder-topmenu-li">'
                . '<a href="?action=plugins#/categoryfinder/">'
                . '<i class="icon16 folder"></i> Поиск категорий'
                . '</a></li>',
        );
    }
}
