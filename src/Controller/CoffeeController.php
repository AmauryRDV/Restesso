<?php

namespace App\Controller;

use App\Entity\Coffee;
use App\Repository\CoffeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CoffeeController extends AbstractController
{

    private const COFFEE_COFFEEID_URL = '/api/v1/coffee/{coffeeId}';

    #[Route('/coffee', name: 'app_coffee')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CoffeeController.php',
        ]);
    }

    #[Route('/api/v1/coffees', name:'coffee.getAll', methods: ['GET'])]
    public function getAllCoffees(CoffeeRepository $coffeeRepository,
    SerializerInterface $serializerInterface): JsonResponse
    {
        $coffees = $coffeeRepository->findAllActive();
        $jsonCoffees = $serializerInterface->serialize($coffees, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffees, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(CoffeeController::COFFEE_COFFEEID_URL, name:'coffee.get', methods: ['GET'])]
    #[ParamConverter('coffee', options: ['id' => 'coffeeId'])]
    public function getCoffee(Coffee $coffee, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/v1/coffee', name:'coffee.create', methods: ['POST'])]
    public function createCoffee(Request $request, UrlGeneratorInterface $urlGeneratorInterface,
    SerializerInterface $serializerInterface, EntityManagerInterface $manager,
    ValidatorInterface $validatorInterface): JsonResponse
    {
        $coffee = $serializerInterface->deserialize($request->getContent(), Coffee::class, 'json');
        // mettre à jour l'objet coffee pour mettre la date de création, d'update et le status
        $coffee->setCreatedAt();
        $coffee->setUpdatedAt();
        $coffee->setStatus('on');

        $errors = $validatorInterface->validate($coffee);
        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }

        $manager->persist($coffee);
        $manager->flush();

        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        $location = $urlGeneratorInterface->generate(
            'coffee.get',
            ['coffeeId'=> $coffee->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route(CoffeeController::COFFEE_COFFEEID_URL, name:'coffee.update', methods: ['PUT'])]
    #[ParamConverter('coffee', options: ['id' => 'coffeeId'])]
    public function updateCoffee(Coffee $coffee, Request $request, SerializerInterface $serializerInterface,
    EntityManagerInterface $manager): JsonResponse
    {
        $serializerInterface->deserialize(
            $request->getContent(),
            Coffee::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $coffee]
        );

        // mettre à jour l'objet coffee pour mettre la date de création, d'update et le status
        $coffee->setUpdatedAt();

        $manager->persist($coffee);
        $manager->flush();

        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(CoffeeController::COFFEE_COFFEEID_URL . '/{isForced}', name:'coffee.delete', methods: ['DELETE'])]
    #[ParamConverter('coffee', options: ['id' => 'coffeeId'])]
    public function deleteCoffeeIsForced(Coffee $coffee, Bool $isForced, EntityManagerInterface $manager): Response
    {
        if ($isForced) {
            $manager->remove($coffee);
            $manager->flush();
            return new Response(null, JsonResponse::HTTP_NO_CONTENT);
        }

        $coffee->setStatus('off');
        $coffee->setUpdatedAt();
        $manager->persist($coffee);
        $manager->flush();
        return new Response(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route(CoffeeController::COFFEE_COFFEEID_URL, name:'coffee.delete', methods: ['DELETE'])]
    #[ParamConverter('coffee', options: ['id' => 'coffeeId'])]
    public function deleteCoffee(Coffee $coffee, EntityManagerInterface $manager): Response
    {
        $coffee->setStatus('off');
        $coffee->setUpdatedAt();
        $manager->persist($coffee);
        $manager->flush();
        return new Response(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
