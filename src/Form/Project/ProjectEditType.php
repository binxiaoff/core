<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\{Companies, MarketSegment, Project};
use Unilend\Form\CollectionWidgetType;
use Unilend\Form\Company\CompanyAutocompleteType;
use Unilend\Form\DataTransformer\IdentityTransformer;
use Unilend\Form\Traits\ConstantsToChoicesTrait;
use Unilend\Form\Tranche\TrancheEditType;

class ProjectEditType extends AbstractType
{
    use ConstantsToChoicesTrait;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * AutocompleteType constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

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
            ->add('tranches', CollectionWidgetType::class, [
                'label'         => false,
                'entry_type'    => TrancheEditType::class,
                'entry_options' => ['label' => false],
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
                'block_prefix'  => 'project_tranche_collection',
                'prototype'     => true,
            ])
            ->add('foncarisGuarantee', ChoiceType::class, [
                'label'   => 'project-form.foncaris-guarantee',
                'choices' => $this->getChoicesFromConstants(Project::getFoncarisGuaranteeOptions(), 'foncaris-guarantee'),
            ])
            ->add('arranger', ChoiceType::class, [
                'label'    => 'project-form.arranger',
                'required' => false,
                'choices'  => [
                    'CA Lending Services' => 1,
                    'CA-CIB'              => 3,
                    'Unifergie'           => 4,
                ],
            ])
            ->add('run', ChoiceType::class, [
                'label'    => 'project-form.run',
                'required' => false,
                'choices'  => [
                    'CA Lending Services' => 1,
                    'CA-CIB'              => 3,
                    'Unifergie'           => 4,
                ],
            ])
        ;

        $builder->get('arranger')->addModelTransformer(new IdentityTransformer($this->managerRegistry, Companies::class));
        $builder->get('run')->addModelTransformer(new IdentityTransformer($this->managerRegistry, Companies::class));
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
