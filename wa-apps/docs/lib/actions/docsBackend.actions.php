<?php
class docsBackendActions extends waViewActions
{
    public function defaultAction()
    {
        $this->redirect('https://docs.google.com/document/d/18rukD2ASxjvH-ZuJv8Bf-O5NTTkHboYfKl1qqJsJyts/edit#heading=h.pfv77mnxpdzn', 302);
    }
}
