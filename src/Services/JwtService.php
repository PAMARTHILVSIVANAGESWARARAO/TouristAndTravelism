<?php

namespace TravelAI\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use TravelAI\Config\EnvConfig;
use Exception;

class JwtService
{
    private string $secret;
    private int $expiry;
    private string $algorithm = 'HS256';
    
    public function __construct()
    {
        $this->secret = EnvConfig::getJwtSecret();
        $this->expiry = EnvConfig::getJwtExpiry();
    }
    
    public function generateToken(string $userId, string $email): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expiry;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'userId' => $userId,
            'email' => $email
        ];
        
        return JWT::encode($payload, $this->secret, $this->algorithm);
    }
    
    public function generateRefreshToken(string $userId): string
    {
        $issuedAt = time();
        $expire = $issuedAt + EnvConfig::getJwtRefreshExpiry();
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'userId' => $userId,
            'type' => 'refresh'
        ];
        
        return JWT::encode($payload, $this->secret, $this->algorithm);
    }
    
    public function verifyToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function getUserIdFromToken(string $token): ?string
    {
        $decoded = $this->verifyToken($token);
        return $decoded->userId ?? null;
    }
}