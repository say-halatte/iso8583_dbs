<?php

/**
 * Analyseur XML pour Messages ISO 8583
 * Cette classe parse les fichiers XML contenant des messages ISO 8583
 * et extrait les données nécessaires pour les stocker en base de données
 */


/**
 * Classe XmlParser
 * 
 * Responsable de l'analyse et de l'extraction des données depuis des fichiers XML
 * contenant des messages ISO 8583. Les messages ISO 8583 peuvent être représentés
 * en XML avec différents formats, cette classe gère le format standard.
 * 
 * FONCTIONNALITÉS:
 * - Parse le contenu XML des messages ISO 8583
 * - Extrait les champs obligatoires et optionnels
 * - Valide la structure et les données
 * - Gère les différents formats de MTI (header vs field 0)
 * - Formate les données pour insertion en base
 */
class XmlParser {
// ========================================================================
    // MÉTHODE PRINCIPALE DE PARSING
    // ========================================================================
    
    /**
     * Parse un fichier XML ISO 8583 et extrait les données structurées
     * 
     * Structure XML attendue:
     * <iso8583>
     *   <header>0200</header>  <!-- MTI optionnel dans header -->
     *   <field id="0" value="0200"/>  <!-- MTI alternatif dans field 0 -->
     *   <field id="2" value="1234567890123456"/>  <!-- PAN -->
     *   <field id="3" value="000000"/>  <!-- Processing code -->
     *   <!-- Autres champs... -->
     * </iso8583>
     * 
     * @param string $xmlContent Contenu XML brut du fichier uploadé
     * @return array Tableau associatif contenant les données extraites
     * @throws Exception Si le XML est invalide ou des champs obligatoires manquent
     */
    public static function parseIso8583Xml($xmlContent) {
        // === PARSING XML DE BASE ===
        
        // Conversion du XML string en objet SimpleXML pour manipulation facile
        // SimpleXML est adapté pour des structures XML relativement simples
        $xml = simplexml_load_string($xmlContent);
        // Vérification de la validité du XML
        if ($xml === false) {
            throw new Exception("Invalid XML format");
        }
        
        // === EXTRACTION DES CHAMPS (FIELDS) ===
        
        // Les messages ISO 8583 en XML contiennent des éléments <field> avec des attributs
        // Chaque field a un 'id' (numéro de champ ISO) et une 'value' (données)
        
        $fields = [];
        // Parcours de tous les éléments <field> dans le XML
        foreach ($xml->field as $field) {
            // Extraction de l'ID du champ (cast en string pour éviter les objets SimpleXML)
            $fieldId = (string) $field['id'];
            
            // Extraction de la valeur du champ
            $fieldValue = (string) $field['value'];
            
            // Stockage dans le tableau associatif [id_champ => valeur]
            $fields[$fieldId] = $fieldValue;
        }
        // === EXTRACTION DU MTI (MESSAGE TYPE INDICATOR) ===
        
        /*
         * Le MTI peut être présent à deux endroits selon l'implémentation:
         * 1. Dans l'élément <header> (format classique)
         * 2. Dans le field avec id="0" (format alternatif)
         * 
         * Cette section gère les deux cas avec priorité au field 0
         */
        $mti = isset($fields['0']) ? $fields['0'] : (string) ($xml->header ?? '');

        // Valider les champs obligatoires
        $requiredFields = ['2', '3', '4', '12', '13', '37', '41', '49'];
        foreach ($requiredFields as $requiredField) {
            if (!isset($fields[$requiredField]) || empty($fields[$requiredField])) {
                throw new Exception("Missing required field: $requiredField");
            }
        }

        // Valider et formater la date de transaction (ajouter l'année en cours si seulement MMJJ)
        $transactionDate = $fields['13'];
        if (strlen($transactionDate) == 4) {
            $currentYear = date('Y');
            $transactionDate = $transactionDate; // Conserver le format MMJJ tel que spécifié
        }

        return [
            'mti' => $mti,
            'pan' => $fields['2'], // Sera crypté dans le modèle
            'processing_code' => $fields['3'],
            'amount' => (int) $fields['4'],
            'transaction_time' => $fields['12'],
            'transaction_date' => $transactionDate,
            'rrn' => $fields['37'],
            'response_code' => $fields['39'] ?? '',
            'terminal_id' => $fields['41'],
            'currency' => $fields['49']
        ];
    }
}
