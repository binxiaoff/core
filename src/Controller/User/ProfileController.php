<?php

declare(strict_types=1);

namespace Unilend\Controller\User;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\Clients;
use Unilend\Form\User\IdentityType;
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
     *
     * @return Response
     */
    public function profile(Request $request, ?UserInterface $user, ClientsRepository $clientsRepository, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(IdentityType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client = $form->getData();
            $clientsRepository->save($client);

            $this->addFlash('updateSuccess', $translator->trans('user-profile-form.update-success-message'));

            return $this->redirectToRoute('profile');
        }

        return $this->render('user/profile.html.twig', ['form' => $form->createView()]);
    }
}
