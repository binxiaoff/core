<?php

use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Entity\PartnerThirdParty;
use Unilend\Bundle\CoreBusinessBundle\Entity\Zones;

class partenairesController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->menu_admin = Zones::ZONE_LABEL_BORROWERS;
    }

    public function _default()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $partners      = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->getPartnersSortedByName();

        $this->render(null, ['partners' => $partners]);
    }

    public function _edit()
    {
        /** @var Doctrine\ORM\EntityManager $entityManager = */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');

        if (
            empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || null === ($partner = $partnerRepository->find($this->params[0]))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            exit;
        }

        $this->render(null, ['partner' => $partner]);
    }

    public function _tiers()
    {
        /** @var Doctrine\ORM\EntityManager $entityManager = */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $partnerRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner');

        if (
            empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
            || null === ($this->partner = $partnerRepository->find($this->params[0]))
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            exit;
        }

        $this->translator = $this->get('translator');
        $this->partner    = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);
    }

    public function _ajout_tiers()
    {
        if (
            $this->request->isMethod(Request::METHOD_POST)
            && false === empty($this->params[0])
            && $this->request->request->get('id_company')
            && $this->request->request->get('third_party_type')
        ) {
            /** @var Doctrine\ORM\EntityManager $entityManager = */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            $company       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($this->request->request->get('id_company'));
            $partner       = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);
            $type          = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerThirdPartyType')->find($this->request->request->get('third_party_type'));

            if ($company && $partner && $type) {
                try {
                    $thirdParty = new PartnerThirdParty();
                    $thirdParty->setIdCompany($company);
                    $thirdParty->setIdPartner($partner);
                    $thirdParty->setIdType($type);

                    $entityManager->persist($thirdParty);
                    $entityManager->flush($thirdParty);

                    $_SESSION['freeow']['title']   = 'Tiers ajouté';
                    $_SESSION['freeow']['message'] = 'le tiers est ajouté avec succès';
                } catch (Exception $exception) {
                    $_SESSION['freeow']['title']   = 'Une erreur survenu';
                    $_SESSION['freeow']['message'] = 'le tiers n\'est pas ajouté';
                }

                header('Location: ' . $this->lurl . '/partenaires/tiers/' . $this->params[0]);
                exit;
            }
        }

        $this->hideDecoration();

        if (false === empty($this->params[0]) && false === empty($this->params[1])) {
            /** @var Doctrine\ORM\EntityManager $entityManager = */
            $entityManager         = $this->get('doctrine.orm.entity_manager');
            $this->translator      = $this->get('translator');
            $this->siren           = $this->params[1];
            $this->partner         = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);
            $this->companies       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['siren' => $this->siren]);
            $this->thirdPartyTypes = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerThirdPartyType')->findAll();
        }
    }
}
