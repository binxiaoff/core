<?php

declare(strict_types=1);

namespace Unilend\Controller\User;

use DateTime;
use Doctrine\ORM\{EntityManagerInterface, ORMException, OptimisticLockException};
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\ClientsStatus;
use Unilend\Entity\ClientsStatusHistory;
use Unilend\Entity\ProjectInvitation;
use Unilend\Entity\TemporaryLinksLogin;
use Unilend\Form\User\InitProfileType;
use Unilend\Message\Client\ClientCreated;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\ClientsStatusRepository;
use Unilend\Repository\ProjectInvitationRepository;
use Unilend\Repository\TemporaryLinksLoginRepository;
use Unilend\Security\LoginAuthenticator;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class AccountController extends AbstractController
{
    /**
     * @Route("/compte/initialisation/{securityToken}/{idInvitation}", name="account_init", requirements={"securityToken": "[0-9a-f]+"}, methods={"GET", "POST"})
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     * @ParamConverter("projectInvitation", options={"mapping": {"idInvitation": "id"}})
     *
     * @param Request                       $request
     * @param TemporaryLinksLogin           $temporaryLink
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param TranslatorInterface           $translator
     * @param UserPasswordEncoderInterface  $userPasswordEncoder
     * @param ClientsRepository             $clientsRepository
     * @param ServiceTermsManager           $serviceTermsManager
     * @param MessageBusInterface           $messageBus
     * @param ProjectInvitation             $projectInvitation
     * @param ClientsStatusRepository       $clientsStatusRepository
     * @param ProjectInvitationRepository   $projectInvitationRepository
     * @param EntityManagerInterface        $entityManager
     * @param LoginAuthenticator            $loginAuthenticator
     * @param RouterInterface               $router
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function init(
        Request $request,
        TemporaryLinksLogin $temporaryLink,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $userPasswordEncoder,
        ClientsRepository $clientsRepository,
        ServiceTermsManager $serviceTermsManager,
        MessageBusInterface $messageBus,
        ProjectInvitation $projectInvitation,
        ClientsStatusRepository $clientsStatusRepository,
        ProjectInvitationRepository $projectInvitationRepository,
        EntityManagerInterface $entityManager,
        LoginAuthenticator $loginAuthenticator,
        RouterInterface $router
    ): Response {
        if ($temporaryLink->getExpires() < new DateTime()) {
            $this->addFlash('error', $translator->trans('account-init.invalid-link-error-message'));

            return $this->render('user/init.html.twig');
        }
        if ($temporaryLink->getIdClient() !== $projectInvitation->getClient()) {
            return $this->redirectToRoute('home');
        }

        $client = $temporaryLink->getIdClient();

        if (null === $client || false === $client->isCreated() || false === empty($client->getPassword())) {
            return $this->redirectToRoute('home');
        }

        $temporaryLink->setAccessed(new DateTime());
        $temporaryLinksLoginRepository->save($temporaryLink);

        $form = $this->createForm(InitProfileType::class, $client);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $securityQuestion  = $form->get('securityQuestion')->getData();
            $encryptedPassword = $userPasswordEncoder->encodePassword($client, $form->get('password')->get('plainPassword')->getData());
            $client
                ->setPassword($encryptedPassword)
                ->setSecurityQuestion($securityQuestion['securityQuestion'])
                ->setSecurityAnswer($securityQuestion['securityAnswer'])
            ;

            $serviceTermsManager->acceptCurrentVersion($client);

            $clientsRepository->save($client);

            $temporaryLink->setExpires(new DateTime());
            $temporaryLinksLoginRepository->save($temporaryLink);

            $messageBus->dispatch(new ClientCreated($client->getIdClient()));
            $projectInvitation->isFinished();
            $projectInvitationRepository->save($projectInvitation);

            $status              = $clientsStatusRepository->findOneBy(['id' => ClientsStatus::STATUS_VALIDATED]);
            $statusClientHistory = (new ClientsStatusHistory())
                ->setIdClient($client)
                ->setIdStatus($status)
            ;
            $entityManager->persist($statusClientHistory);
            $entityManager->flush();

            $this->addFlash('accountCreatedSuccess', $translator->trans('account-init.account-completed'));
            $targetPath = $router->generate('edit_project_details', ['hash' => $projectInvitation->getProject()->getHash()], RouterInterface::ABSOLUTE_URL);
            $loginAuthenticator->setTargetPath(
                $request,
                $targetPath
            );

            return $this->redirectToRoute('login');
        }

        return $this->render('user/init.html.twig', ['form' => $form->createView()]);
    }
}
