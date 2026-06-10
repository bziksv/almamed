<?php

// Fix bug with incorrect actions path after update 1.4.3.
try {
    $autoload_file = wa('shop')->getCachePath('config/autoload.php');
    waFiles::delete($autoload_file);
}
catch (Exception $e)
{
    // :(
}
