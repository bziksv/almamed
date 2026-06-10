<?php
/**
* Класс для работы с таблицей 'shop_wmimageincat_images'
*/
class shopWmimageincatModel extends waModel
{
    protected $table = 'shop_wmimageincat_images';

    /**
     * Получение данных по id категории
     *
     * @param int $cat_id
     * @return array|null
     */
    function getAllofCat($cat_id){
        $result = $this->getByField('category_id', $cat_id, true);
        return $result;
    }


    /**
     * Удаление данных по id категории
     *
     * @param int $cat_id
     */
    function deleteCategoryDataById($cat_id) {
        $this->deleteByField('category_id', $cat_id);
    }
}