<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\CreditGuaranty\DTO\ProgramBorrowerTypeAllocationInput;
use Unilend\CreditGuaranty\Entity\{Constant\FieldAlias, ProgramBorrowerTypeAllocation, ProgramChoiceOption};
use Unilend\CreditGuaranty\Repository\{FieldConfigurationRepository, ProgramChoiceOptionRepository};

class ProgramBorrowerTypeAllocationInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private FieldConfigurationRepository $fieldConfigurationRepository;

    /**
     * @param ValidatorInterface            $validator
     * @param ProgramChoiceOptionRepository $programChoiceOptionRepository
     * @param FieldConfigurationRepository  $fieldConfigurationRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        FieldConfigurationRepository $fieldConfigurationRepository
    ) {
        $this->validator                     = $validator;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->fieldConfigurationRepository  = $fieldConfigurationRepository;
    }
    /**
     * @inheritDoc
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return (ProgramBorrowerTypeAllocation::class === $to) && (ProgramBorrowerTypeAllocationInput::class === $context['input']['class']);
    }

    /**
     * @todo: check user's permission when the habilitation is available
     *
     * @param ProgramBorrowerTypeAllocationInput $object
     * @param string                             $to
     * @param array                              $context
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return ProgramBorrowerTypeAllocation
     */
    public function transform($object, string $to, array $context = []): ProgramBorrowerTypeAllocation
    {
        $this->validator->validate($object);
        $fieldConfiguration = $this->fieldConfigurationRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'            => $object->program,
            'fieldConfiguration' => $fieldConfiguration,
            'description'        => $object->borrowerType,
        ]);
        if (null === $programChoiceOption) {
            $programChoiceOption = new ProgramChoiceOption($object->program, $object->borrowerType, $fieldConfiguration);
            $this->programChoiceOptionRepository->save($programChoiceOption);
        }

        return new ProgramBorrowerTypeAllocation($object->program, $programChoiceOption, $object->maxAllocationRate);
    }
}
