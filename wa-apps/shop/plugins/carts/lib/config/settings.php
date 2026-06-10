<?php

return array(
    'cron_alert' => array(
        'title' => _wp('Unconfigured cron reminder'),
        'value' => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'no_messages_alert' => array(
        'title' => _wp('Unconfigured messages reminder'),
        'value' => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'contacts_save' => array(
        'title' => _wp('Auto save contact information'),
        'description' => _wp('The plugin is saving contact information in order form by default. You can disable this setting if you don\'t need this feature.'),
        'value' => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'auth_save' => array(
        'title' => _wp('Auto save authorized user'),
        'description' => _wp('The plugin doesn\'t saving new data for authorized user by default. You can enable this setting if you need this feature.'),
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'send_if_empty' => array(
        'title' => _wp('Send email if shopping cart is empty'),
        'description' => '',
        'value' => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'admin_stat' => array(
        'title' => _wp('Admin statistics'),
        'description' => _wp('Enable or disable statistics and messages for backend users.'),
        'value' => 1,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'delete_backend_order' => array(
        'title' => _wp('Delete backend carts'),
        'description' => _wp('Delete cart statistics if order has been created in backend.'),
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'message_https' => array(
        'title' => _wp('Use HTTPS for links and images'),
        'description' => '',
        'value' => 0,
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'locale' => array(
        'control_type' => waHtmlControl::HIDDEN,
    )
);