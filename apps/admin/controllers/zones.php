<?php

class zonesController extends bootstrap
{
    /** @var \users_zones */
    protected $userZone;

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess('admin');

        $this->catchAll   = true;
        $this->menu_admin = 'admin';
        $this->userZone   = $this->loadData('users_zones');
    }

    public function _default()
    {
        /** @var \users $userEntity */
        $userEntity = $this->loadData('users');
        /** @var \zones $zoneEntity */
        $zoneEntity = $this->loadData('zones');

        $this->users = $userEntity->select('status = 1', 'name ASC');
        $this->zones = $zoneEntity->select('', 'name ASC');
    }
}
