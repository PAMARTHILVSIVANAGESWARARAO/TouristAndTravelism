<?php

namespace TravelAI\Controllers;

use TravelAI\Services\AuthService;
use TravelAI\Utils\ResponseHelper;
use TravelAI\Middleware\JwtMiddleware;

class AuthController
{
    private AuthService $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    public function register(): void
    {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['name']) || !isset($input['email']) || !isset($input['password'])) {
            ResponseHelper::error('Name, email, and password are required', 400);
        }
        
        $name = trim($input['name']);
        $email = trim($input['email']);
        $password = $input['password'];
        
        // Validate fields
        if (empty($name) || empty($email) || empty($password)) {
            ResponseHelper::error('All fields must be non-empty', 400);
        }
        
        if (strlen($password) < 6) {
            ResponseHelper::error('Password must be at least 6 characters long', 400);
        }
        
        // Attempt registration
        $result = $this->authService->register($name, $email, $password);
        
        if (!$result) {
            ResponseHelper::error('Email already exists or registration failed', 409);
        }
        
        ResponseHelper::created([
            'user' => [
                'userId' => $result['userId'],
                'name' => $result['name'],
                'email' => $result['email']
            ],
            'tokens' => [
                'accessToken' => $result['accessToken'],
                'refreshToken' => $result['refreshToken']
            ]
        ], 'User registered successfully');
    }
    
    public function login(): void
    {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['email']) || !isset($input['password'])) {
            ResponseHelper::error('Email and password are required', 400);
        }
        
        $email = trim($input['email']);
        $password = $input['password'];
        
        // Attempt login
        $result = $this->authService->login($email, $password);
        
        if (!$result) {
            ResponseHelper::error('Invalid email or password', 401);
        }
        
        ResponseHelper::success([
            'user' => [
                'userId' => $result['userId'],
                'name' => $result['name'],
                'email' => $result['email']
            ],
            'tokens' => [
                'accessToken' => $result['accessToken'],
                'refreshToken' => $result['refreshToken']
            ]
        ], 'Login successful');
    }
    
    public function profile(): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Get user profile
        $profile = $this->authService->getProfile($userId);
        
        if (!$profile) {
            ResponseHelper::notFound('User not found');
        }
        
        ResponseHelper::success($profile, 'Profile retrieved successfully');
    }
    
    public function refresh(): void
    {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['refreshToken'])) {
            ResponseHelper::error('Refresh token is required', 400);
        }
        
        $refreshToken = $input['refreshToken'];
        
        // Attempt to refresh
        $result = $this->authService->refreshAccessToken($refreshToken);
        
        if (!$result) {
            ResponseHelper::unauthorized('Invalid or expired refresh token');
        }
        
        ResponseHelper::success($result, 'Token refreshed successfully');
    }
}