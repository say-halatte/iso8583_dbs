<?php
/**
 * Gestionnaire de Chiffrement pour la Protection des Données Sensibles
 * Cette classe implémente le chiffrement AES-256-CBC pour sécuriser
 * les données sensibles comme les PAN (Primary Account Number) des cartes bancaires
 */

/**
 * Classe EncryptionManager
 * 
 * Responsable du chiffrement et déchiffrement des données sensibles
 * Utilise l'algorithme AES-256 en mode CBC (Cipher Block Chaining)
 * avec des vecteurs d'initialisation (IV) uniques pour chaque chiffrement
 * 
 * SÉCURITÉ: Cette classe est critique pour la protection des données PCI-DSS
 * Les PAN de cartes bancaires doivent être chiffrés selon les standards de sécurité
 */
class EncryptionManager {
    
    // ========================================================================
    // CONFIGURATION DU CHIFFREMENT
    // ========================================================================
    
    /**
     * Clé de chiffrement principale
     * @var string
     * 
     * IMPORTANT SÉCURITÉ:
     * - Cette clé doit faire exactement 32 bytes pour AES-256
     * - Elle doit être générée de manière cryptographiquement sécurisée
     * - Elle doit être unique pour chaque environnement
     * - Elle ne doit JAMAIS être hardcodée en production
     * 
     * PRODUCTION: Utiliser des variables d'environnement ou un HSM (Hardware Security Module)
     */
    private static $encryption_key = 'your-32-byte-encryption-key-here!!'; // Exactement 32 caractères
    
    /**
     * Algorithme de chiffrement utilisé
     * @var string
     * 
     * AES-256-CBC signifie:
     * - AES: Advanced Encryption Standard
     * - 256: Taille de clé de 256 bits (très sécurisé)
     * - CBC: Cipher Block Chaining (mode de chiffrement par blocs)
     * 
     * CBC nécessite un vecteur d'initialisation (IV) unique pour chaque chiffrement
     * pour garantir que des données identiques produisent des chiffrements différents
     */
    private static $cipher = 'AES-256-CBC';

    // ========================================================================
    // MÉTHODE DE CHIFFREMENT
    // ========================================================================
    
