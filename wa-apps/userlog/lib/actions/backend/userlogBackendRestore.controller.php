<?php

class userlogBackendRestoreController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->getRights('userlog', 'trash')) {
            throw new waRightsException('Нет прав на восстановление');
        }

        $trash_id = waRequest::post('trash_id', 0, waRequest::TYPE_INT);
        try {
            $entity_id = (new userlogTrashService())->restore($trash_id);
            $this->response = array(
                'status'    => 'ok',
                'entity_id' => $entity_id,
                'message'   => 'Объект восстановлен',
            );
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}
