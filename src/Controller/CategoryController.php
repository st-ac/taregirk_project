<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/category')]
class CategoryController extends AbstractController
{
    #[Route('/create', name: 'category_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $title = trim($request->request->get('title'));
        $description = trim($request->request->get('description'));

        if (!$title || !$description) {
            return new JsonResponse(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setTitle($title);
        $category->setDescription($description);

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $filename = uniqid() . '.' . $imageFile->guessExtension();
            try {
                $imageFile->move($this->getParameter('uploads_category'), $filename);
                $category->setImage($filename);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Image upload failed'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $em->persist($category);
        $em->flush();

        return new JsonResponse([
            'status' => 'Category created',
            'category' => [
                'id' => $category->getId(),
                'title' => $category->getTitle()
            ]
        ], JsonResponse::HTTP_CREATED);
    }


    #[Route('', name: 'category_list', methods: ['GET'])]
public function index(CategoryRepository $repo): JsonResponse
{
    $categories = $repo->findAll();

    $data = array_map(fn($cat) => $cat->toArray(), $categories);

    return $this->json($data);
}

#[Route('/{id}', name: 'category_show', methods: ['GET'])]
public function show(?Category $category): JsonResponse
{
    if (!$category) {
        return $this->json(['error' => 'Catégorie introuvable'], 404);
    }

    $archives = [];
    foreach ($category->getArchives() as $archive) {
        $archives[] = [
            'id' => $archive->getId(),
            'title' => $archive->getTitle(),
            'description' => $archive->getDescription(),
            'author' => $archive->getAuthor(),
            'status' => $archive->getStatus(),
            'createdAt' => $archive->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    return $this->json([
        'id' => $category->getId(),
        'title' => $category->getTitle(),
        'description' => $category->getDescription(),
        'archives' => $archives,
    ]);
}
}