<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
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
public function create(
    Request $request,
    EntityManagerInterface $em,
    CategoryRepository $categoryRepo
): JsonResponse {
    // Vérification de l'utilisateur
    if (!$this->getUser()) {
        return new JsonResponse(['error' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
    }

    // Récupération des champs
    $title = $request->request->get('title');
    $description = $request->request->get('description');
    $author = $request->request->get('author');
    $categoryId = $request->request->get('category_id');

    // Vérification des champs obligatoires
    if (!$title || !$description || !$author || !$categoryId) {
        return new JsonResponse(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Récupération de la catégorie
    $category = $categoryRepo->find($categoryId);
    if (!$category) {
        return new JsonResponse(['error' => 'Invalid category'], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Gestion des images
    $imageFiles = $request->files->get('images');
if ($imageFiles && !is_array($imageFiles)) {
    $imageFiles = [$imageFiles];
}
$uploadedImages = [];

    if ($imageFiles && is_array($imageFiles)) {
        $allowedMimeTypes = ['image/jpeg', 'image/jpg','image/png', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($imageFiles as $imageFile) {
            if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                return new JsonResponse(['error' => 'Unsupported image type'], JsonResponse::HTTP_BAD_REQUEST);
            }

            if (!in_array($imageFile->guessExtension(), $allowedExtensions)) {
                return new JsonResponse(['error' => 'Invalid file extension'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $fileName = uniqid('archive_') . '.' . $imageFile->guessExtension();
            $imageFile->move($this->getParameter('upload_image_directory'), $fileName);

            $uploadedImages[] = '/uploads/images/' . $fileName;
        }
    }

    // Création de l'archive
    $archive = new Archives();
    $archive->setTitle($title);
    $archive->setDescription($description);
    $archive->setAuthor($author);
    $archive->setCreatedAt(new \DateTimeImmutable());
    $status = $this->isGranted('ROLE_ADMIN') ? 'accepted' : 'pending';
    $archive->setStatus($status);
    $archive->setImages($uploadedImages);
    $archive->setCategory($category); // <-- association de la catégorie

    $em->persist($archive);
    $em->flush();

    return new JsonResponse([
        'message' => 'Archive successfully created',
        'archive' => $archive->toArray()
    ], JsonResponse::HTTP_CREATED);
}

    #[Route('/{id}', name: 'update', methods: ['POST'])]
    public function update(Request $request, Archives $archive, EntityManagerInterface $em): JsonResponse
    {
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $author = $request->request->get('author');
    
        if ($title) {
            $archive->setTitle($title);
        }
    
        if ($description) {
            $archive->setDescription($description);
        }
    
        if ($author) {
            $archive->setAuthor($author);
        }

        $imageFile = $request->files->get('image');
        
        if ($imageFile) {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
            return new JsonResponse(['error' => 'Unsupported image type'], JsonResponse::HTTP_BAD_REQUEST);
        }

       if (!in_array($imageFile->guessExtension(), $allowedExtensions)) {
            return new JsonResponse(['error' => 'Invalid file extension'], JsonResponse::HTTP_BAD_REQUEST);
        }
    
        // Suppression de l’ancienne image si elle existe
        $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $archive->getImage();
        if ($archive->getImage() && file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
    
        // Génération d’un nouveau nom de fichier
        $fileName = uniqid('archive_') . '.' . $imageFile->guessExtension();
        $imageFile->move($this->getParameter('upload_image_directory'), $fileName);
        $archive->setImage('/uploads/images/' . $fileName);
        }
        
    
        // Mise à jour des champs
        /*$archive->setTitle($title);
        $archive->setDescription($description);
        $archive->setAuthor($author);*/
    
        $em->flush();
    
        return new JsonResponse([
            'message' => 'Archive updated',
            'archive_id' => $archive->getId()
        ], JsonResponse::HTTP_OK);
    }
    


    
    #[Route('', name: 'list', methods: ['GET'])]
    public function index(ArchivesRepository $repo): JsonResponse
    {
        $archives = $repo->findBy(['status' => 'accepted']);
        $data = array_map(fn($a) => $a->toArray(), $archives);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    #[Route('/admin/pending', name: 'pending', methods: ['GET'])]
    public function getPendingArchives(ArchivesRepository $repo): JsonResponse
    {
        $pending = $repo->findBy(['status' => 'pending']);
        if (empty($pending)) {
            return new JsonResponse(['message' => 'No pending archives'], JsonResponse::HTTP_OK);
        }
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
    public function review(Request $request, Archives $archive, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $action = $data['status'] ?? null;

        if ($action === 'accept') {
            $archive->setStatus("accepted");
            $em->flush();
            return new JsonResponse(['message' => 'Archive accepted'], JsonResponse::HTTP_OK);
        }
        if ($action === 'reject') {
            $em->remove($archive);
            $em->flush();
            return new JsonResponse(['message' => 'Archive rejected and deleted'], JsonResponse::HTTP_OK);
        }

        return new JsonResponse(['error' => 'Invalid action'], JsonResponse::HTTP_BAD_REQUEST);
    }
}