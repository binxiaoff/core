<?php

class oneuiController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll   = true;
        $this->menu_admin = 'oneui';
    }
}
