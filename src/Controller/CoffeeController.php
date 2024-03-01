<?php

namespace App\Controller;

use App\Entity\Coffee;
use App\Repository\CoffeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
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
use OpenApi\Annotations as OA;

class CoffeeController extends AbstractController
{
    private const API_GATEWAY = '/api/v1';
    private const CONTROLLER_NAME_PREFIX = 'coffee_';

    /**
     * This method return all the coffees availables.
     * @OA\Response(response=200, description="Return coffees",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Coffee::class, groups={"getCoffee"})))
     * )
     * @OA\Tag(name="Coffee")
     */
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

    /**
     * This method return a coffee by his ID if he is available.
     * @OA\Parameter(name="id", in="path", description="ID of the coffee", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Return a coffee",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Coffee::class, groups={"getCoffee"})))
     * )
     * @OA\Tag(name="Coffee")
     */
    #[Route(
        CoffeeController::API_GATEWAY . '/coffee/{id}',
        name: CoffeeController::CONTROLLER_NAME_PREFIX . 'get',
        methods: ['GET']
    )]
    public function getCoffee(int $id, SerializerInterface $serializerInterface,
    CoffeeRepository $coffeeRepository): JsonResponse
    {
        $coffee = $coffeeRepository->findActive($id);
        if (!$coffee) { throw $this->createNotFoundException('Coffee not found'); }

        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method give you the possibility to create a new coffee.
     * @OA\Parameter(name="name", in="query", description="Name of the coffee", required=true,
     *  @OA\Schema(type="string")
     * )
     * @OA\Parameter(name="description", in="query", description="Description of the coffee",
     *  required=true, @OA\Schema(type="string")
     * )
     * @OA\Parameter(name="category", in="query", description="ID of the category", required=true,
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(name="bean", in="query", description="ID of the bean", required=true,
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(name="taste", in="query", description="ID of the taste", required=true,
     *  @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Coffee")
     */
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

    /**
     * This method is able to update a coffee by his Id.
     * @OA\Parameter(name="id", in="path", description="Id of the coffee", required=true, @OA\Schema(type="integer"))
     * @OA\Parameter(name="name", in="query", description="Name of the coffee", required=false,
     *  @OA\Schema(type="string")
     * )
     * @OA\Parameter(name="description", in="query", description="Description of the coffee", required=false,
     *  @OA\Schema(type="string")
     * )
     * @OA\Parameter(name="category", in="query", description="ID of the category", required=false,
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(name="bean", in="query", description="ID of the bean", required=false,
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(name="taste", in="query", description="ID of the taste", required=false,
     *  @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Coffee")
     */
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

        // $errors = $validatorInterface->validate($coffee);
        // if (count($errors)) {
        //     return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        // }

        $manager->persist($coffee);
        $manager->flush();

        $tagAwareCacheInterface->invalidateTags(['coffeesCache']);

        $jsonCoffee = $serializerInterface->serialize($coffee, 'json', ['groups' => 'getCoffee']);
        return new JsonResponse($jsonCoffee, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method soft delete a Coffee with the ID, can be forced.
     * @OA\Parameter(name="id", in="path", description="Id of the coffee", required=true, @OA\Schema(type="integer"))
     * @OA\Parameter(name="isForced", in="path", description="Force or not the delete of a coffee", required=true,
     *  @OA\Schema(type="Bool")
     * )
     * @OA\Tag(name="Coffee")
     */
    #[Route(
        CoffeeController::API_GATEWAY . '/coffee/{id}/{isForced}',
        name: CoffeeController::CONTROLLER_NAME_PREFIX . 'delete_forced',
        methods: ['DELETE']
    )]
    public function deleteCoffeeIsForced(Coffee $coffee, Bool $isForced, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): Response
    {
        if ($isForced) {
            $manager->remove($coffee);
        } else {
            $coffee->setStatus('active')->setUpdatedAt();
            $manager->persist($coffee);
        }

        
        $manager->flush();
        $tagAwareCacheInterface->invalidateTags(['coffeesCache']);
        return new Response(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * This method soft delete a Coffee with the ID.
     * @OA\Parameter(name="id", in="path", description="Id of the coffee", required=true, @OA\Schema(type="integer"))
     * @OA\Tag(name="Coffee")
     */
    #[Route(
        CoffeeController::API_GATEWAY . '/coffee/{id}',
        name: CoffeeController::CONTROLLER_NAME_PREFIX . 'delete',
        methods: ['DELETE']
    )]
    public function deleteCoffee(Coffee $coffee, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): Response
    {
        $coffee->setUpdatedAt()->setStatus('inactive');

        $manager->persist($coffee);
        $manager->flush();
        
        $tagAwareCacheInterface->invalidateTags(['coffeesCache']);
        $tagAwareCacheInterface->invalidateTags(['getCoffee']);
        
        return new Response(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
