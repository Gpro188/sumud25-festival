<?php
/**
 * Photo Upload Handler for Team Leaders
 * This file contains functions for handling team leader photo uploads with validation
 */

class TeamLeaderPhotoHandler {
    private $uploadDir;
    private $maxFileSize;
    private $allowedTypes;
    
    public function __construct() {
        $this->uploadDir = '../uploads/';
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
        $this->allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    }
    
    /**
     * Validate uploaded file
     * @param array $file The $_FILES array element
     * @return array Validation result with success status and message
     */
    public function validateFile($file) {
        // Check if file was uploaded without errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => $this->getUploadErrorMessage($file['error'])
            ];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return [
                'success' => false,
                'message' => 'File size exceeds 5MB limit.'
            ];
        }
        
        // Check file type
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type. Only JPG, PNG, and GIF files are allowed.'
            ];
        }
        
        return ['success' => true, 'message' => 'File is valid.'];
    }
    
    /**
     * Upload photo for team leader
     * @param array $file The $_FILES array element
     * @param int $leaderId The team leader ID
     * @return array Upload result with success status, message, and file path
     */
    public function uploadPhoto($file, $leaderId) {
        // Validate file first
        $validation = $this->validateFile($file);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                return [
                    'success' => false,
                    'message' => 'Failed to create upload directory.'
                ];
            }
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = 'leader_' . $leaderId . '_' . time() . '.' . $fileExtension;
        $uploadPath = $this->uploadDir . $newFilename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return [
                'success' => true,
                'message' => 'Photo uploaded successfully.',
                'filepath' => $newFilename
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error uploading file.'
            ];
        }
    }
    
    /**
     * Delete photo file
     * @param string $filename The filename to delete
     * @return bool True if deleted successfully, false otherwise
     */
    public function deletePhoto($filename) {
        $filePath = $this->uploadDir . $filename;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return true; // File doesn't exist, consider it deleted
    }
    
    /**
     * Get human-readable error message for upload errors
     * @param int $errorCode The PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension.';
            default:
                return 'Unknown upload error.';
        }
    }
    
    /**
     * Get allowed file types for display
     * @return string Comma-separated list of allowed file extensions
     */
    public function getAllowedFileTypes() {
        return 'JPG, PNG, GIF';
    }
    
    /**
     * Get maximum file size for display
     * @return string Human-readable file size limit
     */
    public function getMaxFileSize() {
        return '5MB';
    }
}

// Example usage:
// $photoHandler = new TeamLeaderPhotoHandler();
// 
// if (isset($_FILES['leader_photo'])) {
//     $result = $photoHandler->uploadPhoto($_FILES['leader_photo'], $leaderId);
//     if ($result['success']) {
//         // Update database with $result['filepath']
//         echo "Photo uploaded successfully!";
//     } else {
//         echo "Error: " . $result['message'];
//     }
// }
?>