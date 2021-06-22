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
use Unilend\CreditGuaranty\Entity\Field;
use Unilend\CreditGuaranty\Entity\Interfaces\ProgramAwareInterface;
use Unilend\CreditGuaranty\Entity\Interfaces\ProgramChoiceOptionCarrierInterface;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Security\Voter\ProgramChoiceOptionVoter;

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

        foreach ($data as $propertyName => $propertyValue) {
            $field = null;
            if ($object) {
                $field = $this->fieldRepository->findOneBy([
                    'propertyPath' => $propertyName,
                    'objectClass'  => get_class($object),
                ]);
            }

            if (
                $field instanceof Field
                && in_array($field->getFieldAlias(), FieldAlias::PROGRAM_CHOICE_OPTION_FIELDS, true)
            ) {
                $programChoiceOption = $this->denormalizeChoiceOption($field, $propertyValue, $object->getProgram());
                $this->propertyAccessor->setValue($object, $propertyName, $programChoiceOption);
                unset($data[$propertyName]);
            }
        }

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @throws ORMException
     */
    private function denormalizeChoiceOption(Field $field, string $description, Program $program): ProgramChoiceOption
    {
        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $field,
            'description' => $description,
        ]);

        if (false === $programChoiceOption instanceof ProgramChoiceOption) {
            if (is_array($field->getPredefinedItems()) && false === in_array($description, $field->getPredefinedItems(), true)) {
                throw new LogicException(sprintf(
                    'You cannot create a ProgramChoiceOption for %s field alias because the description (%s) is not in the pre-defined list.',
                    $field->getFieldAlias(),
                    $description
                ));
            }

            $programChoiceOption = new ProgramChoiceOption($program, $description, $field);

            if (false === $this->security->isGranted(ProgramChoiceOptionVoter::ATTRIBUTE_CREATE, $programChoiceOption)) {
                throw new AccessDeniedException();
            }

            $this->programChoiceOptionRepository->persist($programChoiceOption);
        }

        return $programChoiceOption;
    }
}
