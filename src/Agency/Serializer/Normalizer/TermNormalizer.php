<?php

declare(strict_types=1);

namespace KLS\Agency\Serializer\Normalizer;

use Exception;
use KLS\Agency\Entity\Term;
use KLS\Agency\Security\Voter\TermVoter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;

class TermNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface
{
    use ObjectToPopulateTrait;
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = __CLASS__ . '_ALREADY_CALLED';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return !isset($context[static::ALREADY_CALLED]) && Term::class === $type;
    }

    /**
     * @param mixed $data
     *
     * @throws Exception
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $context[static::ALREADY_CALLED] = true;

        $sharing = $data['shared'] ?? false;
        unset($data['shared']);

        $archiving = $data['archived'] ?? false;
        unset($data['archived']);

        $objectToPopulate = $this->extractObjectToPopulate(Term::class, $context);

        $isAgent = $this->security->isGranted(TermVoter::ATTRIBUTE_AGENT, $objectToPopulate);

        if ($isAgent) {
            $context['groups'] = \array_merge($context['groups'] ?? [], ['agency:term:update:agent']);
        }

        /** @var Term $term */
        $term = $this->denormalizer->denormalize($data, $type, $format, $context);

        if (false === $term instanceof Term) {
            return $term;
        }

        if (
            $sharing
            && $isAgent
            && (false === $term->isShared())
            && ($term->hasBreach() || $term->getWaiver() || $term->isValid())
        ) {
            $term->share();
        }

        if (
            $archiving
            && $isAgent
            && $term->isShared()
            && false === $term->isArchived()
            && (false === $term->hasBreach() || null !== $term->getWaiver())
        ) {
            $term->archive();
        }

        return $term;
    }
}
