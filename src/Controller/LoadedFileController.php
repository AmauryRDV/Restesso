<?php

namespace App\Controller;

use App\Entity\LoadedFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class LoadedFileController extends AbstractController
{
    #[Route('/loaded/file', name: 'app_loaded_file')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/LoadedFileController.php',
        ]);
    }

    #[Route('/api/files', name: 'loadedFile.getAll', methods: ['GET'])]
    public function getAllLoadedFiles(EntityManagerInterface $entityManagerInterface,
    SerializerInterface $serializerInterface): JsonResponse
    {
        $loadedFiles = $entityManagerInterface->getRepository(LoadedFile::class)->findAll();
        $jsonFiles = $serializerInterface->serialize($loadedFiles, 'json');
        return new JsonResponse($jsonFiles, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/files/{id}', name: 'loadedFile.get', methods: ['GET'])]
    public function getLoadedFile(LoadedFile $loadedFile, SerializerInterface $serializerInterface,
    UrlGeneratorInterface $urlGeneratorInterface): JsonResponse
    {
        $loadedFiles = $loadedFile->getPublicPath();
        $location = $urlGeneratorInterface->generate('loadedFile.create', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $location = $location . str_replace('/public', '', $loadedFiles . '/' . $loadedFile->getRealPath());

        $jsonFiles = $serializerInterface->serialize($loadedFile, 'json');
        return $jsonFiles ?
        new JsonResponse($jsonFiles, JsonResponse::HTTP_OK, ['Location' => $location], true) :
        new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
    }

    #[Route('/api/files', name: 'loadedFile.create', methods: ['POST'])]
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
        $loadedFile->setCreatedAt()->setUpdatedAt()->setStatus('on');

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
