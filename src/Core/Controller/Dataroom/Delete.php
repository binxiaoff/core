<?php

declare(strict_types=1);

namespace Unilend\Core\Controller\Dataroom;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Unilend\Core\Entity\Drive;

class Delete
{
    private ObjectManager $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function __invoke(Drive $data, Request $request)
    {
        $return = $data->get($request->get('path'));

        if (null === $return) {
            throw new NotFoundHttpException();
        }

        if ($return instanceof Drive) {
            throw new AccessDeniedHttpException();
        }

        $this->manager->remove($return);
        $this->manager->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
