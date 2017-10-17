<?php

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class repaymentController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_REPAYMENT);
        $this->menu_admin = 'remboursement';

        /** @var \Symfony\Component\Translation\TranslatorInterface translator */
        $this->translator = $this->get('translator');
    }

    public function _validation()
    {
        /** @var EntityManager $entityManager */
        $entityManager        = $this->get('doctrine.orm.entity_manager');
        $receptionsToValidate = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')
            ->findReceptionsWithPendingRepaymentTasks();
        $this->render(null, ['receptionsToValidate' => $receptionsToValidate]);

        return;
    }
}