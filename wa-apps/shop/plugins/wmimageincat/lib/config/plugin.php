<?php
return array(
    'name' => 'Иконка, изображение и баннер для категории',
    'img' => 'img/wmimageincat.png',
    'description'=>'размеры и генерация эскизов',
    'vendor' => 873332,
    'version' => '1.5',
    'shop_settings' => true,
    'frontend' => true,
    'handlers' => array(
        'backend_category_dialog' => 'backendCategoryDialog',
        'category_save' => 'saveCategorySettings',
        'category_delete' => 'deleteCategory'
    ),
);