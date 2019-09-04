<?php

declare(strict_types=1);

namespace Unilend\Controller\User;

use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\Clients;
use Unilend\Form\User\IdentityType;
use Unilend\Repository\AcceptationLegalDocsRepository;
use Unilend\Repository\ClientsRepository;

class ProfileController extends AbstractController
{
    /**
     * @Route("/profil", name="profile")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     * @param ClientsRepository          $clientsRepository
     * @param TranslatorInterface        $translator
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NonUniqueResultException
     *
     * @return Response
     */
    public function profile(
        Request $request,
        ?UserInterface $user,
        ClientsRepository $clientsRepository,
        TranslatorInterface $translator,
        MailerManager $mailerManager,
        LoggerInterface $logger,
        AcceptationLegalDocsRepository $acceptationLegalDocsRepository
    ): Response {
        $form = $this->createForm(IdentityType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client = $form->getData();

            $this->addFlash('updateSuccess', $translator->trans('user-profile-form.update-success-message'));

            try {
                $mailerManager->sendIdentityUpdated($user);
            } catch (Swift_SwiftException $exception) {
                $logger->error('An error occurred while identity updated email. Message: ' . $exception->getMessage(), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                    'file'     => $exception->getFile(),
                    'line'     => $exception->getLine(),
                ]);
            }

            $clientsRepository->save($client);

            return $this->redirectToRoute('profile');
        }

        $acceptationLegalDocs = $acceptationLegalDocsRepository->findClientsLastSigned($user);

        return $this->render(
            'user/profile.html.twig',
            [
                'form'          => $form->createView(),
                'lastSignedAld' => $acceptationLegalDocs,
            ]
        );
    }
}
