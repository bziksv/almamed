<?php

class userlogBackendEventController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('userlog', 'view')) {
            throw new waRightsException('Нет доступа');
        }

        $event_id = waRequest::get('id', 0, waRequest::TYPE_INT);
        $event_model = new userlogEventModel();
        $event = $event_model->getById($event_id);
        if (!$event) {
            $this->setError('Событие не найдено');
            return;
        }

        $rollback_event_id = (int) $event['id'];
        if (empty($event['before_data']) && $event['action'] === 'product.update') {
            $richer = $event_model->findRicherDuplicate($event);
            if ($richer) {
                $rollback_event_id = (int) $richer['id'];
                if (empty($event['summary']) || strpos($event['summary'], '→') === false) {
                    $event['summary'] = $richer['summary'];
                }
                $event['can_rollback'] = $richer['can_rollback'];
                $event['rolled_back'] = $richer['rolled_back'];
                if (!empty($richer['before_data'])) {
                    $event['before_data'] = $richer['before_data'];
                }
                if (!empty($richer['after_data'])) {
                    $event['after_data'] = $richer['after_data'];
                }
            }
        }

        foreach (array('before_data', 'after_data') as $field) {
            if (!empty($event[$field]) && userlogHelper::isJsonString($event[$field])) {
                $event[$field] = waUtils::jsonDecode($event[$field], true);
            }
        }

        if ($event['contact_id']) {
            $contact = new waContact($event['contact_id']);
            $event['contact'] = array(
                'id'    => $event['contact_id'],
                'name'  => $contact->getName(),
                'photo' => $contact->getPhoto(32),
            );
        }

        $event['diff'] = array();
        if (wa()->appExists('shop')) {
            wa('shop');
            if ($event['action'] === 'product.update' && is_array($event['before_data']) && is_array($event['after_data'])) {
                $event['diff'] = userlogHelper::formatDiff(
                    shopUserlogProductSnapshot::flattenForDiff($event['before_data']),
                    shopUserlogProductSnapshot::flattenForDiff($event['after_data']),
                    'product'
                );
            } elseif ($event['action'] === 'product.sort' && is_array($event['before_data']) && is_array($event['after_data'])) {
                $event['diff'] = userlogHelper::formatSortDiff(
                    ifset($event['before_data'], 'items', array()),
                    ifset($event['after_data'], 'items', array()),
                    ifset($event['after_data'], 'names', array())
                );
            }
        }

        if (!$event['diff'] && !empty($event['summary'])) {
            $event['diff'] = userlogHelper::parseSummaryDiff($event['summary']);
        }

        $event['rollback_event_id'] = $rollback_event_id;
        $event['action_label'] = ifset(userlogHelper::getActionLabels(), $event['action'], $event['action']);
        $event['icon'] = userlogHelper::getActionIcon($event['action']);
        $event['color'] = userlogHelper::getActionColor($event['action']);

        $this->response = $event;
    }
}
