<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\CreditGuaranty\DTO\ProgramBorrowerTypeAllocationInput;
use Unilend\CreditGuaranty\Entity\Constant\FieldAlias;
use Unilend\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;
use Unilend\CreditGuaranty\Repository\FieldRepository;
use Unilend\CreditGuaranty\Repository\ProgramChoiceOptionRepository;

class ProgramBorrowerTypeAllocationInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;
    private ProgramChoiceOptionRepository $programChoiceOptionRepository;
    private FieldRepository               $fieldRepository;

    /**
     * @param ValidatorInterface            $validator
     * @param ProgramChoiceOptionRepository $programChoiceOptionRepository
     * @param FieldRepository               $fieldRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        ProgramChoiceOptionRepository $programChoiceOptionRepository,
        FieldRepository $fieldRepository
    ) {
        $this->validator                     = $validator;
        $this->programChoiceOptionRepository = $programChoiceOptionRepository;
        $this->fieldRepository               = $fieldRepository;
    }

    /**
     * {@inheritDoc}
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
     * @throws \RuntimeException
     *
     * @return ProgramBorrowerTypeAllocation
     */
    public function transform($object, string $to, array $context = []): ProgramBorrowerTypeAllocation
    {
        $this->validator->validate($object);

        if (isset($context['item_operation_name']) && 'post' === $context['item_operation_name']) {
            $field               = $this->fieldRepository->findOneBy(['fieldAlias' => FieldAlias::BORROWER_TYPE]);
            $programChoiceOption = $this->programChoiceOptionRepository->findOneBy([
                'program'     => $object->program,
                'field'       => $field,
                'description' => $object->borrowerType,
            ]);
            if (null === $programChoiceOption) {
                $programChoiceOption = new ProgramChoiceOption($object->program, $object->borrowerType, $field);
                $this->programChoiceOptionRepository->save($programChoiceOption);
            }

            return new ProgramBorrowerTypeAllocation($object->program, $programChoiceOption, $object->maxAllocationRate);
        }

        if (isset($context['item_operation_name']) && 'patch' === $context['item_operation_name']) {
            $programBorrowerTypeAllocation = $context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? null;
            if (false === $programBorrowerTypeAllocation instanceof ProgramBorrowerTypeAllocation) {
                throw new \RuntimeException(sprintf('Can not populate the object of %s from the context.', ProgramBorrowerTypeAllocation::class));
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

        throw new \RuntimeException(sprintf('The operation is not supported by %s', __CLASS__));
    }
}
