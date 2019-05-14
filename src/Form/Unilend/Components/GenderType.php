<?php


namespace Unilend\Form\Unilend\Components;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\Clients;

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
                'common_title-female' => Clients::TITLE_MISS,
                'common_title-male'   => Clients::TITLE_MISTER
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
