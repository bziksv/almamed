<?php
/**
 * Class shopDirecteditPlugin
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Date: 11.09.2016
 */
class shopDirecteditPlugin extends shopPlugin
{

    /**
     * Подключаем js, и настраиваем параметры
     *
     * @param $params
     * @return array
     */
    public function on_backend_products($params)
    {
        // Опеределяем версию плагина
        $version = ifset($this->info['version'], '1.0');

        //Подключаем CSS JS с учетом версии плагина
        $this->addCSS('css/directedit.css?version='.$version.'&v=');
        $this->addJs('js/directedit.js?version='.$version.'&v=');

        //Параметры
        $use_product_list = (int)$this->getSettings("use_product_list")==1 ? "true" : "false";
        $use_jots = (int)$this->getSettings("use_jots")==1 ? "true" : "false";
        $use_stocks = (int)$this->getSettings("use_stocks")==1 ? "true" : "false";
        $use_scroll = (int)$this->getSettings("use_scroll")==1 ? "true" : "false";

        //Перевод
        $look = _wp("Просмотр");

        return array('sidebar_top_li'=>
            "<script>
                directedit__echocompany.lang.look = '$look';
                directedit__echocompany.use_product_list = $use_product_list;
                directedit__echocompany.use_jots = $use_jots; 
                directedit__echocompany.use_stocks = $use_stocks;
                directedit__echocompany.use_scroll = $use_scroll;
            </script>");
    }

    /**
     * Обработка окна редактирования, вызов функции JS
     * @return array
     */
    public function on_backend_product_edit(){
        return array('basics'=>'<script>directedit__echocompany.init_edit()</script>');
    }

}