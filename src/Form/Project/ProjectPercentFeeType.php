<?php

namespace Unilend\Form\Project;

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\ProjectPercentFee;
use Unilend\Form\Fee\PercentFeeType;

class ProjectPercentFeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('percentFee', PercentFeeType::class, ['label' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', ProjectPercentFee::class);
    }

    public function getBlockPrefix()
    {
        return 'project_percent_fee_type';
    }
}