    /**
     * Chiffre des données sensibles
     * 
     * Processus de chiffrement:
     * 1. Génère un IV (Initialization Vector) aléatoire unique
     * 2. Chiffre les données avec AES-256-CBC
     * 3. Concatène les données chiffrées et l'IV
     * 4. Encode le tout en Base64 pour le stockage
     * 
     * @param string $data Les données à chiffrer (ex: numéro PAN)
     * @return string Les données chiffrées encodées en Base64
     * 
     * Format de sortie: base64(données_chiffrées::IV)
     */
    public static function encrypt($data) {
        
        // === GÉNÉRATION DU VECTEUR D'INITIALISATION (IV) ===
        
        // Génère un IV aléatoire de la taille requise par l'algorithme
        // openssl_cipher_iv_length() retourne la taille d'IV nécessaire pour l'algorithme
        // Pour AES-256-CBC, c'est 16 bytes
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$cipher));
        
        /*
         * POURQUOI UN IV UNIQUE ?
         * - Empêche les attaques par analyse de patterns
         * - Garantit que le même texte clair produit des chiffrements différents
         * - Essentiel pour la sécurité cryptographique moderne
         */
        
        // === CHIFFREMENT DES DONNÉES ===
        
        // Chiffrement avec OpenSSL
        // Paramètres:
        // - $data: les données à chiffrer
        // - self::$cipher: l'algorithme AES-256-CBC
        // - self::$encryption_key: la clé de 32 bytes
        // - 0: flags par défaut
        // - $iv: le vecteur d'initialisation unique
        $encrypted = openssl_encrypt($data, self::$cipher, self::$encryption_key, 0, $iv);
        
        // === ASSEMBLAGE ET ENCODAGE ===
        
        // Concatène les données chiffrées et l'IV avec un séparateur "::"
        // Format: "données_chiffrées::IV"
        $combined = $encrypted . '::' . $iv;
        
        // Encode en Base64 pour un stockage sûr en base de données
        // Base64 garantit que tous les caractères sont ASCII et stockables
        return base64_encode($combined);
    }

    // ========================================================================
    // MÉTHODE DE DÉCHIFFREMENT
    // ========================================================================
    
    /**
     * Déchiffre des données précédemment chiffrées
     * 
     * Processus de déchiffrement:
     * 1. Décode de Base64
     * 2. Sépare les données chiffrées et l'IV
     * 3. Déchiffre avec la clé et l'IV originaux
     * 4. Retourne les données en clair
     * 
     * @param string $data Les données chiffrées (format Base64)
     * @return string|false Les données déchiffrées ou false en cas d'erreur
     */
    public static function decrypt($data) {
        try {
            
            // === DÉCODAGE ET SÉPARATION ===
            
            // Décode de Base64 pour récupérer le format "données_chiffrées::IV"
            $decoded = base64_decode($data);
            
            // Sépare les données chiffrées et l'IV en utilisant "::" comme délimiteur
            // explode() avec limite 2 garantit qu'on obtient exactement 2 éléments
            list($encrypted_data, $iv) = explode('::', $decoded, 2);
            
            /*
             * VÉRIFICATIONS DE SÉCURITÉ POSSIBLES (à ajouter si nécessaire):
             * 
             * // Vérifier la longueur de l'IV
             * if (strlen($iv) !== openssl_cipher_iv_length(self::$cipher)) {
             *     return false;
             * }
             * 
             * // Vérifier que les données ne sont pas vides
             * if (empty($encrypted_data)) {
             *     return false;
             * }
             */
            
            // === DÉCHIFFREMENT ===
            
            // Déchiffrement avec OpenSSL en utilisant les mêmes paramètres que pour le chiffrement
            return openssl_decrypt($encrypted_data, self::$cipher, self::$encryption_key, 0, $iv);
            
        } catch (Exception $e) {
            
            // === GESTION DES ERREURS ===
            
            // En cas d'erreur (format invalide, corruption des données, etc.)
            // Retourne false au lieu de lever une exception
            // Cela permet à l'application de continuer à fonctionner
            
            /*
             * AMÉLIORATIONS POSSIBLES:
             * 
             * // Logger l'erreur pour le debugging
             * error_log("Decryption error: " . $e->getMessage());
             * 
             * // En mode développement, afficher plus de détails
             * if (defined('DEBUG') && DEBUG === true) {
             *     error_log("Failed to decrypt data: " . $data);
             * }
             */
            
            return false;
        }
    }
}

/**
 * CONSIDÉRATIONS DE SÉCURITÉ POUR LA PRODUCTION:
 * 
 * 1. GESTION DES CLÉS:
 *    - Utiliser un HSM (Hardware Security Module) ou AWS KMS
 *    - Rotation régulière des clés de chiffrement
 *    - Stockage sécurisé des clés (variables d'environnement, coffres-forts)
 *    - Séparation des clés par environnement (dev/test/prod)
 * 
 * 2. CONFORMITÉ PCI-DSS:
 *    - Auditer régulièrement le code de chiffrement
 *    - Documenter les procédures de gestion des clés
 *    - Tests de pénétration réguliers
 *    - Formation de l'équipe sur les bonnes pratiques crypto
 * 
 * 3. PERFORMANCE:
 *    - Mise en cache des opérations de déchiffrement si approprié
 *    - Optimisation pour les gros volumes de données
 *    - Monitoring des performances crypto
 * 
 * 4. RÉSILIENCE:
 *    - Gestion gracieuse des erreurs de déchiffrement
 *    - Procédures de récupération en cas de perte de clé
 *    - Sauvegarde sécurisée des clés de chiffrement
 * 
 * 5. AUDIT ET TRAÇABILITÉ:
 *    - Logger toutes les opérations de chiffrement/déchiffrement
 *    - Tracer l'accès aux données sensibles déchiffrées
 *    - Alertes en cas d'échec de déchiffrement répétés
 * 
 * 6. ALTERNATIVES D'ALGORITHMES:
 *    - Considérer AES-256-GCM pour l'authentification intégrée
 *    - Évaluer Argon2 pour le hachage de mots de passe
 *    - Rester à jour avec les recommandations cryptographiques
 * 
 * EXEMPLE D'UTILISATION:
 * 
 * // Chiffrement d'un PAN
 * $pan = "1234567890123456";
 * $encrypted_pan = EncryptionManager::encrypt($pan);
 * // Résultat: "base64encodeddata..."
 * 
 * // Déchiffrement
 * $decrypted_pan = EncryptionManager::decrypt($encrypted_pan);
 * // Résultat: "1234567890123456"
 */

?>