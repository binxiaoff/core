<?php

declare(strict_types=1);

namespace Unilend\Form\Tranche;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrancheTypeCollectionType extends AbstractType
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
        return 'tranche_type_collection';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'          => false,
            'entry_type'     => TrancheType::class,
            'entry_options'  => ['label' => false],
            'allow_add'      => true,
            'allow_delete'   => true,
            'by_reference'   => false,
            'prototype'      => true,
            'prototype_name' => '__tranche__',
            'attr'           => ['class' => 'tranches'],
        ]);
    }
}
