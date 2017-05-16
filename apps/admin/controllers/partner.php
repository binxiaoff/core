<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\PartnerThirdParty;

class partnerController extends bootstrap
{
    /** @var \partenaires */
    public $partenaires;

    /** @var \partenaires_types */
    public $partenaires_types;

    public function initialize()
    {
        parent::initialize();

        $this->catchAll = true;

        $this->users->checkAccess('emprunteurs');

        $this->menu_admin = 'emprunteurs';
    }

    public function _default()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager  = $this->get('doctrine.orm.entity_manager');
        $this->partners = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->findall();
    }

    public function _third_party()
    {
        if (false === empty($this->params[0])) {
            /** @var Doctrine\ORM\EntityManager $entityManager = */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Symfony\Component\Translation\TranslatorInterface translator */
            $this->translator = $this->get('translator');
            $this->partner    = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);
        }
    }

    public function _third_party_add_thickbox()
    {
        $this->hideDecoration();
        if (false === empty($this->params[0]) && false === empty($this->params[1])) {
            /** @var Doctrine\ORM\EntityManager $entityManager = */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Symfony\Component\Translation\TranslatorInterface translator */
            $this->translator      = $this->get('translator');
            $this->siren           = $this->params[1];
            $this->partner         = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);
            $this->companies       = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['siren' => $this->siren]);
            $this->thirdPartyTypes = $entityManager->getRepository('UnilendCoreBusinessBundle:PartnerThirdPartyType')->findAll();
        }
    }

    public function _third_party_add()
    {
        if ($this->request->isMethod('POST') && false === empty($this->params[0])
            && $this->request->request->get('id_company') && $this->request->request->get('third_party_type')
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

                    header('Location: /partner/third_party/' . $this->params[0]);
                    die;
                } catch (Exception $exception) {
                    $_SESSION['freeow']['title']   = 'Une erreur survenu';
                    $_SESSION['freeow']['message'] = 'le tiers n\'est pas ajouté';

                    header('Location: /partner/third_party/' . $this->params[0]);
                    die;
                }
            }
        }
        $_SESSION['freeow']['title']   = 'Une erreur survenu';
        $_SESSION['freeow']['message'] = 'le tiers n\'est pas ajouté';

        header('Location: /partner/third_party/' . $this->params[0]);
        die;
    }
}
