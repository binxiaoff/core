<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\ProjectAttachment;
use Unilend\Form\Attachment\AttachmentType;

class ProjectAttachmentType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('attachment', AttachmentType::class, [
            'label'      => false,
            'type_class' => \Unilend\Entity\ProjectAttachmentType::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', ProjectAttachment::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'project_attachment_type';
    }
}
