<?php

class beneficial_ownersController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess();

        $this->menu_admin = 'oneui';
    }

    public function _default()
    {
        $this->render();
    }
}
