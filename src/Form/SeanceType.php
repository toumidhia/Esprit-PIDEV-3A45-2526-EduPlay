<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Seance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startTime', DateTimeType::class, [
                'label' => 'Session Start Time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Select the date and time when the session starts',
            ])
            ->add('endTime', DateTimeType::class, [
                'label' => 'Session End Time',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Select the date and time when the session ends (must be after start time)',
            ])
            ->add('courseId', EntityType::class, [
                'class' => Course::class,
                'choice_label' => function(Course $course) {
                    return $course->getTitle() . ' - ' . $course->getLevel() . ' (' . $course->getStatus() . ')';
                },
                'label' => 'Assign to Course',
                'attr' => [
                    'class' => 'form-control'
                ],
                'placeholder' => 'Select a course',
                'help' => 'Only accepted courses can have sessions assigned',
                'query_builder' => function($repository) {
                    return $repository->createQueryBuilder('c')
                        ->where('c.status = :status')
                        ->setParameter('status', 'accepted')
                        ->orderBy('c.title', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seance::class,
        ]);
    }
}
