<?php

class shopSettingsPrintformTemplateActions extends waJsonActions
{
    /**
     * @var shopPlugin|shopPrintformInterface
     */
    private $plugin;

    protected function preExecute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }

        $id = waRequest::post('id');
        if (preg_match('@^[a-z][a-z_0-9]*$@', $id)) {
            $this->plugin = waSystem::getInstance()->getPlugin($id);
        } else {
            throw new waException(_w("Unknown plugin"));
        }

        if (
            !is_object($this->plugin)
            ||
            !method_exists($this->plugin, 'resetTemplate')
            ||
            !method_exists($this->plugin, 'saveTemplate')
        ) {
            throw new waException(_w("Unknown plugin"));
        }
    }

    public function saveAction()
    {
        $template = waRequest::post('template', '', waRequest::TYPE_STRING_TRIM);
        if (!$template) {
            $this->errors[] = _w("Empty template");
            return;
        }
        $userlog = shopUserlogPlugin::getInstance();
        $plugin_id = waRequest::post('id');
        $before = $userlog ? shopUserlogSettingsSnapshot::capturePrintformTemplate($plugin_id) : null;

        $this->plugin->saveTemplate($template);

        if ($userlog && $before !== null) {
            $userlog->logSettingsChange(
                'Печатная форма: '.$plugin_id,
                $before,
                shopUserlogSettingsSnapshot::capturePrintformTemplate($plugin_id)
            );
        }
    }

    public function resetAction()
    {
        $template = $this->plugin->resetTemplate();
        $this->response['template'] = $template ? $template : $this->plugin->getTemplate();
    }
}
