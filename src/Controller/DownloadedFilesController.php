<?php

namespace App\Controller;

use App\Entity\DownloadedFiles;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class DownloadedFilesController extends AbstractController
{
    #[Route('/', name: 'app_downloaded_files')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/DownloadedFilesController.php',
        ]);
    }

    #[Route('/api/v1/files/{downloadedFile}', name: 'files.get', methods: ['GET'])]
    public function getDownloadedFile(
        DownloadedFiles $downloadedFiles,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
    ): JsonResponse 
    {
        $publicPath = $downloadedFiles->getPublicPath();
        $location = $urlGenerator->generate('app_downloaded_files', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $location = $location . str_replace("/public/","",$publicPath ."/".$downloadedFiles->getRealPath());

        $jsonFiles = $serializer->serialize($downloadedFiles,"json");
        $location = $urlGenerator->generate('file.get');
        return $downloadedFiles ? new JsonResponse($jsonFiles, JsonResponse::HTTP_OK,["Location"=>$location],true) :
        new JsonResponse(null, JsonResponse::HTTP_NOT_FOUND);
    }


    #[Route('/api/v1/files', name: 'app_downloaded_files', methods: ['POST'])]
    public function createDownloadedFile(
    
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator,
    ): JsonResponse 
    {
        $dowloadFile = new DownloadedFiles();
        $file = $request->files->get('file');
        $dowloadFile->setFile($file);
        $dowloadFile->setMimeType($file->getClientMimetype());
        $dowloadFile->setRealName($file->getClientOriginalName());
        $dowloadFile->setName($file->getClientOriginalName());
        $dowloadFile->setPublicPath("/public/medias/pictures");
        // $dowloadFile->setUpdatedAt(new \DateTime())->setCreatedAt(new \DateTime())->setStatus("on");
        $entityManager->persist($dowloadFile);
        $entityManager->flush();
        $jsonFiles = $serializer->serialize($dowloadFile,"json");
        $location = $urlGenerator->generate('file.get', ["downloadedFiles" => $dowloadFile->getId() ],UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonFiles, JsonResponse::HTTP_CREATED,["Location" => $location],true);
    }
}
