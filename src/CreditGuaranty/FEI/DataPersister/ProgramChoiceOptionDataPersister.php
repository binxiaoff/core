<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\DataPersister;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\CreditGuaranty\FEI\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\FEI\Repository\ProgramChoiceOptionRepository;

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
        $this->programChoiceOptionRepository->remove($data);
    }
}
