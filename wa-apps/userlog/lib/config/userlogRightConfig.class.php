<?php

class userlogRightConfig extends waRightConfig
{
    public function init()
    {
        $this->addItem('view', 'Просмотр журнала');
        $this->addItem('rollback', 'Откат действий');
        $this->addItem('trash', 'Корзина и восстановление');
        $this->addItem('settings', 'Настройки');
    }
}
