<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Serializer\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\ORMException;
use JsonException;
use KLS\CreditGuaranty\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\Entity\Field;
use KLS\CreditGuaranty\Entity\Interfaces\ProgramAwareInterface;
use KLS\CreditGuaranty\Entity\Interfaces\ProgramChoiceOptionCarrierInterface;
use KLS\CreditGuaranty\Entity\Program;
use KLS\CreditGuaranty\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\Entity\Reservation;
use KLS\CreditGuaranty\Repository\FieldRepository;
use KLS\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\Security\Voter\ProgramChoiceOptionVoter;
use LogicException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;

class ProgramChoiceOptionCarrierDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROGRAM_CHOICE_OPTION_DENORMALIZER_ALREADY_CALLED';

    private Security                      $security;
    private IriConverterInterface         $iriConverter;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private FieldRepository               $fieldRepository;

    public function __construct(
        Security $security,
        IriConverterInterface $iriConverter,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        FieldRepository $fieldRepository
    ) {
        $this->security                      = $security;
        $this->iriConverter                  = $iriConverter;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->fieldRepository               = $fieldRepository;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED])
            && \is_a($type, ProgramAwareInterface::class, true)
            && \is_a($type, ProgramChoiceOptionCarrierInterface::class, true)
        ;
    }

    /**
     * @param mixed $data
     *
     * @throws JsonException
     * @throws ORMException
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;
        $object                        = $this->extractObjectToPopulate($type, $context);

        foreach ($data as $propertyName => $propertyValue) {
            $field = $this->fieldRepository->findOneBy([
                'propertyPath' => $propertyName,
                'objectClass'  => $type,
            ]);

            if (
                $field instanceof Field
                && \in_array($field->getFieldAlias(), FieldAlias::PROGRAM_CHOICE_OPTION_FIELDS, true)
            ) {
                if ($object) {
                    $program = $object->getProgram();
                } elseif (false === empty($data['reservation'])) {
                    $reservation = $this->iriConverter->getItemFromIri($data['reservation'], [AbstractNormalizer::GROUPS => []]);
                    if (false === $reservation instanceof Reservation) {
                        throw new RuntimeException(\sprintf('Cannot detect the reservation from data %s', \json_encode($data, JSON_THROW_ON_ERROR)));
                    }
                    $program = $reservation->getProgram();
                }

                if (false === isset($program) || false === $program instanceof Program) {
                    throw new RuntimeException(\sprintf('Cannot detect the program from data %s', \json_encode($data, JSON_THROW_ON_ERROR)));
                }

                $programChoiceOption = $this->denormalizeChoiceOption($field, $propertyValue, $program);
                $data[$propertyName] = $this->iriConverter->getIriFromItem($programChoiceOption);
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
            if (\is_array($field->getPredefinedItems()) && false === \in_array($description, $field->getPredefinedItems(), true)) {
                throw new LogicException(\sprintf(
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
