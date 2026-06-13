<?php

class userlogBackendAction extends waViewAction
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('userlog', 'backend')
            && !wa()->getUser()->getRights('userlog', 'view')
            && !wa()->getUser()->isAdmin('userlog')
        ) {
            throw new waRightsException('Нет доступа к журналу');
        }

        userlogLogger::syncForDisplay();

        $event_model = new userlogEventModel();
        $hide_auth = waRequest::get('show_auth', 0, waRequest::TYPE_INT) ? 0 : 1;
        $filters = array(
            'contact_id'  => waRequest::get('user', 0, waRequest::TYPE_INT),
            'action'      => waRequest::get('action', '', waRequest::TYPE_STRING_TRIM),
            'entity_type' => waRequest::get('entity', '', waRequest::TYPE_STRING_TRIM),
            'query'       => waRequest::get('query', '', waRequest::TYPE_STRING_TRIM),
            'date_from'   => waRequest::get('from', '', waRequest::TYPE_STRING_TRIM),
            'date_to'     => waRequest::get('to', '', waRequest::TYPE_STRING_TRIM),
            'hide_auth'   => $hide_auth,
            'limit'       => 50,
            'offset'      => waRequest::get('page', 0, waRequest::TYPE_INT) * 50,
        );

        $tab = waRequest::get('tab', 'events', waRequest::TYPE_STRING_TRIM);

        $events = $event_model->getEvents($filters);
        $action_labels = userlogHelper::getActionLabels();
        foreach ($events as &$event) {
            $event = userlogHelper::enrichEvent($event, $event_model);
            $event['action_label'] = ifset($action_labels, $event['action'], $event['action']);
            $event['icon'] = userlogHelper::getActionIcon($event['action']);
            $event['color'] = userlogHelper::getActionColor($event['action']);
        }
        unset($event);

        $this->view->assign(array(
            'tab'           => $tab,
            'filters'       => $filters,
            'events'        => $events,
            'events_count'  => $event_model->countEvents($filters),
            'users'         => $event_model->getBackendUsers($filters['contact_id']),
            'action_labels' => userlogHelper::getActionLabels(),
            'trash_items'   => $tab === 'trash' ? (new userlogTrashModel())->getItems(array(
                'entity_type' => waRequest::get('entity', '', waRequest::TYPE_STRING_TRIM),
                'query'       => $filters['query'],
                'limit'       => 50,
            )) : array(),
            'trash_count'   => (new userlogTrashModel())->countActive(),
            'can_rollback'  => wa()->getUser()->isAdmin('userlog')
                || wa()->getUser()->isAdmin('webasyst')
                || wa()->getUser()->getRights('userlog', 'rollback'),
            'can_trash'     => wa()->getUser()->isAdmin('userlog') || wa()->getUser()->getRights('userlog', 'trash'),
        ));
    }
}
