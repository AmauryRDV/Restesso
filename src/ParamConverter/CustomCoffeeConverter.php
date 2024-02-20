<?php
namespace App\ParamConverter;

use App\Entity\Coffee;
use App\Repository\CoffeeRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\DoctrineParamConverter;

class CustomCoffeeConverter extends DoctrineParamConverter
{
    private ManagerRegistry $registry;

    private CoffeeRepository $coffeeRepository;

    public function __construct(ManagerRegistry $registry, CoffeeRepository $coffeeRepository)
    {
        parent::__construct($registry);
        $this->registry = $registry;
        $this->coffeeRepository = $coffeeRepository;
    }

    public function apply(Request $request, $configuration)
    {
        // Your custom logic to find Coffee entity
        $coffeeId = $request->attributes->get('coffeeId');
        $coffee = $this->coffeeRepository->findActive($coffeeId);

        // Set the Coffee entity to the request attributes
        $request->attributes->set($configuration->getName(), $coffee);

        return true;
    }
}
