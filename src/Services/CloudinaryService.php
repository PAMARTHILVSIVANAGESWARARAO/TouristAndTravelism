<?php

namespace TravelAI\Services;

use TravelAI\Config\EnvConfig;
use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;

class CloudinaryService
{
    private Cloudinary $cloudinary;
    
    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => EnvConfig::getCloudinaryCloudName(),
                'api_key' => EnvConfig::getCloudinaryApiKey(),
                'api_secret' => EnvConfig::getCloudinaryApiSecret()
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }
    
    public function uploadTripPhoto(array $file, string $userId, string $tripId): ?array
    {
        try {
            // Validate file
            if (!$this->isValidImage($file)) {
                return null;
            }
            
            // Generate folder path
            $folder = "travel-ai/user_{$userId}/trip_{$tripId}";
            
            // Upload to Cloudinary
            $result = $this->cloudinary->uploadApi()->upload(
                $file['tmp_name'],
                [
                    'folder' => $folder,
                    'resource_type' => 'image',
                    'transformation' => [
                        'quality' => 'auto',
                        'fetch_format' => 'auto'
                    ],
                    'overwrite' => false,
                    'unique_filename' => true
                ]
            );
            
            return [
                'imageUrl' => $result['secure_url'],
                'publicId' => $result['public_id'],
                'format' => $result['format'],
                'width' => $result['width'],
                'height' => $result['height'],
                'bytes' => $result['bytes']
            ];
        } catch (\Exception $e) {
            error_log("Cloudinary upload error: " . $e->getMessage());
            return null;
        }
    }
    
    public function deletePhoto(string $publicId): bool
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId);
            return $result['result'] === 'ok';
        } catch (\Exception $e) {
            error_log("Cloudinary delete error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deletePhotoByUrl(string $imageUrl): bool
    {
        try {
            // Extract public_id from Cloudinary URL
            $publicId = $this->extractPublicIdFromUrl($imageUrl);
            
            if (!$publicId) {
                return false;
            }
            
            return $this->deletePhoto($publicId);
        } catch (\Exception $e) {
            error_log("Cloudinary delete by URL error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteTripFolder(string $userId, string $tripId): bool
{
    try {
        $folder = "travel-ai/user_{$userId}/trip_{$tripId}";

        // 1. Delete all resources inside the folder
        $this->cloudinary->adminApi()->deleteAssets([
            'prefix' => $folder
        ]);

        // 2. Delete the folder itself
        $this->cloudinary->adminApi()->deleteFolder($folder);

        return true;
    } catch (\Exception $e) {
        error_log("Cloudinary folder delete error: " . $e->getMessage());
        return false;
    }
}

    
    private function isValidImage(array $file): bool
    {
        // Check if file exists
        if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            return false;
        }
        
        // Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        // Check MIME type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        return in_array($mimeType, $allowedTypes);
    }
    
    private function extractPublicIdFromUrl(string $imageUrl): ?string
    {
        // Cloudinary URL format: https://res.cloudinary.com/{cloud_name}/image/upload/v{version}/{public_id}.{format}
        // Extract public_id from URL
        
        $pattern = '/\/upload\/(?:v\d+\/)?(.+)\.\w+$/';
        if (preg_match($pattern, $imageUrl, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}