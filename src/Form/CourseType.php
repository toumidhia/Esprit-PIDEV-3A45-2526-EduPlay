<?php

namespace App\Form;

use App\Validator\Constraints\SimpleFile;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Entity\Course;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du cours',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Introduction à la programmation'
                ],
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Décrivez le contenu du cours...'
                ],
                'required' => false,
            ])
            ->add('durationTraining', IntegerType::class, [
                'label' => 'Durée de formation (en semaines)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 8'
                ],
                'required' => false,
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Sélectionnez un niveau' => '',
                    'Débutant' => 'Beginner',
                    'Intermédiaire' => 'Intermediate',
                    'Avancé' => 'Advanced',
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'Fichier PDF du cours (optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf'
                ],
                'constraints' => [
                    new SimpleFile(),
                ],
            ]);

        // Add teacher field only if NOT a teacher (admin can assign teacher)
        if (!$options['is_teacher']) {
            $builder->add('teacherId', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFirstName() . ' ' . $user->getLastName() . ' (' . $user->getEmail() . ')';
                },
                'query_builder' => function (UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->where('u.type = :type')
                        ->setParameter('type', 'teacher')
                        ->orderBy('u.firstName', 'ASC');
                },
                'label' => 'Professeur',
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => false,
                'placeholder' => 'Sélectionnez un professeur',
            ]);
        }

        // Add status field only for admins
        if ($options['is_admin']) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'pending',
                    'Accepté' => 'accepted',
                    'Rejeté' => 'rejected',
                ],
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
            'is_teacher' => false,  // Add this option
            'is_admin' => false,    // Add this option
        ]);

        $resolver->setAllowedTypes('is_teacher', 'bool');
        $resolver->setAllowedTypes('is_admin', 'bool');
    }
}