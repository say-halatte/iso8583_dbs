<?php
/**
 * API REST pour la gestion des messages ISO 8583
 * Ce fichier constitue le point d'entrée principal de l'API
 * Il gère toutes les requêtes HTTP et orchestre les opérations CRUD
 */

// ============================================================================
// CONFIGURATION DES HEADERS CORS
// ============================================================================
// Ces headers permettent aux applications web d'accéder à l'API depuis différents domaines
header("Access-Control-Allow-Origin: *"); // Autorise toutes les origines (à restreindre en production)
header("Content-Type: application/json; charset=UTF-8"); // Définit le format de réponse en JSON
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE"); // Méthodes HTTP autorisées
header("Access-Control-Max-Age: 3600"); // Durée de mise en cache des headers CORS (1 heure)
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With"); // Headers autorisés

// ============================================================================
// INCLUSION DES DÉPENDANCES
// ============================================================================
// Inclusion des classes nécessaires au fonctionnement de l'API
include_once '../config/database.php';     // Gestion de la connexion à la base de données
include_once '../config/auth.php';         // Système d'authentification
include_once '../config/encryption.php';   // Chiffrement des données sensibles
include_once '../models/IsoMessage.php';   // Modèle de données pour les messages ISO 8583
include_once '../utils/XmlParser.php';     // Analyseur XML pour parser les fichiers ISO 8583

// ============================================================================
// GESTION DES REQUÊTES PREFLIGHT (OPTIONS)
// ============================================================================
// Les navigateurs envoient une requête OPTIONS avant les requêtes CORS complexes
// Cette section répond à ces requêtes preflight
if ($_SERVER["REQUEST_METHOD"] == 'OPTIONS') {
    http_response_code(200); // Retourne OK
    exit(); // Termine l'exécution
}

// ============================================================================
// AUTHENTIFICATION OBLIGATOIRE
// ============================================================================
// Vérifie que l'utilisateur est authentifié avant d'accéder aux ressources
// Si l'authentification échoue, une erreur 401 est renvoyée automatiquement
$user = AuthManager::requireAuth();

// ============================================================================
// INITIALISATION DE LA BASE DE DONNÉES ET DU MODÈLE
// ============================================================================
// Création de l'instance de connexion à la base de données
$database = new Database();
$db = $database->getConnection();

// Création de l'instance du modèle IsoMessage pour les opérations CRUD
$isoMessage = new IsoMessage($db);

// ============================================================================
// ANALYSE DE LA REQUÊTE HTTP
// ============================================================================
// Récupération de la méthode HTTP utilisée (GET, POST, DELETE, etc.)
$request_method = $_SERVER["REQUEST_METHOD"];

// Extraction et analyse du chemin de l'URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Division du chemin en segments pour identifier les ressources demandées
$path_parts = explode('/', trim($path, '/'));
// Exemple: /api/messages/123 → ['api', 'messages', '123']

