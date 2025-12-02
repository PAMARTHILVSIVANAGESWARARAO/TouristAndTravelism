<?php

namespace TravelAI\Config;

use TravelAI\Config\EnvConfig;
use MongoDB\Client;
use MongoDB\Database;

class DatabaseConfig
{
    private static ?DatabaseConfig $instance = null;
    private Client $client;
    private Database $database;
    
    private function __construct()
    {
        $uri = EnvConfig::getMongoDbUri();
        $dbName = EnvConfig::getMongoDbDatabase();
        
        $this->client = new Client($uri);
        $this->database = $this->client->selectDatabase($dbName);
        
        // Create indexes on first connection
        $this->createIndexes();
    }
    
    public static function getInstance(): DatabaseConfig
    {
        if (self::$instance === null) {
            self::$instance = new DatabaseConfig();
        }
        return self::$instance;
    }
    
    public function getDatabase(): Database
    {
        return $this->database;
    }
    
    public function getCollection(string $collectionName)
    {
        return $this->database->selectCollection($collectionName);
    }
    
    private function createIndexes(): void
    {
        // Users collection indexes
        $usersCollection = $this->database->selectCollection('users');
        $usersCollection->createIndex(['email' => 1], ['unique' => true]);
        
        // Trips collection indexes
        $tripsCollection = $this->database->selectCollection('trips');
        $tripsCollection->createIndex(['userId' => 1]);
        $tripsCollection->createIndex(['createdAt' => -1]);
        
        // UserPhotos collection indexes
        $photosCollection = $this->database->selectCollection('userPhotos');
        $photosCollection->createIndex(['userId' => 1]);
        $photosCollection->createIndex(['tripId' => 1]);
        $photosCollection->createIndex(
            ['userId' => 1, 'tripId' => 1, 'imageUrl' => 1],
            ['unique' => true]
        );
    }
}