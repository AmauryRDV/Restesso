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

class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CategoryController.php',
        ]);
    }

    #[Route('/api/v1/categories', name:"category.getAll", methods: ['GET'])]
    public function getAllCategories(CategoryRepository $categoryRep, SerializerInterface $serializer)
    {
        $categories = $categoryRep->findAll();
        $jsonCategories = $serializer->serialize($categories, 'json', ['groups'=> "getAllCategories"]);
        return new JsonResponse($jsonCategories, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/v1/category/{idCategory}', name:"category.get", methods: ['GET'])]
    #[ParamConverter("category", options:["id"=>"idCategory"])]
    public function getCategory(Category $category, SerializerInterface $serializer): JsonResponse
    {
        $jsonCategory = $serializer->serialize($category, 'json', ['groups'=> 'getAllCategories']);
        return new JsonResponse($jsonCategory, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/v1/category', name:"category.create", methods: ['POST'])]
    public function createCategory(Request $request, UrlGeneratorInterface $urlGenerator,
    SerializerInterface $serializer, EntityManagerInterface $manager)
    {
        $category=$serializer->deserialize($request->getContent(), Category::class,'json');
        $category ->setCreatedAt();
        $category->setUpdatedAt();
        $category->setStatus("on");
        $manager->persist($category);
        $manager->flush();
        $jsonCategory = $serializer->serialize($category, 'json', ["groups"=> "getCategory"]);
        $location = $urlGenerator->generate(
            "category.get",
            ["idCategory"=>$category->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($jsonCategory, JsonResponse::HTTP_CREATED, ["Location"=>$location], true);
    }


    #[Route('/api/v1/category/{category}', name:"category.update", methods: ['PUT'])]
    public function updateCategory(Category $updateCategory,Request $request,
    SerializerInterface $serializer, EntityManagerInterface $manager)
    {
        $updateCategory = $serializer->deserialize(
            $request->getContent(),
            Category::class,'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $updateCategory]
        );
        $updateCategory->setUpdatedAt()->setStatus("on");
        $manager->persist($updateCategory);
        $manager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }


    #[Route('/api/v1/category/{categoryId}/{isForced}', name:'category.delete', methods: ['DELETE'])]
    #[ParamConverter('category', options: ['id' => 'categoryId'])]
    public function deleteCategory(Category $category, Bool $isForced, EntityManagerInterface $manager): Response
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

        $category->setStatus('off');
        $category->setUpdatedAt();
        $manager->persist($category);
        $manager->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
