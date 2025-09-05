<?php

class FileUploader {
    private $uploadDir;

    public function __construct($uploadDir = 'uploads/screenshots') {
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/' . trim($uploadDir, '/');
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function saveBase64Image($base64String) {
        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64String));
        $filename = uniqid() . '.png';
        $filepath = $this->uploadDir . '/' . $filename;
        
        file_put_contents($filepath, $image);
        
        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $filepath);
    }
}
