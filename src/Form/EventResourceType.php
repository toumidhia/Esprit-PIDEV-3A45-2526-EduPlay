<?php
// src/Form/EventResourceType.php

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
class EventResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ✅ Ressource principale
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'PDF' => 'PDF',
                    'Lien (URL)' => 'LINK',
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'placeholder' => 'Ex: Autorisation parentale / Programme / Lien Drive ...',
                ],
            ])
            ->add('context', TextareaType::class, [
                'label' => 'Description / Notes',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => "Ajoute une description ou des notes.",
                ],
            ])
            ->add('url', TextType::class, [
                'label' => 'URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://...',
                    'inputmode' => 'url',
                    'autocomplete' => 'off',
                ],
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
            ])

            // ✅ Checklist & Planning (unmapped) + pré-remplissage via options
            ->add('checklistText', TextareaType::class, [
                'label' => 'Checklist',
                'mapped' => false,
                'required' => false,
                'data' => $options['checklist_data'],
                'attr' => [
                    'rows' => 6,
                    'placeholder' => "- Autorisation signée\n- Tenue de sport\n- Gourde\n- Casquette",
                ],
            ])
            ->add('planningText', TextareaType::class, [
                'label' => 'Planning',
                'mapped' => false,
                'required' => false,
                'data' => $options['planning_data'],
                'attr' => [
                    'rows' => 6,
                    'placeholder' => "08:30 - Accueil\n09:00 - Atelier 1\n10:30 - Pause\n11:00 - Atelier 2\n12:30 - Fin",
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventResource::class,
            'checklist_data' => '',
            'planning_data' => '',
        ]);

        $resolver->setAllowedTypes('checklist_data', 'string');
        $resolver->setAllowedTypes('planning_data', 'string');
    }
}