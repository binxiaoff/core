<?php

class partnerListController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess();

        $this->menu_admin = 'partnerList';
    }

    public function _default()
    {
        $this->render();
    }
}