// ============================================================================
// ROUTAGE DES REQUÊTES SELON LA MÉTHODE HTTP
// ============================================================================
switch($request_method) {
    
    // ========================================================================
    // MÉTHODE GET - RÉCUPÉRATION DE DONNÉES
    // ========================================================================
    case 'GET':
        // Vérification si un ID spécifique est demandé dans l'URL
        if (isset($path_parts[2]) && is_numeric($path_parts[2])) {
            
            // === RÉCUPÉRATION D'UN MESSAGE SPÉCIFIQUE PAR ID ===
            $isoMessage->id = $path_parts[2]; // Attribution de l'ID au modèle
            
            // Tentative de lecture du message depuis la base de données
            if($isoMessage->readOne()) {
                // Construction de la réponse avec les données du message
                $message_item = array(
                    "id" => $isoMessage->id,
                    "mti" => $isoMessage->mti,
                    "pan" => IsoMessage::maskPan($isoMessage->pan), // PAN masqué pour la sécurité
                    "pan_full" => $isoMessage->getDecryptedPan(), // PAN complet déchiffré (accès autorisé uniquement)
                    "processing_code" => $isoMessage->processing_code,
                    "amount" => $isoMessage->amount,
                    "transaction_time" => $isoMessage->transaction_time,
                    "transaction_date" => $isoMessage->transaction_date,
                    "rrn" => $isoMessage->rrn,
                    "response_code" => $isoMessage->response_code,
                    "terminal_id" => $isoMessage->terminal_id,
                    "currency" => $isoMessage->currency,
                    "created_at" => $isoMessage->created_at
                );
                
                // Envoi de la réponse avec le code 200 (OK)
                http_response_code(200);
                echo json_encode($message_item);
            } else {
                // Message non trouvé - Erreur 404
                http_response_code(404);
                echo json_encode(array("message" => "Message not found."));
            }
        } else {
            
            // === RÉCUPÉRATION DE LA LISTE PAGINÉE DES MESSAGES ===
            
            // Récupération des paramètres de pagination depuis l'URL
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;       // Page demandée (défaut: 1)
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;   // Nombre d'éléments par page (défaut: 10)
            
            // Exécution de la requête de lecture paginée
            $stmt = $isoMessage->read($page, $limit);
            // Récupération du nombre total de messages pour la pagination
            $total = $isoMessage->count();
            
            // Construction du tableau des messages
            $messages_arr = array();
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Pour chaque message, création d'un élément avec les données sécurisées
                $message_item = array(
                    "id" => $row['id'],
                    "mti" => $row['mti'],
                    "pan" => IsoMessage::maskPan($row['pan']), // PAN masqué pour la liste
                    "processing_code" => $row['processing_code'],
                    "amount" => (int)$row['amount'], // Conversion en entier
                    "transaction_time" => $row['transaction_time'],
                    "transaction_date" => $row['transaction_date'],
                    "rrn" => $row['rrn'],
                    "response_code" => $row['response_code'],
                    "terminal_id" => $row['terminal_id'],
                    "currency" => $row['currency'],
                    "created_at" => $row['created_at']
                );
                array_push($messages_arr, $message_item); // Ajout au tableau des résultats
            }
            
            // Construction de la réponse avec les données et les informations de pagination
            $response = array(
                "data" => $messages_arr, // Les messages
                "pagination" => array(   // Métadonnées de pagination
                    "page" => $page,
                    "limit" => $limit,
                    "total" => (int)$total,
                    "total_pages" => ceil($total / $limit) // Calcul du nombre total de pages
                )
            );
            
            // Envoi de la réponse
            http_response_code(200);
            echo json_encode($response);
        }
        break;

    // ========================================================================
    // MÉTHODE POST - CRÉATION DE NOUVELLES DONNÉES
    // ========================================================================
    case 'POST':
        // Vérification de la présence d'un fichier XML dans la requête
        if (isset($_FILES['xml_file'])) {
            $uploadedFile = $_FILES['xml_file']; // Récupération du fichier uploadé
            
            // Vérification que l'upload s'est bien déroulé
            if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400); // Bad Request
                echo json_encode(array("message" => "File upload error."));
                break;
            }
            
            // Lecture du contenu du fichier XML
            $xmlContent = file_get_contents($uploadedFile['tmp_name']);
            
            try {
                // === PARSING DU FICHIER XML ISO 8583 ===
                $parsedData = XmlParser::parseIso8583Xml($xmlContent);
                
                // Log de débogage pour voir les données parsées
                error_log("Parsed data: " . json_encode($parsedData));
                
                // === ATTRIBUTION DES DONNÉES PARSÉES AU MODÈLE ===
                $isoMessage->mti = $parsedData['mti'];
                $isoMessage->pan = $parsedData['pan']; // Sera chiffré automatiquement dans create()
                $isoMessage->processing_code = $parsedData['processing_code'];
                $isoMessage->amount = $parsedData['amount'];
                $isoMessage->transaction_time = $parsedData['transaction_time'];
                $isoMessage->transaction_date = $parsedData['transaction_date'];
                $isoMessage->rrn = $parsedData['rrn'];
                $isoMessage->response_code = $parsedData['response_code'];
                $isoMessage->terminal_id = $parsedData['terminal_id'];
                $isoMessage->currency = $parsedData['currency'];
                
                // Logs de débogage pour vérifier les valeurs avant création
                error_log("MTI: " . $isoMessage->mti);
                error_log("PAN: " . $isoMessage->pan);
                error_log("Amount: " . $isoMessage->amount);
                
                // === SAUVEGARDE EN BASE DE DONNÉES ===
                if($isoMessage->create()) {
                    // Succès - Message créé
                    http_response_code(201); // Created
                    echo json_encode(array(
                        "message" => "Message created successfully.",
                        "id" => $isoMessage->id // Retour de l'ID généré
                    ));
                } else {
                    // Échec de la création - Log de l'erreur de base de données
                    $errorInfo = $db->errorInfo();
                    error_log("Database error: " . json_encode($errorInfo));
                    
                    http_response_code(503); // Service Unavailable
                    echo json_encode(array(
                        "message" => "Unable to create message.",
                        "debug" => $errorInfo // Information de débogage (à retirer en production)
                    ));
                }
            } catch (Exception $e) {
                // Gestion des erreurs de parsing XML
                error_log("Exception: " . $e->getMessage());
                http_response_code(400); // Bad Request
                echo json_encode(array("message" => "XML parsing error: " . $e->getMessage()));
            }
        } else {
            // Aucun fichier XML fourni
            http_response_code(400);
            echo json_encode(array("message" => "No XML file provided."));
        }
        break;

    // ========================================================================
    // MÉTHODE DELETE - SUPPRESSION DE DONNÉES
    // ========================================================================
    case 'DELETE':
        // Vérification qu'un ID numérique est fourni dans l'URL
        if (isset($path_parts[2]) && is_numeric($path_parts[2])) {
            $isoMessage->id = $path_parts[2]; // Attribution de l'ID à supprimer
            
            // Tentative de suppression
            if($isoMessage->delete()) {
                // Succès
                http_response_code(200);
                echo json_encode(array("message" => "Message deleted successfully."));
            } else {
                // Échec de suppression
                http_response_code(503);
                echo json_encode(array("message" => "Unable to delete message."));
            }
        } else {
            // ID manquant ou invalide
            http_response_code(400);
            echo json_encode(array("message" => "Invalid message ID."));
        }
        break;

    // ========================================================================
    // MÉTHODES NON SUPPORTÉES
    // ========================================================================
    default:
        // Retour d'une erreur pour les méthodes HTTP non implémentées
        http_response_code(405); // Method Not Allowed
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
