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
    #[Route('/category', name: 'app_category')]
    
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CategoryController.php',
        ]);
    }

        #[Route('/api/v1/categories', name:"category.getAll", methods: ['GET'])]
        public function getAllCategories(CategoryRepository $categoryRep, SerializerInterface $serializer, TagAwareCacheInterface $cache)
        {
            $idCacheGetAllCategories = "getAllCategoriesCache";
                $jsonCategories = $cache->get($idCacheGetAllCategories, function (ItemInterface $item) use ($categoryRep, $serializer) 
                {
                    $item->tag("categoryCache");
                    $categories = $categoryRep->findAll();
                    return  $serializer->serialize($categories, 'json', ['groups'=> "getAllCategories"]);
                }
            );
        
            return new JsonResponse($jsonCategories, Response::HTTP_OK, [], true);
        }

        #[Route('/api/v1/category/{idCategory}', name:"category.get", methods: ['GET'])]
        #[ParamConverter("category", options:["id"=>"idCategory"])]
        public function getCategory(Category $category, SerializerInterface $serializer): JsonResponse
        {
            $jsonCategory = $serializer->serialize($category, 'json', ['groups'=> 'getAllCategories']);
            return new JsonResponse($jsonCategory, 200, [], true);
        }

        #[Route('/api/v1/category', name:"category.create", methods: ['POST'])]
        public function createCategory(Request $request,TagAwareCacheInterface $cache,ValidatorInterface $validator,UrlGeneratorInterface $urlGenerator,
        SerializerInterface $serializer, EntityManagerInterface $manager)
        {
            $category=$serializer->deserialize($request->getContent(), Category::class,'json');
            $category ->setCreatedAt();
            $category->setUpdatedAt();
            $category->setStatus("on");
            $errors = $validator->validate($category);
            if ($errors->count())
            {
                return new JsonResponse($serializer->serialize($errors,'json'),JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $manager->persist($category);
            $manager->flush();
            $cache->invalidateTags(['categoryCache']);
            $jsonCategory = $serializer->serialize($category, 'json', ["groups"=> "getCategory"]);
            $location = $urlGenerator->generate(
                "category.get",
                ["idCategory"=>$category->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            return new JsonResponse($jsonCategory, JsonResponse::HTTP_CREATED, ["Location"=>$location], true);
        }


        #[Route('/api/v1/category/{category}', name:"category.update", methods: ['PUT'])]
        public function updateCategory(Category $updateCategory,ValidatorInterface $validator,Request $request,
        SerializerInterface $serializer, EntityManagerInterface $manager)
        {
            $updateCategory = $serializer->deserialize(
                $request->getContent(),
                Category::class,'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $updateCategory]
            );
            $updateCategory->setUpdatedAt()->setStatus("on");
            $errors = $validator->validate($updateCategory);
            if ($errors->count())
            {
                return new JsonResponse($serializer->serialize($errors,'json'),JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
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
                return new Response(null, JsonResponse::HTTP_NO_CONTENT);
            }
            $category->setStatus('off');
            $category->setUpdatedAt();
            $manager->persist($category);
            $manager->flush();
            return new Response(null, JsonResponse::HTTP_NO_CONTENT);
        }
}
