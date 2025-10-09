<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\MetierOrder;
use App\Entity\MetierPackages;
use App\Repository\UserRepository;
use App\Form\CustomerAutoCompleteField;
use Symfony\Component\Form\AbstractType;
use App\Repository\MetierPackagesRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class OrderFormType extends AbstractType
{

    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }


private function getCustomerChoices($order, string $type): array
{
    // If editing an existing order with a customer, return ONLY that customer
    if ($order && $order->getId() && $order->getCustomer()) {
        return [$order->getCustomer()];
    }
    
    // For new orders or orders without a customer, return all users of the type
    return $this->userRepository->findBy([
        'type' => $type
    ]);
}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $type = $options['type']; // passed from controller

        $builder
            ->add('amount')
            ->add('payment_status', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Paid' => 'paid',
                    'Pending' => 'pending',
                    'Canceled' => 'canceled',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',
                ],
                'placeholder' => 'Payment Status',
            ])
            ->add('valid_from', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('valid_to', DateType::class, [
                'widget' => 'single_text',
            ])
        ->add('plan', EntityType::class, [
    'class' => MetierPackages::class,
    'choice_label' => 'name',
    'query_builder' => function (MetierPackagesRepository $er) use ($type, $builder) {
        $order = $builder->getData();
        
        // Determine the type - use passed type for new orders, or order's category for existing ones
        $filterType = $type ?? $order->getCategory();
        
        $qb = $er->createQueryBuilder('p')
            ->where('p.type = :type')
            ->andWhere('p.category = :category')
            ->setParameter('type', $filterType)
            ->setParameter('category', 'subscription');
        
        // Include current plan if editing
        if ($order && $order->getId() && $order->getPlan()) {
            $qb->orWhere('p.id = :currentPlanId')
               ->setParameter('currentPlanId', $order->getPlan()->getId());
        }
        
        return $qb;
    },
])

          ->add('customer', EntityType::class, [
    'class' => User::class,
    'choice_label' => 'name',
    'choices' => $this->getCustomerChoices($builder->getData(), $type),
])

            // ->add('customer', CustomerAutoCompleteField::class, [
            //     'type' => $type,
            // ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MetierOrder::class,
            'type' => null, // default value in case not provided
        ]);
    }
}