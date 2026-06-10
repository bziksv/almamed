<?php
class shopDescriptionPlugin extends shopPlugin
{
    private $table = "shop_product";
    private $cols = "description";
    private $start = 0;
    public static $limit = 70;
    public $count;

    public function getEmptyField($col,$from){
        $col = ($col) ? $col : $this->cols;
        $result = $this->getSqlEmptyField($col,$from);
        if($result){
            $data = $result->fetchAll();
            foreach($data as &$d){
                $cat = $this->getCategory($d['category_id']);
                $d['cat_name'] = $cat['name'];
                $d['cat_url'] = $cat['url'];
                $category_root = $this->getCategoryRoot($cat['parent_id']);
                $d['cat_parent_name'] = $category_root['name'];
                $d['cat_parent_url'] = $category_root['url'];
            }
            return $data;
        }else return false;

    }

    private function getSqlEmptyField($col,$start){
        $limit = self::$limit;
        $start = ($start) ? $start : $this->start;
        $model = new waModel();
        $sql = "SELECT * FROM $this->table WHERE $col = '' AND not_desc = 0 ORDER BY id ASC LIMIT $start, $limit";
        $result = $model->query($sql);
        if($result->count()){
            $this->count = $this->countSql();
            return $result;
        }else
            return false;
    }

    private function countSql(){
        $model = new waModel();
        $sql = "SELECT * FROM $this->table WHERE $this->cols = '' AND not_desc = 0 ORDER BY id ASC";
        $result = $model->query($sql);
        return $result->count();
    }

    public function updateProduct($id,$status){
        $model = new waModel();
        $sql = "UPDATE shop_product SET not_desc = $status WHERE id = $id;";
        $result = $model->query($sql);
        return $result->affectedRows();
    }

    public function getCategory($id)
    {
        $model = new waModel();
        $sql = "SELECT * FROM shop_category WHERE id = '$id'";
        return $model->query($sql)->fetch();
    }

    private function getCategoryRoot($parent_id){
            $cat = $this->getCategory($parent_id);
            if($cat['parent_id'] > 0){
                return $this->getCategoryRoot($cat['parent_id']);
            }
        return $cat;
    }

    public function product_edit($params){

        $check = ($params['not_desc']) ? "selected" : "";
        $html = '<div class="field">
                    <div class="name">Товар без описания</div>
                    <div class="value no-shift">
                    <select name="product[not_desc]"><option value="0">Выкл.</option><option value="1" '.$check.'>Вкл.</option></select>
                    </div>
                </div>';

        return array(
            'edit_basics'  => $html,
        );
    }

}