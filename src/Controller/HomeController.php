<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function home(?UserInterface $client): Response
    {
        if ($client instanceof Clients) {
            return $this->redirectToRoute('wallet');
        }

        return $this->redirectToRoute('login');
    }
}
