<?php

declare(strict_types=1);

namespace Unilend\Controller\User;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
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
use Unilend\Entity\TemporaryToken;
use Unilend\Form\User\InitProfileType;
use Unilend\Message\Client\ClientCreated;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\TemporaryTokenRepository;
use Unilend\Security\LoginAuthenticator;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

class InitialisationController extends AbstractController
{
    /**
     * @Route("/compte/initialisation/{securityToken}/{slug}", defaults={"slug": ""}, name="account_init", requirements={"securityToken": "[0-9a-f]+"}, methods={"GET", "POST"})
     *
     * @ParamConverter("temporaryToken", options={"mapping": {"securityToken": "token"}})
     * @ParamConverter("project", options={"mapping": {"slug": "slug"}})
     *
     * @param TemporaryToken               $temporaryToken
     * @param Request                      $request
     * @param TemporaryTokenRepository     $temporaryTokenRepository
     * @param TranslatorInterface          $translator
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     * @param ClientsRepository            $clientsRepository
     * @param ServiceTermsManager          $serviceTermsManager
     * @param MessageBusInterface          $messageBus
     * @param LoginAuthenticator           $loginAuthenticator
     * @param RouterInterface              $router
     * @param ProjectParticipationManager  $projectParticipationManager
     * @param Project                      $project
     *
     * @throws Exception
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return RedirectResponse
     */
    public function initialize(
        TemporaryToken $temporaryToken,
        Request $request,
        TemporaryTokenRepository $temporaryTokenRepository,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $userPasswordEncoder,
        ClientsRepository $clientsRepository,
        ServiceTermsManager $serviceTermsManager,
        MessageBusInterface $messageBus,
        LoginAuthenticator $loginAuthenticator,
        RouterInterface $router,
        ProjectParticipationManager $projectParticipationManager,
        ?Project $project = null
    ): Response {
        if (false === $temporaryToken->isValid()) {
            $this->addFlash('error', $translator->trans('account-init.invalid-link-error-message'));

            return $this->render('user/init.html.twig');
        }

        $client = $temporaryToken->getClient();

        if (false === $client->isInvited()) {
            return $project ?
                $this->redirectToRoute('lender_project_details', ['slug' => $project->getSlug()]) :
                $this->redirectToRoute('home');
        }

        if ($project && false === $projectParticipationManager->isConcernedClient($client, $project)) {
            return $this->redirectToRoute('home');
        }

        $temporaryToken->setAccessed();
        $temporaryTokenRepository->save($temporaryToken);

        $form = $this->createForm(InitProfileType::class, $client);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $encryptedPassword = $userPasswordEncoder->encodePassword($client, $form->get('password')->get('plainPassword')->getData());
            $client
                ->setPassword($encryptedPassword)
                ->setCurrentStatus(ClientsStatus::STATUS_CREATED)
            ;
            $clientsRepository->save($client);

            $serviceTermsManager->acceptCurrentVersion($client);

            $clientsRepository->save($client);

            $temporaryToken->setExpired();
            $temporaryTokenRepository->save($temporaryToken);

            $messageBus->dispatch(new ClientCreated($client));

            $this->addFlash('accountCreatedSuccess', $translator->trans('account-init.account-completed'));

            if ($project) {
                $loginAuthenticator->setTargetPath(
                    $request,
                    $router->generate('lender_project_details', ['slug' => $project->getSlug()], RouterInterface::ABSOLUTE_URL)
                );
            }

            return $this->redirectToRoute('login');
        }

        return $this->render('user/init.html.twig', ['form' => $form->createView()]);
    }
}
