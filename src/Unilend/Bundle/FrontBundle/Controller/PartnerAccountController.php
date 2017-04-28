<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsComments;
use Unilend\Bundle\CoreBusinessBundle\Entity\TemporaryLinksLogin;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;
use Unilend\Bundle\FrontBundle\Service\DataLayerCollector;
use Unilend\Bundle\FrontBundle\Service\SourceManager;
use Unilend\core\Loader;

class PartnerAccountController extends Controller
{
    /**
     * @Route("partenaire/depot", name="partner_project_request")
     * @Method("GET")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function projectRequestAction(Request $request)
    {
        $projectManager  = $this->get('unilend.service.project_manager');
        $borrowingMotive = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:BorrowingMotive');

        $template = [
            'loanPeriods'      => $projectManager->getPossibleProjectPeriods(),
            'projectAmountMin' => $projectManager->getMinProjectAmount(),
            'projectAmountMax' => $projectManager->getMaxProjectAmount(),
            'borrowingMotives' => $borrowingMotive->findBy([], ['rank' => 'ASC']),
            'form'             => $request->getSession()->get('partnerProjectRequest', [])
        ];

        $request->getSession()->remove('partnerProjectRequest');

        return $this->render('/partner_account/project_request.html.twig', $template);
    }

    /**
     * @Route("partenaire/depot", name="partner_project_request_form")
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function projectRequestFormAction(Request $request)
    {
        $translator     = $this->get('translator');
        $projectManager = $this->get('unilend.service.project_manager');
        $entityManager  = $this->get('doctrine.orm.entity_manager');

        $amount   = null;
        $motive   = null;
        $duration = null;
        $siren    = null;
        $email    = null;

        try {
            if (empty($request->request->get('esim')) || false === is_array($request->request->get('esim'))) {
                throw new \InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $formData = $request->request->get('esim');

            if (empty($formData['amount']) || empty($formData['motive']) || empty($formData['duration']) || empty($formData['siren']) || empty($formData['email'])) {
                throw new \InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            // Amount
            $minimumAmount = $projectManager->getMinProjectAmount();
            $maximumAmount = $projectManager->getMaxProjectAmount();
            $amount        = str_replace([' ', '€'], '', $formData['amount']);

            if (false === filter_var($amount, FILTER_VALIDATE_INT)) {
                throw new \InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            if ($amount < $minimumAmount || $amount > $maximumAmount) {
                throw new \InvalidArgumentException($translator->trans('partner-project-request_amount-value-error'));
            }

            // Motive
            if (false === filter_var($formData['motive'], FILTER_VALIDATE_INT)) {
                throw new \InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $motive = $formData['motive'];

            // Duration
            if (false === filter_var($formData['duration'], FILTER_VALIDATE_INT)) {
                throw new \InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $duration = $formData['duration'];

            // SIREN
            $siren       = str_replace(' ', '', $formData['siren']);
            $sirenLength = strlen($siren);

            if (
                1 !== preg_match('/^[0-9]*$/', $siren)
                || false === in_array($sirenLength, [9, 14]) // SIRET numbers also allowed
            ) {
                throw new \InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $siren = substr($siren, 0, 9);

            // Email
            if (false === filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException($translator->trans('partner-project-request_required-fields-error'));
            }

            $email = $formData['email'];
        } catch (\InvalidArgumentException $exception) {
            $this->addFlash('partnerProjectRequestErrors', $exception->getMessage());

            $request->getSession()->set('partnerProjectRequest', [
                'values' => [
                    'amount'   => $amount,
                    'siren'    => $siren,
                    'email'    => $email,
                    'motive'   => $motive,
                    'duration' => $duration
                ]
            ]);

            return $this->redirect($request->headers->get('referer'));
        }

        $sourceManager = $this->get('unilend.frontbundle.service.source_manager');

        if ($entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->existEmail($email)) {
            $email .= '-' . time();
        }

        $client = new Clients();
        $client
            ->setEmail($email)
            ->setIdLangue('fr')
            ->setStatus(Clients::STATUS_ONLINE)
            ->setSource($sourceManager->getSource(SourceManager::SOURCE1))
            ->setSource2($sourceManager->getSource(SourceManager::SOURCE2))
            ->setSource3($sourceManager->getSource(SourceManager::SOURCE3))
            ->setSlugOrigine($sourceManager->getSource(SourceManager::ENTRY_SLUG));

        $siret   = $sirenLength === 14 ? str_replace(' ', '', $formData['siren']) : '';
        $company = new Companies();
        $company->setSiren($siren)
            ->setSiret($siret)
            ->setStatusAdresseCorrespondance(1)
            ->setEmailDirigeant($email)
            ->setEmailFacture($email);

        $entityManager->beginTransaction();
        try {
            $entityManager->persist($client);

            $clientAddress = new ClientsAdresses();
            $clientAddress->setIdClient($client);

            $entityManager->persist($clientAddress);
            $entityManager->flush($clientAddress);

            $company->setIdClientOwner($client->getIdClient());

            $entityManager->persist($company);
            $entityManager->flush($company);

            $this->get('unilend.service.wallet_creation_manager')->createWallet($client, WalletType::PARTNER);

            $entityManager->commit();
        } catch (\Exception $exception) {
            $entityManager->getConnection()->rollBack();
            $this->get('logger')->error('An error occurred while creating client: ' . $exception->getMessage(), [['class' => __CLASS__, 'function' => __FUNCTION__]]);
        }

        if (empty($client->getIdClient())) {
            return $this->redirect($request->headers->get('referer'));
        }

//        $request->getSession()->set(DataLayerCollector::SESSION_KEY_CLIENT_EMAIL, $client->getEmail());
//        $request->getSession()->set(DataLayerCollector::SESSION_KEY_BORROWER_CLIENT_ID, $client->getIdClient());

        /** @var UserPartner $user */
        $user        = $this->getUser();
        $rootCompany = $user->getCompany();

