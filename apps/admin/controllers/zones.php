<?php

use Unilend\Entity\{Users, Zones};

class zonesController extends bootstrap
{
    /** @var \users_zones */
    protected $userZone;

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_ADMINISTRATION);

        $this->menu_admin = 'admin';
        $this->userZone   = $this->loadData('users_zones');
    }

    public function _default()
    {
        /** @var \users $userEntity */
        $userEntity = $this->loadData('users');
        /** @var \zones $zoneEntity */
        $zoneEntity = $this->loadData('zones');

        $this->users = $userEntity->select('id_user NOT IN (-1, -2, -3) AND status = ' . Users::STATUS_ONLINE, 'name ASC');
        $this->zones = $zoneEntity->select('', 'name ASC');
    }
}
