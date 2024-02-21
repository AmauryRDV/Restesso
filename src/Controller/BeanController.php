<?php

namespace App\Controller;

use App\Entity\Bean;
use App\Repository\BeanRepository;
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
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class BeanController extends AbstractController
{
    #[Route('/bean', name: 'app_bean')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/BeanController.php',
        ]);
    }

    
    #[Route('/api/v1/beans', name:"bean.getAll", methods: ['GET'])]
    public function getAllBeans(BeanRepository $beanRep, SerializerInterface $serializer, TagAwareCacheInterface $cache)
    {
        $idCacheGetAllBeans = "getAllBeansCache";
            $jsonBeans = $cache->get($idCacheGetAllBeans, function (ItemInterface $item) use ($beanRep, $serializer) 
            {
                $item->tag("beanCache");
                $beans = $beanRep->findAll();
                return  $serializer->serialize($beans, 'json', ['groups'=> "getBean"]);
            }
        );
    
        return new JsonResponse($jsonBeans, JsonResponse::HTTP_OK, [], true);
    }

    
    #[Route('/api/v1/bean/{idBean}', name:"bean.get", methods: ['GET'])]
    #[ParamConverter("bean", options:["id"=>"idBean"])]
    public function getBean(Bean $bean, SerializerInterface $serializer): JsonResponse
    {
        $jsonBean = $serializer->serialize($bean, 'json', ['groups'=> 'getBean']);
        return new JsonResponse($jsonBean, 200, [], true);
    }

    #[Route('/api/v1/bean', name:'bean.create', methods: ['POST'])]
    public function createBean(Request $request, UrlGeneratorInterface $urlGeneratorInterface,
    SerializerInterface $serializerInterface, EntityManagerInterface $manager,
    ValidatorInterface $validatorInterface, TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $bean = $serializerInterface->deserialize($request->getContent(), Bean::class, 'json');
        $errors = $validatorInterface->validate($bean);
        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }
        $manager->persist($bean);
        $manager->flush();
        $tagAwareCacheInterface->invalidateTags(['beanCache']);

        $jsonBean = $serializerInterface->serialize($bean, 'json', ['groups' => 'getBean']);
        $location = $urlGeneratorInterface->generate(
            'bean.get',
            ['idBean'=> $bean->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($jsonBean, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }


    #[Route('/api/v1/bean/{id}', name:"bean.update", methods: ['PUT'])]
    public function updateBean(Bean $updateBean,ValidatorInterface $validator,Request $request,
    SerializerInterface $serializer, EntityManagerInterface $manager, TagAwareCacheInterface $tagAwareCacheInterface)
    {
        $updateBean = $serializer->deserialize(
            $request->getContent(),
            Bean::class,'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $updateBean]
        );
        $errors = $validator->validate($updateBean);
        if ($errors->count())
        {
            return new JsonResponse($serializer->serialize($errors,'json'),JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $manager->persist($updateBean);
        $manager->flush();
        $tagAwareCacheInterface->invalidateTags(['beanCache']);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }


    #[Route('/api/v1/bean/{beanId}', name:'bean.delete', methods: ['DELETE'])]
    #[ParamConverter('bean', options: ['id' => 'beanId'])]
    public function deleteBean(Bean $bean, EntityManagerInterface $manager, TagAwareCacheInterface $tagAwareCacheInterface):Response 
    {
            $coffees=$bean->getCoffees();
            foreach($coffees as $coffee) {
            $bean->removeCoffee($coffee);
            }
            $manager->remove($bean);
            $manager->flush();
            $tagAwareCacheInterface->invalidateTags(['beanCache']);
            return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

}