        while ($rootCompany->getIdParentCompany()) {
            $rootCompany = $rootCompany->getIdParentCompany();
        }

        $project                                       = $this->get('unilend.service.entity_manager')->getRepository('projects');
        $project->id_company                           = $company->getIdCompany();
        $project->amount                               = $amount;
        $project->period                               = $duration;
        $project->id_borrowing_motive                  = $motive;
        $project->ca_declara_client                    = 0;
        $project->resultat_exploitation_declara_client = 0;
        $project->fonds_propres_declara_client         = 0;
        $project->status                               = \projects_status::SIMULATION;
        $project->id_partner                           = $entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->findOneBy(['idCompany' => $rootCompany->getIdCompany()])->getId();
        $project->commission_rate_funds                = \projects::DEFAULT_COMMISSION_RATE_FUNDS;
        $project->commission_rate_repayment            = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT;
        $project->id_company_submitter                 = $user->getCompany()->getIdCompany();
        $project->id_client_submitter                  = $user->getClientId();
        $project->create();

        $projectManager->addProjectStatus(Users::USER_ID_FRONT, \projects_status::SIMULATION, $project);

        $request->getSession()->set('partnerProjectRequest', [
            'values' => [
                'amount'   => $amount,
                'siren'    => $siren,
                'email'    => $email,
                'motive'   => $motive,
                'duration' => $duration
            ]
        ]);

