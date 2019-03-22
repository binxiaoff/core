<?php

namespace Unilend\Bundle\FrontBundle\Form\Project;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectPercentFee;
use Unilend\Bundle\FrontBundle\Form\Fee\PercentFeeType;

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
