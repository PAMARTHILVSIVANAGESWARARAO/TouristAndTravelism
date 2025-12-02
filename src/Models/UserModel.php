<?php

namespace TravelAI\Models;

use TravelAI\Config\DatabaseConfig;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class UserModel
{
    private Collection $collection;
    
    public function __construct()
    {
        $db = DatabaseConfig::getInstance();
        $this->collection = $db->getCollection('users');
    }
    
    public function create(array $userData): ?string
    {
        $document = [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'passwordHash' => $userData['passwordHash'],
            'createdAt' => new UTCDateTime(),
            'updatedAt' => new UTCDateTime()
        ];
        
        try {
            $result = $this->collection->insertOne($document);
            return (string) $result->getInsertedId();
        } catch (\Exception $e) {
            // Handle duplicate email error
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                return null;
            }
            throw $e;
        }
    }
    
    public function findByEmail(string $email): ?array
    {
        $user = $this->collection->findOne(['email' => $email]);
        
        if (!$user) {
            return null;
        }
        
        return $this->formatUser($user);
    }
    
    public function findById(string $userId): ?array
    {
        try {
            $user = $this->collection->findOne(['_id' => new ObjectId($userId)]);
            
            if (!$user) {
                return null;
            }
            
            return $this->formatUser($user);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function updateLastLogin(string $userId): bool
    {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new ObjectId($userId)],
                ['$set' => ['lastLogin' => new UTCDateTime()]]
            );
            return $result->getModifiedCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function formatUser($user): array
    {
        return [
            '_id' => (string) $user['_id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'passwordHash' => $user['passwordHash'],
            'createdAt' => $user['createdAt']->toDateTime()->format('Y-m-d H:i:s'),
            'updatedAt' => $user['updatedAt']->toDateTime()->format('Y-m-d H:i:s')
        ];
    }
}