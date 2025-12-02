<?php

namespace TravelAI\Middleware;

use TravelAI\Services\JwtService;
use TravelAI\Utils\ResponseHelper;

class JwtMiddleware
{
    private JwtService $jwtService;
    
    public function __construct()
    {
        $this->jwtService = new JwtService();
    }
    
    public function handle(): ?string
    {
        // Get Authorization header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        
        if (!$authHeader) {
            ResponseHelper::unauthorized('Authorization header missing');
            return null;
        }
        
        // Extract token from "Bearer <token>"
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            ResponseHelper::unauthorized('Invalid authorization format. Use: Bearer <token>');
            return null;
        }
        
        $token = $matches[1];
        
        // Verify token
        $decoded = $this->jwtService->verifyToken($token);
        
        if (!$decoded) {
            ResponseHelper::unauthorized('Invalid or expired token');
            return null;
        }
        
        // Check if token has expired
        if (isset($decoded->exp) && $decoded->exp < time()) {
            ResponseHelper::unauthorized('Token has expired');
            return null;
        }
        
        // Return userId to be used in controllers
        return $decoded->userId;
    }
    
    public static function getUserId(): ?string
    {
        $middleware = new self();
        return $middleware->handle();
    }
}