<?php
/**
 * Gestionnaire d'Authentification pour l'API ISO 8583
 * Cette classe gère l'authentification basée sur des tokens Bearer
 * et contrôle l'accès aux ressources protégées de l'API
 */

/**
 * Classe AuthManager
 * Responsable de la validation des tokens d'authentification
 * et de la protection des endpoints de l'API
 */
class AuthManager {
    
    // ========================================================================
    // CONFIGURATION DE SÉCURITÉ
    // ========================================================================
    
    /**
     * Clé secrète pour la signature des tokens (si JWT était implémenté)
     * @var string
     * IMPORTANT: Cette clé doit être changée en production et stockée de manière sécurisée
     */
    private static $secret_key = 'your-secret-key-change-this-in-production';
    
    /**
     * Table de correspondance des tokens valides et des utilisateurs associés
     * @var array
     * 
     * Structure: [token => [informations_utilisateur]]
     * En production, ces données devraient être stockées en base de données
     * avec des tokens générés dynamiquement et des dates d'expiration
     */
    private static $valid_tokens = [
        // Token d'exemple pour un administrateur
        'bearer_token_example_123456789' => [
            'user_id' => 1, 
            'username' => 'admin'
        ],
        // Token d'exemple pour un service automatisé
        'api_key_iso8583_secure_2024' => [
            'user_id' => 2, 
            'username' => 'service'
        ]
    ];

    // ========================================================================
    // MÉTHODES D'AUTHENTIFICATION
    // ========================================================================
    
    /**
     * Valide un token d'authentification
     * 
     * @param string $token Le token à valider (avec ou sans préfixe "Bearer ")
     * @return array|false Retourne les informations utilisateur si valide, false sinon
     * 
     * Processus de validation:
     * 1. Vérifie que le token n'est pas vide
     * 2. Supprime le préfixe "Bearer " s'il est présent
     * 3. Recherche le token dans la table des tokens valides
     * 4. Retourne les informations utilisateur ou false
     */
    public static function validateToken($token) {
        // Vérification de la présence du token
        if (!$token) {
            return false; // Token vide ou null
        }

        // Nettoyage du token - suppression du préfixe "Bearer " standard
        // Les clients peuvent envoyer "Bearer abc123" ou directement "abc123"
        $token = str_replace('Bearer ', '', $token);

        // Recherche du token dans la table des tokens valides
        // Retourne les données utilisateur si trouvé, false sinon
        return isset(self::$valid_tokens[$token]) ? self::$valid_tokens[$token] : false;
    }

    /**
     * Méthode de protection des endpoints - Authentification obligatoire
     * 
     * Cette méthode doit être appelée au début de chaque endpoint protégé.
     * Elle vérifie l'authentification et termine l'exécution si elle échoue.
     * 
     * @return array Informations de l'utilisateur authentifié
     * @throws exit() Termine l'exécution avec une erreur 401 si non authentifié
     * 
     * Processus:
     * 1. Récupère le header Authorization de la requête HTTP
     * 2. Vérifie la présence du token
     * 3. Valide le token
     * 4. Retourne les infos utilisateur ou termine avec une erreur 401
     */
    public static function requireAuth() {
        
        // === EXTRACTION DU TOKEN DEPUIS LES HEADERS HTTP ===
        
        // Récupération de tous les headers HTTP de la requête
        $headers = getallheaders();
        
        // Recherche du header Authorization (gestion de la casse)
        // Certains serveurs peuvent modifier la casse des headers
        $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        // === VÉRIFICATION DE LA PRÉSENCE DU TOKEN ===
        
        if (!$token) {
            // Aucun token fourni - Accès refusé
            http_response_code(401); // Unauthorized
            echo json_encode([
                'error' => 'Unauthorized', 
                'message' => 'Bearer token required'
            ]);
            exit(); // Arrête l'exécution du script
        }

        // === VALIDATION DU TOKEN ===
        
        // Appel de la méthode de validation
        $user = self::validateToken($token);
        
        if (!$user) {
            // Token invalide ou expiré - Accès refusé
            http_response_code(401); // Unauthorized
            echo json_encode([
                'error' => 'Unauthorized', 
                'message' => 'Invalid token'
            ]);
            exit(); // Arrête l'exécution du script
        }

        // === AUTHENTIFICATION RÉUSSIE ===
        
        // Retourne les informations de l'utilisateur authentifié
        // Ces informations peuvent être utilisées dans le reste de l'application
        return $user;
    }
}

/**
 * NOTES DE SÉCURITÉ POUR LA PRODUCTION:
 * 
 * 1. TOKENS STATIQUES: Les tokens actuels sont statiques et doivent être remplacés
 *    par un système dynamique (JWT, tokens en base de données avec expiration)
 * 
 * 2. STOCKAGE SÉCURISÉ: Les tokens ne doivent pas être hardcodés dans le code
 *    Utiliser des variables d'environnement ou une base de données sécurisée
 * 
 * 3. EXPIRATION: Implémenter une expiration automatique des tokens
 * 
 * 4. RATE LIMITING: Ajouter une protection contre les attaques par force brute
 * 
 * 5. LOGS DE SÉCURITÉ: Logger les tentatives d'authentification échouées
 * 
 * 6. HTTPS OBLIGATOIRE: S'assurer que l'API n'est accessible que via HTTPS
 * 
 * 7. ROTATION DES CLÉS: Implémenter une rotation régulière des clés secrètes
 */
