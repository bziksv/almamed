<?php 
/*
	Подключите данный скрипт на страницы, на которые ведут рекламные объявления. При необходимости подключите скрипт на все страницы Вашего сайта.
	В большинстве случаев подключать легче в основной шаблон сайта или в шаблоны, которые отвечают за header и footer.

	Пример: в шаблон добавляется строка
		include_once($path);
		где $path - путь к данному скрипту;	
	Например:
	<?php 
		$path = "./clickfrogru_udp_tcp.php";
		include_once($path);
	?>
	
	Если код подключен, но не определяется, и проверка кода по адресу http://ваш.домен/clickfrogru_udp_tcp.php говорит "Серверный скрипт: нет", попробуйте скачать и подключить этот скрипт - http://a.clickfrog.ru/clickfrogru_tcp.zip
	Если код подключен, но не определяется, и проверка кода по адресу http://ваш.домен/clickfrogru_udp_tcp.php говорит "Серверный скрипт: есть" - вы некорректно подключили файл clickfrogru_udp_tcp.php к страницам вашего сайта.
*/   

class ClickfrogUDPSender
{
	private static $CLFG_REQUEST = array('_COUNTER_UDP_PORT' => '83', '_COUNTER_HOST' => 'stat.clickfrog.ru', '_COUNTER_GET'=>'/server_side_action.php');
	
	public static function sendto()
	{
		try {
		
		$occur = false;		
		if(isset($_SERVER['HTTP_REFERER']) && isset($_SERVER['HTTP_HOST'])) {
			if(preg_match("/^(https?:\/\/)?(www.)?([^\/?]+)/i", $_SERVER['HTTP_REFERER'], $ref_matches) && preg_match("/^(https?:\/\/)?(www.)?([^\/?]+)/i", $_SERVER['HTTP_HOST'], $d_matches)) {
				if(isset($ref_matches[3]) && isset($d_matches[3]) && $ref_matches[3] != '' && $d_matches[3] != '' && $d_matches[3] != $ref_matches[3]) {
					$occur = true;
				}
			}
		}
		elseif(isset($_SERVER['HTTP_HOST']) && preg_match("/^(https?:\/\/)?(www.)?([^\/?]+)/i", $_SERVER['HTTP_HOST'], $d_matches) && (!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER']==''))
		{
			$occur = true;
		}

		if(!$occur)
			return 0;		
		
		$s_udp = @fsockopen("udp://".self::$CLFG_REQUEST['_COUNTER_HOST'], self::$CLFG_REQUEST['_COUNTER_UDP_PORT'], $errno, $errstr);
		
		$headers = array("HTTP_HOST", "REMOTE_ADDR", "REQUEST_METHOD", "REQUEST_URI", "PATH_INFO", "HTTP_REFERER", "HTTP_X_FORWARDED_FOR", "QUERY_STRING", "REQUEST_URI_CLICKFROG", "HTTP_USER_AGENT");
		$srv = array();
		foreach($headers AS $h)
			if(isset($_SERVER[$h]))
				$srv[$h] = $_SERVER[$h];
		if(count($srv) == 0)
			exit;
			
		$msg = 'header='.@urlencode(@json_encode($srv));
		$msg_id = self::msgid($msg);

		$send_data = sprintf('CFSTAT#%s[%s]END', $msg_id, $msg);	
		
		$tries = 5;
		$len = strlen($send_data);
		$err_id = false;
		for($i=0;$i<$tries;$i++)
		{	
			if($s_udp) {
				//send mesg
				@fwrite($s_udp, $send_data);				
			}
			else {				
				$err_id = 1;
				break;
			}			
		}		
		@fclose($s_udp);
		if($err_id!== false && $err_id === 1)			
			self::sendto_tcp($msg);
		}
		catch(Exception $e) { } 
	}
	
	private static function sendto_tcp($msg)
	{
		try {  
			$fp = fsockopen(self::$CLFG_REQUEST['_COUNTER_HOST'], 80, $errno, $errstr, 1); 
			if ($fp) {
				$out = '';
				
				$post = $msg;
				$out .= "POST ".self::$CLFG_REQUEST['_COUNTER_GET']." HTTP/1.0\r\n";
				$out .= "Content-Length: ".strlen($post)."\r\n";
				$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$out .= "Host: ".self::$CLFG_REQUEST['_COUNTER_HOST']."\r\n";
				$out .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16\r\n";
				$out .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
				$out .= "Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7\r\n";
				$out .= "Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3\r\n";
				$out .= "Accept-Encoding: none\r\n";
				$out .= "Connection: Close\r\n\r\n";
				$out .= $post;    
				fwrite($fp, $out);
				stream_set_timeout($fp,2); 
				while (!feof($fp)) {
					fgets($fp, 128);
				}
				fclose($fp);   
			}
		}
		catch(Exception $e) {
		} 
	}
	
	private static function getmicrotime() 
	{ 
		$res = time();
		if(function_exists("microtime"))
		{
			list($usec, $sec) = explode(" ", microtime());			
			$res = ((float)$usec + (float)$sec) * 10000;			
		}
		if(strlen($res) > 8)
			return substr($res, strlen($res) - 8, 8);
		return $res;
	}	
	
	private static function msgid($data)
	{	//max size-16	
		return str_replace('-','',sprintf("%08x%08x", crc32($data), self::getmicrotime()));
	}		
	
}

ClickfrogUDPSender::sendto();
?>
