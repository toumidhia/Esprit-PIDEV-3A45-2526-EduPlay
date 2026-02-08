<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isTeacher = $options['is_teacher'] ?? false;
        $isAdmin = $options['is_admin'] ?? false;
        
        $builder
            ->add('title', TextType::class, [
                'label' => 'Course Title',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter course title (e.g., "Introduction to Programming")',
                    'class' => 'form-control'
                ],
            ])
            ->add('durationTraining', TextType::class, [
                'label' => 'Training Duration',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g., "5 hours", "2 weeks", "1 month"',
                    'class' => 'form-control'
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Course Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Describe what students will learn in this course...',
                    'class' => 'form-control',
                    'rows' => 4
                ],
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Course Level',
                'required' => false,
                'choices' => [
                    'Beginner' => 'Beginner',
                    'Intermediate' => 'Intermediate',
                    'Advanced' => 'Advanced',
                ],
                'placeholder' => 'Select a level',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'Course Material (PDF)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                    ])
                ],
            ]);
        
        // Only admin can change status
        if ($isAdmin) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Course Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Accepted' => 'accepted',
                    'Rejected' => 'rejected',
                ],
                'attr' => ['class' => 'form-control'],
            ]);
        }
        
        // Only show teacher selection for admin
        if ($isAdmin) {
            $builder->add('teacherId', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFirstName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'Assign Teacher',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Select a teacher',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
            'is_teacher' => false,
            'is_admin' => false,
        ]);
    }
}
