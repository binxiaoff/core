<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Listener\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\ORMException;
use Exception;
use KLS\CreditGuaranty\FEI\Entity\Constant\FieldAlias;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibility;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProgramEligibilityDeleteListener
{
    /**
     * @throws ORMException
     * @throws Exception
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (false === $entity instanceof ProgramEligibility) {
                continue;
            }

            // we need to check if the field of the programEligibility is related to ESB calculation activated
            // before deleting it
            $fieldAlias = $entity->getField()->getFieldAlias();

            if (
                $entity->getProgram()->isEsbCalculationActivated()
                && \in_array($fieldAlias, FieldAlias::ESB_RELATED_FIELDS, true)
            ) {
                $message = 'Impossible to delete this ProgramEligibility, ' .
                    'the related Field (%s) is needed to the gross subsidy equivalent calculation which is activated.';

                throw new BadRequestHttpException(\sprintf($message, $fieldAlias));
            }

            $em->remove($entity);
        }
    }
}
