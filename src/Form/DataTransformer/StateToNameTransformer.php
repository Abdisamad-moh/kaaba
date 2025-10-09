<?php 

namespace App\Form\DataTransformer;

use App\Entity\MetierCity;
use App\Entity\MetierState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class StateToNameTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function transform($state): ?string
    {
        // Transform the entity to a string (name)
        if (null === $state) {
            return '';
        }
        
        return $state;
    }

    public function reverseTransform($stateName): ?MetierState
    {
        // Transform the string (name) back to an entity
        if (!$stateName) {
            return null;
        }

        $state = $this->entityManager
            ->getRepository(MetierState::class)
            ->findOneBy(['name' => $stateName]);

        if (null === $state) {
            throw new TransformationFailedException(sprintf(
                'A state with name "%s" does not exist!',
                $stateName
            ));
        }
        
        return $state;
    }
}
