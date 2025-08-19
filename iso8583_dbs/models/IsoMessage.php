<?php
/**
 * Modèle de Données pour les Messages ISO 8583
 * Cette classe implémente le pattern Active Record pour gérer
 * les opérations CRUD sur les messages de transactions bancaires ISO 8583
 */

/**
 * Classe IsoMessage
 * 
 * Représente un message de transaction bancaire selon la norme ISO 8583.
 * Cette norme définit le format des messages électroniques échangés
 * entre les systèmes bancaires pour les transactions par cartes.
 * 
 * RESPONSABILITÉS:
 * - Validation des données ISO 8583
 * - Chiffrement/déchiffrement des données sensibles (PAN)
 * - Operations CRUD en base de données
 * - Masquage des numéros de carte pour l'affichage
 */
class IsoMessage {
    
    // ========================================================================
    // CONFIGURATION DE LA CLASSE
    // ========================================================================
    
    /**
     * Connexion à la base de données PDO
     * @var PDO
     */
    private $conn;
    
    /**
     * Nom de la table en base de données
     * @var string
     */
    private $table_name = "iso_messages";

    // ========================================================================
    // PROPRIÉTÉS CORRESPONDANT AUX CHAMPS ISO 8583
    // ========================================================================
    
    /**
     * @var int Identifiant unique en base de données (auto-increment)
     */
    public $id;
    
    /**
     * @var string MTI - Message Type Indicator (4 digits)
     * Indique le type de message (0200 = demande d'autorisation, etc.)
     * Exemples: 0200, 0210, 0400, 0410, 0800, 0810
     */
    public $mti;
    
    /**
     * @var string PAN - Primary Account Number (numéro de carte)
     * SENSIBLE: Chiffré automatiquement avant stockage
     * Longueur typique: 13-19 digits selon le type de carte
     */
    public $pan;
    
    /**
     * @var string Code de traitement (6 digits)
     * Définit le type de transaction (achat, retrait, remboursement, etc.)
     * Format: TTFFFF (TT=type transaction, FFFF=fonction)
     */
    public $processing_code;
    
    /**
     * @var int Montant de la transaction (en centimes)
     * Stocké en entier pour éviter les problèmes de précision des flottants
     * Exemple: 12.50€ = 1250 centimes
     */
    public $amount;
    
    /**
     * @var string Heure de la transaction (HHMMSS)
     * Format 24h, exemple: "143052" = 14h30m52s
     */
    public $transaction_time;
    
    /**
     * @var string Date de la transaction (MMDD)
     * Format mois-jour, l'année est souvent implicite (année courante)
     * Exemple: "1225" = 25 décembre
     */
    public $transaction_date;
    
    /**
     * @var string RRN - Retrieval Reference Number (12 caractères)
     * Numéro de référence unique pour tracer la transaction
     * Format: YYDDDHHNNNN (YY=année, DDD=jour, HH=heure, NNNN=séquence)
     */
    public $rrn;
    
    /**
     * @var string Code de réponse (2 digits)
     * Indique le résultat de la transaction
     * "00" = approuvé, "05" = refusé, etc.
     */
    public $response_code;
    
    /**
     * @var string Identifiant du terminal (8 caractères)
     * Identifie le point de vente ou le DAB qui génère la transaction
     */
    public $terminal_id;
    
    /**
     * @var string Code devise (3 digits selon ISO 4217)
     * Exemples: "978" = EUR, "840" = USD, "756" = CHF
     */
    public $currency;
    
    /**
     * @var string Timestamp de création en base de données
     * Format MySQL DATETIME: "YYYY-MM-DD HH:MM:SS"
     */
    public $created_at;

    // ========================================================================
    // CONSTRUCTEUR
    // ========================================================================
    
