<?php

namespace TravelAI\Services;

use TravelAI\Models\UserModel;

class AuthService
{
    private UserModel $userModel;
    private JwtService $jwtService;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->jwtService = new JwtService();
    }
    
    public function register(string $name, string $email, string $password): ?array
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        // Check if user already exists
        if ($this->userModel->findByEmail($email)) {
            return null;
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Create user
        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'passwordHash' => $passwordHash
        ]);
        
        if (!$userId) {
            return null;
        }
        
        // Generate tokens
        $accessToken = $this->jwtService->generateToken($userId, $email);
        $refreshToken = $this->jwtService->generateRefreshToken($userId);
        
        return [
            'userId' => $userId,
            'name' => $name,
            'email' => $email,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken
        ];
    }
    
    public function login(string $email, string $password): ?array
    {
        // Find user by email
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            return null;
        }
        
        // Verify password
        if (!password_verify($password, $user['passwordHash'])) {
            return null;
        }
        
        // Update last login
        $this->userModel->updateLastLogin($user['_id']);
        
        // Generate tokens
        $accessToken = $this->jwtService->generateToken($user['_id'], $user['email']);
        $refreshToken = $this->jwtService->generateRefreshToken($user['_id']);
        
        return [
            'userId' => $user['_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken
        ];
    }
    
    public function getProfile(string $userId): ?array
    {
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            return null;
        }
        
        // Remove sensitive data
        unset($user['passwordHash']);
        
        return $user;
    }
    
    public function refreshAccessToken(string $refreshToken): ?array
    {
        $decoded = $this->jwtService->verifyToken($refreshToken);
        
        if (!$decoded || !isset($decoded->userId)) {
            return null;
        }
        
        // Check if it's a refresh token
        if (!isset($decoded->type) || $decoded->type !== 'refresh') {
            return null;
        }
        
        $user = $this->userModel->findById($decoded->userId);
        
        if (!$user) {
            return null;
        }
        
        // Generate new access token
        $accessToken = $this->jwtService->generateToken($user['_id'], $user['email']);
        
        return [
            'accessToken' => $accessToken
        ];
    }
}