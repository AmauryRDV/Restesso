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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class BeanController extends AbstractController
{
    private const API_GATEWAY = '/api/v1';
    private const CONTROLLER_NAME_PREFIX = 'bean_';

    #[Route(
        BeanController::API_GATEWAY . '/beans',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'getAll',
        methods: ['GET']
    )]
       /**
     * This method return all the beans availables.
     * @OA\Response(
     * response=200,
     * description="Return beans list",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref=@Model(type=Bean::class, groups={"getBeans"}))
     * )
     * )
     * @OA\Tag(name="Bean")
     *
     */
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

    
    #[Route(
        BeanController::API_GATEWAY . '/bean/{id}',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'get',
        methods: ['GET']
    )]
       /**
     * This method return a bean by his ID.
     *  @OA\Parameter(
     * name="id",
     * in="path",
     * description="ID of the bean",
     * required=true,
     * @OA\Schema(type="integer")
     * )
     * @OA\Response(
     * response=200,
     * description="Return a bean",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref=@Model(type=Bean::class, groups={"getBean"}))
     * )
     * )
     * @OA\Tag(name="Bean")
     *
     */
    public function getBean(Bean $bean, SerializerInterface $serializer): JsonResponse
    {
        $jsonBean = $serializer->serialize($bean, 'json', ['groups'=> 'getBean']);
        return new JsonResponse($jsonBean, 200, [], true);
    }

    #[Route(
        BeanController::API_GATEWAY . '/bean',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'create',
        methods: ['POST']
    )]
    /**
     * This method give you the possibility to create a new bean.
     * @OA\Parameter(
     * name="name",
     * in="path",
     * description="Name of the bean",
     * required=true,
     * @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Bean")
     *
     */
    public function createBean(Request $request, UrlGeneratorInterface $urlGeneratorInterface,
    SerializerInterface $serializerInterface, EntityManagerInterface $manager,
    ValidatorInterface $validatorInterface, TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $bean = $serializerInterface->deserialize($request->getContent(), Bean::class, 'json');
        $bean->setCreatedAt();
        $bean->setUpdatedAt();
        $bean->setStatus('active');
        $errors = $validatorInterface->validate($bean);
        if (count($errors) > 0) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }
        $manager->persist($bean);
        $manager->flush();
        $tagAwareCacheInterface->invalidateTags(['beanCache']);

        $jsonBean = $serializerInterface->serialize($bean, 'json', ['groups' => 'getBean']);
        $location = $urlGeneratorInterface->generate(
            'bean_get',
            ['id'=> $bean->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($jsonBean, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }


    #[Route(
        BeanController::API_GATEWAY . '/bean/{id}',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'update',
        methods: ['PUT']
    )]    
       /**
     * This method is able to update a bean by her Id.
     * @OA\Parameter(
     * name="id",
     * in="path",
     * description="Id of the bean",
     * required=true,
     * @OA\Schema(type="int")
     * )
     * @OA\Parameter(
     * name="name",
     * in="path",
     * description="Name of the bean",
     * required=true,
     * @OA\Schema(type="string")
     * )
     * @OA\Parameter(
     * name="status",
     * in="path",
     * description="Status of the bean (Can be active or unactive)",
     * required=true,
     * @OA\Schema(type="string")
     * )
     * @OA\Tag(name="Bean")
     *
     */
    public function updateBean(Bean $updateBean,ValidatorInterface $validator,Request $request,
    SerializerInterface $serializer, EntityManagerInterface $manager, TagAwareCacheInterface $tagAwareCacheInterface)
    {
            $serializer->deserialize(
            $request->getContent(),
            Bean::class,'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $updateBean]
        );
        $updateBean->setUpdatedAt();
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


    #[Route(
        BeanController::API_GATEWAY . '/bean/{id}',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'delete',
        methods: ['DELETE']
    )]
    /**
    * This method remove a Category with the ID.
    * @OA\Parameter(
    * name="id",
    * in="path",
    * description="Id of the category",
    * required=true,
    * @OA\Schema(type="int")
    * )
    * @OA\Tag(name="Bean")
    *
    */
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
