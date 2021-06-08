<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Serializer\Normalizer;

use Doctrine\ORM\ORMException;
use LogicException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
use Unilend\CreditGuaranty\Entity\Interfaces\ProgramAwareInterface;
use Unilend\CreditGuaranty\Entity\Interfaces\ProgramChoiceOptionCarrierInterface;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Security\Voter\ProgramChoiceOptionVoter;
use function Symfony\Component\String\s;

class ProgramChoiceOptionCarrierDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROGRAM_CHOICE_OPTION_DENORMALIZER_ALREADY_CALLED';

    private Security                      $security;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private FieldRepository               $fieldRepository;
    private PropertyAccessorInterface     $propertyAccessor;

    public function __construct(
        Security $security,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        FieldRepository $fieldRepository,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->security                      = $security;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->fieldRepository               = $fieldRepository;
        $this->propertyAccessor              = $propertyAccessor;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        $object = $this->extractObjectToPopulate($type, $context);

        return !isset($context[self::ALREADY_CALLED]) && $object instanceof ProgramAwareInterface && $object instanceof ProgramChoiceOptionCarrierInterface;
    }

    /**
     * @param mixed $data
     *
     * @throws ORMException
     * @throws LogicException
     * @throws AccessDeniedException
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;
        $object                        = $this->extractObjectToPopulate($type, $context);

        foreach ($data as $propertyName => $description) {
            $fieldAlias = s($propertyName)->snake()->toString();
            if (in_array($fieldAlias, FieldAlias::PROGRAM_CHOICE_OPTION_FIELDS, true)) {
                $programChoiceOption = $this->denormalizeChoiceOption($fieldAlias, $description, $object->getProgram());
                $this->propertyAccessor->setValue($object, $propertyName, $programChoiceOption);
                unset($data[$propertyName]);
            }
        }

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @throws ORMException
     */
    private function denormalizeChoiceOption(string $fieldAlias, string $description, Program $program)
    {
        $field = $this->fieldRepository->findOneBy(['fieldAlias' => $fieldAlias]);

        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $field,
            'description' => $description,
        ]);

        if (false === $programChoiceOption instanceof ProgramChoiceOption) {
            $programChoiceOption = new ProgramChoiceOption($program, $description, $field);

            if (false === $this->security->isGranted(ProgramChoiceOptionVoter::ATTRIBUTE_CREATE, $programChoiceOption)) {
                throw new AccessDeniedException();
            }

            $this->programChoiceOptionRepository->persist($programChoiceOption);
        }

        return $programChoiceOption;
    }
}
