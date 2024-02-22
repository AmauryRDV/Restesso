<?php

namespace App\Controller;

use App\Entity\Coffee;
use App\Repository\CoffeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CoffeeController extends AbstractController
{
    private const API_GATEWAY = '/api/v1';
    private const CONTROLLER_NAME_PREFIX = 'coffee_';

    #[Route(
        CoffeeController::API_GATEWAY . '/coffees',
        name: CoffeeController::CONTROLLER_NAME_PREFIX . 'getAll',
        methods: ['GET']
    )]
    public function getAllCoffees(CoffeeRepository $coffeeRepository, SerializerInterface $serializerInterface,
    TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $tagAwareCacheInterface->get('getAllCoffeesCache',
            function (ItemInterface $itemInterface) use ($coffeeRepository, $serializerInterface)
            {
                $itemInterface->tag(['coffeesCache']);
                $coffees = $coffeeRepository->findAllActive();
                return $serializerInterface->serialize($coffees, 'json', ['groups' => 'getCoffee']);
            }
        );

        $coffees = $coffeeRepository->findAllActive();
        $jsonCoffees = $serializerInterface->serialize($coffees, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffees, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(
        CoffeeController::API_GATEWAY . '/coffee/{id}',
        name: CoffeeController::CONTROLLER_NAME_PREFIX . 'get',
        methods: ['GET']
    )]
    public function getCoffee(Coffee $coffee, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(
        CoffeeController::API_GATEWAY . '/coffee',
        name: CoffeeController::CONTROLLER_NAME_PREFIX . 'create',
        methods: ['POST']
    )]
    public function createCoffee(Request $request, UrlGeneratorInterface $urlGeneratorInterface,
    SerializerInterface $serializerInterface, EntityManagerInterface $manager,
    ValidatorInterface $validatorInterface, TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $coffee = $serializerInterface->deserialize($request->getContent(), Coffee::class, 'json');

        // mettre à jour l'objet coffee pour mettre la date de création, d'update et le status
        $coffee->setCreatedAt();
        $coffee->setUpdatedAt();
        $coffee->setStatus('active');

        $errors = $validatorInterface->validate($coffee);
        if (count($errors)) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }

        $manager->persist($coffee);
        $manager->flush();

        $tagAwareCacheInterface->invalidateTags(['coffeesCache']);

        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        $location = $urlGeneratorInterface->generate(
            CoffeeController::CONTROLLER_NAME_PREFIX . 'get',
            ['id' => $coffee->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route(
        CoffeeController::API_GATEWAY . '/coffee/{id}',
        name: CoffeeController::CONTROLLER_NAME_PREFIX . 'update',
        methods: ['PUT']
    )]
    public function updateCoffee(Coffee $coffee, Request $request, SerializerInterface $serializerInterface,
    EntityManagerInterface $manager, ValidatorInterface $validatorInterface,
    TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $serializerInterface->deserialize(
            $request->getContent(),
            Coffee::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $coffee]
        );

        // mettre à jour l'objet coffee pour mettre la date de création, d'update et le status
        $coffee->setUpdatedAt();

        $errors = $validatorInterface->validate($coffee);
        if (count($errors)) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }

        $manager->persist($coffee);
        $manager->flush();

        $tagAwareCacheInterface->invalidateTags(['coffeesCache']);

        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(
        CoffeeController::API_GATEWAY . '/coffee/{id}/{isForced}',
        name: CoffeeController::CONTROLLER_NAME_PREFIX . 'delete',
        methods: ['DELETE']
    )]
    public function deleteCoffeeIsForced(Coffee $coffee, Bool $isForced, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): Response
    {
        if ($isForced) {
            $manager->remove($coffee);
            $manager->flush();

            $tagAwareCacheInterface->invalidateTags(['coffeesCache']);

            return new Response(null, JsonResponse::HTTP_NO_CONTENT);
        }

        $coffee->setStatus('active');
        $coffee->setUpdatedAt();
        $manager->persist($coffee);
        $manager->flush();

        $tagAwareCacheInterface->invalidateTags(['coffeesCache']);

        return new Response(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route(
        CoffeeController::API_GATEWAY . '/coffee/{id}',
        name: CoffeeController::CONTROLLER_NAME_PREFIX . 'delete',
        methods: ['DELETE']
    )]
    public function deleteCoffee(Coffee $coffee, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): Response
    {
        $coffee->setUpdatedAt();
        $coffee->setStatus('inactive');

        $manager->persist($coffee);
        $manager->flush();
        
        $tagAwareCacheInterface->invalidateTags(['coffeesCache']);
        
        return new Response(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
