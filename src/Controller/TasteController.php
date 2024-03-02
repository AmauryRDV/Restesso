<?php

namespace App\Controller;

use App\Entity\Taste;
use App\Repository\TasteRepository;
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

class TasteController extends AbstractController
{
    private const API_GATEWAY = '/api/v1';
    private const CONTROLLER_NAME_PREFIX = 'taste_';

    /**
     * This method return all the tastes availables.
     * @OA\Response(response=200, description="Return tastes",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Taste::class, groups={"getAllTasteS"})))
     * )
     * @OA\Tag(name="Taste")
     */
    #[Route(
        TasteController::API_GATEWAY . '/tastes',
        name: TasteController::CONTROLLER_NAME_PREFIX . 'getAll',
        methods: ['GET']
    )]
    public function getAllTastes(TasteRepository $tasteRepository, SerializerInterface $serializerInterface,
    TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $tastes = $tagAwareCacheInterface->get('getAllTastesCache',
            function (ItemInterface $itemInterface) use ($tasteRepository, $serializerInterface)
            {
                $itemInterface->tag(['tastesCache']);
                $tastes = $tasteRepository->findAllActive();
                return $serializerInterface->serialize($tastes, 'json', ['groups' => 'getAllTastes']);
            }
        );

        return new JsonResponse($tastes, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method return a taste by his ID if he is available.
     * @OA\Parameter(name="id", in="path", description="ID of the taste", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Return a taste",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Taste::class, groups={"getTaste"})))
     * )
     * @OA\Tag(name="Taste")
     */
    #[Route(
        TasteController::API_GATEWAY . '/taste/{id}',
        name: TasteController::CONTROLLER_NAME_PREFIX . 'get',
        methods: ['GET']
    )]
    public function getTaste(int $id, SerializerInterface $serializerInterface,
    TasteRepository $tasteRepository): JsonResponse
    {
        $taste = $tasteRepository->findActive($id);
        if (!$taste) { throw $this->createNotFoundException('Taste not found'); }
        
        $jsonTaste = $serializerInterface->serialize($taste, 'json', ['groups' => 'getTaste']);
        return new JsonResponse($jsonTaste, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method give you the possibility to create a new taste.
     * @OA\Parameter(name="name", in="query", description="Name of the taste", required=true,
     *  @OA\Schema(type="string")
     * )
     * @OA\Parameter(name="description", in="query", description="Description of the taste",
     *  required=true, @OA\Schema(type="string")
     * )
     * @OA\Parameter(name="intensity", in="query", description="intensity of thhis taste", required=true,
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(name="caffeineRate", in="query", description="caffeine rate of this taste", required=true,
     *  @OA\Schema(type="float")
     * )
     * @OA\Tag(name="Taste")
     */
    #[Route(
        TasteController::API_GATEWAY . '/taste',
        name: TasteController::CONTROLLER_NAME_PREFIX . 'create',
        methods: ['POST']
    )]
    public function createTaste(Request $request, UrlGeneratorInterface $urlGeneratorInterface,
    SerializerInterface $serializerInterface, EntityManagerInterface $manager,
    ValidatorInterface $validatorInterface, TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $taste = $serializerInterface->deserialize($request->getContent(), Taste::class, 'json');

        // mettre à jour l'objet taste pour mettre la date de création, d'update et le status
        $taste->setCreatedAt();
        $taste->setUpdatedAt();
        $taste->setStatus('active');

        $errors = $validatorInterface->validate($taste);
        if (count($errors)) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }

        $manager->persist($taste);
        $manager->flush();

        $tagAwareCacheInterface->invalidateTags(['tastesCache']);

        $jsonTaste = $serializerInterface->serialize($taste, 'json', ['groups' => 'getTaste']);
        $location = $urlGeneratorInterface->generate(
            TasteController::CONTROLLER_NAME_PREFIX . 'get',
            ['id' => $taste->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        return new JsonResponse($jsonTaste, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * This method is able to update a taste by his Id.
     * @OA\Parameter(name="id", in="path", description="Id of the taste", required=true, @OA\Schema(type="integer"))
     * @OA\Parameter(name="name", in="query", description="Name of the taste", required=false,
     *  @OA\Schema(type="string")
     * )
     * @OA\Parameter(name="description", in="query", description="Description of the taste", required=false,
     *  @OA\Schema(type="string")
     * )
     * @OA\Parameter(name="intensity", in="query", description="intensity of thhis taste", required=false,
     *  @OA\Schema(type="integer")
     * )
     * @OA\Parameter(name="caffeineRate", in="query", description="caffeine rate of this taste", required=false,
     *  @OA\Schema(type="float")
     * )
     * @OA\Tag(name="Taste")
     */
    #[Route(
        TasteController::API_GATEWAY . '/taste/{id}',
        name: TasteController::CONTROLLER_NAME_PREFIX . 'update',
        methods: ['PUT']
    )]
    public function updateTaste(Taste $taste, Request $request, SerializerInterface $serializerInterface,
    EntityManagerInterface $manager, ValidatorInterface $validatorInterface,
    TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $serializerInterface->deserialize(
            $request->getContent(),
            Taste::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $taste]
        );

        // mettre à jour l'objet taste pour mettre la date de création, d'update et le status
        $taste->setUpdatedAt();

        $errors = $validatorInterface->validate($taste);
        if (count($errors)) {
            return new JsonResponse($serializerInterface->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }

        $manager->persist($taste);
        $manager->flush();

        $tagAwareCacheInterface->invalidateTags(['tastesCache', 'coffeesCache']);

        $jsonTaste = $serializerInterface->serialize($taste, 'json', ['groups' => 'getTaste']);
        return new JsonResponse($jsonTaste, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method soft delete a Taste with the ID, can be forced.
     * @OA\Parameter(name="id", in="path", description="Id of the taste", required=true, @OA\Schema(type="integer"))
     * @OA\Parameter(name="isForced", in="path", description="Force or not the delete of a taste", required=true,
     *  @OA\Schema(type="string", enum={"1", "true", "oui", "yes", "forced", "vrai", "force"})
     * )
     * @OA\Tag(name="Taste")
     */
    #[Route(
        TasteController::API_GATEWAY . '/taste/{id}/{isForced}',
        name: TasteController::CONTROLLER_NAME_PREFIX . 'delete_forced',
        methods: ['DELETE']
    )]
    public function deleteTasteIsForced(Taste $taste, string $isForced, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): Response
    {
        $tagToInvalidate = ['tastesCache'];
        $forcedVar = ['1', 'true', 'oui', 'yes', 'forced', 'vrai', 'force'];

        if (in_array(strtolower($isForced), $forcedVar)) {
            $coffees = $taste->getCoffees();
            foreach($coffees as $coffee) {
                $coffee->setCategory(null);
                $coffee->setUpdatedAt();
                $coffee->setStatus('inactive');

                $manager->persist($coffee);
            }

            $manager->remove($taste);
            $tagToInvalidate[] = 'coffeesCache';
        } else {
            $taste->setStatus('active')->setUpdatedAt();
            $manager->persist($taste);
        }

        
        $manager->flush();
        $tagAwareCacheInterface->invalidateTags($tagToInvalidate);
        return new Response(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * This method soft delete a Taste with the ID.
     * @OA\Parameter(name="id", in="path", description="Id of the taste", required=true, @OA\Schema(type="integer"))
     * @OA\Tag(name="Taste")
     */
    #[Route(
        TasteController::API_GATEWAY . '/taste/{id}',
        name: TasteController::CONTROLLER_NAME_PREFIX . 'delete',
        methods: ['DELETE']
    )]
    public function deleteTaste(Taste $taste, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): Response
    {
        $taste->setUpdatedAt()->setStatus('inactive');

        $manager->persist($taste);
        $manager->flush();
        
        $tagAwareCacheInterface->invalidateTags(['tastesCache']);
        return new Response(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
