<?php

namespace Unilend\Form\Unilend\Components;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Unilend\Service\LocationManager;

class NationalitiesType extends AbstractType
{
    /** @var  LocationManager */
    private $locationManager;

    /**
     * CountriesType constructor.
     * @param LocationManager $locationManager
     */
    public function __construct(LocationManager $locationManager)
    {
        $this->locationManager = $locationManager;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $nationalityList = $this->locationManager->getNationalities();

        $resolver->setDefaults([
            'choices' => array_flip($nationalityList),
            'expanded' => false,
            'multiple' => false
        ]);
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

}
