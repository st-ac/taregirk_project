<?php

namespace App\Controller;

use App\Entity\Archives;
use App\Repository\ArchivesRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/archives', name: 'archives_')]
final class ArchivesController extends AbstractController
{
    // URL absolue pour images (pour le tableau "images")
    private function absUrl(string $relPathOrName): string
    {
        $rel = str_starts_with($relPathOrName, '/uploads/')
            ? $relPathOrName
            : '/uploads/images/' . ltrim($relPathOrName, '/');
        return 'http://127.0.0.1:8000' . $rel;
    }

    // Chemin relatif pour "image" (home)
    private function relPath(string $nameOrPath): string
    {
        return str_starts_with($nameOrPath, '/uploads/')
            ? $nameOrPath
            : '/uploads/images/' . ltrim($nameOrPath, '/');
    }

    // Sérialisation UNIFORME (pas de toArray dans l’entité)
    private function serializeArchive(Archives $a): array
    {
        $names = $a->getImages() ?? [];
        if (!is_array($names)) $names = $names ? [$names] : [];

        $relList = array_map(fn($n) => $this->relPath((string)$n), $names);
        $absList = array_map(fn($rel) => $this->absUrl($rel), $relList);

        return [
            'id'         => $a->getId(),
            'title'      => $a->getTitle(),
            'description'=> $a->getDescription(),
            'author'     => $a->getAuthor(),
            'status'     => $a->getStatus(),
            'createdAt'  => $a->getCreatedAt()?->format('Y-m-d H:i:s'),
            'image'      => $relList[0] ?? null,  // home
            'images'     => $absList,             // ArchiveList + page détail
            'category'   => $a->getCategory()?->getTitle(),
            'user'       => $a->getUser()?->getUsername(),
        ];
    }