    /**
     * Constructeur de la classe
     * 
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    // ========================================================================
    // MÉTHODE CREATE - INSERTION EN BASE DE DONNÉES
    // ========================================================================
    
    /**
     * Crée un nouveau message ISO 8583 en base de données
     * 
     * Processus:
     * 1. Préparation de la requête SQL avec des placeholders
     * 2. Sanitisation et chiffrement des données sensibles
     * 3. Liaison des paramètres (binding)
     * 4. Exécution de la requête
     * 5. Récupération de l'ID généré
     * 
     * @return bool true si succès, false si échec
     */
    public function create() {
        
        // === PRÉPARATION DE LA REQUÊTE SQL ===
        
        // Requête préparée avec des placeholders nommés pour éviter l'injection SQL
        // SET au lieu de VALUES pour une meilleure lisibilité
        $query = "INSERT INTO " . $this->table_name . " 
                 SET mti=:mti, pan=:pan, processing_code=:processing_code, 
                     amount=:amount, transaction_time=:transaction_time, 
                     transaction_date=:transaction_date, rrn=:rrn, 
                     response_code=:response_code, terminal_id=:terminal_id, 
                     currency=:currency, created_at=:created_at";

        // Préparation de la requête (compilation SQL)
        $stmt = $this->conn->prepare($query);

        // === SANITISATION ET SÉCURISATION DES DONNÉES ===
        
        // Nettoyage du MTI - suppression des balises HTML/scripts malveillants
        $this->mti = htmlspecialchars(strip_tags($this->mti));
        
        // CHIFFREMENT DU PAN - DONNÉES SENSIBLES PCI-DSS
        // Le PAN est chiffré avant stockage pour conformité réglementaire
        $this->pan = EncryptionManager::encrypt($this->pan);
        
        // Sanitisation des autres champs texte
        $this->processing_code = htmlspecialchars(strip_tags($this->processing_code));
        $this->transaction_time = htmlspecialchars(strip_tags($this->transaction_time));
        $this->transaction_date = htmlspecialchars(strip_tags($this->transaction_date));
        $this->rrn = htmlspecialchars(strip_tags($this->rrn));
        $this->response_code = htmlspecialchars(strip_tags($this->response_code));
        $this->terminal_id = htmlspecialchars(strip_tags($this->terminal_id));
        $this->currency = htmlspecialchars(strip_tags($this->currency));
        
        // Conversion du montant en entier (protection contre injection + format correct)
        $this->amount = (int)$this->amount;
        
        // Génération automatique du timestamp de création
        $this->created_at = date('Y-m-d H:i:s');

        // === LIAISON DES PARAMÈTRES (PARAMETER BINDING) ===
        
        // Association des valeurs PHP aux placeholders SQL
        // Cette méthode empêche l'injection SQL de manière native
        $stmt->bindParam(":mti", $this->mti);
        $stmt->bindParam(":pan", $this->pan);
        $stmt->bindParam(":processing_code", $this->processing_code);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":transaction_time", $this->transaction_time);
        $stmt->bindParam(":transaction_date", $this->transaction_date);
        $stmt->bindParam(":rrn", $this->rrn);
        $stmt->bindParam(":response_code", $this->response_code);
        $stmt->bindParam(":terminal_id", $this->terminal_id);
        $stmt->bindParam(":currency", $this->currency);
        $stmt->bindParam(":created_at", $this->created_at);

        // === EXÉCUTION ET VÉRIFICATION ===
        
        if($stmt->execute()) {
            // Récupération de l'ID auto-généré par MySQL
            $this->id = $this->conn->lastInsertId();
            return true; // Succès
        }
        
