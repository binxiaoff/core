<?php

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
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
            return;
        }

        $agencies = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['idParentCompany' => $partner->getIdCompany()->getIdCompany()]);

        $this->render(null, [
            'agencies' => $agencies,
            'partner'  => $partner
        ]);
    }

    public function _agence()
    {
        if (
            false === $this->request->isXmlHttpRequest()
            || empty($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
        ) {
            header('Location: ' . $this->lurl . '/partenaires');
            return;
        }

        $this->hideDecoration();
        $this->autoFireView = false;

        header('Content-Type: application/json');

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $partner       = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($this->params[0]);

        if (null === $partner) {
            echo json_encode([
                'success' => false,
                'error'   => ['Partenaire inconnu']
            ]);
            return;
        }

        /** @var Companies $agency */
        $agency = null;
        $errors = [];

        switch ($this->request->request->get('action')) {
            case 'create':
                $agency       = new Companies();
                $agencyErrors = $this->setAngecyData($this->request, $agency);
                $errors       = array_merge($errors, $agencyErrors);

                if (empty($agencyErrors)) {
                    $agency->setIdParentCompany($partner->getIdCompany());
                    $agency->setIdClientOwner(0); // @todo remove once id_client_owner is nullable

                    $entityManager->persist($agency);
                    $entityManager->flush($agency);
                }
                break;
            case 'modify':
                if (empty($this->request->request->getInt('id'))) {
                    $errors[] = 'Agence inconnue';
                    break;
                }

                $companiesRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
                $agency              = $companiesRepository->find($this->request->request->getInt('id'));

                if (null === $agency) {
                    $errors[] = 'Agence inconnue';
                    break;
                }

                $agencyErrors = $this->setAngecyData($this->request, $agency);
                $errors       = array_merge($errors, $agencyErrors);

                if (empty($agencyErrors)) {
                    $entityManager->flush($agency);
                }
                break;
            default:
                $errors[] = 'Action inconnue';
                break;
        }

        echo json_encode([
            'success' => empty($errors),
            'error'   => $errors,
            'id'      => $agency instanceof Companies ? $agency->getIdCompany() : '',
            'data'    => $agency instanceof Companies ? [
                $agency->getName(),
                $agency->getSiren(),
                $agency->getPhone(),
                $agency->getAdresse1(),
                $agency->getZip(),
                $agency->getCity(),
            ] : []
        ]);
    }

    /**
     * @param Request   $request
     * @param Companies $agency
     *
     * @return array
     */
    private function setAngecyData(Request $request, Companies $agency)
    {
        $errors   = [];
        $name     = $request->request->filter('name', FILTER_SANITIZE_STRING);
        $siren    = $request->request->filter('siren', FILTER_SANITIZE_STRING);
        $phone    = $request->request->filter('phone', FILTER_SANITIZE_STRING);
        $address  = $request->request->filter('address', FILTER_SANITIZE_STRING);
        $postcode = $request->request->filter('postcode', FILTER_SANITIZE_STRING);
        $city     = $request->request->filter('city', FILTER_SANITIZE_STRING);

        if (empty($name)) {
            $errors[] = 'Vous devez renseigner le nom de l\'agence';
        }

        if (false === empty($siren) && 1 !== preg_match('/^[0-9]{9}$/', $siren)) {
            $errors[] = 'Numéro de SIREN invalide';
        }

        if (empty($errors)) {
            $agency->setName($name);
            $agency->setSiren($siren);
            $agency->setPhone($phone);
            $agency->setAdresse1($address);
            $agency->setZip($postcode);
            $agency->setCity($city);
        }

        return $errors;
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
            return;
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
                return;
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
