<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyClient;
use Unilend\Bundle\CoreBusinessBundle\Entity\TemporaryLinksLogin;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;
use Unilend\core\Loader;

class UsersController extends Controller
{
    /**
     * @Route("partenaire/utilisateurs", name="partner_users")
     * @Method("GET")
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @return Response
     */
    public function usersAction()
    {
        $template                = ['users' => []];
        $entityManager           = $this->get('doctrine.orm.entity_manager');
        $companyClientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient');
        $users                   = $companyClientRepository->findBy(['idCompany' => $this->getUserCompanies()]);

        foreach ($users as $user) {
            $template['users'][] = [
                'client' => $user->getIdClient(),
                'role'   => $user->getRole() === UserPartner::ROLE_ADMIN ? 'admin' : 'agent',
                'entity' => $user->getIdCompany()
            ];
        }

        return $this->render('/partner_account/users.html.twig', $template);
    }

    /**
     * @Route("partenaire/utilisateurs", name="partner_user_form")
     * @Method("POST")
     * @Security("has_role('ROLE_PARTNER_ADMIN')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function userFormAction(Request $request)
    {
        $entityManager      = $this->get('doctrine.orm.entity_manager');
        $clientRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $settingsRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings');
        $clientHash         = $request->request->get('user');
        $action             = $request->request->get('action');

        if (false === empty($clientHash) && false === empty($action) && ($client = $clientRepository->findOneBy(['hash' => $clientHash]))) {
            switch ($action) {
                case 'password':
                    /** @var \temporary_links_login $temporaryLink */
                    $temporaryLink = $this->get('unilend.service.entity_manager')->getRepository('temporary_links_login');
                    $token         = $temporaryLink->generateTemporaryLink($client->getIdClient(), \temporary_links_login::PASSWORD_TOKEN_LIFETIME_SHORT);
                    $keywords      = [
                        'surl'          => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default'),
                        'url'           => $this->getParameter('router.request_context.scheme') . '://' . $this->getParameter('url.host_default'),
                        'prenom'        => $client->getPrenom(),
                        'login'         => $client->getEmail(),
                        'link_password' => $this->generateUrl('partner_security', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                        'lien_fb'       => $settingsRepository->findOneBy(['type' => 'Facebook'])->getValue(),
                        'lien_tw'       => $settingsRepository->findOneBy(['type' => 'Twitter'])->getValue()
                    ];

                    $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('mot-de-passe-oublie', $keywords);
                    $message->setTo($client->getEmail());
                    $mailer = $this->get('mailer');
                    $mailer->send($message);
                    break;
                case 'deactivate':
                    $client->setStatus(Clients::STATUS_OFFLINE);
                    $entityManager->persist($client);
                    $entityManager->flush();
                    break;
                case 'activate':
                    $client->setStatus(Clients::STATUS_ONLINE);
                    $entityManager->persist($client);
                    $entityManager->flush();
                    break;
            }
        }

        return $this->redirectToRoute('partner_users');
    }

    /**
     * @Route("partenaire/utilisateurs/creation", name="partner_user_creation")
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
     * @Route("partenaire/utilisateurs/creation", name="partner_user_creation_form")
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
        if (
            empty($request->request->get('email'))
            || false === filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)
            || $clientRepository->existEmail($request->request->get('email'))
        ) {
            $errors['email'] = true;
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
        $client->setNom($request->request->get('lastname'))
            ->setPrenom($request->request->get('firstname'))
            ->setEmail($request->request->get('email'))
            ->setTelephone($request->request->get('phone'))
            ->setIdLangue('fr')
            ->setSlug($ficelle->generateSlug($request->request->get('firstname') . '-' . $request->request->get('lastname')))
            ->setStatus(Clients::STATUS_ONLINE);

        $companyClient = new CompanyClient();
        $companyClient->setIdCompany($company);
        $companyClient->setIdClient($client);
        $companyClient->setRole('admin' === $request->request->get('role') ? UserPartner::ROLE_ADMIN : UserPartner::ROLE_USER);

        $entityManager->persist($client);
        $entityManager->persist($companyClient);
        $entityManager->flush();

        return $this->redirectToRoute('partner_users');
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

        return $tree;
    }
}
