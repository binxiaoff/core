<?php

declare(strict_types=1);

namespace Unilend\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * It can be deleted once we are upgraded to Symfony 4.3: https://symfony.com/blog/new-in-symfony-4-3-simpler-form-theming .
 */
class CollectionWidgetType extends AbstractType
{
    /**
     * @var null
     */
    private $prefix;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->prefix = $options['block_prefix'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['block_prefix']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return $this->prefix;
    }
}
