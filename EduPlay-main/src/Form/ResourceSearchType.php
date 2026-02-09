<?php
// src/Form/ResourceSearchType.php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ResourceSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Recherche globale
            ->add('keyword', TextType::class, [
                'label' => 'Recherche',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Titre, auteur, résumé...'
                ]
            ])
            
            // Recherche spécifique par titre
            ->add('title', TextType::class, [
                'label' => 'Titre uniquement',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher par titre...'
                ]
            ])
            
            // Recherche spécifique par auteur
            ->add('author', TextType::class, [
                'label' => 'Auteur uniquement',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rechercher par auteur...'
                ]
            ])
            
            // Recherche par mot-clé dans le résumé
            ->add('summaryKeyword', TextType::class, [
                'label' => 'Mot-clé dans résumé',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Mot-clé dans le résumé...'
                ]
            ])
            
            // Options de tri
            ->add('sortBy', ChoiceType::class, [
                'label' => 'Trier par',
                'required' => false,
                'choices' => [
                    'Titre (A → Z)' => 'title_asc',
                    'Titre (Z → A)' => 'title_desc',
                    'Auteur (A → Z)' => 'author_asc',
                    'Auteur (Z → A)' => 'author_desc',
                    'Âge min (croissant)' => 'minAge_asc',
                    'Âge min (décroissant)' => 'minAge_desc',
                    'Âge max (croissant)' => 'maxAge_asc',
                    'Âge max (décroissant)' => 'maxAge_desc',
                ],
                'placeholder' => 'Sélectionnez un tri',
                'attr' => [
                    'class' => 'select-sort'
                ]
            ])
            
            // Boutons
            ->add('search', SubmitType::class, [
                'label' => '🔍 Rechercher',
                'attr' => [
                    'class' => 'btn-search'
                ]
            ])
            
            ->add('reset', SubmitType::class, [
                'label' => '🔄 Réinitialiser',
                'attr' => [
                    'class' => 'btn-reset'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Pas de classe d'entité liée
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}