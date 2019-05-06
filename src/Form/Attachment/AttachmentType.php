<?php

declare(strict_types=1);

namespace Unilend\Form\Attachment;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{FileType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\{Attachment, AttachmentType as AttachmentTypeEntity, Interfaces\EntityAttachmentTypeInterface, ProjectAttachmentType};
use Unilend\Repository\AttachmentTypeRepository;
use Unilend\Repository\ProjectAttachmentTypeRepository;

class AttachmentType extends AbstractType
{
    /**
     * @var ProjectAttachmentTypeRepository
     */
    private $projectAttachmentTypeRepository;

    /**
     * @var AttachmentTypeRepository
     */
    private $attachmentTypeRepository;

    /**
     * @param AttachmentTypeRepository        $attachmentTypeRepository
     * @param ProjectAttachmentTypeRepository $projectAttachmentTypeRepository
     */
    public function __construct(AttachmentTypeRepository $attachmentTypeRepository, ProjectAttachmentTypeRepository $projectAttachmentTypeRepository)
    {
        $this->projectAttachmentTypeRepository = $projectAttachmentTypeRepository;
        $this->attachmentTypeRepository        = $attachmentTypeRepository;
    }

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
                'choices'     => $this->getGroupingOptions($options['type_class']),
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
        $resolver->setDefaults([
            'data_class' => Attachment::class,
            'type_class' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'attachment_type';
    }

    /**
     * @param string|null $typeClass
     *
     * @return array
     */
    private function getGroupingOptions(?string $typeClass)
    {
        $repository         = null;
        $translationSection = '';
        switch ($typeClass) {
            case ProjectAttachmentType::class:
                $repository         = $this->projectAttachmentTypeRepository;
                $translationSection = 'project-attachment-type';

                break;
            default:
                break;
        }

        if (null === $repository) {
            return $this->attachmentTypeRepository->findAll();
        }

        /** @var EntityAttachmentTypeInterface[] $entityAttachmentTypes */
        $entityAttachmentTypes = $repository->findAll();

        $options = [];

        foreach ($entityAttachmentTypes as $entityAttachmentType) {
            $category             = sprintf('%s-%s.%s', $translationSection, 'category', $entityAttachmentType->getCategory()->getLabel());
            $options[$category][] = $entityAttachmentType->getAttachmentType();
        }

        return $options;
    }
}
