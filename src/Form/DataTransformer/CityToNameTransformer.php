<?php 

namespace App\Form\DataTransformer;

use App\Entity\MetierCity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CityToNameTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function transform($city): ?string
    {
        // Transform the entity to a string (name)
        if (null === $city) {
            return '';
        }
        
        return $city;
    }

    public function reverseTransform($cityName): ?MetierCity
    {
        // Transform the string (name) back to an entity
        if (!$cityName) {
            return null;
        }

        $city = $this->entityManager
            ->getRepository(MetierCity::class)
            ->findOneBy(['name' => $cityName]);

        if (null === $city) {
            throw new TransformationFailedException(sprintf(
                'A city with name "%s" does not exist!',
                $cityName
            ));
        }
        
        return $city;
    }
}
