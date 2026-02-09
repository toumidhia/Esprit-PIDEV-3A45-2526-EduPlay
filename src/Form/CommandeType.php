<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $u) { return trim($u->getFirstName() . ' ' . $u->getLastName() . ' (' . $u->getEmail() . ')'); },
                'label' => 'Utilisateur',
                'placeholder' => '-- Choisir un utilisateur --',
                'attr' => ['class' => 'form-select rounded-lg border border-gray-300 px-4 py-2 w-full'],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez sélectionner un utilisateur.']),
                ],
            ])
            ->add('idProduct', EntityType::class, [
                'class' => Product::class,
                'choice_label' => fn(Product $p) => $p->getName() . ' - ' . number_format($p->getPrice(), 2) . ' €',
                'label' => 'Produit',
                'placeholder' => '-- Choisir un produit --',
                'attr' => ['class' => 'form-select rounded-lg border border-gray-300 px-4 py-2 w-full'],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez sélectionner un produit.']),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'class' => 'form-input rounded-lg border border-gray-300 px-4 py-2 w-full',
                    'min' => 1,
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'La quantité est obligatoire.']),
                    new Assert\Type(['type' => 'integer', 'message' => 'La quantité doit être un entier.']),
                    new Assert\Positive(['message' => 'La quantité doit être strictement positive.']),
                ],
            ])
            ->add('dateCommande', DateType::class, [
                'label' => 'Date de commande',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-input rounded-lg border border-gray-300 px-4 py-2 w-full'],
                'constraints' => [
                    new Assert\NotNull(['message' => 'La date de commande est obligatoire.']),
                ],
            ])
            ->add('totalAmount', NumberType::class, [
                'label' => 'Montant total (€)',
                'attr' => [
                    'class' => 'form-input rounded-lg border border-gray-300 px-4 py-2 w-full',
                    'min' => 0,
                    'step' => '0.01',
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le montant total est obligatoire.']),
                    new Assert\PositiveOrZero(['message' => 'Le montant total doit être positif ou nul.']),
                    new Assert\Type(['type' => 'numeric', 'message' => 'Le montant doit être un nombre.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