        return false; // Échec
    }

    // ========================================================================
    // MÉTHODE READ - LECTURE AVEC PAGINATION
    // ========================================================================
    
    /**
     * Récupère une liste paginée de messages ISO 8583
     * 
     * @param int $page Numéro de la page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @return PDOStatement Statement exécuté pour récupération des résultats
     */
    public function read($page = 1, $limit = 10) {
        
        // === CALCUL DE L'OFFSET ===
        
        // Calcul de l'offset pour la pagination
        // Page 1: offset = 0, Page 2: offset = 10, etc.
        $offset = ($page - 1) * $limit;
        
        // === REQUÊTE DE SÉLECTION AVEC PAGINATION ===
        
        // Sélection de tous les champs nécessaires
        // ORDER BY pour un tri cohérent (plus récents en premier)
        // LIMIT/OFFSET pour la pagination MySQL
        $query = "SELECT id, mti, pan, processing_code, amount, transaction_time, 
                         transaction_date, rrn, response_code, terminal_id, currency, created_at 
                 FROM " . $this->table_name . " 
                 ORDER BY created_at DESC 
                 LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        // Binding des paramètres de pagination avec type spécifique
        // PDO::PARAM_INT garantit que les valeurs sont traitées comme des entiers
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();

        return $stmt; // Retourne le statement pour traitement par l'appelant
    }

    // ========================================================================
    // MÉTHODE COUNT - COMPTAGE TOTAL
    // ========================================================================
    
    /**
     * Compte le nombre total de messages en base
     * Nécessaire pour calculer le nombre de pages en pagination
     * 
     * @return int Nombre total d'enregistrements
     */
    public function count() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // ========================================================================
    // MÉTHODE READONE - LECTURE D'UN ENREGISTREMENT SPÉCIFIQUE
    // ========================================================================
    
    /**
     * Récupère un message ISO 8583 par son ID
     * 
     * @return bool true si trouvé et chargé, false sinon
     */
    public function readOne() {
        // Requête avec WHERE pour un enregistrement spécifique
        // LIMIT 0,1 pour optimisation (arrêt après le premier résultat)
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        // Récupération du résultat sous forme de tableau associatif
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            // === HYDRATATION DE L'OBJET ===
            
            // Attribution des valeurs de la base aux propriétés de l'objet
            $this->id = $row['id'];
            $this->mti = $row['mti'];
            $this->pan = $row['pan']; // Reste chiffré - sera déchiffré à la demande
            $this->processing_code = $row['processing_code'];
            $this->amount = $row['amount'];
            $this->transaction_time = $row['transaction_time'];
            $this->transaction_date = $row['transaction_date'];
            $this->rrn = $row['rrn'];
            $this->response_code = $row['response_code'];
            $this->terminal_id = $row['terminal_id'];
            $this->currency = $row['currency'];
            $this->created_at = $row['created_at'];
            
            return true; // Objet hydraté avec succès
        }
        
        return false; // Aucun enregistrement trouvé
    }

    // ========================================================================
    // MÉTHODE DELETE - SUPPRESSION
    // ========================================================================
    
    /**
     * Supprime un message ISO 8583 de la base de données
     * 
     * @return bool true si suppression réussie, false sinon
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // ========================================================================
    // MÉTHODES UTILITAIRES POUR LA SÉCURITÉ
    // ========================================================================
    
    /**
     * Déchiffre et retourne le PAN complet
     * 
     * À utiliser uniquement pour les utilisateurs autorisés
     * Conforme aux exigences PCI-DSS d'accès contrôlé
     * 
     * @return string PAN déchiffré ou false si erreur
     */
    public function getDecryptedPan() {
        return EncryptionManager::decrypt($this->pan);
    }

    /**
     * Masque un PAN pour affichage sécurisé
     * 
     * Affiche seulement les 4 premiers et 4 derniers chiffres
     * Format: 1234****5678 (conforme PCI-DSS)
     * 
     * @param string $pan PAN chiffré en base de données
     * @return string PAN masqué pour affichage
     */
    public static function maskPan($pan) {
        // Déchiffrement du PAN pour masquage
        $decrypted = EncryptionManager::decrypt($pan);
        
        // Vérification de la longueur minimale pour masquage
        if (strlen($decrypted) > 8) {
            // Format standard: 4 premiers + **** + 4 derniers
            return substr($decrypted, 0, 4) . '****' . substr($decrypted, -4);
        }
        
        // Si PAN trop court, retourne tel quel (cas d'erreur ou test)
        return $decrypted;
    }
}

/**
 * AMÉLIORATIONS POUR LA PRODUCTION:
 * 
 * 1. VALIDATION DES DONNÉES:
 *    - Validation stricte des formats ISO 8583
 *    - Vérification de la validité des codes devise
 *    - Contrôle des plages de valeurs pour chaque champ
 * 
 * 2. AUDIT ET TRAÇABILITÉ:
 *    - Logging de tous les accès aux PAN déchiffrés
 *    - Traçabilité des modifications (qui, quand, quoi)
 *    - Historique des suppressions (soft delete)
 * 
 * 3. PERFORMANCE:
 *    - Index sur les champs de recherche fréquents (RRN, terminal_id)
 *    - Mise en cache pour les requêtes répétitives
 *    - Optimisation des requêtes de pagination
 * 
 * 4. SÉCURITÉ RENFORCÉE:
 *    - Chiffrement d'autres champs sensibles si nécessaire
 *    - Contrôle d'accès granulaire par utilisateur/rôle
 *    - Protection contre les attaques par timing
 * 
 * 5. ROBUSTESSE:
 *    - Gestion des erreurs de déchiffrement
 *    - Validation des données avant insertion
 *    - Transactions pour les opérations complexes
 */
