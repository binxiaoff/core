<?php

class companyController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;
        $this->users->checkAccess('emprunteurs');

        $this->menu_admin = 'emprunteurs';
        $this->translator = $this->get('translator');
    }

    public function _add()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $this->sectors = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanySector')->findAll();
        $this->siren   = '';
        if (isset($this->params[0])) {
            $this->siren = $this->params[0];
        }
    }
}
