<?php

namespace TravelAI\Routes;

use TravelAI\Controllers\AuthController;
use TravelAI\Controllers\TripController;
use TravelAI\Controllers\PhotoController;
use TravelAI\Utils\ResponseHelper;

class RouteConfig
{
    private string $requestMethod;
    private string $requestUri;
    
    public function __construct()
    {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->requestUri = $this->getRequestUri();
    }
    
    public function handleRequest(): void
    {
        // Match routes
        $this->matchRoute();
    }
    
    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove base path if exists (e.g., /index.php)
        $uri = str_replace('/index.php', '', $uri);
        
        return rtrim($uri, '/');
    }
    
    private function matchRoute(): void
    {
        $method = $this->requestMethod;
        $uri = $this->requestUri;
        
        // Auth routes
        if ($uri === '/api/auth/register' && $method === 'POST') {
            $controller = new AuthController();
            $controller->register();
            return;
        }
        
        if ($uri === '/api/auth/login' && $method === 'POST') {
            $controller = new AuthController();
            $controller->login();
            return;
        }
        
        if ($uri === '/api/auth/profile' && $method === 'GET') {
            $controller = new AuthController();
            $controller->profile();
            return;
        }
        
        if ($uri === '/api/auth/refresh' && $method === 'POST') {
            $controller = new AuthController();
            $controller->refresh();
            return;
        }
        
        // Trip routes
        if ($uri === '/api/trips/plan' && $method === 'POST') {
            $controller = new TripController();
            $controller->planTrip();
            return;
        }
        
        if ($uri === '/api/trips' && $method === 'POST') {
            $controller = new TripController();
            $controller->createTrip();
            return;
        }
        
        if ($uri === '/api/trips' && $method === 'GET') {
            $controller = new TripController();
            $controller->getTrips();
            return;
        }
        
        // Trip by ID routes
        if (preg_match('#^/api/trips/([a-f0-9]{24})$#', $uri, $matches) && $method === 'GET') {
            $controller = new TripController();
            $controller->getTrip($matches[1]);
            return;
        }
        
        if (preg_match('#^/api/trips/([a-f0-9]{24})/status$#', $uri, $matches) && $method === 'PATCH') {
            $controller = new TripController();
            $controller->updateTripStatus($matches[1]);
            return;
        }
        
        if (preg_match('#^/api/trips/([a-f0-9]{24})$#', $uri, $matches) && $method === 'DELETE') {
            $controller = new TripController();
            $controller->deleteTrip($matches[1]);
            return;
        }
        
        // Photo routes
        if (preg_match('#^/api/trips/([a-f0-9]{24})/photos$#', $uri, $matches) && $method === 'POST') {
            $controller = new PhotoController();
            $controller->uploadPhoto($matches[1]);
            return;
        }
        
        if (preg_match('#^/api/trips/([a-f0-9]{24})/photos$#', $uri, $matches) && $method === 'GET') {
            $controller = new PhotoController();
            $controller->getTripPhotos($matches[1]);
            return;
        }
        
        if ($uri === '/api/photos' && $method === 'GET') {
            $controller = new PhotoController();
            $controller->getUserPhotos();
            return;
        }
        
        if (preg_match('#^/api/photos/([a-f0-9]{24})$#', $uri, $matches) && $method === 'DELETE') {
            $controller = new PhotoController();
            $controller->deletePhoto($matches[1]);
            return;
        }
        
        // Health check
        if ($uri === '/api/health' && $method === 'GET') {
            ResponseHelper::success(['status' => 'healthy'], 'API is running');
            return;
        }
        
        // 404 Not Found
        ResponseHelper::notFound('Endpoint not found');
    }
}