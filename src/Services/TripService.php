<?php

namespace TravelAI\Services;

use TravelAI\Models\TripModel;
use TravelAI\Models\UserPhotoModel;

class TripService
{
    private TripModel $tripModel;
    private UserPhotoModel $photoModel;
    private CloudinaryService $cloudinaryService;
    
    public function __construct()
    {
        $this->tripModel = new TripModel();
        $this->photoModel = new UserPhotoModel();
        $this->cloudinaryService = new CloudinaryService();
    }
    
    public function createTrip(string $userId, array $tripData): ?array
    {
        // Validate required fields
        if (empty($tripData['startPlace']) || empty($tripData['destination'])) {
            return null;
        }
        
        return $this->tripModel->create($userId, $tripData);
    }
    
    public function getTripById(string $tripId, string $userId): ?array
    {
        $trip = $this->tripModel->findById($tripId);
        
        if (!$trip) {
            return null;
        }
        
        // Check ownership
        if ($trip['userId'] !== $userId) {
            return null;
        }
        
        return $trip;
    }
    
    public function getUserTrips(string $userId, array $options = []): array
    {
        return $this->tripModel->findByUserId($userId, $options);
    }
    
    public function updateTripStatus(string $tripId, string $userId, string $status): bool
    {
        // Validate status
        $allowedStatuses = ['planned', 'completed'];
        if (!in_array($status, $allowedStatuses)) {
            return false;
        }
        
        // Check ownership
        if (!$this->tripModel->belongsToUser($tripId, $userId)) {
            return false;
        }
        
        return $this->tripModel->updateStatus($tripId, $status);
    }
    
    public function deleteTrip(string $tripId, string $userId): bool
    {
        // Check ownership
        if (!$this->tripModel->belongsToUser($tripId, $userId)) {
            return false;
        }
        
        // Get all photos for this trip
        $photos = $this->photoModel->findByTripId($tripId);
        
        // Delete photos from Cloudinary
        foreach ($photos as $photo) {
            $this->cloudinaryService->deletePhotoByUrl($photo['imageUrl']);
        }
        
        // Delete photos from database
        $this->photoModel->deleteByTripId($tripId);
        
        // Delete trip folder from Cloudinary
        $this->cloudinaryService->deleteTripFolder($userId, $tripId);
        
        // Delete trip from database
        return $this->tripModel->delete($tripId);
    }
    
    public function validateTripOwnership(string $tripId, string $userId): bool
    {
        return $this->tripModel->belongsToUser($tripId, $userId);
    }
}