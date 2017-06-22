<?php

class twigController extends bootstrap
{
    public function _default()
    {
        $this->render('twig/default.html.twig', ['var' => time()]);
    }
}
