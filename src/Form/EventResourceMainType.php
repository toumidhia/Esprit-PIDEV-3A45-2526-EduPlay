<?php
// src/Form/EventResourceMainType.php

namespace App\Form;

use App\Entity\EventResource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * @extends AbstractType<EventResource>
 */
class EventResourceMainType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'PDF' => 'PDF',
                    'Lien (URL)' => 'LINK',
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('context', TextareaType::class, [
                'label' => 'Description / Notes (optionnel)',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('url', TextType::class, [
                'label' => 'URL',
                'required' => false,
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'Fichier PDF',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventResource::class,
        ]);
    }
}