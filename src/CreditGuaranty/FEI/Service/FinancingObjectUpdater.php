<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use KLS\CreditGuaranty\FEI\Security\Voter\ProgramVoter;
use KLS\CreditGuaranty\FEI\Validator\Constraints\FinancingObjectImportData;
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
        Security $security,
        FinancingObjectRepository $financingObjectRepository
    ) {
        $this->validator                 = $validator;
        $this->security                  = $security;
        $this->financingObjectRepository = $financingObjectRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(array $data): array
    {
        $importDataConstraint = new FinancingObjectImportData();

        $violations = new ConstraintViolationList();
        // First we check if the file contains errors
        foreach ($data as $line => $item) {
            // +2 because array starts with key 0 and we need to skip the header line
            $item['line'] = $line + 2;
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function processData(array $data): array
    {
        $batchSize                   = 100;
        $itemUpdated                 = 0;
        $notFoundFinancingObject     = [];
        $accessDeniedFinancingObject = [];

        foreach ($data as $line => $item) {
            $financingObject = $this->financingObjectRepository->findOneBy([
                'loanNumber'      => $item[self::GREEN_COLUMN],
                'operationNumber' => $item[self::OPERATION_COLUMN],
            ]);

            $lineNumber = $line + 2;

            if (false === $financingObject instanceof FinancingObject) {
                // We need to return the lines not corresponding with our database
                $notFoundFinancingObject[] = [
                    // +2 because array starts with key 0 and we need to skip the header line
                    'Ligne ' . $lineNumber => [
                        self::GREEN_COLUMN     => $item[self::GREEN_COLUMN],
                        self::OPERATION_COLUMN => $item[self::OPERATION_COLUMN],
                    ],
                ];

                continue;
            }

            if (false === $this->security->isGranted(ProgramVoter::ATTRIBUTE_IMPORT, $financingObject->getProgram())) {
                // +2 because array starts with key 0 and we need to skip the header line
                $accessDeniedFinancingObject[] = 'Ligne ' . $lineNumber;

                continue;
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
            'accessDeniedFinancingObject' => $accessDeniedFinancingObject,
            'notFoundFinancingObject'     => $notFoundFinancingObject,
            'itemUpdated'                 => $itemUpdated,
            'itemTotal'                   => \count($data),
        ];
    }
}
