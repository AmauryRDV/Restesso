<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use OpenApi\Annotations as OA;


class CategoryController extends AbstractController
{
   
    private const API_GATEWAY = '/api/v1';
    private const CONTROLLER_NAME_PREFIX = 'category_';

    /**
     * This method return all the categories availables.
     * @OA\Response(response=200, description="Return categories",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Category::class, groups={"getCategory"})))
     * )
     * @OA\Tag(name="Category")
     */
    #[Route(
        CategoryController::API_GATEWAY . '/categories',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'getAll',
        methods: ['GET']
    )]
    public function getAllCategories(CategoryRepository $categoryRep, SerializerInterface $serializer,
    TagAwareCacheInterface $cache): JsonResponse
    {
        $idCacheGetAllCategories = "getAllCategoriesCache";
        $jsonCategories = $cache->get(
            $idCacheGetAllCategories,
            function (ItemInterface $item) use ($categoryRep, $serializer) {
                $item->tag("categoryCache");
                $categories = $categoryRep->findAllActive();
                return  $serializer->serialize($categories, 'json', ['groups'=> "getCategory"]);
            }
        );
        
        return new JsonResponse($jsonCategories, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method return a category by his ID.
     * @OA\Parameter(name="id", in="path", description="ID of the category", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Return a category",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Category::class, groups={"getCategory"})))
     * )
     * @OA\Tag(name="Category")
     */
    #[Route(
        CategoryController::API_GATEWAY . '/category/{id}',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'get',
        methods: ['GET']
    )]
    public function getCategory(int $id, SerializerInterface $serializer,
    CategoryRepository $categoryRepository): JsonResponse
    {
        $category = $categoryRepository->findActive($id);
        $jsonCategory = $serializer->serialize($category, 'json', ['groups'=> 'getCategory']);
        return new JsonResponse($jsonCategory, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method give you the possibility to create a new category .
     * @OA\Parameter(name="name", in="query", description="Name of the category", required=true,
     *  @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Category")
     */
    #[Route(
        CategoryController::API_GATEWAY . '/category',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'create',
        methods: ['POST']
    )]
    public function createCategory(Request $request,TagAwareCacheInterface $cache,ValidatorInterface $validator,
    UrlGeneratorInterface $urlGenerator,SerializerInterface $serializer, EntityManagerInterface $manager): JsonResponse
    {
        $category=$serializer->deserialize($request->getContent(), Category::class,'json');
        $category ->setCreatedAt()->setUpdatedAt()->setStatus("active");

        $errors = $validator->validate($category);
        if ($errors->count()) {
            return new JsonResponse($serializer->serialize($errors,'json'),JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $manager->persist($category);
        $manager->flush();

        $cache->invalidateTags(['categoryCache']);

        $jsonCategory = $serializer->serialize($category, 'json', ['groups' => 'getCategory']);
        $location = $urlGenerator->generate(
            'category_get',
            ['id' => $category->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($jsonCategory, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * This method is able to update a category by her Id.
     * @OA\Parameter(name="id", in="path", description="Id of the category", required=true, @OA\Schema(type="integer"))
     * @OA\Parameter(name="name", in="query", description="Name of the category", required=false,
     *  @OA\Schema(type="string")
     * )
     * @OA\Parameter(name="status", in="query", description="Status of the category (Can be active or unactive)",
     *  required=false, @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Category")
     */
    #[Route(
        CategoryController::API_GATEWAY . '/category/{id}',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'update',
        methods: ['PUT']
    )]
    public function updateCategory(Category $updateCategory,TagAwareCacheInterface $cache,ValidatorInterface $validator,
    Request $request, SerializerInterface $serializer, EntityManagerInterface $manager)
    {
        $updateCategory = $serializer->deserialize(
            $request->getContent(),
            Category::class,'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $updateCategory]
        );

        $updateCategory->setUpdatedAt()->setStatus("active");

        $errors = $validator->validate($updateCategory);
        if ($errors->count()) {
            return new JsonResponse(
                $serializer->serialize($errors, 'json'),
                JsonResponse::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $manager->persist($updateCategory);
        $manager->flush();

        $cache->invalidateTags(['categoryCache']);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * This method soft delete a Category with the ID, can be forced.
     * @OA\Parameter(name="id", in="path", description="Id of the category", required=true, @OA\Schema(type="integer"))
     * @OA\Parameter(name="isForced", in="path", description="Disable or Delete a category", required=true,
     *  @OA\Schema(type="Bool")
     * )
     * @OA\Tag(name="Category")
     */
    #[Route(
        CategoryController::API_GATEWAY . '/category/{id}/{isForced}',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'delete_forced',
        methods: ['DELETE']
    )]
    public function deleteCategoryIsForced(Category $category, TagAwareCacheInterface $cache, Bool $isForced,
    EntityManagerInterface $manager): Response
    {
        $tagToInvalidate = ['categoryCache'];
        if ($isForced) {
            $coffees=$category->getCoffees();
            foreach($coffees as $coffee) {
                $category->removeCoffee($coffee);

                $coffee->setCategory(null);
                $coffee->setUpdatedAt();
                $coffee->setStatus('inactive');

                $manager->persist($coffee);
            }
            $manager->remove($category);

            $tagToInvalidate[] = 'coffeesCache';
        } else {
            $category->setStatus('inactive');
            $category->setUpdatedAt();
            $manager->persist($category);
        }

        
        $manager->flush();
        $cache->invalidateTags($tagToInvalidate);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * This method soft delete a Category with the ID.
     * @OA\Parameter(name="id", in="path", description="Id of the category", required=true, @OA\Schema(type="integer"))
     * @OA\Tag(name="Category")
     */
    #[Route(
        CategoryController::API_GATEWAY . '/category/{id}',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'delete',
        methods: ['DELETE']
    )]
    public function deleteCategory(Category $category, TagAwareCacheInterface $cache,
    EntityManagerInterface $manager): Response
    {
        $category->setStatus('inactive');
        $category->setUpdatedAt();

        $manager->persist($category);
        $manager->flush();

        $cache->invalidateTags(['categoryCache']);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
