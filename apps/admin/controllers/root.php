<?php

class rootController extends Controller
{
    public function _default()
    {
        header('Location: ' . $this->url . '/traductions');
        exit;
    }
}
