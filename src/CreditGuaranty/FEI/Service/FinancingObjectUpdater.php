<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Service;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\ReportingTemplate;
use KLS\CreditGuaranty\FEI\Repository\FinancingObjectRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FinancingObjectUpdater
{
    private ValidatorInterface $validator;
    private FinancingObjectRepository $financingObjectRepository;

    public function __construct(ValidatorInterface $validator, FinancingObjectRepository $financingObjectRepository)
    {
        $this->validator                 = $validator;
        $this->financingObjectRepository = $financingObjectRepository;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws MappingException
     */
    public function update(array $data): array
    {
        $constraints = new Assert\Collection([
            'fields' => [
                ReportingTemplate::GREEN_COLUMN => new Assert\Required([
                    'constraints' => new Assert\NotBlank(),
                ]),
                ReportingTemplate::OPERATION_COLUMN => new Assert\Required([
                    'constraints' => new Assert\NotBlank(),
                ]),
                ReportingTemplate::CRD_COLUMN => new Assert\Required([
                    'constraints' => [
                        new Assert\PositiveOrZero(),
                        new Assert\Type('numeric'),
                    ],
                ]),
                ReportingTemplate::MATURITE_COLUMN => new Assert\Required([
                    'constraints' => new Assert\GreaterThanOrEqual(1),
                ]),
            ],
        ]);

        $violations              = [];
        $notFoundFinancingObject = [];
        $batchSize               = 100;
        $i                       = 1;

        foreach ($data as $key => $item) {
            $error = $this->validator->validate($item, $constraints);

            //if we have at least one violation, we don't want to process the data, but only return the errors
            if (\count($error) > 0 || \count($violations) > 0) {
                foreach ($error as $itemError) {
                    if (false === \in_array($itemError->getCode(), \array_column($violations, 'code'), true)) {
                        $violations[] = [
                            'propertyPath' => $itemError->getPropertyPath(),
                            'message'      => $itemError->getMessage(),
                            'code'         => $itemError->getCode(),
                        ];
                    }
                }

                continue;
            }

            $financingObject = $this->financingObjectRepository->findOneBy([
                'loanNumber'      => $item[ReportingTemplate::GREEN_COLUMN],
                'operationNumber' => $item[ReportingTemplate::OPERATION_COLUMN],
            ]);

            if (false === $financingObject instanceof FinancingObject) {
                //We need to return the lines not corresponding with our database
                $notFoundFinancingObject[] = [
                    //add the header line
                    'Ligne ' . ($key + 1) => [
                        ReportingTemplate::GREEN_COLUMN     => $item[ReportingTemplate::GREEN_COLUMN],
                        ReportingTemplate::OPERATION_COLUMN => $item[ReportingTemplate::OPERATION_COLUMN],
                    ],
                ];

                continue;
            }

            $financingObject->setNewMaturity((int) $item[ReportingTemplate::MATURITE_COLUMN]);
            $financingObject->setRemainingCapital(new NullableMoney('EUR', $item[ReportingTemplate::CRD_COLUMN]));
            ++$i;

            if (($i % $batchSize) === 0) {
                $this->financingObjectRepository->flush();
                $this->financingObjectRepository->clear();
            }
        }

        $this->financingObjectRepository->flush();

        //This itemCount is used on the front part to get how many items have been (not)updated
        // (itemCount - violations['notFoundFinancingObject'])
        return [
            'violations'              => $violations,
            'notFoundFinancingObject' => $notFoundFinancingObject,
            'itemCount'               => \count($data),
        ];
    }
}
