<?php

class shopFormRuCities
{
    const OTHER_VALUE = '__other__';
    const OTHER_LABEL = 'Моего города нет в списке';
    const TOP20_SEPARATOR = 'Топ-20 городов по численности населения';
    const ALL_SEPARATOR = 'Все города России (А–Я)';

    /** @var array|null */
    private static $data;

    public static function getData()
    {
        if (self::$data === null) {
            $path = wa()->getAppPath('plugins/form/lib/data/russian_cities.php', 'shop');
            self::$data = is_readable($path) ? include $path : array('top20' => array(), 'all' => array());
        }
        return self::$data;
    }

    public static function getTop20()
    {
        $data = self::getData();
        return (array) ifset($data, 'top20', array());
    }

    public static function getAll()
    {
        $data = self::getData();
        return (array) ifset($data, 'all', array());
    }

    public static function getRestAlphabetical()
    {
        $top = array_flip(self::getTop20());
        $rest = array();
        foreach (self::getAll() as $city) {
            if (!isset($top[$city])) {
                $rest[] = $city;
            }
        }
        return $rest;
    }

    public static function isKnownCity($city)
    {
        $city = trim((string) $city);
        if ($city === '') {
            return false;
        }
        return in_array($city, self::getAll(), true);
    }

    /**
     * @return array{value:string,label:string}|null
     */
    public static function resolveFromPost($city, $city_custom)
    {
        $city = trim((string) $city);
        $city_custom = trim(strip_tags((string) $city_custom));

        if ($city === self::OTHER_VALUE) {
            if ($city_custom === '') {
                return null;
            }
            return array(
                'value' => $city_custom,
                'label' => $city_custom . ' (' . self::OTHER_LABEL . ')',
            );
        }

        if (!self::isKnownCity($city)) {
            return null;
        }

        return array(
            'value' => $city,
            'label' => $city,
        );
    }

    public static function getJsonForJs()
    {
        return json_encode(array(
            'otherValue' => self::OTHER_VALUE,
            'otherLabel' => self::OTHER_LABEL,
            'top20Separator' => self::TOP20_SEPARATOR,
            'allSeparator' => self::ALL_SEPARATOR,
            'top20' => self::getTop20(),
            'rest' => self::getRestAlphabetical(),
        ), JSON_UNESCAPED_UNICODE);
    }
}
