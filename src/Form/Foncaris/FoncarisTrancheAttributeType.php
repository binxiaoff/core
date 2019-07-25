<?php

declare(strict_types=1);

namespace Unilend\Form\Foncaris;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface, FormError, FormEvent, FormEvents};
use Unilend\Entity\{ConstantList\FoncarisFundingType, ConstantList\FoncarisSecurity, FoncarisRequest};

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
                'required'     => false,
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
                'required'     => false,
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
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'checkFoncarisAttributes'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function checkFoncarisAttributes(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();
        if (FoncarisRequest::FONCARIS_GUARANTEE_NEED === $form->getParent()->get('choice')->getData()) {
            $fundingType = $form->get('fundingType');
            if (null === $fundingType->getData()) {
                $fundingType->addError(new FormError('tranche-form.foncaris-funding-type-required'));
            }

            $security = $form->get('security');
            if (0 === count($security->getData())) {
                $security->addError(new FormError('tranche-form.foncaris-security-required'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'foncaris_tranche_attribute_type';
    }
}
