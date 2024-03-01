<?php

namespace App\Controller;

use App\Entity\LoadedFile;
use App\Repository\LoadedFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class LoadedFileController extends AbstractController
{
    private const API_GATEWAY = '/api/v1';
    private const CONTROLLER_NAME_PREFIX = 'loadedFile_';

    #[Route(
        LoadedFileController::API_GATEWAY . '/files',
        name: LoadedFileController::CONTROLLER_NAME_PREFIX . 'getAll',
        methods: ['GET']
    )]
    public function getAllLoadedFiles(SerializerInterface $serializerInterface,
    LoadedFileRepository $loadedFileRepository): JsonResponse
    {
        $loadedFiles = $loadedFileRepository->findAllActive();
        $jsonFiles = $serializerInterface->serialize($loadedFiles, 'json');
        return new JsonResponse($jsonFiles, JsonResponse::HTTP_OK, [], true);
    }

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
}
