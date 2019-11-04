<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\ProjectParticipationFee;
use Unilend\Form\Fee\FeeType;

class ProjectFeeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('fee', FeeType::class, [
            'label'    => false,
            'fee_type' => ProjectParticipationFee::getFeeTypes(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', ProjectParticipationFee::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'project_fee_type';
    }
}
