<?php

class shopSearchproException extends waException
{
	public $errors = array(
		'EMPTY_QUERY' => 'Пустая строка поиска'
	);

	public function __construct($message = '', $code = 500, $previous = null)
	{
		$message = ifset($this->errors, $message, null);

		parent::__construct($message, $code, $previous);
	}
}