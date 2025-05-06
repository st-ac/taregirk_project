<?php

namespace App\Controller;

use App\Entity\Archives;
use App\Repository\ArchivesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/archives', name: 'archives_')]
final class ArchivesController extends AbstractController
{
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        try {
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $author = $request->request->get('author');
            $imageFile = $request->files->get('image');

            if (!$title || !$description || !$author || !$imageFile) {
                return new JsonResponse(['error' => 'Missing fields'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                return new JsonResponse(['error' => 'Unsupported image type'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($imageFile->guessExtension(), $allowedExtensions)) {
    return new JsonResponse(['error' => 'Invalid file extension'], JsonResponse::HTTP_BAD_REQUEST);
}

            $fileName = uniqid('archive_') . '.' . $imageFile->guessExtension();
            $imageFile->move($this->getParameter('upload_image_directory'), $fileName);

            $archive = new Archives();
            $archive->setTitle($title);
            $archive->setDescription($description);
            $archive->setAuthor($author);
            $archive->setImage('/uploads/images/' . $fileName);
            $archive->setCreatedAt(new \DateTimeImmutable());
            $status = $this->isGranted('ROLE_ADMIN') ? true : false;
            $archive->setStatus($status);

            $em->persist($archive);
            $em->flush();

            return new JsonResponse([
                'message' => 'Archive successfully created',
                'archive' => $archive->toArray()
            ], JsonResponse::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error while creating archive'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function index(ArchivesRepository $repo): JsonResponse
    {
        $archives = $repo->findBy(['status' => true]);
        $data = array_map(fn($a) => $a->toArray(), $archives);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/admin/pending', name: 'pending', methods: ['GET'])]
    public function getPendingArchives(ArchivesRepository $repo): JsonResponse
    {
        $pending = $repo->findBy(['status' => false]);
        $data = array_map(fn($a) => $a->toArray(), $pending);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function getArchive(Archives $archive): JsonResponse
    {
        if (!$archive->getStatus()) {
            return new JsonResponse(['error' => 'Archive not yet validated'], JsonResponse::HTTP_NOT_FOUND);
        }
        return new JsonResponse($archive->toArray(), JsonResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Archives $archive, EntityManagerInterface $em): JsonResponse
    {
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $author = $request->request->get('author');
        $imageFile = $request->files->get('image');
    
        if (!$title || !$description || !$author) {
            return new JsonResponse(['error' => 'Missing fields'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Vérification du type de fichier image
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
            return new JsonResponse(['error' => 'Unsupported image type'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($imageFile->guessExtension(), $allowedExtensions)) {
    return new JsonResponse(['error' => 'Invalid file extension'], JsonResponse::HTTP_BAD_REQUEST);
}
    
        // Suppression de l’ancienne image si elle existe
        $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $archive->getImage();
        if (file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
    
        // Génération d’un nouveau nom de fichier
        $fileName = uniqid('archive_') . '.' . $imageFile->guessExtension();
        $imageFile->move($this->getParameter('upload_image_directory'), $fileName);
    
        // Mise à jour des champs
        $archive->setTitle($title);
        $archive->setDescription($description);
        $archive->setAuthor($author);
        $archive->setImage('/uploads/images/' . $fileName);
    
        $em->flush();
    
        return new JsonResponse(['message' => 'Archive updated'], JsonResponse::HTTP_OK);
    }
    

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Archives $archive, EntityManagerInterface $em): JsonResponse
    {
        $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $archive->getImage();
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    
        $em->remove($archive);
        $em->flush();
    
        return new JsonResponse(['message' => 'Archive deleted'], JsonResponse::HTTP_OK);
    }

    #[Route('/admin/{id}/review', name: 'review', methods: ['PATCH'])]
    public function reviewArchive(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $archive = $this->findArchiveOr404($id, $em);
        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? null;

        if (!in_array($action, ['accept', 'reject'], true)) {
            return new JsonResponse(['error' => 'Invalid action'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($action === 'accept') {
            $archive->setStatus(true);
            $em->flush();
            return new JsonResponse(['message' => 'Archive accepted']);
        }

        $em->remove($archive);
        $em->flush();
        return new JsonResponse(['message' => 'Archive rejected and deleted']);
    }

    private function findArchiveOr404(int $id, EntityManagerInterface $em): Archives
    {
        $archive = $em->getRepository(Archives::class)->find($id);
        if (!$archive) {
            throw $this->createNotFoundException('Archive not found');
        }
        return $archive;
    }
}
