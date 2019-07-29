<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, SubmitType, TextareaType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Project;

class ConfidentialityEditionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('confidential', CheckboxType::class, [
                'value'    => true,
                'label'    => false,
                'required' => false,
            ])
            ->add('confidentialityDisclaimer', TextareaType::class, [
                'label'    => false,
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'confidentiality-form.submit-button-label',
            ])
            ->getForm()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Project::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'confidentiality';
    }
}
