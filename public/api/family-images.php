<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration de la base de données
$config = [
    'host' => 'localhost',
    'dbname' => 'casa_home_db',
    'username' => 'root',
    'password' => ''
];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        handleGet($pdo, $action);
        break;
    case 'POST':
        handlePost($pdo, $action);
        break;
    case 'DELETE':
        handleDelete($pdo, $action);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
}

function handleGet($pdo, $action) {
    switch ($action) {
        case 'family_images':
            $familyId = $_GET['family_id'] ?? null;
            if (!$familyId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de famille requis']);
                return;
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM images 
                WHERE family_id = ? 
                ORDER BY is_primary DESC, created_at ASC
            ");
            $stmt->execute([$familyId]);
            $images = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'images' => $images]);
            break;
            
        case 'primary_image':
            $familyId = $_GET['family_id'] ?? null;
            if (!$familyId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de famille requis']);
                return;
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM images 
                WHERE family_id = ? AND is_primary = 1 
                LIMIT 1
            ");
            $stmt->execute([$familyId]);
            $image = $stmt->fetch();
            
            if (!$image) {
                // Retourner une image par défaut
                $image = [
                    'file_path' => '/assets/images/placeholder.jpg',
                    'filename' => 'placeholder.jpg'
                ];
            }
            
            echo json_encode(['success' => true, 'image' => $image]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}

function handlePost($pdo, $action) {
    switch ($action) {
        case 'upload_image':
            $familyId = $_POST['family_id'] ?? null;
            $isPrimary = $_POST['is_primary'] ?? false;
            
            if (!$familyId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de famille requis']);
                return;
            }
            
            if (!isset($_FILES['image'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Aucune image fournie']);
                return;
            }
            
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                http_response_code(400);
                echo json_encode(['error' => 'Type de fichier non autorisé']);
                return;
            }
            
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
                http_response_code(400);
                echo json_encode(['error' => 'Fichier trop volumineux (max 5MB)']);
                return;
            }
            
            // Créer le dossier de destination
            $uploadDir = '../../assets/uploads/families/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Générer un nom de fichier unique
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'family_' . $familyId . '_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Si c'est une image primaire, désactiver les autres
                if ($isPrimary) {
                    $stmt = $pdo->prepare("UPDATE images SET is_primary = 0 WHERE family_id = ?");
                    $stmt->execute([$familyId]);
                }
                
                // Insérer dans la base de données
                $stmt = $pdo->prepare("
                    INSERT INTO images (family_id, filename, original_name, file_path, file_size, mime_type, is_primary)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $relativePath = '/assets/uploads/families/' . $filename;
                $stmt->execute([
                    $familyId,
                    $filename,
                    $file['name'],
                    $relativePath,
                    $file['size'],
                    $file['type'],
                    $isPrimary ? 1 : 0
                ]);
                
                $imageId = $pdo->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Image uploadée avec succès',
                    'image_id' => $imageId,
                    'file_path' => $relativePath
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de l\'upload du fichier']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}

function handleDelete($pdo, $action) {
    switch ($action) {
        case 'delete_image':
            $imageId = $_GET['image_id'] ?? null;
            
            if (!$imageId) {
                http_response_code(400);
                echo json_encode(['error' => 'ID d\'image requis']);
                return;
            }
            
            // Récupérer les informations de l'image
            $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
            $stmt->execute([$imageId]);
            $image = $stmt->fetch();
            
            if (!$image) {
                http_response_code(404);
                echo json_encode(['error' => 'Image non trouvée']);
                return;
            }
            
            // Supprimer le fichier physique
            $filepath = '../../' . ltrim($image['file_path'], '/');
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Supprimer de la base de données
            $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
            $stmt->execute([$imageId]);
            
            echo json_encode(['success' => true, 'message' => 'Image supprimée avec succès']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action non reconnue']);
    }
}
?>
