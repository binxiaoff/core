<?php

declare(strict_types=1);

namespace Unilend\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Unilend\Entity\Clients;
use Unilend\Repository\ClientsRepository;

class ClientDataPersister implements DataPersisterInterface
{
    /** @var ClientsRepository */
    private $clientsRepository;
    /** @var UserPasswordEncoderInterface */
    private $userPasswordEncoder;

    /**
     * @param ClientsRepository            $clientsRepository
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     */
    public function __construct(ClientsRepository $clientsRepository, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->clientsRepository   = $clientsRepository;
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data): bool
    {
        return $data instanceof Clients;
    }

    /**
     * {@inheritdoc}
     *
     * @param Clients $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function persist($data): void
    {
        if ($data->getPlainPassword()) {
            $data->setPassword(
                $this->userPasswordEncoder->encodePassword($data, $data->getPlainPassword())
            );

            $data->eraseCredentials();
        }

        $this->clientsRepository->save($data);
    }

    /**
     * {@inheritdoc}
     *
     * @param Clients $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove($data): void
    {
        $this->clientsRepository->remove($data);
    }
}
