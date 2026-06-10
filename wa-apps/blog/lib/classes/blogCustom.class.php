<?

class blogCustom {
	
	public static function str_width($str = null, $long = 200){
		
		if(!$str)
            return false;

        $string = strip_tags($str);
        $string = substr($string, 0, $long);
        $string = rtrim($string, "!,.-");
        $string = substr($string, 0, strrpos($string, ' '));
        return $string."… ";
	}
	
	
}