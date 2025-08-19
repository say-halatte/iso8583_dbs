<?php
/**
 * Configuration pour servir Swagger UI et la documentation OpenAPI
 * Ce fichier permet de servir l'interface Swagger UI avec les bonnes configurations CORS
 */

// ============================================================================
// CONFIGURATION DES HEADERS CORS POUR SWAGGER UI
// ============================================================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Gestion des requêtes OPTIONS pour CORS
if ($_SERVER["REQUEST_METHOD"] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// ROUTAGE SIMPLE POUR LES FICHIERS DE DOCUMENTATION
// ============================================================================

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Déterminer le type de fichier demandé
$file_requested = end($path_parts);

switch($file_requested) {
    case 'docs':
    case 'swagger-ui':
    case 'swagger-ui.html':
        // Servir l'interface Swagger UI
        serveSwaggerUI();
        break;
        
    case 'swagger.yaml':
    case 'openapi.yaml':
        // Servir le fichier de spécification OpenAPI
        serveSwaggerYaml();
        break;
        
    case 'swagger.json':
    case 'openapi.json':
        // Servir la spécification en format JSON (optionnel)
        serveSwaggerJson();
        break;
        
    default:
        // Redirection par défaut vers l'interface Swagger UI
        if (strpos($path, '/docs') !== false || strpos($path, '/swagger') !== false) {
            serveSwaggerUI();
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Documentation endpoint not found. Try /docs or /swagger-ui"));
        }
        break;
}

/**
 * Sert l'interface HTML Swagger UI
 */
function serveSwaggerUI() {
    header("Content-Type: text/html; charset=UTF-8");
    
    // Le contenu HTML sera lu depuis le fichier swagger-ui.html
    $html_file = __DIR__ . '/swagger-ui.html';
    
    if (file_exists($html_file)) {
        readfile($html_file);
    } else {
        // Fallback: générer l'HTML de base si le fichier n'existe pas
        echo generateSwaggerUIHTML();
    }
}

/**
 * Sert le fichier swagger.yaml
 */
function serveSwaggerYaml() {
    header("Content-Type: application/x-yaml; charset=UTF-8");
    header("Content-Disposition: inline; filename=swagger.yaml");
    
    $yaml_file = __DIR__ . '/swagger.yaml';
    
    if (file_exists($yaml_file)) {
        readfile($yaml_file);
    } else {
        http_response_code(404);
        echo "# Fichier swagger.yaml non trouvé\n";
        echo "# Assurez-vous que le fichier swagger.yaml existe dans le même répertoire\n";
    }
}

/**
 * Convertit et sert la spécification en format JSON
 */
function serveSwaggerJson() {
    header("Content-Type: application/json; charset=UTF-8");
    
    $yaml_file = __DIR__ . '/swagger.yaml';
    
    if (file_exists($yaml_file)) {
        // Tentative de conversion YAML vers JSON
        if (function_exists('yaml_parse_file')) {
            $yaml_content = yaml_parse_file($yaml_file);
            echo json_encode($yaml_content, JSON_PRETTY_PRINT);
        } else {
            // Fallback: message d'erreur si l'extension YAML n'est pas disponible
            echo json_encode(array(
                "error" => "YAML extension not available",
                "message" => "Please use the YAML version at /swagger.yaml"
            ), JSON_PRETTY_PRINT);
        }
    } else {
        http_response_code(404);
        echo json_encode(array("error" => "swagger.yaml file not found"));
    }
}

/**
 * Génère l'HTML de base pour Swagger UI (fallback)
 */
function generateSwaggerUIHTML() {
    // URL de base pour l'API (à adapter selon votre configuration)
    $api_base_url = getApiBaseUrl();
    
    return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>API ISO 8583 Messages - Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui.min.css" />
    <style>
        body { margin: 0; background: #fafafa; }
        .swagger-ui .topbar { background-color: #1f4e79; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.15.5/swagger-ui-standalone-preset.min.js"></script>
    <script>
        SwaggerUIBundle({
            url: "' . $api_base_url . '/swagger.yaml",
            dom_id: "#swagger-ui",
            presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
            layout: "StandaloneLayout",
            deepLinking: true,
            persistAuthorization: true
        });
    </script>
</body>
</html>';
}

/**
 * Détermine l'URL de base de l'API
 */
function getApiBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['REQUEST_URI']);
    
    // Nettoyer le chemin pour enlever les segments de documentation
    $path = preg_replace('/\/(docs|swagger-ui|swagger).*$/', '', $path);
    
    return $protocol . '://' . $host . $path;
}

/**
 * Utilitaire pour loguer les accès à la documentation
 */
function logDocumentationAccess() {
    $log_entry = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'endpoint' => $_SERVER['REQUEST_URI'],
        'method' => $_SERVER['REQUEST_METHOD']
    );
    
    // Log dans un fichier (optionnel)
    error_log("Swagger Access: " . json_encode($log_entry));
}

// Log de l'accès (si activé)
if (defined('LOG_SWAGGER_ACCESS') && LOG_SWAGGER_ACCESS === true) {
    logDocumentationAccess();
}
?>