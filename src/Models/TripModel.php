<?php

namespace TravelAI\Models;

use TravelAI\Config\DatabaseConfig;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class TripModel
{
    private Collection $collection;
    
    public function __construct()
    {
        $db = DatabaseConfig::getInstance();
        $this->collection = $db->getCollection('trips');
    }
    
    public function create(string $userId, array $tripData): ?array
    {
        $document = [
            'userId' => $userId,
            'startPlace' => $tripData['startPlace'],
            'destination' => $tripData['destination'],
            'budget' => $tripData['budget'] ?? null,
            'flights' => $tripData['flights'] ?? [],
            'locations' => $tripData['locations'] ?? [],
            'seasonInfo' => $tripData['seasonInfo'] ?? null,
            'itinerary' => $tripData['itinerary'] ?? [],
            'status' => 'planned',
            'createdAt' => new UTCDateTime(),
            'updatedAt' => new UTCDateTime()
        ];
        
        try {
            $result = $this->collection->insertOne($document);
            $document['_id'] = $result->getInsertedId();
            return $this->formatTrip($document);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function findById(string $tripId): ?array
    {
        try {
            $trip = $this->collection->findOne(['_id' => new ObjectId($tripId)]);
            
            if (!$trip) {
                return null;
            }
            
            return $this->formatTrip($trip);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function findByUserId(string $userId, array $options = []): array
    {
        $sort = $options['sort'] ?? ['createdAt' => -1];
        $limit = $options['limit'] ?? 0;
        
        $cursor = $this->collection->find(
            ['userId' => $userId],
            [
                'sort' => $sort,
                'limit' => $limit
            ]
        );
        
        $trips = [];
        foreach ($cursor as $trip) {
            $trips[] = $this->formatTrip($trip);
        }
        
        return $trips;
    }
    
    public function updateStatus(string $tripId, string $status): bool
    {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($tripId)],
                [
                    '$set' => [
                        'status' => $status,
                        'updatedAt' => new UTCDateTime()
                    ]
                ]
            );
            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function delete(string $tripId): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($tripId)]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function belongsToUser(string $tripId, string $userId): bool
    {
        try {
            $trip = $this->collection->findOne([
                '_id' => new ObjectId($tripId),
                'userId' => $userId
            ]);
            return $trip !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function formatTrip($trip): array
    {
        return [
            '_id' => (string) $trip['_id'],
            'userId' => $trip['userId'],
            'startPlace' => $trip['startPlace'],
            'destination' => $trip['destination'],
            'budget' => $trip['budget'],
            'flights' => $trip['flights'],
            'locations' => $trip['locations'],
            'seasonInfo' => $trip['seasonInfo'],
            'itinerary' => $trip['itinerary'],
            'status' => $trip['status'],
            'createdAt' => $trip['createdAt']->toDateTime()->format('Y-m-d H:i:s'),
            'updatedAt' => $trip['updatedAt']->toDateTime()->format('Y-m-d H:i:s')
        ];
    }
}