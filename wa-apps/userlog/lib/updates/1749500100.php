<?php

$rights_model = new waContactRightsModel();
$contact_model = new waContactModel();

$admin_ids = $contact_model->select('id')
    ->where('is_user = 1')
    ->fetchAll(null, true);

foreach ($admin_ids as $contact_id) {
    $user = new waUser($contact_id);
    if ($user->isAdmin('webasyst')) {
        $rights_model->save($contact_id, 'userlog', 'backend', 2);
        $rights_model->save($contact_id, 'userlog', 'view', 1);
        $rights_model->save($contact_id, 'userlog', 'rollback', 1);
        $rights_model->save($contact_id, 'userlog', 'trash', 1);
    }
}
