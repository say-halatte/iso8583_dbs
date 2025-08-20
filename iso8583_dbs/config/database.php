<?php
/**
 * Gestionnaire de Connexion à la Base de Données
 * Cette classe utilise le pattern Singleton pour gérer la connexion MySQL
 * via PDO (PHP Data Objects) pour les opérations sur les messages ISO 8583
 */

/**
 * Classe Database
 * Responsable de l'établissement et de la gestion de la connexion à la base de données MySQL
 * 
 * Utilise PDO pour:
 * - Une meilleure sécurité (protection contre l'injection SQL)
 * - Une interface cohérente pour différents SGBD
 * - Un support natif des requêtes préparées
 */
class Database {
    
    // ========================================================================
    // PARAMÈTRES DE CONNEXION À LA BASE DE DONNÉES
    // ========================================================================
    
    /**
     * Adresse du serveur de base de données
     * @var string
     * 'localhost' = serveur local (même machine que l'application)
     * En production: pourrait être une adresse IP ou un nom de domaine
     */
    private $host = 'localhost';
    
    /**
     * Nom de la base de données contenant les tables ISO 8583
     * @var string
     * Cette base doit contenir au minimum la table 'iso_messages'
     */
    private $db_name = 'iso8583_db';
    
    /**
     * Nom d'utilisateur pour la connexion MySQL
     * @var string
     * En production: utiliser un utilisateur dédié avec des privilèges minimaux
     * Éviter d'utiliser 'root' en production pour des raisons de sécurité
     */
    private $username = '';
    
    /**
     * Mot de passe pour la connexion MySQL
     * @var string
     * ATTENTION: Ce mot de passe est exposé dans le code source
     * En production: utiliser des variables d'environnement ou un fichier de config sécurisé
     */
    private $password = '';
    
    /**
     * Objet de connexion PDO
     * @var PDO|null
     * Stocke la connexion active à la base de données
     * Initialisé à null, sera créé lors du premier appel à getConnection()
     */
    public $conn;

    // ========================================================================
    // MÉTHODE DE CONNEXION
    // ========================================================================
    
    /**
     * Établit et retourne une connexion à la base de données
     * 
     * Cette méthode:
     * 1. Initialise la connexion PDO si elle n'existe pas
     * 2. Configure l'encodage UTF-8 pour supporter les caractères internationaux
     * 3. Gère les erreurs de connexion avec un try-catch
     * 4. Retourne l'objet de connexion pour utilisation par les modèles
     * 
     * @return PDO|null Objet de connexion PDO ou null en cas d'erreur
     */
    public function getConnection() {
        
        // === INITIALISATION DE LA CONNEXION ===
        
        // Réinitialisation de la connexion (permet la reconnexion si nécessaire)
        $this->conn = null;
        
        try {
            // === CRÉATION DE LA CONNEXION PDO ===
            
            // Construction de la chaîne de connexion (DSN - Data Source Name)
            // Format: "mysql:host=serveur;dbname=base_de_données"
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name;
            
            // Création de l'instance PDO avec les paramètres de connexion
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // === CONFIGURATION DE LA CONNEXION ===
            
            // Configuration de l'encodage UTF-8 pour la session MySQL
            // Essentiel pour gérer correctement les caractères spéciaux et internationaux
            // Les messages ISO 8583 peuvent contenir des données de différents pays
            $this->conn->exec("set names utf8");
            
            /*
             * CONFIGURATIONS PDO RECOMMANDÉES (à ajouter si nécessaire):
             * 
             * // Mode d'erreur: lancer des exceptions pour les erreurs SQL
             * $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
             * 
             * // Mode de récupération par défaut: tableau associatif
             * $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
             * 
             * // Désactiver l'émulation des requêtes préparées pour plus de sécurité
             * $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
             */
            
        } catch(PDOException $exception) {
            
            // === GESTION DES ERREURS DE CONNEXION ===
            
            // Affichage de l'erreur (à adapter selon l'environnement)
            // En production: logger l'erreur au lieu de l'afficher directement
            echo "Connection error: " . $exception->getMessage();
            
            /*
             * GESTION D'ERREURS AMÉLIORÉE POUR LA PRODUCTION:
             * 
             * // Logger l'erreur dans un fichier de log
             * error_log("Database connection error: " . $exception->getMessage());
             * 
             * // En mode développement uniquement: afficher le détail
             * if (defined('DEBUG') && DEBUG === true) {
             *     echo "Connection error: " . $exception->getMessage();
             * }
             * 
             * // Retourner null pour indiquer l'échec de connexion
             * return null;
             */
        }
        
        // === RETOUR DE LA CONNEXION ===
        
        // Retourne l'objet de connexion PDO (ou null si échec)
        // Cette connexion sera utilisée par les modèles pour exécuter les requêtes SQL
        return $this->conn;
    }
}

/**
 * AMÉLIORATIONS RECOMMANDÉES POUR LA PRODUCTION:
 * 
 * 1. SÉCURITÉ DES CREDENTIALS:
 *    - Utiliser des variables d'environnement ($_ENV, getenv())
 *    - Fichier de configuration externe non versionné
 *    - Gestionnaire de secrets (AWS Secrets Manager, Azure Key Vault)
 * 
 * 2. GESTION DES ERREURS:
 *    - Logger les erreurs dans un fichier dédié
 *    - Ne pas exposer les détails techniques aux utilisateurs
 *    - Implémenter un système d'alerting pour les erreurs critiques
 * 
 * 3. PERFORMANCE:
 *    - Pool de connexions pour les applications à forte charge
 *    - Connexions persistantes si approprié
 *    - Monitoring des performances de la base de données
 * 
 * 4. CONFIGURATION PDO:
 *    - Mode d'erreur en exception pour un meilleur debugging
 *    - Désactiver l'émulation des requêtes préparées
 *    - Timeout de connexion configuré
 * 
 * 5. HAUTE DISPONIBILITÉ:
 *    - Support de la réplication master/slave
 *    - Reconnexion automatique en cas de perte de connexion
 *    - Failover vers un serveur de secours
 * 
 * 6. SÉCURITÉ RÉSEAU:
 *    - Connexions chiffrées (SSL/TLS)
 *    - Restriction des accès réseau par IP
 *    - Utilisateur de base avec privilèges minimaux
 */

?>
