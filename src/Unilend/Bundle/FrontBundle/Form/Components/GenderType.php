<?php


namespace Unilend\Bundle\FrontBundle\Form\Components;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatorInterface;

class GenderType extends AbstractType
{
    /** @var  TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                $this->translator->trans('common_title-female') => \clients::TITLE_MISS,
                $this->translator->trans('common_title-male')   => \clients::TITLE_MISTER
            ],
            'expanded' => true,
            'multiple' => false
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

}
