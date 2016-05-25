<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Unilend\Service\Simulator\EntityManager;

class TestimonialManager
{
    /** @var  EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getActiveTestimonials()
    {

    }

}