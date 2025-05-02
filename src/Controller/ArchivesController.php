<?php

namespace App\Controller;

use App\Entity\Archives;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ArchivesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ArchivesController extends AbstractController
{
    #[Route('/api/archives', name: 'archives_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
                try {
                    // Récupération des champs
                    $titre = $request->request->get('titre');
                    $description = $request->request->get('description');
                    $auteur = $request->request->get('auteur');
                    $imageFile = $request->files->get('image');
        
                    // Vérification des champs obligatoires
                    if (!$titre || !$description || !$auteur || !$imageFile) {
                        return new JsonResponse(['error' => 'Champs manquants'], JsonResponse::HTTP_BAD_REQUEST);
                    }
        
                    // Vérification du type de fichier image
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                        return new JsonResponse(['error' => 'Type d\'image non supporté'], JsonResponse::HTTP_BAD_REQUEST);
                    }
        
                    // Génère un nom de fichier unique
                    $fileName = uniqid('archive_') . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('upload_image_directory'), $fileName);
        
                    // Création de l'entité Archives
                    $archive = new Archives();
                    $archive->setTitre($titre);
                    $archive->setDescription($description);
                    $archive->setAuteur($auteur);
                    $archive->setImage('/uploads/images/' . $fileName); // lien relatif
                    $archive->setCreatedAt(new \DateTimeImmutable());
        
                    $em->persist($archive);
                    $em->flush();
        
                    return new JsonResponse([
                        'message' => 'Archive créée avec succès',
                        'archive' => [
                            'id' => $archive->getId(),
                            'titre' => $archive->getTitre(),
                            'image' => $archive->getImage(),
                        ]
                    ], JsonResponse::HTTP_CREATED);
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'Erreur lors de la création'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        

    #[Route('', name: 'archives_list', methods: ['GET'])]
    public function index(ArchivesRepository $repo): JsonResponse
    {
        $archives = $repo->findAll();
        $data = array_map(fn($archive) => [
            'id' => $archive->getId(),
            'title' => $archive->getTitle(),
            'description' => $archive->getDescription(),
            'image' => $archive->getImage(),
            'author' => $archive->getAuthor(),
            'createdAt' => $archive->getCreatedAt()->format('Y-m-d H:i:s')
        ], $archives);

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }

    

    #[Route('/{id}', name: 'archives_show', methods: ['GET'])]
    public function show(Archives $archive): JsonResponse
    {
        return new JsonResponse([
            'title' => $archive->getTitle(),
            'description' => $archive->getDescription(),
            'image' => $archive->getImage(),
            'author' => $archive->getAuthor(),
            'createdAt' => $archive->getCreatedAt()->format('Y-m-d H:i:s')
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'archives_update', methods: ['PUT'])]
    public function update(Request $request, Archives $archive, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $archive->setTitle($data['title']);
        $archive->setDescription($data['description']);
        $archive->setImage($data['image']);
        $archive->setAuthor($data['author']);

        $em->flush();

        return new JsonResponse(['status' => 'Archive updated'], JsonResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'archives_delete', methods: ['DELETE'])]
    public function delete(Archives $archive, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($archive);
        $em->flush();

        return new JsonResponse(['status' => 'Archive deleted'], JsonResponse::HTTP_OK);
    }
}
