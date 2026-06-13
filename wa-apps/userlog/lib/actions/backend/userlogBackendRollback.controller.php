<?php

class userlogBackendRollbackController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin('userlog')
            && !wa()->getUser()->isAdmin('webasyst')
            && !wa()->getUser()->getRights('userlog', 'rollback')
        ) {
            throw new waRightsException('Нет прав на откат');
        }

        $event_id = waRequest::post('event_id', 0, waRequest::TYPE_INT);
        try {
            $result = (new userlogRollbackService())->rollbackEvent($event_id);
            $this->response = array(
                'status'    => 'ok',
                'entity_id' => $result['entity_id'],
                'message'   => $result['message'],
            );
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}
