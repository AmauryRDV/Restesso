<?php

namespace App\Controller;

use App\Entity\Coffee;
use App\Repository\CoffeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Boolean;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class CoffeeController extends AbstractController
{
    #[Route('/coffee', name: 'app_coffee')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CoffeeController.php',
        ]);
    }

    #[Route('/api/v1/coffees', name:'coffee.getAll', methods: ['GET'])]
    public function getAllCoffees(CoffeeRepository $coffeeRepository, SerializerInterface $serializerInterface): JsonResponse
    {
        $coffees = $coffeeRepository->findAll();
        $jsonCoffees = $serializerInterface->serialize($coffees, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffees, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/v1/coffee/{coffeeId}', name:'coffee.get', methods: ['GET'])]
    #[ParamConverter('coffee', options: ['id' => 'coffeeId'])]
    public function getCoffee(Coffee $coffee, SerializerInterface $serializerInterface): JsonResponse
    {
        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/v1/coffee', name:'coffee.create', methods: ['POST'])]
    public function createCoffee(Request $request, UrlGeneratorInterface $urlGeneratorInterface, SerializerInterface $serializerInterface, EntityManagerInterface $manager): JsonResponse
    {
        $coffee = $serializerInterface->deserialize($request->getContent(), Coffee::class, 'json');

        // mettre à jour l'objet coffee pour mettre la date de création, d'update et le status
        $coffee->setCreatedAt();
        $coffee->setUpdatedAt();
        $coffee->setStatus('on');

        $manager->persist($coffee);
        $manager->flush();

        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        $location = $urlGeneratorInterface->generate('coffee.get', ['coffeeId'=> $coffee->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/api/v1/coffee/{coffeeId}', name:'coffee.update', methods: ['PUT'])]
    #[ParamConverter('coffee', options: ['id' => 'coffeeId'])]
    public function updateCoffee(Coffee $coffee, Request $request, SerializerInterface $serializerInterface, EntityManagerInterface $manager): JsonResponse
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

    #[Route('/api/v1/coffee/{coffeeId}/{isForced}', name:'coffee.delete', methods: ['DELETE'])]
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

    #[Route('/api/v1/coffee/{coffeeId}', name:'coffee.delete', methods: ['DELETE'])]
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