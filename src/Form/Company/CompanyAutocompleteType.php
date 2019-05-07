<?php

declare(strict_types=1);

namespace Unilend\Form\Company;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Entity\Companies;
use Unilend\Form\AutocompleteType;

class CompanyAutocompleteType extends AbstractType
{
    /** @var RouterInterface */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'url'   => $this->router->generate('company_search_by_siren'),
            'class' => Companies::class,
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return AutocompleteType::class;
    }
}
