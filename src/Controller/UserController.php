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
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['error' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }
        // Crée un nouvel utilisateur
        $user = new User();
        $user->setUsername($data['username']);
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
                'username' => $user->getUsername(), 
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
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ], JsonResponse::HTTP_OK);
    }
    #[Route('/users/{id}/password', name: 'user_update_password', methods: ['PUT'])]
    public function updatePassword(int $id, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): JsonResponse 
    {
    $user = $userRepository->find($id);

    if (!$user) {
        return new JsonResponse(['error' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    $currentUser = $this->getUser();
    
    if ($currentUser->getId() !== $user->getId()) {
        return new JsonResponse(['error' => 'Access denied'], JsonResponse::HTTP_FORBIDDEN);
    }

    // Récupérer le JSON
    $data = json_decode($request->getContent(), true);
    if (empty($data['oldPassword']) || empty($data['newPassword'])) {
        return new JsonResponse(['error' => 'Missing fields'], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Vérifier l’ancien mot de passe
    if (!$passwordHasher->isPasswordValid($user, $data['oldPassword'])) {
        return new JsonResponse(['error' => 'Old password is incorrect'], JsonResponse::HTTP_BAD_REQUEST);
    }

    // Hacher et mettre à jour le nouveau mot de passe
    $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
    $user->setPassword($hashedPassword);
    $em->flush();

    return new JsonResponse(['status' => 'Password updated'], JsonResponse::HTTP_OK);
}
    #[Route('/users/{id}', name: 'user_update', methods: ['PUT'])]
    public function update(Request $request, User $user, EntityManagerInterface $em): JsonResponse
    {
        // Récupère les données JSON de la requête
        $data = json_decode($request->getContent(), true);

        // Met à jour l'utilisateur
        $user->setUsername($data['username']);
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