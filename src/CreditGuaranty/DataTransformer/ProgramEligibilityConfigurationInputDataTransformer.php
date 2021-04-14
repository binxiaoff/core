<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\CreditGuaranty\DTO\ProgramEligibilityConfigurationInput;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;

class ProgramEligibilityConfigurationInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;

    /**
     * @param ValidatorInterface            $validator
     * @param ProgramChoiceOptionRepository $programChoiceOptionRepository
     */
    public function __construct(ValidatorInterface $validator, ProgramChoiceOptionRepository $programChoiceOptionRepository)
    {
        $this->validator                     = $validator;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return (ProgramEligibilityConfiguration::class === $to) && (ProgramEligibilityConfigurationInput::class === $context['input']['class']);
    }

    /**
     * @todo: check user's permission when the habilitation is available
     *
     * @param ProgramEligibilityConfigurationInput $object
     * @param string                               $to
     * @param array                                $context
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return ProgramEligibilityConfiguration
     */
    public function transform($object, string $to, array $context = []): ProgramEligibilityConfiguration
    {
        $this->validator->validate($object);

        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $object->programEligibility->getProgram(),
            'field'       => $object->programEligibility->getField(),
            'description' => $object->value,
        ]);

        if (null === $programChoiceOption) {
            $programChoiceOption = new ProgramChoiceOption($object->programEligibility->getProgram(), $object->value, $object->programEligibility->getField());
            $this->programChoiceOptionRepository->save($programChoiceOption);
        }

        return new ProgramEligibilityConfiguration($object->programEligibility, $programChoiceOption, null, true);
    }
}
