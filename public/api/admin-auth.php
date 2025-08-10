<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Charger le fichier JSON des administrateurs
function loadAdminUsers() {
    $jsonFile = '../data/admin_users.json';
    if (!file_exists($jsonFile)) {
        return ['error' => 'Fichier de configuration administrateur non trouvé'];
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Erreur de format JSON'];
    }
    
    return $data;
}

// Fonction pour valider les identifiants
function validateAdminCredentials($email, $password) {
    $data = loadAdminUsers();
    
    if (isset($data['error'])) {
        return $data;
    }
    
    $admins = $data['admins'] ?? [];
    
    foreach ($admins as $admin) {
        if (strtolower($admin['email']) === strtolower($email)) {
            // Vérifier le statut du compte
            if ($admin['status'] !== 'active') {
                return ['error' => 'Ce compte administrateur est désactivé'];
            }
            
            // Vérifier le mot de passe
            if ($admin['password'] === $password) {
                // Retourner les données de l'admin (sans le mot de passe)
                $adminData = $admin;
                unset($adminData['password']);
                
                // Ajouter un timestamp de connexion
                $adminData['login_timestamp'] = date('Y-m-d H:i:s');
                
                return [
                    'success' => true,
                    'admin' => $adminData,
                    'message' => 'Connexion réussie'
                ];
            } else {
                return ['error' => 'Mot de passe incorrect'];
            }
        }
    }
    
    return ['error' => 'Aucun compte administrateur trouvé avec cette adresse email'];
}

// Fonction pour obtenir la liste des administrateurs (pour les super admins)
function getAdminList() {
    $data = loadAdminUsers();
    
    if (isset($data['error'])) {
        return $data;
    }
    
    $admins = $data['admins'] ?? [];
    
    // Masquer les mots de passe
    foreach ($admins as &$admin) {
        unset($admin['password']);
    }
    
    return [
        'success' => true,
        'admins' => $admins,
        'permissions' => $data['permissions'] ?? [],
        'settings' => $data['settings'] ?? []
    ];
}

// Traitement des requêtes
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Données invalides']);
            exit();
        }
        
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $email = $input['email'] ?? '';
                $password = $input['password'] ?? '';
                
                if (empty($email) || empty($password)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Email et mot de passe requis']);
                    exit();
                }
                
                $result = validateAdminCredentials($email, $password);
                
                if (isset($result['error'])) {
                    http_response_code(401);
                    echo json_encode($result);
                } else {
                    echo json_encode($result);
                }
                break;
                
            case 'list':
                // Vérifier si l'utilisateur a les permissions (simulation)
                $result = getAdminList();
                echo json_encode($result);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Action non reconnue']);
        }
        break;
        
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'permissions':
                $data = loadAdminUsers();
                if (isset($data['error'])) {
                    http_response_code(500);
                    echo json_encode($data);
                } else {
                    echo json_encode([
                        'success' => true,
                        'permissions' => $data['permissions'] ?? [],
                        'settings' => $data['settings'] ?? []
                    ]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Action non reconnue']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée']);
}
?>
