<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use KLS\CreditGuaranty\FEI\Security\Voter\ProgramVoter;
use KLS\CreditGuaranty\FEI\Validator\Constraints\FinancingObjectImportData;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FinancingObjectUpdater
{
    public const IMPORT_FILE_COLUMNS = [
        self::GREEN_COLUMN,
        self::OPERATION_COLUMN,
        self::CRD_COLUMN,
        self::MATURITY_COLUMN,
    ];
    public const GREEN_COLUMN     = 'n° GREEN';
    public const OPERATION_COLUMN = 'n° d\'opération';
    public const CRD_COLUMN       = 'CRD';
    public const MATURITY_COLUMN  = 'Maturité';

    private ValidatorInterface $validator;
    private FinancingObjectRepository $financingObjectRepository;
    private Security $security;

    public function __construct(
        ValidatorInterface $validator,
        FinancingObjectRepository $financingObjectRepository,
        Security $security
    ) {
        $this->validator                 = $validator;
        $this->financingObjectRepository = $financingObjectRepository;
        $this->security                  = $security;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws MappingException
     */
    public function update(array $data): array
    {
        $importDataConstraint = new FinancingObjectImportData();

        $violations = new ConstraintViolationList();
        // First we check if the file contains errors
        foreach ($data as $line => $item) {
            $item['line'] = $line + 1;
            $violations->addAll($this->validator->validate($item, $importDataConstraint));

            if (\count($violations) > 10) {
                break;
            }
        }

        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }

        return $this->processData($data);
    }

    /**
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function processData(array $data): array
    {
        $batchSize               = 100;
        $itemUpdated             = 0;
        $notFoundFinancingObject = [];
        $programAccessibilities  = [];

        foreach ($data as $line => $item) {
            $financingObject = $this->financingObjectRepository->findOneBy([
                'loanNumber'      => $item[self::GREEN_COLUMN],
                'operationNumber' => $item[self::OPERATION_COLUMN],
            ]);

            if (false === $financingObject instanceof FinancingObject) {
                //We need to return the lines not corresponding with our database
                $notFoundFinancingObject[] = [
                    //+ 1 because we need to add the header line
                    'Ligne ' . ($line + 1) => [
                        self::GREEN_COLUMN     => $item[self::GREEN_COLUMN],
                        self::OPERATION_COLUMN => $item[self::OPERATION_COLUMN],
                    ],
                ];

                continue;
            }

            if (false === \in_array($financingObject->getProgram()->getId(), $programAccessibilities, true)) {
                $programAccessibilities[$financingObject->getProgram()->getId()] = $this->security->isGranted(
                    ProgramVoter::ATTRIBUTE_REPORTING,
                    $financingObject->getProgram()
                );
            }

            if (false === $programAccessibilities[$financingObject->getProgram()->getId()]) {
                throw new AccessDeniedException();
            }

            $financingObject->setNewMaturity((int) $item[self::MATURITY_COLUMN]);
            $financingObject->setRemainingCapital(new NullableMoney('EUR', $item[self::CRD_COLUMN]));

            if (($itemUpdated % $batchSize) === 0) {
                $this->financingObjectRepository->flush();
            }
            ++$itemUpdated;
        }

        $this->financingObjectRepository->flush();

        return [
            'notFoundFinancingObject' => $notFoundFinancingObject,
            'itemUpdated'             => $itemUpdated,
            'itemTotal'               => \count($data),
        ];
    }
}
