<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class ProjectFeeTypeCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'project_fee_type_collection';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'          => false,
            'constraints'    => [new Valid()],
            'entry_type'     => ProjectFeeType::class,
            'entry_options'  => ['label' => false],
            'allow_add'      => true,
            'allow_delete'   => true,
            'by_reference'   => false,
            'prototype'      => true,
            'prototype_name' => '__project_fees__',
            'attr'           => ['class' => 'project-fees'],
        ]);
    }
}
