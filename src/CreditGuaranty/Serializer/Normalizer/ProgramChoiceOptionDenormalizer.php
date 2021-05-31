<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Serializer\Normalizer;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\ORM\ORMException;
use LogicException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Unilend\CreditGuaranty\Entity\Interfaces\ProgramAwareInterface;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use Unilend\CreditGuaranty\Security\Voter\ProgramChoiceOptionVoter;

class ProgramChoiceOptionDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use ObjectToPopulateTrait;

    private const ALREADY_CALLED = 'PROGRAM_CHOICE_OPTION_DENORMALIZER_ALREADY_CALLED';

    private Security $security;
    private IriConverterInterface $iriConverter;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private FieldRepository $fieldRepository;

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
        return !isset($context[self::ALREADY_CALLED]) && ProgramChoiceOption::class === $type;
    }

    /**
     * @throws ORMException
     * @throws LogicException
     * @throws AccessDeniedException
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $object = $this->iriConverter->getItemFromIri($context['request_uri'], [AbstractNormalizer::GROUPS => []]);

        if (false === ($object instanceof ProgramAwareInterface)) {
            throw new LogicException(sprintf('The object given of %s does not implement %s.', get_class($object), ProgramAwareInterface::class));
        }

        $program = $object->getProgram();
        $field   = $this->fieldRepository->findOneBy(['fieldAlias' => $data['fieldAlias']]);

        $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
            'program'     => $program,
            'field'       => $field,
            'description' => $data['description'],
        ]);

        if (false === $programChoiceOption instanceof ProgramChoiceOption) {
            $programChoiceOption = new ProgramChoiceOption($program, $data['description'], $field);

            if (false === $this->security->isGranted(ProgramChoiceOptionVoter::ATTRIBUTE_CREATE, $programChoiceOption)) {
                throw new AccessDeniedException();
            }

            $this->programChoiceOptionRepository->persist($programChoiceOption);
        }

        return $programChoiceOption;
    }
}
