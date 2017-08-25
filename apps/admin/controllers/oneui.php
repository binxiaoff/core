<?php

class oneuiController extends bootstrap
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
