<?php

//Удаление данных из общедоступной области
$path = wa()->getDataPath("wmimageincatPlugin/", true, 'shop');
waFiles::delete($path, false);

//Удаление данных из защищённой области
$path = wa()->getDataPath("wmimageincatPlugin/", false, 'shop');
waFiles::delete($path, false);