<?php

$contacts_model = new waContactModel();
$contacts_query = $contacts_model->select('id')->where('is_user = 1')->query();

foreach ($contacts_query as $contact_row)
{
	$contact = new waContact($contact_row['id']);
	if (!$contact->isAdmin() && $contact->getRights('shop', 'settings') != 0)
	{
		$contact->setRight('shop', 'seofilter.filter_edit', 1);
	}
}
unset($contacts);