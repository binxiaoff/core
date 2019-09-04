<?php

declare(strict_types=1);

namespace Unilend\Form\Foncaris;

use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, TextareaType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface, FormError, FormEvent, FormEvents};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\FoncarisRequest;

class FoncarisRequestType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('choice', ChoiceType::class, [
                'label'        => 'project-form.foncaris-guarantee-label',
                'required'     => true,
                'placeholder'  => '',
                'choices'      => FoncarisRequest::getFoncarisGuaranteeOptions(),
                'choice_label' => function ($option, string $key, string $value) {
                    return 'foncaris-guarantee.' . mb_strtolower($key);
                },
            ])
            ->add('comment', TextareaType::class, [
                'label'    => 'project-form.foncaris-guarantee-comment-label',
                'required' => false,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'checkFoncarisAttributes'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function checkFoncarisAttributes(FormEvent $formEvent): void
    {
        $form = $formEvent->getForm();
        if (FoncarisRequest::FONCARIS_GUARANTEE_NEED === $form->get('choice')->getData()) {
            $comment = $form->get('comment');
            if (null === $comment->getData()) {
                $comment->addError(new FormError($this->translator->trans('project-form.foncaris-guarantee-comment-required')));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', FoncarisRequest::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'foncaris_request_type';
    }
}
