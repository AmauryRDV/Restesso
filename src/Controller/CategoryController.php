<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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

class CategoryController extends AbstractController
{
    private const API_GATEWAY = '/api/v1';
    private const CONTROLLER_NAME_PREFIX = 'category_';

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
                $categories = $categoryRep->findAll();
                return  $serializer->serialize($categories, 'json', ['groups'=> "getCategory"]);
            }
        );
        
        return new JsonResponse($jsonCategories, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(
        CategoryController::API_GATEWAY . '/category/{id}',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'get',
        methods: ['GET']
    )]
    public function getCategory(Category $category,TagAwareCacheInterface $cache, SerializerInterface $serializer): JsonResponse
    {
        $jsonCategory = $serializer->serialize($category, 'json', ['groups'=> 'getCategory']);
        return new JsonResponse($jsonCategory, JsonResponse::HTTP_OK, [], true);
    }

    #[Route(
        CategoryController::API_GATEWAY . '/category',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'create',
        methods: ['POST']
    )]
    public function createCategory(Request $request,TagAwareCacheInterface $cache,ValidatorInterface $validator,
    UrlGeneratorInterface $urlGenerator,SerializerInterface $serializer, EntityManagerInterface $manager): JsonResponse
    {
        $category=$serializer->deserialize($request->getContent(), Category::class,'json');
        $category ->setCreatedAt();
        $category->setUpdatedAt();
        $category->setStatus("active");
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


    #[Route(
        CategoryController::API_GATEWAY . '/category/{id}',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'update',
        methods: ['PUT']
    )]
    public function updateCategory(Category $updateCategory,TagAwareCacheInterface $cache,ValidatorInterface $validator,Request $request,
    SerializerInterface $serializer, EntityManagerInterface $manager)
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
        
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }


    #[Route(
        CategoryController::API_GATEWAY . '/category/{id}/{isForced}',
        name: CategoryController::CONTROLLER_NAME_PREFIX . 'delete',
        methods: ['DELETE']
    )]
    public function deleteCategory(Category $category,TagAwareCacheInterface $cache, Bool $isForced, EntityManagerInterface $manager): Response
    {
        if ($isForced) {
            $coffees=$category->getCoffees();
            foreach($coffees as $coffee) {
            $category->removeCoffee($coffee);
            }
            $manager->remove($category);
            $manager->flush();
            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        $category->setStatus('inactive');
        $category->setUpdatedAt();
        $manager->persist($category);
        $manager->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
