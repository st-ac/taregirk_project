<?php

namespace App\Controller;

use App\Entity\Citation;
use App\Entity\Archives;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/citations')]
class CitationController extends AbstractController
{
    #[Route('', name: 'citation_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $citations = $em->getRepository(Citation::class)->findBy([], ['createdAt' => 'DESC']);

        $data = array_map(fn($c) => $c->toArray(), $citations);
        return $this->json($data);
    }

    #[Route('/create', name: 'citation_create', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
public function create(Request $request, EntityManagerInterface $em): JsonResponse
{
    $description = trim((string) $request->request->get('description'));
    $author      = trim((string) $request->request->get('author'));
    $archiveId   = (int) $request->request->get('archive_id');

    if (!$description || !$author || !$archiveId) {
        return new JsonResponse(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
    }

    /** @var Archives|null $archive */
    $archive = $em->getRepository(Archives::class)->find($archiveId);
    if (!$archive) {
        return new JsonResponse(['error' => 'Invalid archive_id'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $imageFile = $request->files->get('image');
    if (!$imageFile) {
        return new JsonResponse(['error' => 'Image is required'], JsonResponse::HTTP_BAD_REQUEST);
    }

    $filename = uniqid('citation_') . '.' . $imageFile->guessExtension();
    try {
        $imageFile->move($this->getParameter('upload_image_directory'), $filename);
    } catch (\Exception $e) {
        return new JsonResponse(['error' => 'Image upload failed'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    $citation = new Citation();
    $citation->setDescription($description);
    $citation->setAuthor($author);
    $citation->setArchive($archive);
    $citation->setImage($filename); 

    $em->persist($citation);
    $em->flush();

    return new JsonResponse([
        'status'   => 'Citation created',
        'citation' => [
            'id'          => $citation->getId(),
            'description' => $citation->getDescription(),
        ],
    ], JsonResponse::HTTP_CREATED);
}

    #[Route('/{id}', name: 'citation_delete', methods: ['DELETE'])]
    public function delete(Citation $citation, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($citation);
        $em->flush();

        return $this->json(['message' => 'Citation supprimée']);
    }
}