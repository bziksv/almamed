<?php

class shopSearchproKeyboardLayoutSmartCyrillicCorrector
{
	public $diactrical_re = '\xcc[\x80-\xb9]|\xcd[\x80-\xaf]|\xe2\x83[\x90-\xbf]|\xe1\xb7[\x80-\xbf]| \xef\xb8[\xa0-\xaf]|\xc2\xad';

	public function deleteDiactrical($query, &$restore = array())
	{
		$regexp = '/((?>' . $this->diactrical_re . ')+)/sxSX';

		$restore = array();
		$splitted = preg_split($regexp, $query, -1, PREG_SPLIT_DELIM_CAPTURE);
		$splitted_count = count($splitted);

		if($splitted_count === 1)
			return $query;

		$position = 0;
		$query2 = '';

		for($i = 0; $i < $splitted_count - 1; $i += 2) {
			$query2 .= $splitted[$i];
			$position += mb_strlen($splitted[$i]);
			$restore['offsets'][$position] = $splitted[$i + 1];
		}

		$restore['length'] = $position + mb_strlen(end($splitted));
		return $query2 . end($splitted);
	}

	public static function returnDiactrical($query, $restore)
	{
		if(!$restore)
			return $query;

		if(!is_int(@$restore['length']) || !is_array(@$restore['offsets']) || $restore['length'] !== mb_strlen($query))
			return false;

		$query2 = '';
		$offset = 0;

		foreach($restore['offsets'] as $position => $diactricals) {
			$length = $position - $offset;
			$query2 .= mb_substr($query, $offset, $length) . $diactricals;
			$offset = $position;
		}

		return $query2 . mb_substr($query, $offset, strlen($query));
	}
}