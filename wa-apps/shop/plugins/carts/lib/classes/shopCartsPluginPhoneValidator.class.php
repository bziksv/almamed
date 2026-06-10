<?php

class shopCartsPluginPhoneValidator extends waRegexValidator
{

    const REGEX_PHONE = '/^[- ()+0-9]{7,25}$/';

    protected function init()
    {
        //new waContactPhoneFormatter();
        $this->setMessage('not_match', _wp('Invalid phone'));
        $this->setPattern(self::REGEX_PHONE);
    }
}