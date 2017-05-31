<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

Interface UniversignEntityInterface
{
    const STATUS_PENDING  = 0;
    const STATUS_SIGNED   = 1;
    const STATUS_CANCELED = 2;
    const STATUS_FAILED   = 3;
    const STATUS_ARCHIVED = 4;

    const STATUS_LABEL_PENDING  = 'ready';
    const STATUS_LABEL_SIGNED   = 'completed';
    const STATUS_LABEL_CANCELED = 'canceled';

    public function getId();

    public function getName();

    public function getIdUniversign();

    public function getUrlUniversign();

    public function getStatus();

    public function setName($name);

    public function setIdUniversign($id);

    public function setUrlUniversign($url);

    public function setStatus($status);
}
