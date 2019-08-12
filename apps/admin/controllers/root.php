<?php

class rootController extends bootstrap
{
    public function _default()
    {
        header('Location: ' . $this->url . '/tree');
        exit;
    }
}
