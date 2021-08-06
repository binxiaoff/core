<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Unilend\CreditGuaranty\DTO\ProgramEligibilityConfigurationInput;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Security\Voter\ProgramChoiceOptionVoter;
use Unilend\CreditGuaranty\Security\Voter\ProgramEligibilityConfigurationVoter;

class ProgramEligibilityConfigurationInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface            $validator;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private Security                      $security;

    public function __construct(ValidatorInterface $validator, ProgramChoiceOptionRepository $programChoiceOptionRepository, Security $security)
    {
        $this->validator                     = $validator;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->security                      = $security;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return (ProgramEligibilityConfiguration::class === $to) && (ProgramEligibilityConfigurationInput::class === $context['input']['class']);
    }

    /**
     * @param ProgramEligibilityConfigurationInput $object
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function transform($object, string $to, array $context = []): ProgramEligibilityConfiguration
    {
        $this->validator->validate($object);

        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $object->programEligibility->getProgram(),
            'field'       => $object->programEligibility->getField(),
            'description' => $object->description,
        ]);

        if (null === $programChoiceOption) {
            $programChoiceOption = new ProgramChoiceOption($object->programEligibility->getProgram(), $object->description, $object->programEligibility->getField());
            if (false === $this->security->isGranted(ProgramChoiceOptionVoter::ATTRIBUTE_CREATE, $programChoiceOption)) {
                throw new AccessDeniedException();
            }
            $this->programChoiceOptionRepository->save($programChoiceOption);
        }

        $programEligibilityConfiguration = new ProgramEligibilityConfiguration($object->programEligibility, $programChoiceOption, null, true);
        if (false === $this->security->isGranted(ProgramEligibilityConfigurationVoter::ATTRIBUTE_CREATE, $programEligibilityConfiguration)) {
            throw new AccessDeniedException();
        }

        return $programEligibilityConfiguration;
    }
}
