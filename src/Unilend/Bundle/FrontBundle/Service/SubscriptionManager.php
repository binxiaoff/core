<?php


namespace Unilend\Bundle\FrontBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class SubscriptionManager
{

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getCountryList()
    {
        /** @var \pays_v2 $countries */
        $countries = $this->entityManager->getRepository('pays_v2');
        return $countries->select('', 'ordre ASC');
    }

    public function getNationalityList()
    {
        /** @var \nationalites_v2 $nationality */
        $nationality = $this->entityManager->getRepository('nationalites_v2');
        return $nationality->select('', 'ordre ASC');
    }

    public function handleSubscriptionStepOneData($aFormData)
    {

    }




}