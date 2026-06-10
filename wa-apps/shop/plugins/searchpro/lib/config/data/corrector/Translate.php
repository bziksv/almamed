<?php

return array(
	'parts' => array(
		'веб' => 'web',
		'скрин' => 'screen',
		'фон$' => 'phone',
		'айл' => 'ile',
		'ай$' => 'ie',
		'кся' => 'xia',
		'айз' => 'yz',
		'ово' => 'ovo'
	),

	'table' => array(
		'а' => array(
			'next_is' => array(
				'й' => 'i'
			),
			'else' => 'a'
		),
		'б' => 'b',
		'в' => array(
			'previous_is' => array(
				'vowel' => 'u',
				'к' => 'u',
				'у' => 'w'
			),
			'else' => 'v'
		),
		'г' => 'g',
		'д' => 'd',
		'е' => 'e',
		'ё' => 'yo',
		'ж' => array(
			'is_last' => 'ge',
			'else' => 's'
		),
		'з' => 'z',
		'и' => array(
			'is_last' => 'ee',
			'else' => 'i'
		),
		'й' => array(
			'first_is' => array(
				'а' => ''
			),
			'previous_is' => array(
				'е' => 'y'
			),
			'else' => 'gh'
		),
		'к' => array(
			'next_is' => array(
				'в' => 'q',
				'с' => 'x'
			),
			'previous_is' => array(
				'с' => 'c'
			),
			'else' => 'k'
		),
		'л' => array(
			'previous_is' => array(
				'л' => 'l'
			),
			'next_is' => array(
				'л' => 'l',
			),
			'is_last' => 'le',
			'else' => 'l'
		),
		'м' => 'm',
		'н' => 'n',
		'о' => 'o',
		'п' => 'p',
		'р' => 'r',
		'с' => array(
			'previous_is' => array(
				'к' => ''
			),
			'else' => 's'
		),
		'т' => 't',
		'у' => 'u',
		'ф' => 'f',
		'х' => 'h',
		'ц' => 'c',
		'ч' => 'ch',
		'ш' => 'sh',
		'щ' => 'sh',
		'ъ' => '',
		'ы' => 'y',
		'ь' => '',
		'э' => array(
			'is_first' => 'a',
			'else' => 'e'
		),
		'ю' => 'yu',
		'я' => 'ya'
	)
);