    #[Route('', name: 'list', methods: ['GET'])]
public function index(Request $request, ArchivesRepository $repo): JsonResponse
{
    $status = $request->query->get('status', 'accepted');
    $search = trim((string) $request->query->get('search', ''));

    // Filtres optionnels
    $cat = $request->query->get('category');
    $uid = $request->query->get('userId');

    // Pas de recherche → logique existante (findBy)
    if ($search === '') {
        $criteria = ['status' => $status];
        if ($cat) $criteria['category'] = $cat;
        if ($uid) $criteria['user']     = $uid;

        $list = $repo->findBy($criteria, ['createdAt' => 'DESC']);
        return $this->json(array_map(fn($a) => $this->serializeArchive($a), $list));
    }

    // Avec recherche → QueryBuilder (title/description/author)
    $qb = $repo->createQueryBuilder('a')
        ->andWhere('a.status = :status')->setParameter('status', $status)
        ->andWhere('LOWER(a.title) LIKE :q OR LOWER(a.description) LIKE :q OR LOWER(a.author) LIKE :q')
        ->setParameter('q', '%'.mb_strtolower($search).'%')
        ->orderBy('a.createdAt', 'DESC');

    if ($cat) $qb->andWhere('a.category = :cat')->setParameter('cat', $cat);
    if ($uid) $qb->andWhere('a.user = :uid')->setParameter('uid', $uid);

    $list = $qb->getQuery()->getResult();
    return $this->json(array_map(fn($a) => $this->serializeArchive($a), $list));
}

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, ArchivesRepository $repo): JsonResponse
    {
        $a = $repo->find($id);
        if (!$a) return $this->json(['error' => 'Archive not found'], 404);
        return $this->json($this->serializeArchive($a));
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, CategoryRepository $catRepo): JsonResponse
    {
        if (!$this->getUser()) {
            return $this->json(['error' => 'User not authenticated'], 401);
        }

        $title       = $request->request->get('title');
        $description = $request->request->get('description');
        $author      = $request->request->get('author');
        $categoryId  = $request->request->get('category_id');

        if (!$title || !$description || !$author || !$categoryId) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        $category = $catRepo->find($categoryId);
        if (!$category) {
            return $this->json(['error' => 'Invalid category'], 400);
        }

        $files = $request->files->all('images') ?: $request->files->get('images', []);
        if (!is_array($files)) $files = [$files];

        $allowedMime = ['image/jpeg','image/png','image/webp','image/gif'];
        $allowedExt  = ['jpg','jpeg','png','webp','gif'];
        $uploadDir   = rtrim((string)$this->getParameter('upload_image_directory'), DIRECTORY_SEPARATOR);

        $names = [];
        foreach ($files as $file) {
            if (!$file) continue;
            if (!in_array($file->getMimeType(), $allowedMime, true)) {
                return $this->json(['error' => 'Unsupported image type'], 400);
            }
            $ext = $file->guessExtension();
            if (!in_array($ext, $allowedExt, true)) {
                return $this->json(['error' => 'Invalid file extension'], 400);
            }
            $name = uniqid('archive_') . '.' . $ext;
            $file->move($uploadDir, $name);
            $names[] = $name; // on stocke UNIQUEMENT le nom
        }

        $a = new Archives();
        $a->setTitle($title);
        $a->setDescription($description);
        $a->setAuthor($author);
        $a->setCreatedAt(new \DateTimeImmutable());
        $a->setStatus($this->isGranted('ROLE_ADMIN') ? 'accepted' : 'pending');
        $a->setImages($names);
        $a->setCategory($category);
        $a->setUser($this->getUser());

        $em->persist($a);
        $em->flush();

        return $this->json(['message' => 'Archive created', 'archive' => $this->serializeArchive($a)], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['POST'])]
    public function update(Request $request, Archives $archive, EntityManagerInterface $em): JsonResponse
    {
        $title       = $request->request->get('title');
        $description = $request->request->get('description');
        $author      = $request->request->get('author');

        if ($title)       $archive->setTitle($title);
        if ($description) $archive->setDescription($description);
        if ($author)      $archive->setAuthor($author);

        $files = $request->files->all('images') ?: $request->files->get('images', []);
        if (!is_array($files)) $files = [$files];

        if (count($files) > 0) {
            $allowedMime = ['image/jpeg','image/png','image/webp','image/gif'];
            $allowedExt  = ['jpg','jpeg','png','webp','gif'];
            $uploadDir   = rtrim((string)$this->getParameter('upload_image_directory'), DIRECTORY_SEPARATOR);

            // supprimer anciennes
            foreach (($archive->getImages() ?? []) as $old) {
                $path = $uploadDir . DIRECTORY_SEPARATOR . ltrim((string)$old, DIRECTORY_SEPARATOR);
                if (is_file($path)) @unlink($path);
            }

            $names = [];
            foreach ($files as $file) {
                if (!$file) continue;
                if (!in_array($file->getMimeType(), $allowedMime, true)) {
                    return $this->json(['error' => 'Unsupported image type'], 400);
                }
                $ext = $file->guessExtension();
                if (!in_array($ext, $allowedExt, true)) {
                    return $this->json(['error' => 'Invalid file extension'], 400);
                }
                $name = uniqid('archive_') . '.' . $ext;
                $file->move($uploadDir, $name);
                $names[] = $name;
            }
            $archive->setImages($names);
        }

        $em->flush();

        return $this->json(['message' => 'Archive updated', 'archive' => $this->serializeArchive($archive)]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Archives $archive, EntityManagerInterface $em): JsonResponse
    {
        $uploadDir = rtrim((string)$this->getParameter('upload_image_directory'), DIRECTORY_SEPARATOR);

        foreach (($archive->getImages() ?? []) as $name) {
            $path = $uploadDir . DIRECTORY_SEPARATOR . ltrim((string)$name, DIRECTORY_SEPARATOR);
            if (is_file($path)) @unlink($path);
        }

        $em->remove($archive);
        $em->flush();

        return $this->json(['message' => 'Archive deleted']);
    }

    #[Route('/admin/{id}/review', name: 'review', methods: ['PATCH'])]
    public function review(Request $request, Archives $archive, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $action = $data['status'] ?? null;

        if ($action === 'accept') {
            $archive->setStatus('accepted');
            $em->flush();
            return $this->json(['message' => 'Archive accepted']);
        }

        if ($action === 'reject') {
            $uploadDir = rtrim((string)$this->getParameter('upload_image_directory'), DIRECTORY_SEPARATOR);
            foreach (($archive->getImages() ?? []) as $name) {
                $path = $uploadDir . DIRECTORY_SEPARATOR . ltrim((string)$name, DIRECTORY_SEPARATOR);
                if (is_file($path)) @unlink($path);
            }
            $em->remove($archive);
            $em->flush();
            return $this->json(['message' => 'Archive rejected and deleted']);
        }

        return $this->json(['error' => 'Invalid action'], 400);
    }
}