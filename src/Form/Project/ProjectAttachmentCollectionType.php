<?php

namespace Unilend\Form\Project;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectAttachmentCollectionType extends AbstractType
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
        return 'project_attachments_widget';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'         => false,
            'entry_type'    => ProjectAttachmentType::class,
            'entry_options' => ['label' => false],
            'allow_add'     => true,
            'allow_delete'  => true,
            'by_reference'  => false,
            'prototype'     => true,
            'attr'          => ['class' => 'attachments'],
        ]);
    }
}
