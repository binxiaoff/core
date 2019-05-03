<?php

declare(strict_types=1);

namespace Unilend\Form\Attachment;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{FileType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\{Attachment, AttachmentType as AttachmentTypeEntity};

class AttachmentType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', EntityType::class, [
                'label'       => false,
                'class'       => AttachmentTypeEntity::class,
                'placeholder' => 'attachment-form.type-placeholder',
            ])
            ->add('description', TextType::class, [
                'label'    => false,
                'required' => false,
                'attr'     => ['placeholder' => 'attachment-form.description-placeholder'],
            ])
            ->add('file', FileType::class, [
                'label'  => false,
                'mapped' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Attachment::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'attachment_type';
    }
}
