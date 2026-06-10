<?php

final class shopWholesale {

    private static $items = array();

    private static function getItems() {
        if (self::$items) {
            return self::$items;
        } else {
            $cart = new shopCart();
            return $cart->items();
        }
    }

    private static function getTotal() {
        if (self::$items) {
            $total = 0;
            foreach (self::$items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            return $total;
        } else {
            $cart = new shopCart();
            return $cart->total(true);
        }
    }

    private static function getCount() {
        if (self::$items) {
            $count = 0;
            foreach (self::$items as $item) {
                if ($item['type'] == 'product') {
                    $count += $item['quantity'];
                }
            }
            return $count;
        } else {
            $cart = new shopCart();
            return $cart->count();
        }
    }

    /**
     * Проверка текущего заказ на соответсвие минимальным требованиям.
     * Возвращает result = TRUE - если условия минимального заказа выполняются, FALSE - если условия не выполняются.
     * message - сообщение об ошибке.
     * 
     * @return array('result' => boolean, 'message' => string)
     */
    public static function checkOrder($items = null) {
        $return = array(
            'result' => true,
            'message' => '',
        );

        $route_hash = null;
        if (shopWholesaleRouteHelper::getRouteSettings(null, 'status')) {
            $route_hash = null;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings();
        } elseif (shopWholesaleRouteHelper::getRouteSettings(0, 'status')) {
            $route_hash = 0;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings(0);
        } else {
            return $return;
        }

        if ($items && class_exists('shopInstantorderPlugin')) {
            self::$items = $items;
        }

        $primary_currency = wa('shop')->getConfig()->getCurrency(true);
        $frontend_currency = wa('shop')->getConfig()->getCurrency(false);

        $total = self::getTotal();
        $total = shop_currency($total, $frontend_currency, $primary_currency, false);
        $min_order_sum = $route_settings['min_order_sum'];
        $min_order_sum_format = shop_currency($min_order_sum);
        $count = self::getCount();

        if ($route_settings['min_order_sum_enabled'] && $total < $min_order_sum) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['min_order_sum_message'], $min_order_sum_format);
        } elseif ($route_settings['min_order_products_enabled'] && $count < $route_settings['min_order_products']) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['min_order_products_message'], $route_settings['min_order_products']);
        } elseif ($route_settings['product_count_setting'] && !self::checkMinProductsCartCount($product_name, $min_product_count)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['min_product_count_message'], $product_name, $min_product_count);
        } elseif ($route_settings['product_multiplicity_setting'] && !self::checkMultiplicityProductsCartCount($product_name, $multiplicity_product_count)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['multiplicity_product_message'], $product_name, $multiplicity_product_count);
        } elseif ($route_settings['category_sum_setting'] && !self::checkMinCategorySum($category_name, $min_category_sum)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['min_order_sum_category_message'], $category_name, shop_currency($min_category_sum));
        } elseif ($route_settings['category_count_setting'] && !self::checkMinCategoryCount($category_name, $min_category_count)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['min_order_count_category_message'], $category_name, $min_category_count);
        } elseif ($route_settings['product_count_setting'] && !self::checkMinSkusCartCount($product_name, $min_sku_count)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['min_product_count_message'], $product_name, $min_sku_count);
        } elseif ($route_settings['product_multiplicity_setting'] && !self::checkMultiplicitySkusCartCount($product_name, $multiplicity_sku_count)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['multiplicity_product_message'], $product_name, $multiplicity_sku_count);
        }
		
		$cart_item = (new shopCart())->items();
		foreach($cart_item as $item){
			if((int)$item['product']['price'] === 0)
				$return['result'] = true;
		}
		
        return $return;
    }

    /**
     * Проверка минимальной суммы заказа для способа доставки с $shipping_id
     * Возвращает result = TRUE - если условия минимальной суммы выполняются, FALSE - если условия не выполняются.
     * message - сообщение об ошибке.
     * 
     * @param int $shipping_id
     * @return array('result' => boolean, 'message' => string)
     */
    public static function checkShipping($shipping_id) {
        $return = array(
            'result' => true,
            'message' => '',
        );

        $route_hash = null;
        if (shopWholesaleRouteHelper::getRouteSettings(null, 'status')) {
            $route_hash = null;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings();
        } elseif (shopWholesaleRouteHelper::getRouteSettings(0, 'status')) {
            $route_hash = 0;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings(0);
        } else {
            return $return;
        }

        $plugins = $route_settings['plugins'];

        $def_currency = wa('shop')->getConfig()->getCurrency(true);
        $cur_currency = wa('shop')->getConfig()->getCurrency(false);
        $total = self::getTotal();
        $total = shop_currency($total, $cur_currency, $def_currency, false);

        if (!empty($plugins[$shipping_id]) && $total < $plugins[$shipping_id]) {
            $message = sprintf($route_settings['shipping_message'], shop_currency($plugins[$shipping_id]));
            $return = array('result' => 0, 'message' => $message);
        }

        return $return;
    }

    /**
     * 
     */
    public static function checkProduct($product_id, $sku_id = null, $quantity = null, $old_quantity = null) {
        if (!$quantity) {
            $quantity = 1;
        }
        $return = array(
            'result' => true,
            'message' => '',
            'quantity' => $quantity,
        );
        $route_hash = null;
        if (shopWholesaleRouteHelper::getRouteSettings(null, 'status')) {
            $route_hash = null;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings();
        } elseif (shopWholesaleRouteHelper::getRouteSettings(0, 'status')) {
            $route_hash = 0;
            $route_settings = shopWholesaleRouteHelper::getRouteSettings(0);
        } else {
            return $return;
        }

        $product_model = new shopProductModel();
        $product = $product_model->getById($product_id);
        if ($sku_id) {
            $sku_model = new shopProductSkusModel();
            $sku = $sku_model->getById($sku_id);
        }

        if ($route_settings['product_count_setting'] && !self::checkMinProductCount($product, $quantity, $product_name, $min_product_count)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['min_product_count_message'], $product_name, $min_product_count);
            $return['quantity'] = $min_product_count;
        } elseif ($route_settings['product_multiplicity_setting'] && !self::checkMultiplicityProductCount($product, $quantity, $product_name, $multiplicity_product_count)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['multiplicity_product_message'], $product_name, $multiplicity_product_count);
            if ($old_quantity < $quantity) {
                $k = ceil($quantity / $multiplicity_product_count);
                $set_quantity = $k * $multiplicity_product_count;
            } else {
                $k = floor($quantity / $multiplicity_product_count);
                $set_quantity = $k * $multiplicity_product_count;
            }
            if ($set_quantity == 0) {
                $set_quantity = $multiplicity_product_count;
            }
            $return['quantity'] = $set_quantity;
        } elseif ($route_settings['product_count_setting'] && !empty($sku) && !self::checkMinSkuCount($sku, $quantity, $product_name, $min_sku_count)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['min_product_count_message'], $product_name, $min_sku_count);
            $return['quantity'] = $min_sku_count;
        } elseif ($route_settings['product_multiplicity_setting'] && !self::checkMultiplicitySkuCount($sku, $quantity, $product_name, $multiplicity_sku_count)) {
            $return['result'] = false;
            $return['message'] = sprintf($route_settings['multiplicity_product_message'], $product_name, $multiplicity_sku_count);
            if ($old_quantity < $quantity) {
                $k = ceil($quantity / $multiplicity_sku_count);
                $set_quantity = $k * $multiplicity_sku_count;
            } else {
                $k = floor($quantity / $multiplicity_sku_count);
                $set_quantity = $k * $multiplicity_sku_count;
            }
            if ($set_quantity == 0) {
                $set_quantity = $multiplicity_sku_count;
            }
            $return['quantity'] = $set_quantity;
        }

        return $return;
    }

    /**
     * Проверка наличия в корзине минимального количества товара для категории
     * Возвращает TRUE - если условия минимального количества выполняются, FALSE - если условия не выполняются.
     * 
     * @param string $category_name - в эту переменную записывается имя категории, для которой условия минимального количества не выполняются.
     * @param int $min_category_count - в эту переменную записывается минимальное количество товара для категории.
     * @return boolean
     */
    public static function checkMinCategoryCount(&$category_name = null, &$min_category_count = null) {
        $items = self::getItems();
        $wholesale_categories = array();
        $category_products_model = new shopCategoryProductsModel();
        foreach ($items as $item) {
            if ($item['type'] == 'product') {
                $product_categories = $category_products_model->getByField('product_id', $item['product']['id'], true);
                foreach ($product_categories as $product_category) {
                    if (self::getCategoryMinCount($product_category['category_id'], $category)) {
                        $wholesale_categories[] = $category;
                    }
                }
            }
        }
        if ($wholesale_categories) {
            foreach ($wholesale_categories as $wholesale_category) {
                $category_count = self::getCategoryProductsCount($wholesale_category['id']);
                if ($category_count < $wholesale_category['wholesale_min_product_count']) {
                    $min_category_count = $wholesale_category['wholesale_min_product_count'];
                    $category_name = $wholesale_category['name'];
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Возвращает количество товара добавленного в корзину для заданной категории и ее подкатегорий
     * @param array $category_id
     * @return int
     */
    protected static function getCategoryProductsCount($category_id) {
        $category_model = new shopCategoryModel();
        $category = $category_model->getById($category_id);
        $count = 0;
        $items = self::getItems();
        foreach ($items as $item) {
            if ($item['type'] == 'product' && self::inCategory($category, $item['product'])) {
                $count += $item['quantity'];
            }
        }
        return $count;
    }

    /**
     * Возвращает минимальное количество товаров для заказа для указанной категории. 
     * Текущая категория может наслетовать количество минимального товара от родительской категории, поэтому функция вызывается рекурсивно.
     * 
     * @param int $category_id - идентификатор категории, для которой возвращается минимальное количество товаров для заказа. 
     * @param string $category_name - имя категории, для которой установлено ограничение минимального количества товаров.
     * @return int
     */
    public static function getCategoryMinCount($category_id, &$category) {
        $category_model = new shopCategoryModel();
        $category = $category_model->getById($category_id);
        if ($category['wholesale_min_product_count'] > 0) {
            return $category['wholesale_min_product_count'];
        } elseif ($category['parent_id']) {
            return self::getCategoryMinCount($category['parent_id'], $category);
        }
        return 0;
    }

    /**
     * Проверка минимальную сумму товаров для категории. 
     * Возвращает TRUE - если условия минимальной суммы выполняются, FALSE - если условия не выполняются.
     *  
     * @param string $category_name - в эту переменную записывается имя категории, для которой условия минимальной суммы не выполняются.
     * @param type $min_category_sum - в эту переменную записывается минимальная сумма заказа для категории.
     * @return boolean
     */
    public static function checkMinCategorySum(&$category_name = null, &$min_category_sum = null) {
        $items = self::getItems();
        $wholesale_categories = array();
        $category_products_model = new shopCategoryProductsModel();
        foreach ($items as $item) {
            if ($item['type'] == 'product') {
                $product_categories = $category_products_model->getByField('product_id', $item['product']['id'], true);
                foreach ($product_categories as $product_category) {
                    if (self::getCategoryMinSum($product_category['category_id'], $category)) {
                        $wholesale_categories[] = $category;
                    }
                }
            }
        }
        if ($wholesale_categories) {
            foreach ($wholesale_categories as $wholesale_category) {
                $category_sum = self::getCategoryProductsSum($wholesale_category['id']);
                if ($category_sum < $wholesale_category['wholesale_min_sum']) {
                    $min_category_sum = $wholesale_category['wholesale_min_sum'];
                    $category_name = $wholesale_category['name'];
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Возвращает сумму товаров добавленных в корзину для заданной категории и ее подкатегорий. 
     * @param array $category
     * @return float
     */
    protected static function getCategoryProductsSum($category_id) {
        $category_model = new shopCategoryModel();
        $category = $category_model->getById($category_id);
        $sum = 0;
        $items = self::getItems();
        $primary_currency = wa('shop')->getConfig()->getCurrency(true);
        foreach ($items as $item) {
            if ($item['type'] == 'product' && self::inCategory($category, $item['product'])) {
                $sum += shop_currency($item['price'], $item['product']['currency'], $primary_currency, false) * $item['quantity'];
            }
        }
        return $sum;
    }

    /**
     * Возвращает минимальную сумму товаров для заказа для указанной категории. 
     * Текущая категория может наслетовать минимальную сумму товара от родительской категории, поэтому функция вызывается рекурсивно.
     * 
     * @param int $category_id - идентификатор категории, для которой возвращается минимальное количество товаров для заказа. 
     * @param string $category_name - имя категории, для которой установлено ограничение минимального количества товаров.
     * @return int
     */
    public static function getCategoryMinSum($category_id, &$category) {
        $category_model = new shopCategoryModel();
        $category = $category_model->getById($category_id);
        if ($category['wholesale_min_sum'] > 0) {
            return $category['wholesale_min_sum'];
        } elseif ($category['parent_id']) {
            return self::getCategoryMinSum($category['parent_id'], $category);
        }
        return 0;
    }

    /**
     * Проверка наличия товара в указанной категории или подкатегориях.
     * 
     * @param type $category
     * @param type $product
     * @return boolean
     */
    protected static function inCategory($category, $product) {
        if ($product['category_id'] == $category['id']) {
            return true;
        } else {
            $category_model = new shopCategoryModel();
            $subcategories = $category_model->getTree($category['id'], null, false);
            if (!empty($subcategories[$product['category_id']])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверка минимального количества заказанных продуктов.
     * Возвращает TRUE - если условие минимального количества для товара выполняется, FALSE - если условие не выполняется.
     * 
     * @param type $product_name - в эту переменную записывается имя товара, для которого условие минимального количества не выполняется.
     * @param type $min_product_count - в эту переменную записывается минимальное количество товара.
     * @return boolean
     */
    public static function checkMinProductsCartCount(&$product_name = null, &$min_product_count = null, &$item = null) {
        $items = self::getItems();
        foreach ($items as $item) {
            if ($item['type'] == 'product' && !self::checkMinProductCount($item['product'], $item['quantity'], $product_name, $min_product_count)) {
                return false;
            }
        }
        return true;
    }

    public static function checkMinProductCount($product, $quantity, &$product_name = null, &$min_product_count = null) {
        if ($quantity < $product['wholesale_min_product_count']) {
            $product_name = $product['name'];
            $min_product_count = $product['wholesale_min_product_count'];
            return false;
        }
        return true;
    }

    /**
     * Проверка кратности количества заказанных продуктов.
     * Возвращает TRUE - если условие кратности количества для товара выполняется, FALSE - если условие не выполняется.
     * 
     * @param type $product_name - в эту переменную записывается имя товара, для которого условие кратности количества не выполняется.
     * @param type $multiplicity_product_count - в эту переменную записывается кратность товара.
     */
    public static function checkMultiplicityProductsCartCount(&$product_name = null, &$multiplicity_product_count = null, &$item = null) {
        $items = self::getItems();
        foreach ($items as $item) {
            if ($item['type'] == 'product' && !self::checkMultiplicityProductCount($item['product'], $item['quantity'], $product_name, $multiplicity_product_count)) {
                return false;
            }
        }
        return true;
    }

    public static function checkMultiplicityProductCount($product, $quantity, &$product_name = null, &$multiplicity_product_count = null) {
        if ($product['wholesale_multiplicity'] > 0 && $quantity % $product['wholesale_multiplicity'] != 0) {
            $product_name = $product['name'];
            $multiplicity_product_count = $product['wholesale_multiplicity'];
            return false;
        }
        return true;
    }

    /**
     * Проверка минимального количества заказанных продуктов для артикулов.
     * Возвращает TRUE - если условие минимального количества для товара выполняется, FALSE - если условие не выполняется.
     * 
     * @param type $product_name - в эту переменную записывается имя товара, для которого условие минимального количества не выполняется.
     * @param type $min_sku_count - в эту переменную записывается минимальное количество товара для выбранного артикула.
     * @return boolean
     */
    public static function checkMinSkusCartCount(&$product_name = null, &$min_sku_count = null, &$item = null) {
        $sku_model = new shopProductSkusModel();
        $items = self::getItems();
        ;
        foreach ($items as $item) {
            if ($item['type'] == 'product') {
                $sku = $sku_model->getById($item['sku_id']);
                if (!empty($sku) && !self::checkMinSkuCount($sku, $item['quantity'], $product_name, $min_sku_count)) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function checkMinSkuCount($sku, $quantity, &$product_name = null, &$min_sku_count = null) {
        if (!empty($sku) && $quantity < $sku['wholesale_min_sku_count']) {
            $product_model = new shopProductModel();
            $product = $product_model->getById($sku['product_id']);

            $product_name = $product['name'];
            if (!empty($sku['name'])) {
                $product_name .= " (" . $sku['name'] . ")";
            }
            $min_sku_count = $sku['wholesale_min_sku_count'];
            return false;
        }
        return true;
    }

    /**
     * Проверка кратности количества заказанных продуктов для артикулов.
     * Возвращает TRUE - если условие кратности количества для товара выполняется, FALSE - если условие не выполняется.
     * 
     * @param type $product_name - в эту переменную записывается имя товара, для которого условие кратности количества не выполняется.
     * @param type $multiplicity_product_count - в эту переменную записывается кратность товара для артикула.
     */
    public static function checkMultiplicitySkusCartCount(&$product_name = null, &$multiplicity_sku_count = null, &$item = null) {
        $sku_model = new shopProductSkusModel();
        $items = self::getItems();
        foreach ($items as $item) {
            if ($item['type'] == 'product') {
                $sku = $sku_model->getById($item['sku_id']);
                if (!empty($sku) && !self::checkMultiplicitySkuCount($sku, $item['quantity'], $product_name, $multiplicity_sku_count)) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function checkMultiplicitySkuCount($sku, $quantity, &$product_name = null, &$multiplicity_sku_count = null) {
        if ($sku['wholesale_sku_multiplicity'] > 0 && $quantity % $sku['wholesale_sku_multiplicity'] != 0) {
            $product_model = new shopProductModel();
            $product = $product_model->getById($sku['product_id']);

            $product_name = $product['name'];
            if (!empty($sku['name'])) {
                $product_name .= " (" . $sku['name'] . ")";
            }
            $multiplicity_sku_count = $sku['wholesale_sku_multiplicity'];
            return false;
        }
        return true;
    }

}
