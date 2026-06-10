<?php

class shopSignupEmailPolicy
{
    const SUPPORT_PHONE = '8-800-100-37-97';
    const SUPPORT_EMAIL = 'info@almamed.su';

    protected static $ru_providers = array(
        'mail.ru',
        'inbox.ru',
        'list.ru',
        'bk.ru',
        'internet.ru',
        'yandex.ru',
        'ya.ru',
        'yandex.com',
        'yandex.by',
        'yandex.kz',
        'yandex.ua',
        'rambler.ru',
        'lenta.ru',
        'autorambler.ru',
        'ro.ru',
        'pochta.ru',
        'e-mail.ru',
        'qip.ru',
        'live.ru',
    );

    public static function getDomain($email)
    {
        $email = strtolower(trim((string) $email));
        if ($email === '' || strpos($email, '@') === false) {
            return '';
        }
        return substr(strrchr($email, '@'), 1);
    }

    public static function isAllowed($email)
    {
        $domain = self::getDomain($email);
        if ($domain === '') {
            return false;
        }

        if (preg_match('/\.(ru|su)$/u', $domain)) {
            return true;
        }

        foreach (self::$ru_providers as $provider) {
            if ($domain === $provider || substr($domain, -strlen('.' . $provider)) === '.' . $provider) {
                return true;
            }
        }

        return false;
    }

    public static function getErrorMessage($email = '')
    {
        unset($email);

        return 'Регистрация на сайте доступна только с адресом электронной почты в доменных зонах .ru или .su '
            . 'либо на российском почтовом сервисе (например, '
            . '<a href="https://360.yandex.ru/mail/" target="_blank" rel="noopener">Яндекс</a> или '
            . '<a href="https://mail.ru/" target="_blank" rel="noopener">Mail.ru</a>). '
            . 'Адреса зарубежных почтовых сервисов и доменов других зон не принимаются.<br><br>'
            . 'Вы можете отправить нам заявку с любого почтового ящика на '
            . '<a href="mailto:' . self::SUPPORT_EMAIL . '">' . self::SUPPORT_EMAIL . '</a>.<br><br>'
            . 'Данная мера применяется в соответствии с Федеральным законом от 31.07.2023 № 406-ФЗ, '
            . 'а также в связи с требованиями Федерального закона от 27.07.2006 № 152-ФЗ «О персональных данных» '
            . '(в том числе в части локализации баз данных на территории Российской Федерации).<br><br>'
            . 'Если вы считаете, что это ошибка, позвоните нам: '
            . '<a href="tel:88001003797">' . self::SUPPORT_PHONE . '</a>.';
    }
}
