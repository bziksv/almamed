<?php

class shopProductmanagerCategoryModel extends waModel
{
    protected $table = 'shop_productmanager_category';

    /**
     * @return array<int,int> category_id => manager_id
     */
    public function getAllBindings()
    {
        $result = array();
        foreach ($this->query('SELECT category_id, manager_id FROM ' . $this->table) as $row) {
            $result[(int) $row['category_id']] = (int) $row['manager_id'];
        }
        return $result;
    }

    public function getManagerId($category_id)
    {
        $category_id = (int) $category_id;
        if (!$category_id) {
            return 0;
        }
        return (int) $this->select('manager_id')
            ->where('category_id = ?', $category_id)
            ->fetchField();
    }

    public function setBinding($category_id, $manager_id)
    {
        $category_id = (int) $category_id;
        $manager_id = (int) $manager_id;

        if (!$category_id) {
            return false;
        }

        if (!$manager_id) {
            return (bool) $this->deleteByField('category_id', $category_id);
        }

        if ($this->getByField('category_id', $category_id)) {
            return (bool) $this->updateByField('category_id', $category_id, array(
                'manager_id' => $manager_id,
                'updated' => date('Y-m-d H:i:s'),
            ));
        }

        return (bool) $this->insert(array(
            'category_id' => $category_id,
            'manager_id' => $manager_id,
            'updated' => date('Y-m-d H:i:s'),
        ));
    }

    public function removeBinding($category_id)
    {
        return (bool) $this->deleteByField('category_id', (int) $category_id);
    }
}
