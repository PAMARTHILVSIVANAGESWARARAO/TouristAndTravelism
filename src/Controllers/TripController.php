<?php

namespace TravelAI\Controllers;

use TravelAI\Services\TripService;
use TravelAI\Services\GeminiService;
use TravelAI\Utils\ResponseHelper;
use TravelAI\Middleware\JwtMiddleware;

class TripController
{
    private TripService $tripService;
    private GeminiService $geminiService;
    
    public function __construct()
    {
        $this->tripService = new TripService();
        $this->geminiService = new GeminiService();
    }
    
    /**
     * POST /api/trips/plan
     * Generate trip plan using Gemini AI (does not save to DB)
     */
    public function planTrip(): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['startPlace']) || !isset($input['destination'])) {
            ResponseHelper::error('startPlace and destination are required', 400);
        }
        
        $startPlace = trim($input['startPlace']);
        $destination = trim($input['destination']);
        
        if (empty($startPlace) || empty($destination)) {
            ResponseHelper::error('startPlace and destination must be non-empty', 400);
        }
        
        // Generate trip plan using Gemini
        $tripPlan = $this->geminiService->generateTripPlan($startPlace, $destination);
        
        if (!$tripPlan) {
            ResponseHelper::serverError('Failed to generate trip plan. Please try again.');
        }
        
        ResponseHelper::success($tripPlan, 'Trip plan generated successfully');
    }
    
    /**
     * POST /api/trips
     * Save trip plan to database
     */
    public function createTrip(): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['startPlace']) || !isset($input['destination'])) {
            ResponseHelper::error('startPlace and destination are required', 400);
        }
        
        // Create trip
        $trip = $this->tripService->createTrip($userId, $input);
        
        if (!$trip) {
            ResponseHelper::serverError('Failed to create trip');
        }
        
        ResponseHelper::created($trip, 'Trip created successfully');
    }
    
    /**
     * GET /api/trips
     * Get all trips for logged-in user
     */
    public function getTrips(): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Get query parameters
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        $options = [
            'sort' => ['createdAt' => -1],
            'limit' => $limit
        ];
        
        // Get trips
        $trips = $this->tripService->getUserTrips($userId, $options);
        
        // Filter by status if provided
        if ($status) {
            $trips = array_filter($trips, function($trip) use ($status) {
                return $trip['status'] === $status;
            });
            $trips = array_values($trips); // Re-index array
        }
        
        ResponseHelper::success($trips, 'Trips retrieved successfully');
    }
    
    /**
     * GET /api/trips/{tripId}
     * Get single trip by ID
     */
    public function getTrip(string $tripId): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Get trip
        $trip = $this->tripService->getTripById($tripId, $userId);
        
        if (!$trip) {
            ResponseHelper::notFound('Trip not found or access denied');
        }
        
        ResponseHelper::success($trip, 'Trip retrieved successfully');
    }
    
    /**
     * PATCH /api/trips/{tripId}/status
     * Update trip status
     */
    public function updateTripStatus(string $tripId): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['status'])) {
            ResponseHelper::error('Status is required', 400);
        }
        
        $status = $input['status'];
        
        // Update status
        $success = $this->tripService->updateTripStatus($tripId, $userId, $status);
        
        if (!$success) {
            ResponseHelper::error('Failed to update trip status. Invalid status or access denied.', 400);
        }
        
        ResponseHelper::success(['status' => $status], 'Trip status updated successfully');
    }
    
    /**
     * DELETE /api/trips/{tripId}
     * Delete trip and associated photos
     */
    public function deleteTrip(string $tripId): void
    {
        // Get userId from JWT middleware
        $userId = JwtMiddleware::getUserId();
        
        if (!$userId) {
            ResponseHelper::unauthorized();
        }
        
        // Delete trip
        $success = $this->tripService->deleteTrip($tripId, $userId);
        
        if (!$success) {
            ResponseHelper::error('Failed to delete trip or access denied', 403);
        }
        
        ResponseHelper::success(null, 'Trip deleted successfully');
    }
}