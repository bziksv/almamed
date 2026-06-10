<?php

return array(
    'switchoff' => array(
        'title' => 'Switchoff',
        'value' => 0,
        'control_type' => waHtmlControl::SELECT,
    ), 
    'summ_in_carts' => array(
        'title' => 'Summ in carts',
        'value' => 0,
        'control_type' => waHtmlControl::INPUT,
    ),
    't0_cookie' => array(
        'title' => 'Сookie',
        'value' => 30,
        'control_type' => waHtmlControl::INPUT,
    ),
    'after_order' => array(
        'title' => 'After order in hours',
        'value' => 24,
        'control_type' => waHtmlControl::INPUT,
    ),
    /*'dont_show_yandexmarket' => array(
        'title' => 'Dont show yandexmarket',
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),*/
    'dont_show_checkout' => array(
        'title' => 'Dont show in checkout process',
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'dont_show_cart' => array(
        'title' => 'Dont show in cart page',
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'dont_show_urls' => array(
        'title' => 'Dont show in this urls',
        'value' => '',
        'control_type' => waHtmlControl::TEXTAREA,
    ),



    'type' => array(
        'title' => 'Type',
        'value' => 0,
        'control_type' => waHtmlControl::SELECT,
    ),
    't0_type' => array(
        'title' => 'Type0 type',
        'value' => 0,
        'control_type' => waHtmlControl::SELECT,
    ),
    't0_value' => array(
        'title' => 'Type0 value',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ), 
    't0_hours' => array(
        'title' => 'Type0 hours',
        'value' => 24,
        'control_type' => waHtmlControl::INPUT,
    ), 
    't0_comment' => array(
        'title' => 'Type0 comment',
        'value' => 'Всплывающее окно при уходе с сайта',
        'control_type' => waHtmlControl::INPUT,
    ), 

    'flexdiscount_id' => array(
        'title' => 'Flexdiscount generator id',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    't6_value' => array(
        'title' => 'Type6 value',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    't6_hours' => array(
        'title' => 'Type6 hours',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),

    't1_code' => array(
        'title' => 'Type1 coupon code',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    't1_value' => array(
        'title' => 'Type1 value',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    't1_hours' => array(
        'title' => 'Type1 hours',
        'value' => 24,
        'control_type' => waHtmlControl::INPUT,
    ),

    't5_field_name' => array(
        'title' => 'Field Name',
        'value' => 1,
        'control_type' => waHtmlControl::SELECT,
    ),
    't5_field_email' => array(
        'title' => 'Field Email',
        'value' => 2,
        'control_type' => waHtmlControl::SELECT,
    ),
    't5_field_phone' => array(
        'title' => 'Field Phone',
        'value' => 1,
        'control_type' => waHtmlControl::SELECT,
    ),
    't5_name' => array(
        'title' => 'Type5 name',
        'value' => 'Отправить',
        'control_type' => waHtmlControl::INPUT,
    ),
    't5_form_id' => array(
        'title' => 'Type5 form ID',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
	'pdn' => array(
        'title' => 'Pdn',
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'pdn_text' => array(
        'title' => 'Pdn Text',
        'value' => 'Я принимаю условия <a href="---ВСТАВЬТЕ СЮДА ССЫЛКУ НА ДОКУМЕНТ!---" target="_blank">политики обработки персональных данных</a>',
        'control_type' => waHtmlControl::TEXTAREA,
    ),

    't4_name' => array(
        'title' => 'Type4 name',
        'value' => 'Открыть',
        'control_type' => waHtmlControl::INPUT,
    ), 
    't4_url' => array(
        'title' => 'Type4 url',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ), 

    'title' => array(
        'title' => 'Name',
        'value' => 'Не торопитесь уходить!',
        'control_type' => waHtmlControl::INPUT,
    ), 
    'text' => array(
        'title' => 'Description',
        'value' => 'Уже уходите? А мы приготовили для Вас специальный подарок - купон на скидку!',
        'control_type' => waHtmlControl::TEXTAREA,
    ),
    'img' => array(
        'title' => 'Image',
        'value' => 2,
        'control_type' => waHtmlControl::HIDDEN,
    ),
    'result_text' => array(
        'title' => 'Result text',
        'value' => 'Спасибо, информация отправлена.',
        'control_type' => waHtmlControl::TEXTAREA,
    ),



    'email' => array(
        'title' => 'Email to',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'email_from' => array(
        'title' => 'Email from',
        'value' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'email_subject' => array(
        'title' => 'Email subject',
        'value' => 'Всплывающее окно при уходе с сайта - заполнена форма',
        'control_type' => waHtmlControl::INPUT,
    ),  



    'templateCss' => array(
        'title' => 'templateCss',
        'value' => "#wait-plugin h3 {
    /*Заголовок формы*/
}

#wait-plugin .wait-description {
    /*Описание формы*/
}

#wait-plugin .wait-plugin {
    /*Цвет и толщина рамки вокруг формы*/
    /*border: 4px solid #555;*/
  
    /*Цвет фона и фоновая картинка*/
    /*background: #000000 url(/wa-apps/shop/plugins/wait/img/bg3.jpg) no-repeat 0 0;*/
}

.wait-plugin-top {
    /*Цвет фона плашки после выдачи купона*/
    /*background: #e8627b;*/
}",
        'control_type' => waHtmlControl::TEXTAREA,
    ),
    
    'templateJs' => array(
        'title' => 'templateJs',
        'value' => "//успешная отправка формы при вводе имени/e-mail/телефона
function wait_event_mail_success() {
  console.log('wait_event_mail_success');
    //return true;
}

//нажатие кнопки при вводе имени/e-mail/телефона
function wait_event_mail() {
  console.log('wait_event_mail');
    //return true;
}

//нажатие кнопки при генерации купона
function wait_event_coupon() {
  console.log('wait_event_coupon');
    //return true;
}

//открытие формы
function wait_event_form_show() {
  console.log('wait_event_form_show');
    //return true;
}",
        'control_type' => waHtmlControl::TEXTAREA,
    ),
);
