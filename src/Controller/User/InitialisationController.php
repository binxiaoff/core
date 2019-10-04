<?php

declare(strict_types=1);

namespace Unilend\Controller\User;

use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\ClientsStatus;
use Unilend\Entity\Project;
use Unilend\Entity\TemporaryLinksLogin;
use Unilend\Form\User\InitProfileType;
use Unilend\Message\Client\ClientCreated;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\TemporaryLinksLoginRepository;
use Unilend\Security\LoginAuthenticator;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class InitialisationController extends AbstractController
{
    /**
     * @Route("/compte/initialisation/{securityToken}/{slug}", name="account_init", requirements={"securityToken": "[0-9a-f]+"}, methods={"GET", "POST"})
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     * @ParamConverter("project", options={"mapping": {"slug": "slug"}})
     *
     * @param TemporaryLinksLogin           $temporaryLink
     * @param Project                       $project
     * @param Request                       $request
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param TranslatorInterface           $translator
     * @param UserPasswordEncoderInterface  $userPasswordEncoder
     * @param ClientsRepository             $clientsRepository
     * @param ServiceTermsManager           $serviceTermsManager
     * @param MessageBusInterface           $messageBus
     * @param LoginAuthenticator            $loginAuthenticator
     * @param RouterInterface               $router
     * @param ProjectParticipationManager   $projectParticipationManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return RedirectResponse
     */
    public function initialize(
        TemporaryLinksLogin $temporaryLink,
        Project $project,
        Request $request,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $userPasswordEncoder,
        ClientsRepository $clientsRepository,
        ServiceTermsManager $serviceTermsManager,
        MessageBusInterface $messageBus,
        LoginAuthenticator $loginAuthenticator,
        RouterInterface $router,
        ProjectParticipationManager $projectParticipationManager
    ): Response {
        $client = $temporaryLink->getIdClient();

        if (false === $client->isInvited()) {
            return $this->redirectToRoute('lender_project_details', ['slug' => $project->getSlug()]);
        }

        if ($temporaryLink->isExpires()) {
            $this->addFlash('error', $translator->trans('account-init.invalid-link-error-message'));

            return $this->render('user/init.html.twig');
        }

        $client = $temporaryLink->getIdClient();

        if (false === $projectParticipationManager->isConcernedClient($client, $project)) {
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
                ->setCurrentStatus(ClientsStatus::STATUS_CREATED)
            ;
            $clientsRepository->save($client);

            $serviceTermsManager->acceptCurrentVersion($client);

            $temporaryLink->setExpires(new DateTime());
            $temporaryLinksLoginRepository->save($temporaryLink);

            $messageBus->dispatch(new ClientCreated($client));

            $this->addFlash('accountCreatedSuccess', $translator->trans('account-init.account-completed'));

            $targetPath = $router->generate('lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL);
            $loginAuthenticator->setTargetPath($request, $targetPath);

            return $this->redirectToRoute('login');
        }

        return $this->render('user/init.html.twig', ['form' => $form->createView()]);
    }
}
