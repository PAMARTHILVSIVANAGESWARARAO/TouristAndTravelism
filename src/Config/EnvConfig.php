<?php

namespace TravelAI\Config;

use Dotenv\Dotenv;

class EnvConfig
{
    private static ?EnvConfig $instance = null;

    private function __construct()
    {
        // Load .env only in local development
        if (getenv('APP_ENV') !== 'production') {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad();
        }
    }

    public static function getInstance(): EnvConfig
    {
        if (self::$instance === null) {
            self::$instance = new EnvConfig();
        }
        return self::$instance;
    }

    public static function get(string $key, $default = null)
    {
        self::getInstance();
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    public static function getMongoDbUri(): string
    {
        return self::get('MONGODB_URI');
    }

    public static function getMongoDbDatabase(): string
    {
        return self::get('MONGODB_DATABASE', 'travel_ai_db');
    }

    public static function getJwtSecret(): string
    {
        return self::get('JWT_SECRET');
    }

    public static function getJwtExpiry(): int
    {
        return (int) self::get('JWT_EXPIRY', 3600);
    }

    public static function getJwtRefreshExpiry(): int
    {
        return (int) self::get('JWT_REFRESH_EXPIRY', 86400);
    }

    public static function getCloudinaryCloudName(): string
    {
        return self::get('CLOUDINARY_CLOUD_NAME');
    }

    public static function getCloudinaryApiKey(): string
    {
        return self::get('CLOUDINARY_API_KEY');
    }

    public static function getCloudinaryApiSecret(): string
    {
        return self::get('CLOUDINARY_API_SECRET');
    }

    public static function getGeminiApiKey(): string
    {
        return self::get('GEMINI_API_KEY');
    }

    public static function getGeminiModel(): string
    {
        return self::get('GEMINI_MODEL', 'gemini-pro');
    }

    public static function getCorsAllowedOrigins(): array
    {
        $origins = self::get('CORS_ALLOWED_ORIGINS', 'http://localhost:3000');
        return explode(',', $origins);
    }

    public static function isDebug(): bool
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }
}