        return $this->redirectToRoute('partner_project_request_eligibility_test');
    }

    /**
     * @Route("partenaire/depot/eligibilite", name="partner_project_request_eligibility_test")
     * @Route("partenaire/depot/eligibilite/{hash}", name="partner_project_request_eligibility", requirements={"hash":"[0-9a-z]{32}"})
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestEligibilityAction()
    {
        return $this->render('/partner_account/project_request_eligibility.html.twig');
    }


    /**
     * @Route("partenaire/depot/details", name="partner_project_request_details_form")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestDetailsAction()
    {
        return $this->render('/partner_account/project_request_details.html.twig');
    }

    /**
     * @Route("partenaire/depot/fin", name="partner_project_request_end")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestEndAction()
    {
        return $this->render('/partner_account/project_request_end.html.twig');
    }

    /**
     * @Route("partenaire/emprunteurs", name="partner_projects_list")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectsListAction()
    {
        /** @var UserPartner $user */
        $user          = $this->getUser();
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $companies = [$user->getCompany()];

        if (in_array('ROLE_PARTNER_ADMIN', $user->getRoles())) {
            $companies = $this->getCompanyTree($user->getCompany(), $companies);
        }

        $prospects = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
            ->findBy(
                ['status' => \projects_status::SIMULATION, 'idCompanySubmitter' => $companies],
                ['added' => 'DESC']
            );

        $borrowers = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')
            ->getPartnerProjects($companies);

        $template = [
            'prospects' => $this->formatProject($prospects),
            'borrowers' => $this->formatProject($borrowers)
        ];

        return $this->render('/partner_account/projects_list.html.twig', $template);
    }

    /**
     * @Route("partenaire/depot/abandon", name="partner_projects_abandon")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function projectRequestAbandon()
    {
        // Must return a response to initial Depot de dossier page - projectAbandoned = true
        return $this->redirectToRoute('partner_project_request');
    }

    /**
     * @Route("partenaire/utilisateurs", name="partner_users")
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @return Response
     */
    public function usersAction()
    {
        return $this->render('/partner_account/users.html.twig');
    }

    /**
     * @Route("partenaire/performance", name="partner_statistics")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function statisticsAction()
    {
        return $this->render('/partner_account/statistics.html.twig');
    }

    /**
     * @Route("partenaire/contact", name="partner_contact")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @return Response
     */
    public function contactAction()
    {
        return $this->render('/partner_account/contact.html.twig');
    }

    /**
     * @Route("partenaire/securite/{token}", name="partner_security", requirements={"token": "[0-9a-f]+"})
     *
     * @param string  $token
     * @param Request $request
     *
     * @return Response
     */
    public function securityAction($token, Request $request)
    {
        $isLinkExpired = false;
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var TemporaryLinksLogin $temporaryLinks */
        $temporaryLinks = $entityManager->getRepository('UnilendCoreBusinessBundle:TemporaryLinksLogin')->findOneBy(['token' => $token]);

        if (null === $temporaryLinks) {
            return $this->redirectToRoute('home');
        }

        $now         = new \DateTime();
        $linkExpires = $temporaryLinks->getExpires();

        if ($linkExpires <= $now) {
            $isLinkExpired = true;
        } else {
            $client = $temporaryLinks->getIdClient();

            if (null === $client || false === $client->isPartner()) {
                return $this->redirectToRoute('home');
            }

            $temporaryLinks->setAccessed($now);

            $entityManager->persist($temporaryLinks);
            $entityManager->flush();

            if ($request->isMethod('POST')) {
                $translator = $this->get('translator');
                /** @var \ficelle $ficelle */
                $ficelle  = Loader::loadLib('ficelle');
                $formData = $request->request->get('partner_security', []);
                $error    = false;

                if (empty($formData['password']) || false === $ficelle->password_fo($formData['password'], 6)) {
                    $error = true;
                    $this->addFlash('error', $translator->trans('common-validator_password-invalid'));
                }

                if ($formData['password'] !== $formData['repeated_password']) {
                    $error = true;
                    $this->addFlash('error', $translator->trans('common-validator_password-not-equal'));
                }

                if (empty($formData['question'])) {
                    $error = true;
                    $this->addFlash('error', $translator->trans('common-validator_secret-question-invalid'));
                }

                if (empty($formData['answer'])) {
                    $error = true;
                    $this->addFlash('error', $translator->trans('common-validator_secret-answer-invalid'));
                }

                if (false === $error) {
                    $formData['question'] = filter_var($formData['question'], FILTER_SANITIZE_STRING);

                    $userPartner = $this->get('unilend.frontbundle.security.user_provider')->loadUserByUsername($client->getEmail());
                    $password    = $this->get('security.password_encoder')->encodePassword($userPartner, $formData['password']);

                    $client->setPassword($password);
                    $client->setSecreteQuestion($formData['question']);
                    $client->setSecreteReponse(md5($formData['answer']));
                    $client->setStatus(1);

                    $entityManager->persist($client);

                    $temporaryLinks->setExpires($now);

                    $entityManager->persist($temporaryLinks);
                    $entityManager->flush();

                    return $this->redirectToRoute('login');
                }
            }
        }

        return $this->render('partner_account/security.html.twig', ['expired' => $isLinkExpired, 'token' => $token]);
    }

    /**
     * @param Companies $rootCompany
     * @param array     $tree
     *
     * @return Companies[]
     */
    private function getCompanyTree(Companies $rootCompany, array $tree)
    {
        $childCompanies = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Companies')
            ->findBy(['idParentCompany' => $rootCompany]);

        foreach ($childCompanies as $company) {
            $tree[] = $company;
            $tree = $this->getCompanyTree($company, $tree);
        }

        return $tree;
    }

    /**
     * @param Projects[] $projects
     * @return array
     */
    private function formatProject(array $projects)
    {
        $display                 = [];
        $translator              = $this->get('translator');
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $projectStatusRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus');

        foreach ($projects as $project) {
            $display[$project->getIdProject()] = [
                'id'         => $project->getIdProject(),
                'name'       => empty($project->getTitle()) ? $project->getIdCompany()->getName() : $project->getTitle(),
                'submitter'  => [
                    'firstName' => $project->getIdClientSubmitter()->getPrenom(),
                    'lastName'  => $project->getIdClientSubmitter()->getNom(),
                    'entity'    => $project->getIdCompanySubmitter()->getName()
                ],
                'motive'     => $project->getIdBorrowingMotive() ? $translator->trans('borrowing-motive_motive-' . $project->getIdBorrowingMotive()) : '',
                'amount'     => $project->getAmount(),
                'duration'   => $project->getPeriod(),
                'status'     => $projectStatusRepository->findOneBy(['status' => $project->getStatus()])->getLabel(),
                'memos'      => $this->formatNotes($project->getNotes()),
                'hasChanged' => $this->hasProjectChanged($project)
            ];
        }

        return $display;
    }

    /**
     * @param ProjectsComments[] $notes
     *
     * @return array
     */
    private function formatNotes($notes)
    {
        $display = [];

        foreach ($notes as $note) {
            $display[] = [
                'author'  => $note->getIdUser()->getFirstname() . ' ' . $note->getIdUser()->getName(),
                'date'    => $note->getAdded(),
                'content' => $note->getContent()
            ];
        }

        return $display;
    }

    /**
     * @param Projects $project
     *
     * @return bool
     */
    private function hasProjectChanged(Projects $project)
    {
        $entityManager                  = $this->get('doctrine.orm.entity_manager');
        $projectStatusRepositoryHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
        $lastLoginDate = $this->getUser()->getLastLoginDate();
        $lastLoginDate = $lastLoginDate ? new \DateTime($lastLoginDate) : null;
        $notes = $project->getNotes();

        return (
            null === $lastLoginDate
            || count($notes) && $lastLoginDate < $project->getNotes()[0]->getAdded()
            || $lastLoginDate < $projectStatusRepositoryHistory->findOneBy(['idProject' => $project->getIdProject()], ['added' => 'DESC', 'idProjectStatusHistory' => 'DESC'])->getAdded()
        );
    }
}
