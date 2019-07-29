<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Validator\Constraints\NotBlank;

class ConfidentialityAcceptanceType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('accept', CheckboxType::class, [
                'value'       => true,
                'label'       => false,
                'required'    => true,
                'constraints' => new NotBlank(),
            ])
            ->getForm()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'confidentiality_acceptance';
    }
}
