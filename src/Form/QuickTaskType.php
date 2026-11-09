<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuickTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Tytuł zadania',
                'attr' => ['placeholder' => 'Krótki tytuł zadania…', 'class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Opis',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Szczegóły (opcjonalnie)…', 'class' => 'form-control'],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorytet',
                'choices' => Task::PRIORITIES,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => Task::STATUSES,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('assignedTo', EntityType::class, [
                'label' => 'Przypisz do',
                'class' => User::class,
                'choice_label' => fn(User $u) => $u->getFirstName() . ' ' . $u->getLastName(),
                'required' => false,
                'placeholder' => '— nieprzypisane —',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Termin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}

