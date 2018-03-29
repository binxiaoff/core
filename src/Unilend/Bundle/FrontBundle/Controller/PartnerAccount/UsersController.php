<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\{
    Method, Route, Security
};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{
    Request, Response
};
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsStatus, Companies, CompanyClient, TemporaryLinksLogin, Users, WalletType
};
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;
use Unilend\core\Loader;

class UsersController extends Controller
{
    /**
     * @Route("/partenaire/utilisateurs", name="partner_users")
     * @Method("GET")
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @return Response
     */
    public function usersAction(): Response
    {
        $template                = ['users' => []];
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $companyClientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient');
        $users                   = $companyClientRepository->findBy(['idCompany' => $this->getUserCompanies()]);

        foreach ($users as $user) {
            /** @var Clients $client */
            $client       = $user->getIdClient();
            $clientStatus = $client->getIdClientStatusHistory()->getIdStatus()->getId();
            $userData     = [
                'client' => $client,
                'role'   => $user->getRole() === UserPartner::ROLE_ADMIN ? 'admin' : 'agent',
                'entity' => $user->getIdCompany()
            ];

            if (ClientsStatus::STATUS_DISABLED === $clientStatus) {
                $template['offlineUsers'][] = $userData;
            } else {
                $template['onlineUsers'][] = $userData;
            }
        }

        return $this->render('/partner_account/users.html.twig', $template);
    }

    /**
     * @Route("/partenaire/utilisateurs", name="partner_user_form")
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function userFormAction(Request $request): Response
    {
        $entityManager    = $this->get('doctrine.orm.entity_manager');
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $clientHash       = $request->request->get('user');
        $action           = $request->request->get('action');

        if (false === empty($clientHash) && false === empty($action) && ($client = $clientRepository->findOneBy(['hash' => $clientHash]))) {
            switch ($action) {
                case 'password':
                    /** @var \temporary_links_login $temporaryLink */
                    $temporaryLink = $this->get('unilend.service.entity_manager')->getRepository('temporary_links_login');
                    $token         = $temporaryLink->generateTemporaryLink($client->getIdClient(), \temporary_links_login::PASSWORD_TOKEN_LIFETIME_MEDIUM);
                    $keywords      = [
                        'firstName'    => $client->getPrenom(),
                        'login'        => $client->getEmail(),
                        'passwordLink' => $this->generateUrl('partner_security', ['securityToken' => $token], UrlGeneratorInterface::ABSOLUTE_URL)
                    ];

                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('mot-de-passe-oublie-partenaire', $keywords);

                    try {
                        $message->setTo($client->getEmail());
                        $mailer = $this->get('mailer');
                        $mailer->send($message);
                    } catch (\Exception $exception) {
                        $this->get('logger')->warning(
                            'Could not send email : mot-de-passe-oublie-partenaire - Exception: ' . $exception->getMessage(),
                            ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                        );
                    }
                    break;
                case 'deactivate':
                    $this->get('unilend.service.client_status_manager')->addClientStatus($client, Users::USER_ID_FRONT, ClientsStatus::STATUS_DISABLED);
                    break;
                case 'activate':
                    $duplicates = $clientRepository->findByEmailAndStatus($client->getEmail(), ClientsStatus::GRANTED_LOGIN);

