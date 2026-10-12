<?php
// api/upload.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Target upload folders
$targetDirReact = '../../my-react-app/public/uploads/';
$targetDirBackend = '../uploads/';

if (!is_dir($targetDirReact)) {
    mkdir($targetDirReact, 0777, true);
}
if (!is_dir($targetDirBackend)) {
    mkdir($targetDirBackend, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileKey = isset($_FILES['file']) ? 'file' : (isset($_FILES['image']) ? 'image' : null);

    if (!$fileKey || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = "No file uploaded or file upload error.";
        if ($fileKey && isset($_FILES[$fileKey]['error'])) {
            $errorMsg .= " Code: " . $_FILES[$fileKey]['error'];
        }
        echo json_encode(["status" => "error", "message" => $errorMsg]);
        exit();
    }

    $fileTmpPath = $_FILES[$fileKey]['tmp_name'];
    $fileNameOriginal = $_FILES[$fileKey]['name'];
    $fileSize = $_FILES[$fileKey]['size'];
    $fileExtension = strtolower(pathinfo($fileNameOriginal, PATHINFO_EXTENSION));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(["status" => "error", "message" => "Only JPG, PNG, GIF, WEBP and SVG formats are allowed."]);
        exit();
    }

    // Generate safe clean unique filename
    $cleanBaseName = preg_replace('/[^A-Za-z0-9_-]/', '', pathinfo($fileNameOriginal, PATHINFO_FILENAME));
    $newFileName = time() . '_' . substr($cleanBaseName, 0, 30) . '.' . $fileExtension;

    $destReact = $targetDirReact . $newFileName;
    $destBackend = $targetDirBackend . $newFileName;

    // Move to React public/uploads/
    if (move_uploaded_file($fileTmpPath, $destReact)) {
        // Also copy to backend uploads directory
        @copy($destReact, $destBackend);

        $publicUrl = '/uploads/' . $newFileName;

        // Build absolute URL for full flexibility
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $backendUrl = $protocol . $host . '/assinment/php-backend/uploads/' . $newFileName;

        echo json_encode([
            "status" => "success",
            "message" => "Image uploaded successfully.",
            "url" => $publicUrl,
            "full_url" => $backendUrl,
            "filename" => $newFileName
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to move uploaded file."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>
