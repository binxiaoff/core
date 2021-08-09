<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\CreditGuaranty\DTO\ProgramBorrowerTypeAllocationInput;
use KLS\CreditGuaranty\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation;
use KLS\CreditGuaranty\Entity\ProgramChoiceOption;
use KLS\CreditGuaranty\Repository\FieldRepository;
use KLS\CreditGuaranty\Repository\ProgramChoiceOptionRepository;
use KLS\CreditGuaranty\Security\Voter\ProgramBorrowerTypeAllocationVoter;
use KLS\CreditGuaranty\Security\Voter\ProgramChoiceOptionVoter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ProgramBorrowerTypeAllocationInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface            $validator;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private FieldRepository               $fieldRepository;
    private Security                      $security;

    public function __construct(
        ValidatorInterface $validator,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        FieldRepository $fieldRepository,
        Security $security
    ) {
        $this->validator                     = $validator;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->fieldRepository               = $fieldRepository;
        $this->security                      = $security;
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return (ProgramBorrowerTypeAllocation::class === $to) && (ProgramBorrowerTypeAllocationInput::class === $context['input']['class']);
    }

    /**
     * @param ProgramBorrowerTypeAllocationInput $object
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \RuntimeException
     */
    public function transform($object, string $to, array $context = []): ProgramBorrowerTypeAllocation
    {
        $this->validator->validate($object);

        if (isset($context['collection_operation_name']) && 'post' === $context['collection_operation_name']) {
            $field               = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
            $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
                'program'     => $object->program,
                'field'       => $field,
                'description' => $object->borrowerType,
            ]);
            if (null === $programChoiceOption) {
                $programChoiceOption = new ProgramChoiceOption($object->program, $object->borrowerType, $field);
                if (false === $this->security->isGranted(ProgramChoiceOptionVoter::ATTRIBUTE_CREATE, $programChoiceOption)) {
                    throw new AccessDeniedException();
                }
                $this->programChoiceOptionRepository->save($programChoiceOption);
            }

            $programBorrowerTypeAllocation = new ProgramBorrowerTypeAllocation($object->program, $programChoiceOption, $object->maxAllocationRate);
            if (false === $this->security->isGranted(ProgramBorrowerTypeAllocationVoter::ATTRIBUTE_CREATE, $programBorrowerTypeAllocation)) {
                throw new AccessDeniedException();
            }

            return $programBorrowerTypeAllocation;
        }

        if (isset($context['item_operation_name']) && 'patch' === $context['item_operation_name']) {
            $programBorrowerTypeAllocation = $context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? null;
            if (false === $programBorrowerTypeAllocation instanceof ProgramBorrowerTypeAllocation) {
                throw new \RuntimeException(\sprintf('Can not populate the object of %s from the context.', ProgramBorrowerTypeAllocation::class));
            }

            if (false === $this->security->isGranted(ProgramBorrowerTypeAllocationVoter::ATTRIBUTE_EDIT, $programBorrowerTypeAllocation)) {
                throw new AccessDeniedException();
            }

            foreach ($object as $name => $value) {
                switch ($name) {
                    case 'borrowerType':
                        $programBorrowerTypeAllocation->getProgramChoiceOption()->setDescription($value);

                        break;

                    case 'maxAllocationRate':
                        $programBorrowerTypeAllocation->setMaxAllocationRate($object->maxAllocationRate);

                        break;

                    default:
                        //noting
                        break;
                }
            }

            return $programBorrowerTypeAllocation;
        }

        throw new \RuntimeException(\sprintf('The operation is not supported by %s', __CLASS__));
    }
}
