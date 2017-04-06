<?php

namespace Unilend\Bundle\CoreBusinessBundle;

Interface UniversignEntityInterface
{
    public function getId();

    public function getName();

    public function getIdUniversign();

    public function getUrlUniversign();

    public function getStatus();
}
