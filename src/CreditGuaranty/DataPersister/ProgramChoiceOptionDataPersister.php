<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;

class ProgramChoiceOptionDataPersister implements DataPersisterInterface
{
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;

    public function __construct(ProgramChoiceOptionRepository $programChoiceOptionRepository)
    {
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
    }

    public function supports($data): bool
    {
        return $data instanceof ProgramChoiceOption;
    }

    /**
     * @param ProgramChoiceOption $data
     *
     * @throws ORMException
     */
    public function persist($data): void
    {
        $this->programChoiceOptionRepository->save($data);
    }

    /**
     * @param ProgramChoiceOption $data
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove($data): void
    {
        if ($data->isArchived()) {
            return;
        }

        try {
            $this->programChoiceOptionRepository->remove($data);
        } catch (ForeignKeyConstraintViolationException $exception) {
            // we have to reset registry manager because it closes on exception
            $this->programChoiceOptionRepository->resetManager();

            // we use find() instead of merge() because it will be deprecated
            $data = $this->programChoiceOptionRepository->find($data->getId());
            $data->archive();
            $this->programChoiceOptionRepository->save($data);
        }
    }
}
