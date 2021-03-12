<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\CreditGuaranty\DTO\ProgramBorrowerTypeAllocationInput;
use Unilend\CreditGuaranty\Entity\{Constant\EligibilityFieldAlias, ProgramBorrowerTypeAllocation, ProgramChoiceOption};
use Unilend\CreditGuaranty\Repository\{EligibilityCriteriaRepository, ProgramChoiceOptionRepository};

class ProgramBorrowerTypeAllocationInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private EligibilityCriteriaRepository $eligibilityCriteriaRepository;

    /**
     * @param ValidatorInterface            $validator
     * @param ProgramChoiceOptionRepository $programChoiceOptionRepository
     * @param EligibilityCriteriaRepository $eligibilityCriteriaRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        EligibilityCriteriaRepository $eligibilityCriteriaRepository
    ) {
        $this->validator                     = $validator;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->eligibilityCriteriaRepository = $eligibilityCriteriaRepository;
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
        $eligibilityCriteria = $this->eligibilityCriteriaRepository->findOneBy(['fieldAlias' => EligibilityFieldAlias::BORROWER_TYPE]);
        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'             => $object->program,
            'eligibilityCriteria' => $eligibilityCriteria,
            'description'         => $object->borrowerType,
        ]);
        if (null === $programChoiceOption) {
            $programChoiceOption = new ProgramChoiceOption($object->program, $object->borrowerType, $eligibilityCriteria);
            $this->programChoiceOptionRepository->save($programChoiceOption);
        }

        return new ProgramBorrowerTypeAllocation($object->program, $programChoiceOption, $object->maxAllocationRate);
    }
}
