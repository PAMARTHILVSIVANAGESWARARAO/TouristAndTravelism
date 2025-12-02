<?php

namespace TravelAI\Models;

use TravelAI\Config\DatabaseConfig;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class UserPhotoModel
{
    private Collection $collection;
    
    public function __construct()
    {
        $db = DatabaseConfig::getInstance();
        $this->collection = $db->getCollection('userPhotos');
    }
    
    public function create(array $photoData): ?array
    {
        $document = [
            'userId' => $photoData['userId'],
            'tripId' => $photoData['tripId'],
            'imageUrl' => $photoData['imageUrl'],
            'caption' => $photoData['caption'] ?? null,
            'createdAt' => new UTCDateTime()
        ];
        
        try {
            $result = $this->collection->insertOne($document);
            $document['_id'] = $result->getInsertedId();
            return $this->formatPhoto($document);
        } catch (\Exception $e) {
            // Handle duplicate constraint
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                return null;
            }
            throw $e;
        }
    }
    
    public function findById(string $photoId): ?array
    {
        try {
            $photo = $this->collection->findOne(['_id' => new ObjectId($photoId)]);
            
            if (!$photo) {
                return null;
            }
            
            return $this->formatPhoto($photo);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function findByTripId(string $tripId): array
    {
        $cursor = $this->collection->find(
            ['tripId' => $tripId],
            ['sort' => ['createdAt' => -1]]
        );
        
        $photos = [];
        foreach ($cursor as $photo) {
            $photos[] = $this->formatPhoto($photo);
        }
        
        return $photos;
    }
    
    public function findByUserId(string $userId): array
    {
        $cursor = $this->collection->find(
            ['userId' => $userId],
            ['sort' => ['createdAt' => -1]]
        );
        
        $photos = [];
        foreach ($cursor as $photo) {
            $photos[] = $this->formatPhoto($photo);
        }
        
        return $photos;
    }
    
    public function delete(string $photoId): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($photoId)]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function deleteByTripId(string $tripId): int
    {
        try {
            $result = $this->collection->deleteMany(['tripId' => $tripId]);
            return $result->getDeletedCount();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    public function belongsToUser(string $photoId, string $userId): bool
    {
        try {
            $photo = $this->collection->findOne([
                '_id' => new ObjectId($photoId),
                'userId' => $userId
            ]);
            return $photo !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function formatPhoto($photo): array
    {
        return [
            '_id' => (string) $photo['_id'],
            'userId' => $photo['userId'],
            'tripId' => $photo['tripId'],
            'imageUrl' => $photo['imageUrl'],
            'caption' => $photo['caption'] ?? null,
            'createdAt' => $photo['createdAt']->toDateTime()->format('Y-m-d H:i:s')
        ];
    }
}