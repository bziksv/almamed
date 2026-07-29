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

    /**
     * @param string $context signup|checkout
     * @return string
     */
    public static function getNoticeHtml($context = 'signup')
    {
        $email = htmlspecialchars(self::SUPPORT_EMAIL, ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars(self::SUPPORT_PHONE, ENT_QUOTES, 'UTF-8');

        if ($context === 'checkout') {
            $title = 'Оформление заказа: требования к e-mail';
            $lead = 'Оформление заказа на сайте доступно только с адресом электронной почты в доменных зонах <strong>.ru</strong> или <strong>.su</strong>, '
                . 'либо на российском почтовом сервисе (например, '
                . '<a href="https://360.yandex.ru/mail/" target="_blank" rel="noopener">Яндекс</a> или '
                . '<a href="https://mail.ru/" target="_blank" rel="noopener">Mail.ru</a>). '
                . 'Адреса зарубежных почтовых сервисов и доменов других зон не принимаются.';
        } else {
            $title = 'Регистрация: требования к e-mail';
            $lead = 'Регистрация на сайте доступна только с адресом электронной почты в доменных зонах <strong>.ru</strong> или <strong>.su</strong>, '
                . 'либо на российском почтовом сервисе (например, '
                . '<a href="https://360.yandex.ru/mail/" target="_blank" rel="noopener">Яндекс</a> или '
                . '<a href="https://mail.ru/" target="_blank" rel="noopener">Mail.ru</a>). '
                . 'Адреса зарубежных почтовых сервисов и доменов других зон не принимаются.';
        }

        return '<div class="almamed-notice-block signup-email-policy-notice">'
            . '<div class="almamed-notice-block__inner">'
            . '<div class="almamed-notice-block__icon" aria-hidden="true">!</div>'
            . '<div class="almamed-notice-block__content">'
            . '<div class="almamed-notice-block__title">' . $title . '</div>'
            . '<div class="almamed-notice-block__text">'
            . '<p>' . $lead . '</p>'
            . '<p>Вы можете отправить нам заявку с любого почтового ящика на '
            . '<a href="mailto:' . $email . '">' . $email . '</a>.</p>'
            . '<p class="almamed-notice-block__legal">Данная мера применяется в соответствии с Федеральным законом от 31.07.2023 № 406-ФЗ, '
            . 'а также в связи с требованиями Федерального закона от 27.07.2006 № 152-ФЗ «О персональных данных» '
            . '(в том числе в части локализации баз данных на территории Российской Федерации).</p>'
            . '<p>Если вы считаете, что это ошибка, позвоните нам: '
            . '<a href="tel:88001003797">' . $phone . '</a>.</p>'
            . '</div></div></div></div>';
    }

    public static function getErrorMessage($email = '', $context = 'signup')
    {
        unset($email);

        return self::getNoticeHtml($context);
    }
}
