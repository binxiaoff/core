<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, ClientsStatus, CompanyClient, TemporaryLinksLogin, Users, WalletType};

class UsersController extends Controller
{
    /**
     * @Route("/partenaire/utilisateurs", name="partner_users", methods={"GET"})
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function usersAction(?UserInterface $partnerUser): Response
    {
        $template                = ['users' => []];
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $partnerManager          = $this->get('unilend.service.partner_manager');
        $companyClientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient');
        $users                   = $companyClientRepository->findBy(['idCompany' => $partnerManager->getUserCompanies($partnerUser)]);

        foreach ($users as $user) {
            /** @var Clients $client */
            $client       = $user->getIdClient();
            $clientStatus = $client->getIdClientStatusHistory()->getIdStatus()->getId();
            $userData     = [
                'client' => $client,
                'role'   => in_array(Clients::ROLE_PARTNER_ADMIN, $client->getRoles()) ? 'admin' : 'agent',
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
     * @Route("/partenaire/utilisateurs", name="partner_user_form", methods={"POST"})
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
                    $token = $entityManager
                        ->getRepository('UnilendCoreBusinessBundle:TemporaryLinksLogin')
                        ->generateTemporaryLink($client, TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_MEDIUM);

                    $keywords = [
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
                        $this->get('logger')->warning('Could not send email : mot-de-passe-oublie-partenaire - Exception: ' . $exception->getMessage(), [
                            'id_mail_template' => $message->getTemplateId(),
                            'id_client'        => $client->getIdClient(),
                            'class'            => __CLASS__,
                            'function'         => __FUNCTION__
                        ]);
                    }
                    break;
                case 'deactivate':
                    $this->get('unilend.service.client_status_manager')->addClientStatus($client, Users::USER_ID_FRONT, ClientsStatus::STATUS_DISABLED);
                    break;
                case 'activate':
                    $duplicates = $clientRepository->findGrantedLoginAccountsByEmail($client->getEmail());

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
     * @Route("/partenaire/utilisateurs/creation", name="partner_user_creation", methods={"GET"})
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $partnerUser
     *
     * @return Response
     */
    public function userCreationAction(Request $request, ?UserInterface $partnerUser): Response
    {
        $session = $request->getSession()->get('partnerUserCreation');
        $errors  = isset($session['errors']) ? $session['errors'] : [];
        $values  = isset($session['values']) ? $session['values'] : [];

        $request->getSession()->remove('partnerUserCreation');

        $template = [
            'entities'  => $this->get('unilend.service.partner_manager')->getUserCompanies($partnerUser),
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
     * @Route("/partenaire/utilisateurs/creation", name="partner_user_creation_form", methods={"POST"})
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function userCreationFormAction(Request $request): Response
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
        $duplicates = $clientRepository->findGrantedLoginAccountsByEmail($request->request->get('email'));
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
            ->setRole('admin' === $request->request->get('role') ? Clients::ROLE_PARTNER_ADMIN : Clients::ROLE_PARTNER_USER);

        $entityManager->beginTransaction();

        try {
            $entityManager->persist($client);
            $entityManager->persist($companyClient);
            $entityManager->flush();

            $this->get('unilend.service.client_creation_manager')->createAccount($client, WalletType::PARTNER, Users::USER_ID_FRONT, ClientsStatus::STATUS_VALIDATED);

            $entityManager->commit();

            $mailerManager = $this->get('unilend.service.email_manager');
            $mailerManager->sendPartnerAccountActivation($client);
        } catch (\Exception $exception) {
            $this->get('logger')->error('An error occurred while creating client. Message: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);

            $entityManager->getConnection()->rollBack();
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
            $client = $temporaryLinks->getIdClient();

            if (null === $client || false === $client->isPartner() || false === $client->isValidated()) {
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

                try {
                    if (empty($formData['password'])) {
                        throw new \Exception('password empty');
                    }
                    $password = $this->get('security.password_encoder')->encodePassword($client, $formData['password']);
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
}
