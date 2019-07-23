<?php

declare(strict_types=1);

namespace Unilend\Form\Foncaris;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Unilend\Entity\ConstantList\FoncarisFundingType;
use Unilend\Entity\ConstantList\FoncarisSecurity;

class FoncarisTrancheAttributeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('greenId', TextType::class, [
                'label'    => 'tranche-form.credit-agricole-green-id-label',
                'required' => false,
            ])
            ->add('fundingType', EntityType::class, [
                'label'        => 'tranche-form.foncaris-funding-type-label',
                'required'     => true,
                'choice_label' => function (FoncarisFundingType $foncarisFundingType) {
                    return $foncarisFundingType->getDescription();
                },
                'class'    => FoncarisFundingType::class,
                'group_by' => function (FoncarisFundingType $foncarisFundingType) {
                    switch ($foncarisFundingType->getCategory()) {
                        case FoncarisFundingType::CATEGORY_ID_SIGNATURE_COMMITMENTS:
                            return 'Engagement par signature (Caution)';
                        case FoncarisFundingType::CATEGORY_ID_SHORT_TERM:
                            return 'Court Terme';
                        case FoncarisFundingType::CATEGORY_ID_MEDIUM_LONG_TERM:
                            return 'Moyen / Long Terme (>12 mois)';
                        default:
                            return '';
                    }
                },
            ])
            ->add('security', EntityType::class, [
                'label'        => 'tranche-form.foncaris-security-label',
                'required'     => true,
                'attr'         => ['class' => 'select2'],
                'multiple'     => true,
                'choice_label' => function (FoncarisSecurity $foncarisSecurity) {
                    return $foncarisSecurity->getDescription();
                },
                'class'    => FoncarisSecurity::class,
                'group_by' => function (FoncarisSecurity $foncarisSecurity) {
                    switch ($foncarisSecurity->getCategory()) {
                        case FoncarisSecurity::CATEGORY_ID_SIGNATURE_COMMITMENTS:
                            return 'Engagement par signature';
                        case FoncarisSecurity::CATEGORY_ID_ASSIGNMENT_OF_CLAIM:
                            return 'Cession de créances';
                        case FoncarisSecurity::CATEGORY_ID_MORTGAGE:
                            return 'Hypothèque';
                        case FoncarisSecurity::CATEGORY_ID_COLLATERAL_PLEDGE:
                            return 'Nantissement / Gage';
                        default:
                            return '';
                    }
                },
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'foncaris_tranche_attribute_type';
    }
}
