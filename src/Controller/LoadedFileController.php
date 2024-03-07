<?php

namespace App\Controller;

use App\Entity\LoadedFile;
use App\Repository\LoadedFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use OpenApi\Annotations as OA;


class LoadedFileController extends AbstractController
{
    private const API_GATEWAY = '/api/v1';
    private const CONTROLLER_NAME_PREFIX = 'loadedFile_';

    /**
     * This method return all the files availables.
     * @OA\Response(response=200, description="Return files",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=LoadedFile::class, groups={"getAllLoadedFiles"})))
     * )
     * @OA\Tag(name="LoadedFile")
     * @Security(name="Bearer")
     */
    #[Route(
        LoadedFileController::API_GATEWAY . '/files',
        name: LoadedFileController::CONTROLLER_NAME_PREFIX . 'getAll',
        methods: ['GET']
    )]
    public function getAllLoadedFiles(SerializerInterface $serializerInterface,
    LoadedFileRepository $loadedFileRepository): JsonResponse
    {
        $loadedFiles = $loadedFileRepository->findAllActive();
        $jsonFiles = $serializerInterface->serialize($loadedFiles, 'json', ['groups' => 'getAllLoadedFiles']);
        return new JsonResponse($jsonFiles, JsonResponse::HTTP_OK, [], true);
    }

    /**
     * This method return a file by his ID if he is available.
     * @OA\Parameter(name="id", in="path", description="ID of the file", required=true, @OA\Schema(type="integer"))
     * @OA\Response(response=200, description="Return a file",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=LoadedFile::class, groups={"getLoadedFile"})))
     * )
     * @OA\Tag(name="LoadedFile")
     * @Security(name="Bearer")
     */
    #[Route(
        LoadedFileController::API_GATEWAY . '/files/{id}',
        name: LoadedFileController::CONTROLLER_NAME_PREFIX . 'get',
        methods: ['GET']
    )]
    public function getLoadedFile(int $id, SerializerInterface $serializerInterface,
    UrlGeneratorInterface $urlGeneratorInterface, LoadedFileRepository $loadedFileRepository): JsonResponse
    {
        $loadedFile = $loadedFileRepository->findActive($id);
        $loadedFiles = $loadedFile->getPublicPath();
        $location = $urlGeneratorInterface->generate(
            LoadedFileController::CONTROLLER_NAME_PREFIX . 'create',
            [], UrlGeneratorInterface::ABSOLUTE_URL
        );
        $location = $location . str_replace('/public', '', $loadedFiles . '/' . $loadedFile->getRealPath());

        $jsonFiles = $serializerInterface->serialize($loadedFile, 'json', ['groups' => 'getLoadedFile']);
        return $jsonFiles ?
        new JsonResponse($jsonFiles, JsonResponse::HTTP_OK, ['Location' => $location], true) :
        new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
    }

    /**
     * This method create a new file.
     * @OA\RequestBody(
     *  request="createLoadedFile",
     *  required=true,
     *  description="The file to create",
     *  @OA\JsonContent(
     *      required={"file"},
     *      @OA\Property(property="file", type="string", format="binary")
     *  )
     * )
     * @OA\Response(response=201, description="Return the created file",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=LoadedFile::class, groups={"getLoadedFile"})))
     * )
     * @OA\Tag(name="LoadedFile")
     * @Security(name="Bearer")
     */
    #[Route(
        LoadedFileController::API_GATEWAY . '/files',
        name: LoadedFileController::CONTROLLER_NAME_PREFIX . 'create',
        methods: ['POST']
    )]
    public function createLoadedFile(Request $request, EntityManagerInterface $entityManagerInterface,
    SerializerInterface $serializerInterface, UrlGeneratorInterface $urlGeneratorInterface): JsonResponse
    {
        $loadedFile = new LoadedFile();
        $file = $request->files->get('file');

        $loadedFile->setFile($file);
        $loadedFile->setRealName($file->getClientOriginalName());
        $loadedFile->setName($file->getClientOriginalName());
        $loadedFile->setMimeType($file->getMimeType());
        $loadedFile->setPublicPath("/public/medias/pictures");
        $loadedFile->setCreatedAt()->setUpdatedAt()->setStatus('active');

        $entityManagerInterface->persist($loadedFile);
        $entityManagerInterface->flush();

        $jsonFile = $serializerInterface->serialize($loadedFile, 'json');
        $location = $urlGeneratorInterface->generate(
            'loadedFile.get',
            ['id' => $loadedFile->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($jsonFile, JsonResponse::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     * This method update a file by his ID.
     * @OA\Parameter(name="id", in="path", description="ID of the file", required=true, @OA\Schema(type="integer"))
     * @OA\RequestBody(
     *  request="updateLoadedFile",
     *  required=true,
     *  description="The file to update",
     *  @OA\JsonContent(
     *      required={"file"},
     *      @OA\Property(property="file", type="string", format="binary")
     *  )
     * )
     * @OA\Response(response=200, description="Return the updated file",
     *  @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=LoadedFile::class, groups={"getLoadedFile"})))
     * )
     * @OA\Tag(name="LoadedFile")
     * @Security(name="Bearer")
     */
    public function updateLoadedFile(): JsonResponse
    {
        return new JsonResponse(null, JsonResponse::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * This method soft delete a file with the ID, can be forced.
     * @OA\Parameter(name="id", in="path", description="Id of the file", required=true, @OA\Schema(type="integer"))
     * @OA\Parameter(name="isForced", in="path", description="Force or not the delete of a file", required=true,
     *  @OA\Schema(type="string", enum={"1", "true", "oui", "yes", "forced", "vrai", "force"})
     * )
     * @OA\Response(response=204, description="No content")
     * @OA\Tag(name="LoadedFile")
     * @Security(name="Bearer")
     */
    #[Route(
        LoadedFileController::API_GATEWAY . '/files/{id}/{isForced}',
        name: LoadedFileController::CONTROLLER_NAME_PREFIX . 'delete_forced',
        methods: ['DELETE']
    )]
    public function deleteLoadedFileIsForced(LoadedFile $loadedFile, string $isForced, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $tagToInvalidate = ['loadedFile'];
        $forcedVar = ['1', 'true', 'oui', 'yes', 'forced', 'vrai', 'force'];

        if (in_array(strtolower($isForced), $forcedVar)) {
            $coffees = $loadedFile->getCoffees();
            foreach($coffees as $coffee) {
                $loadedFile->removeCoffee($coffee);
                $coffee->setUpdatedAt()->setStatus('inactive');
                $manager->persist($coffee);
            }

            $manager->remove($loadedFile);
            $tagToInvalidate[] = 'coffeesCache';
        } else {
            $loadedFile->setStatus('inactive')->setUpdatedAt();
            $manager->persist($loadedFile);
        }

        
        $manager->flush();
        $tagAwareCacheInterface->invalidateTags($tagToInvalidate);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], true);
    }

    /**
     * This method soft delete a LoadedFile with the ID.
     * @OA\Parameter(
     *  name="id",
     *  in="path",
     *  description="Id of the loadedfile",
     *  required=true,
     *  @OA\Schema(type="integer")
     * )
     * @OA\Response(response=204, description="No content")
     * @OA\Tag(name="LoadedFile")
     * @Security(name="Bearer")
     */
    #[Route(
        LoadedFileController::API_GATEWAY . '/files/{id}',
        name: LoadedFileController::CONTROLLER_NAME_PREFIX . 'delete',
        methods: ['DELETE']
    )]
    public function deleteLoadedFile(LoadedFile $loadedFile, EntityManagerInterface $manager,
    TagAwareCacheInterface $tagAwareCacheInterface): JsonResponse
    {
        $loadedFile->setStatus('inactive')->setUpdatedAt();
        $manager->persist($loadedFile);
        $manager->flush();

        $tagAwareCacheInterface->invalidateTags(['loadedFile']);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT, [], true);
    }
}
