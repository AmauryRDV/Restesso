<?php

namespace App\Controller;

use App\Entity\Bean;
use App\Repository\BeanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use OpenApi\Annotations as OA;

class BeanController extends AbstractController
{
    private const API_GATEWAY = '/api/v1';
    private const CONTROLLER_NAME_PREFIX = 'bean_';

    /**
     * This method return all the beans availables.
     * @OA\Response(response=200, description="Return beans list",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Bean::class, groups={"getAllBeans"})))
     * )
     * @OA\Tag(name="Bean")
     * @Security(name="Bearer")
     */
    #[Route(
        BeanController::API_GATEWAY . '/beans',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'getAll',
        methods: ['GET']
    )]
    public function getAllBeans(BeanRepository $beanRep, SerializerInterface $serializer, TagAwareCacheInterface $cache)
    {
        $idCacheGetAllBeans = "getAllBeansCache";
        $jsonBeans = $cache->get($idCacheGetAllBeans, function (ItemInterface $item) use ($beanRep, $serializer)
        {
            $item->tag("beanCache");
            $beans = $beanRep->findAllActive();
            return  $serializer->serialize($beans, 'json', ['groups'=> "getAllBeans"]);
        });
    
        return new JsonResponse($jsonBeans, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method return a bean by his ID if it is available.
     * @OA\Parameter(name="id", in="path", description="ID of the bean", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Return a bean",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Bean::class, groups={"getBean"})))
     * )
     * @OA\Tag(name="Bean")
     * @Security(name="Bearer")
     */
    #[Route(
        BeanController::API_GATEWAY . '/bean/{id}',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'get',
        methods: ['GET']
    )]
    public function getBean(int $id, SerializerInterface $serializer, BeanRepository $beanRepository): JsonResponse
    {
        $bean = $beanRepository->findActive($id);
        if (!$bean) { throw $this->createNotFoundException('Bean not found'); }
        
        $jsonBean = $serializer->serialize($bean, 'json', ['groups'=> 'getBean']);
        return new JsonResponse($jsonBean, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method create a new bean.
     * @OA\RequestBody(@OA\JsonContent(ref=@Model(type=Bean::class, groups={"createBean"})))
     * @OA\Response(response=201, description="Return the created bean",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Bean::class, groups={"getBean"})))
     * )
     * @OA\Tag(name="Bean")
     * @Security(name="Bearer")
     */
    #[Route(
        BeanController::API_GATEWAY . '/bean',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'create',
        methods: ['POST']
    )]
    public function createBean(Request $request, UrlGeneratorInterface $urlGeneratorInterface,
    SerializerInterface $serializerInterface, EntityManagerInterface $manager,
    ValidatorInterface $validatorInterface, TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $bean = $serializerInterface->deserialize($request->getContent(), Bean::class, 'json');
        $bean->setCreatedAt()->setUpdatedAt()->setStatus('active');

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

    /**
     * This method is able to update a bean by her Id.
     * @OA\Parameter(name="id", in="path", description="Id of the bean", required=true, @OA\Schema(type="int"))
     * @OA\RequestBody(@OA\JsonContent(ref=@Model(type=Bean::class, groups={"updateBean"})))
     * @OA\Response(response=200, description="Return the updated bean",
     * @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Bean::class, groups={"getBean"})))
     * )
     * @OA\Tag(name="Bean")
     * @Security(name="Bearer")
     */
    #[Route(
        BeanController::API_GATEWAY . '/bean/{id}',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'update',
        methods: ['PUT']
    )]
    public function updateBean(Bean $updateBean, ValidatorInterface $validator,Request $request,
    SerializerInterface $serializer, EntityManagerInterface $manager, TagAwareCacheInterface $tagCache): JsonResponse
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
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $manager->persist($updateBean);
        $manager->flush();
        $tagCache->invalidateTags(['beanCache', 'coffeesCache']);

        $jsonBean = $serializer->serialize($updateBean, 'json', ['groups' => 'getBean']);
        return new JsonResponse($jsonBean, JsonResponse::HTTP_OK, [], true);
    }

    /**
    * This method soft delete a Bean with the ID, can be forced.
    * @OA\Parameter(name="id", in="path", description="Id of the bean", required=true, @OA\Schema(type="integer"))
    * @OA\Parameter(name="isForced", in="path", description="Disable or Delete a bean", required=true,
    *  @OA\Schema(type="string", enum={"1", "true", "oui", "yes", "forced", "vrai", "force"})
    * )
    * @OA\Response(response=204, description="No content")
    * @OA\Tag(name="Bean")
    * @Security(name="Bearer")
    */
    #[Route(
        BeanController::API_GATEWAY . '/bean/{id}/{isForced}',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'delete_forced',
        methods: ['DELETE']
    )]
    public function deleteBeanIsForced(Bean $bean, string $isForced, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $tagToInvalidate = ['beanCache'];
        $forcedVar = ['1', 'true', 'oui', 'yes', 'forced', 'vrai', 'force'];

        if (in_array(strtolower($isForced), $forcedVar)) {
            echo 'isForced true';
            $coffees = $bean->getCoffees();
            foreach($coffees as $coffee) {
                $coffee->setBean(null);
                $coffee->setUpdatedAt();
                $coffee->setStatus('inactive');

                $manager->persist($coffee);
            }
            $manager->remove($bean);
        } else {
            $bean->setUpdatedAt()->setStatus('inactive');
            $manager->persist($bean);
        }

        $manager->flush();

        $tagAwareCacheInterface->invalidateTags($tagToInvalidate);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
    * This method soft delete a Bean with the ID.
    * @OA\Parameter(name="id", in="path", description="Id of the bean", required=true, @OA\Schema(type="integer"))
    * @OA\Response(response=204, description="No content")
    * @OA\Tag(name="Bean")
    * @Security(name="Bearer")
    */
    #[Route(
        BeanController::API_GATEWAY . '/bean/{id}',
        name: BeanController::CONTROLLER_NAME_PREFIX . 'delete',
        methods: ['DELETE']
    )]
    public function deleteBean(Bean $bean, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $bean->setUpdatedAt()->setStatus('inactive');

        $manager->persist($bean);
        $manager->flush();

        $tagAwareCacheInterface->invalidateTags(['beanCache']);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
