<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\TemporaryLinksLogin;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;
use Unilend\core\Loader;

class UsersController extends Controller
{
    /**
     * @Route("partenaire/utilisateurs", name="partner_users")
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
                'role'   => $user->getRole() === 'ROLE_PARTNER_ADMIN' ? 'admin' : 'agent',
                'entity' => $user->getIdCompany()
            ];
        }

        return $this->render('/partner_account/users.html.twig', $template);
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

        if (in_array('ROLE_PARTNER_ADMIN', $user->getRoles())) {
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
