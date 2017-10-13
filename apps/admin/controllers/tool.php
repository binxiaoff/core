<?php

class toolController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();
        $this->users->checkAccess();
    }

    public function _default()
    {
        $this->render();
    }
}