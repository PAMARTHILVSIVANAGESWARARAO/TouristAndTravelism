<?php

namespace TravelAI\Controllers;

use TravelAI\Services\PhotoService;
use TravelAI\Utils\ResponseHelper;
use TravelAI\Middleware\JwtMiddleware;

class PhotoController
{
    private PhotoService $photoService;
    
    public function __construct()
    {
        $this->photoService = new PhotoService();
    }
    
    /**
     * POST /api/trips/{tripId}/photos
     * Upload photo for a specific trip
     */
    public function uploadPhoto(string $tripId): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            ResponseHelper::error('Photo file is required', 400);
        }
        
        $file = $_FILES['photo'];
        $caption = $_POST['caption'] ?? null;
        
        // Upload photo
        $photo = $this->photoService->uploadPhoto($userId, $tripId, $file, $caption);
        
        if (!$photo) {
            ResponseHelper::error('Failed to upload photo. Invalid file or access denied.', 400);
        }
        
        ResponseHelper::created($photo, 'Photo uploaded successfully');
    }
    
    /**
     * GET /api/trips/{tripId}/photos
     * Get all photos for a specific trip
     */
    public function getTripPhotos(string $tripId): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Get photos
        $photos = $this->photoService->getTripPhotos($tripId, $userId);
        
        if ($photos === null) {
            ResponseHelper::forbidden('Access denied to this trip');
        }
        
        ResponseHelper::success($photos, 'Photos retrieved successfully');
    }
    
    /**
     * GET /api/photos
     * Get all photos for logged-in user
     */
    public function getUserPhotos(): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Get photos
        $photos = $this->photoService->getUserPhotos($userId);
        
        ResponseHelper::success($photos, 'User photos retrieved successfully');
    }
    
    /**
     * DELETE /api/photos/{photoId}
     * Delete a specific photo
     */
    public function deletePhoto(string $photoId): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Delete photo
        $success = $this->photoService->deletePhoto($photoId, $userId);
        
        if (!$success) {
            ResponseHelper::error('Failed to delete photo or access denied', 403);
        }
        
        ResponseHelper::success(null, 'Photo deleted successfully');
    }
}