                    if (empty($duplicates)) {
                        $this->get('unilend.service.client_status_manager')->addClientStatus($client, Users::USER_ID_FRONT, ClientsStatus::STATUS_VALIDATED);
                    } else {
                        $this->addFlash('partnerUserError', $this->get('translator')->trans('partner-users_duplicate-online-user-error-message'));
                    }
                    break;
            }
        }

        return $this->redirectToRoute('partner_users');
    }

    /**
     * @Route("/partenaire/utilisateurs/creation", name="partner_user_creation")
     * @Method("GET")
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function userCreationAction(Request $request)
    {
        $session = $request->getSession()->get('partnerUserCreation');
        $errors  = isset($session['errors']) ? $session['errors'] : [];
        $values  = isset($session['values']) ? $session['values'] : [];

        $request->getSession()->remove('partnerUserCreation');

        $template = [
            'entities'  => $this->getUserCompanies(),
            'form'      => [
                'errors' => $errors,
                'values' => [
                    'firstname' => isset($values['firstname']) ? $values['firstname'] : '',
                    'lastname'  => isset($values['lastname']) ? $values['lastname'] : '',
                    'email'     => isset($values['email']) ? $values['email'] : '',
                    'phone'     => isset($values['phone']) ? $values['phone'] : '',
                    'entity'    => isset($values['entity']) ? $values['entity'] : '',
                    'role'      => isset($values['role']) ? $values['role'] : ''
                ]
            ]
        ];

        return $this->render('/partner_account/user_creation.html.twig', $template);
    }

    /**
     * @Route("/partenaire/utilisateurs/creation", name="partner_user_creation_form")
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function userCreationFormAction(Request $request)
    {
        $errors            = [];
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $clientRepository  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $companyRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Companies');

        if (empty($request->request->get('lastname'))) {
            $errors['lastname'] = true;
        }
        if (empty($request->request->get('firstname'))) {
            $errors['firstname'] = true;
        }
        if (empty($request->request->get('email')) || false === filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = true;
        }
        $duplicates = $clientRepository->findByEmailAndStatus($request->request->get('email'), ClientsStatus::GRANTED_LOGIN);
        if (false === empty($duplicates)) {
            $errors['email_existing'] = true;
        }
        if (empty($request->request->get('phone'))) {
            $errors['phone'] = true;
        }
        if (empty($request->request->getInt('entity')) || null === ($company = $companyRepository->find($request->request->getInt('entity')))) {
            $errors['entity'] = true;
        }
        if (empty($request->request->get('role')) || false === in_array($request->request->get('role'), ['admin', 'agent'])) {
            $errors['role'] = true;
        }

        if (false === empty($errors)) {
            $request->getSession()->set('partnerUserCreation', [
                'errors' => $errors,
                'values' => $request->request->all()
            ]);

            return $this->redirectToRoute('partner_user_creation');
        }

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $client = new Clients();
        $client
            ->setNom($request->request->get('lastname'))
            ->setPrenom($request->request->get('firstname'))
            ->setEmail($request->request->get('email'))
            ->setTelephone($request->request->get('phone'))
            ->setIdLangue('fr');

        $companyClient = new CompanyClient();
        $companyClient
            ->setIdCompany($company)
            ->setIdClient($client)
            ->setRole('admin' === $request->request->get('role') ? UserPartner::ROLE_ADMIN : UserPartner::ROLE_USER);

        $entityManager->beginTransaction();

        try {
            $entityManager->persist($client);
            $entityManager->persist($companyClient);
            $entityManager->flush();

            $this->get('unilend.service.client_creation_manager')->createAccount($client, WalletType::PARTNER, Users::USER_ID_FRONT, ClientsStatus::STATUS_VALIDATED);

            $entityManager->commit();

            /** @var \Unilend\Bundle\CoreBusinessBundle\Service\MailerManager $mailerManager */
            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendPartnerAccountActivation($client);
        } catch (\Exception $exception) {
            $entityManager->getConnection()->rollBack();
            $this->get('logger')->error('An error occurred while creating client ', [['class' => __CLASS__, 'function' => __FUNCTION__]]);
        }

        return $this->redirectToRoute('partner_users');
    }

    /**
     * @Route("/partenaire/securite/{securityToken}", name="partner_security", requirements={"securityToken": "[0-9a-f]+"})
     *
     * @param string  $securityToken
     * @param Request $request
     *
     * @return Response
     */
    public function securityAction(string $securityToken, Request $request): Response
    {
        $isLinkExpired = false;
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var TemporaryLinksLogin $temporaryLinks */
        $temporaryLinks = $entityManager->getRepository('UnilendCoreBusinessBundle:TemporaryLinksLogin')->findOneBy(['token' => $securityToken]);

        if (null === $temporaryLinks) {
            return $this->redirectToRoute('home');
        }

        $now         = new \DateTime();
        $linkExpires = $temporaryLinks->getExpires();

        if ($linkExpires <= $now) {
            $isLinkExpired = true;
        } else {
            $client       = $temporaryLinks->getIdClient();
            $clientStatus = $client->getIdClientStatusHistory()->getIdStatus()->getId();

            if (null === $client || false === $client->isPartner() || ClientsStatus::STATUS_VALIDATED !== $clientStatus) {
                return $this->redirectToRoute('home');
            }

            $temporaryLinks->setAccessed($now);

            $entityManager->persist($temporaryLinks);
            $entityManager->flush();

            if ($request->isMethod(Request::METHOD_POST)) {
                $translator  = $this->get('translator');
                $formData    = $request->request->get('partner_security', []);
                $error       = false;
                $password    = '';
                $userPartner = $this->get('unilend.frontbundle.security.user_provider')->loadUserByUsername($client->getEmail());

                try {
                    if (empty($formData['password'])) {
                        throw new \Exception('password empty');
                    }
                    $password = $this->get('security.password_encoder')->encodePassword($userPartner, $formData['password']);
                } catch (\Exception $exception) {
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
                    $client->setPassword($password);
                    $client->setSecreteQuestion(filter_var($formData['question'], FILTER_SANITIZE_STRING));
                    $client->setSecreteReponse(md5($formData['answer']));

                    $entityManager->persist($client);

                    $temporaryLinks->setExpires($now);

                    $entityManager->persist($temporaryLinks);
                    $entityManager->flush();

                    return $this->redirectToRoute('login');
                }
            }
        }

        return $this->render('partner_account/security.html.twig', ['expired' => $isLinkExpired, 'token' => $securityToken]);
    }

    /**
     * @return Companies[]
     */
    private function getUserCompanies()
    {
        /** @var UserPartner $user */
        $user      = $this->getUser();
        $companies = [$user->getCompany()];

        if (in_array(UserPartner::ROLE_ADMIN, $user->getRoles())) {
            $companies = $this->getCompanyTree($user->getCompany(), $companies);
        }

        return $companies;
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

        usort($tree, function ($first, $second) {
            return strcasecmp($first->getName(), $second->getName());
        });

        return $tree;
    }
}
