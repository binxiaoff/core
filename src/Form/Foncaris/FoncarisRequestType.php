<?php

declare(strict_types=1);

namespace Unilend\Form\Foncaris;

use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, TextareaType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\FoncarisRequest;

class FoncarisRequestType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('choice', ChoiceType::class, [
                'label'        => 'project-form.foncaris-guarantee-label',
                'required'     => true,
                'placeholder'  => '',
                'choices'      => FoncarisRequest::getFoncarisGuaranteeOptions(),
                'choice_label' => function ($option, string $key, string $value) {
                    return 'foncaris-guarantee.' . mb_strtolower($key);
                },
            ])
            ->add('comment', TextareaType::class, [
                'label'    => 'project-form.foncaris-guarantee-comment-label',
                'required' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', FoncarisRequest::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'foncaris_request_type';
    }
}
