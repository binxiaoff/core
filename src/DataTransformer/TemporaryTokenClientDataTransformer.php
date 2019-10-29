<?php

declare(strict_types=1);

namespace Unilend\DataTransformer;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use libphonenumber\{NumberParseException, PhoneNumberUtil};
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Unilend\Dto\TemporaryTokenClient;
use Unilend\Entity\{Clients, TemporaryToken};

class TemporaryTokenClientDataTransformer implements DataTransformerInterface
{
    /** @var PhoneNumberUtil */
    private $phoneNumberUtil;
    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param PhoneNumberUtil    $phoneNumberUtil
     * @param ValidatorInterface $validator
     */
    public function __construct(PhoneNumberUtil $phoneNumberUtil, ValidatorInterface $validator)
    {
        $this->phoneNumberUtil = $phoneNumberUtil;
        $this->validator       = $validator;
    }

    /**
     * {@inheritdoc}
     *
     * @param TemporaryTokenClient $object
     *
     * @throws NumberParseException
     */
    public function transform($object, string $to, array $context = [])
    {
        $temporaryToken = $context[AbstractNormalizer::OBJECT_TO_POPULATE];
        if ($temporaryToken instanceof TemporaryToken) {
            $client = $temporaryToken->getClient();
            $client->setFirstName($object->getFirstName())
                ->setLastName($object->getLastName())
                ->setMobile($this->phoneNumberUtil->parse($object->getMobile(), Clients::PHONE_NUMBER_DEFAULT_REGION))
                ->setJobFunction($object->getJobFunction())
                ->setPlainPassword($object->getPassword())
            ;

            $violations = $this->validator->validate($client);

            if (0 !== count($violations)) {
                throw new ValidationException($violations);
            }
        }

        return $temporaryToken;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return TemporaryToken::class === $to && TemporaryTokenClient::class === ($context['input']['class'] ?? null);
    }
}
