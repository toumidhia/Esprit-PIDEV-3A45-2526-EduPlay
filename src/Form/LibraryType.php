<?php
// src/Form/LibraryType.php

namespace App\Form;

use App\Entity\Library;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;

class LibraryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la bibliothèque',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Entrez le nom de la bibliothèque'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Description optionnelle (max 100 caractères)'
                ]
            ])
            ->add('minAge', IntegerType::class, [
                'label' => 'Âge minimum',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: 3'
                ]
            ])
            ->add('maxAge', IntegerType::class, [
                'label' => 'Âge maximum',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: 12'
                ]
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Niveau de difficulté',
                'required' => true,
                'choices' => [
                    'Débutant' => 'Débutant',
                    'Intermédiaire' => 'Intermédiaire',
                    'Avancé' => 'Avancé',
                    'Expert' => 'Expert'
                ],
                'placeholder' => 'Sélectionnez un niveau'
            ])
            ->add('theme', TextType::class, [
                'label' => 'Thème/Catégorie',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Aventure, Science, Histoire...'
                ]
            ])
            ->add('coverImageFile', FileType::class, [
                'label' => 'Image de couverture',
                'required' => false,
                'mapped' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG ou WebP)',
                    ])
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Library::class,
            'attr' => [
                'novalidate' => 'novalidate', // Désactive la validation HTML5
            ]
        ]);
    }
}