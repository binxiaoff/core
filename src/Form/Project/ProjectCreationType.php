<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\MarketSegment;
use Unilend\Entity\Project;
use Unilend\Form\Company\CompanyAutocompleteType;

class ProjectCreationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, ['label' => 'project-form.title'])
            ->add('borrowerCompany', CompanyAutocompleteType::class, ['label' => 'project-form.borrower-company'])
            ->add('marketSegment', EntityType::class, [
                'label' => 'project-form.market-segment',
                'class' => MarketSegment::class,
            ])
            ->add('replyDeadline', null, [
                'label'  => 'project-form.replay-deadline',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
            ])
            ->add('expectedClosingDate', null, [
                'label'  => 'project-form.expected-closing-date',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
            ])
            ->add('description', null, [
                'label' => 'project-form.description',
                'attr'  => ['row' => 10],
            ])
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
        return 'project_creation_type';
    }
}
