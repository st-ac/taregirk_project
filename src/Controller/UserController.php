<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'taregirk_')]
final class UserController extends AbstractController 
{
    #[Route('/register', name: 'create_user', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Récupère les données JSON de la requête
        $data = json_decode($request->getContent(), true);
        // Vérifie si les données sont valides
        if (empty($data['userName']) || empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }
        // Crée un nouvel utilisateur
        $user = new User();
        $user->setUserName($data['userName']);
        $user->setEmail($data['email']);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        // Enregistre l'utilisateur dans la base de données
        $em->persist($user);
        $em->flush();

        // Retourne une confirmation de création
        return new JsonResponse([
            'status' => 'User created'
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/users', name: 'user_list', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        // Récupère tous les utilisateurs
        $users = $userRepository->findAll();
        $data = array_map(function (User $user) {
            return [
                'userName' => $user->getUserName(), 
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }, $users);
        // Retourne la liste des utilisateurs
        return new JsonResponse($data, JsonResponse::HTTP_OK);
}
    #[Route('/user/{id}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        // Retourne les détails d'un utilisateur
        return new JsonResponse([
            'userName' => $user->getUserName(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], JsonResponse::HTTP_OK);
    }
    #[Route('/users/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(Request $request, User $user, EntityManagerInterface $em): JsonResponse
    {
        // Récupère les données JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Met à jour l'utilisateur
        $user->setUserName($data['userName']);
        $user->setEmail($data['email']);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        // Enregistre les modifications dans la base de données
        $em->flush();

        // Retourne une confirmation de mise à jour
        return new JsonResponse(['status' => 'User updated'], JsonResponse::HTTP_OK);
    }
    #[Route('/users/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $em): JsonResponse
    {
        // Supprime l'utilisateur
        $em->remove($user);
        $em->flush();

        // Retourne une confirmation de suppression
        return new JsonResponse(['status' => 'User deleted'], JsonResponse::HTTP_OK);
    }
}