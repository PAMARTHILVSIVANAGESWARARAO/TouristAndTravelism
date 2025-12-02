<?php

namespace TravelAI\Services;

use TravelAI\Models\UserPhotoModel;
use TravelAI\Models\TripModel;

class PhotoService
{
    private UserPhotoModel $photoModel;
    private TripModel $tripModel;
    private CloudinaryService $cloudinaryService;
    
    public function __construct()
    {
        $this->photoModel = new UserPhotoModel();
        $this->tripModel = new TripModel();
        $this->cloudinaryService = new CloudinaryService();
    }
    
    public function uploadPhoto(string $userId, string $tripId, array $file, ?string $caption = null): ?array
    {
        // Check if trip belongs to user
        if (!$this->tripModel->belongsToUser($tripId, $userId)) {
            return null;
        }
        
        // Upload to Cloudinary
        $uploadResult = $this->cloudinaryService->uploadTripPhoto($file, $userId, $tripId);
        
        if (!$uploadResult) {
            return null;
        }
        
        // Save to database
        $photoData = [
            'userId' => $userId,
            'tripId' => $tripId,
            'imageUrl' => $uploadResult['imageUrl'],
            'caption' => $caption
        ];
        
        $photo = $this->photoModel->create($photoData);
        
        if (!$photo) {
            // If database save fails, delete from Cloudinary
            $this->cloudinaryService->deletePhotoByUrl($uploadResult['imageUrl']);
            return null;
        }
        
        return $photo;
    }
    
    public function getTripPhotos(string $tripId, string $userId): ?array
    {
        // Check if trip belongs to user
        if (!$this->tripModel->belongsToUser($tripId, $userId)) {
            return null;
        }
        
        return $this->photoModel->findByTripId($tripId);
    }
    
    public function getUserPhotos(string $userId): array
    {
        return $this->photoModel->findByUserId($userId);
    }
    
    public function deletePhoto(string $photoId, string $userId): bool
    {
        // Check if photo belongs to user
        if (!$this->photoModel->belongsToUser($photoId, $userId)) {
            return false;
        }
        
        // Get photo details
        $photo = $this->photoModel->findById($photoId);
        
        if (!$photo) {
            return false;
        }
        
        // Delete from Cloudinary
        $this->cloudinaryService->deletePhotoByUrl($photo['imageUrl']);
        
        // Delete from database
        return $this->photoModel->delete($photoId);
    }
    
    public function validatePhotoOwnership(string $photoId, string $userId): bool
    {
        return $this->photoModel->belongsToUser($photoId, $userId);
    }
}