<?php


namespace Unilend\Bundle\FrontBundle\Form\Components;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

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
                $this->translator->trans('common_title-female') => Clients::TITLE_MISS,
                $this->translator->trans('common_title-male')   => Clients::TITLE_MISTER
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
