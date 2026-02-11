<?php
// src/Form/LibrarySearchType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LibrarySearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // RECHERCHE
            ->add('keyword', TextType::class, [
                'label' => 'Recherche',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom, thème, description...',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                ]
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Niveau',
                'required' => false,
                'choices' => [
                    'Tous les niveaux' => '',
                    'Débutant' => 'Débutant',
                    'Intermédiaire' => 'Intermédiaire',
                    'Avancé' => 'Avancé',
                    'Expert' => 'Expert'
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                ]
            ])
            ->add('theme', TextType::class, [
                'label' => 'Thème',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Filtrer par thème...',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                ]
            ])
            
            // TRI
            ->add('sortBy', ChoiceType::class, [
                'label' => 'Trier par',
                'required' => false,
                'choices' => [
                    'Nom (A → Z)' => 'name_asc',
                    'Nom (Z → A)' => 'name_desc',
                    'Thème (A → Z)' => 'theme_asc',
                    'Thème (Z → A)' => 'theme_desc',
                    'Niveau (Facile → Difficile)' => 'level_asc',
                    'Niveau (Difficile → Facile)' => 'level_desc',
                    'Âge min (Croissant)' => 'minAge_asc',
                    'Âge min (Décroissant)' => 'minAge_desc',
                    'Âge max (Croissant)' => 'maxAge_asc',
                    'Âge max (Décroissant)' => 'maxAge_desc'
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                ]
            ])
            
            ->add('search', SubmitType::class, [
                'label' => 'Filtrer',
                'attr' => [
                    'class' => 'px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors'
                ]
            ])
            ->add('reset', SubmitType::class, [
                'label' => 'Réinitialiser',
                'attr' => [
                    'class' => 'px-6 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'attr' => ['class' => 'space-y-4']
        ]);
    }
}