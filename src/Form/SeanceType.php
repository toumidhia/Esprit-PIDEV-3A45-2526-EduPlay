<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Seance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la séance',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Introduction aux variables'
                ],
                'required' => false,
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Salle A101'
                ],
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Sélectionnez un statut' => '',
                    'Planifiée' => 'scheduled',
                    'En cours' => 'ongoing',
                    'Terminée' => 'completed',
                    'Annulée' => 'cancelled',
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnel)',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Détails de la séance...'
                ],
                'required' => false,
            ])
            ->add('course', EntityType::class, [
                'label' => 'Cours',
                'class' => Course::class,
                'choice_label' => 'title',
                'attr' => [
                    'class' => 'form-control'
                ],
                'query_builder' => function ($repository) {
                    return $repository->createQueryBuilder('c')
                        ->where('c.status = :status')
                        ->setParameter('status', 'accepted')
                        ->orderBy('c.title', 'ASC');
                },
                'placeholder' => 'Sélectionnez un cours',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seance::class,
        ]);
    }
}
