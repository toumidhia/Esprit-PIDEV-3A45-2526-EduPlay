<?php
// src/Form/ResourceType.php

namespace App\Form;

use App\Entity\Resource;
use App\Entity\Library;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;
        
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
            ])
            ->add('author', TextType::class, [
                'label' => 'Auteur',
                'required' => true,
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Résumé',
                'required' => false,
            ])
            ->add('coverImageFile', FileType::class, [
                'label' => 'Image de couverture',
                'required' => !$isEdit, // Requis seulement en création
                'mapped' => false,
                'constraints' => $isEdit ? [
                    // En édition, seulement valider le format si un fichier est uploadé
                    new File([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WebP).',
                    ])
                ] : [
                    // En création, le fichier est requis
                    new NotBlank([
                        'message' => 'L\'image de couverture est requise'
                    ]),
                    new File([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WebP).',
                    ])
                ],
            ])
            ->add('pdfFileFile', FileType::class, [
                'label' => 'Fichier PDF',
                'required' => !$isEdit, // Requis seulement en création
                'mapped' => false,
                'constraints' => $isEdit ? [
                    // En édition, seulement valider le format si un fichier est uploadé
                    new File([
                        'mimeTypes' => [
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide.',
                    ])
                ] : [
                    // En création, le fichier est requis
                    new NotBlank([
                        'message' => 'Le fichier PDF est requis'
                    ]),
                    new File([
                        'mimeTypes' => [
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide.',
                    ])
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'required' => true,
                'choices' => [
                    'Livre' => 'Livre',
                    'Magazine' => 'Magazine',
                    'Journal' => 'Journal',
                    'Manuel' => 'Manuel'
                ],
                'placeholder' => 'Sélectionnez un type'
            ])
            ->add('minAge', IntegerType::class, [
                'label' => 'Âge minimum',
                'required' => true,
            ])
            ->add('maxAge', IntegerType::class, [
                'label' => 'Âge maximum',
                'required' => true,
            ])
            ->add('language', ChoiceType::class, [
                'label' => 'Langue',
                'required' => true,
                'choices' => [
                    'Français' => 'Français',
                    'Anglais' => 'Anglais',
                    'Arabe' => 'Arabe',
                    'Espagnol' => 'Espagnol'
                ],
                'placeholder' => 'Sélectionnez une langue'
            ])
            ->add('libraryId', EntityType::class, [
                'label' => 'Bibliothèque',
                'class' => Library::class,
                'choice_label' => 'name',
                'required' => true,
                'placeholder' => 'Sélectionnez une bibliothèque'
            ])
            ->add('save', SubmitType::class, [
                'label' => $isEdit ? 'Modifier' : 'Enregistrer'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Resource::class,
            'is_edit' => false, // Option par défaut
        ]);
        
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}