# iso8583_dbs

Test de recrutement de Digital Business Solutions SA - Poste de Ingénieur Support Monétique. 
API REST en PHP pour parser, stocker et gérer les messages ISO 8583 selon les spécifications du test avec authentification JWT, chiffrement du PAN et documentation Swagger.
L'API et l'application web sont tous deux dans le même répertoire, mais sont tout à fait deux modules ou applications différentes. L'API peut etre utilisé sur un autre serveur indépendamment du GUI. N'importe qu'elle application front-end peut interagir avec le REST API ISO8583.

Interface principale APPLICATION : [http://localhost/iso8583_dbs/index.html]
Interface principale API : [http://localhost/iso8583_dbs/api/index.php]
Documentation API (Swagger UI) : [http://localhost/iso8583_dbs/api/docs]

# Fonctionnalités

✅ Fonctionnalités principales
Parser XML : Analyse et validation des fichiers XML contenant des messages ISO 8583 
API RESTful : Endpoints complets pour CRUD des messages
Pagination : Pagination côté serveur pour la liste des messages
Base de données : Stockage structuré en MySQL
Validation : Validation complète des champs obligatoires

🔐 Sécurité (Bonus)
Authentification JWT : Protection des APIs avec Bearer Token
Chiffrement AES-256 : Chiffrement du PAN en base de données
CORS : Configuration pour les appels cross-origin

📋 Documentation
Swagger UI : Interface interactive pour tester l'API
OpenAPI 3.0 : Spécification complète de l'API

# Prérequis
PHP 7.4 ou supérieur
MySQL 5.7 ou supérieur
Extensions PHP : pdo, pdo_mysql, openssl, json, simplexml
- Configurer upload_max_filesize = 10M
- Configurer post_max_size = 10M
- Activer OpenSSL pour le chiffrement
Serveur web (Apache/Nginx) ou PHP built-in server

=== CONFIGURATION ET INSTALLATION ===

# 1. Structure des dossiers :
`iso8583_dbs/
├── api/
│   ├── index.php              
│   ├── swagger.yaml           (spécification OpenAPI pour Swagger UI)
│   ├── swagger-ui.html        (interface utilisateur Swagger UI)
│   └── swagger-config.php     (configuration serveur Swagger UI)
├── config/
│   ├── database.php
│   ├── auth.php
│   └── encryption.php
├── models/
│   └── IsoMessage.php
├── utils/
│   └── XmlParser.php
├── index.html
├── database.sql
├── .htaccess
└── exemples/
    ├── sample_msg3.xml
    ├── sample_msg4.xml
    └── sample_msg5.xml
`

# 2. Installation
1. Vous pouvez Créer la base de données avec le script database.sql qui se trouve dans le repertoire.
Vous avez aussi le choix de laisser les tables de base de données etre créées automatiquement au premier lancement de l'API.
3. Configuration du fichier API
  a- Téléchargez le dossier "iso8583_dbs"
  b- Modifiez les paramètres de connexion à la base de données dans la classe "Database" :

php
private $host = 'localhost';
private $db_name = 'iso8583_db'; // Votre nom de base de donnée MySQL
private $username = 'root'; // Votre nom d'utilisateur MySQL
private $password = '' ; // Votre mot de passe MySQL

# 3. Déploiement
Option : Serveur web (Apache/Nginx)
1. Placez le dossier "iso8583_dbs" dans le répertoire web de votre serveur
2. Configurez un virtual host pointant vers ce fichier (Optionel, sinon utilisé le virtual host existant)

# 4. Format XML requis (SIMPLIFIÉ) :
<isomsg direction="incoming">
      <header>3936303031</header>
      <field id="0" value="1110"/>
      <field id="2" value="4000510010065678"/>
      <field id="3" value="000000"/>
      <field id="4" value="000000560000"/>
      <field id="12" value="053607"/>
      <field id="13" value="0722"/>
      <field id="37" value="520323002113"/>
      <field id="39" value="00"/>
      <field id="41" value="60002065"/>
      <field id="49" value="950"/>
</isomsg>

# 5. TOKENS D'AUTHENTIFICATION POUR LES TESTS :

1. Token Admin (accès complet) :
   bearer_token_example_123456789

2. Token Service (accès API) :
   api_key_iso8583_secure_2024

# 6. Tests de l'application :
1. Démarrer le serveur Apache/PHP avec HTTP/HTTPS
3. Ouvrir "[http://localhost/iso8583_dbs/index.html]" dans un navigateur
4. S'authentifier avec un token valide (Token d'exemple fourni au point 5) 
5. Téléverser un fichier XML d'exemple
6. Vérifier le chiffrement des PANs en base de données
7. Tester la révélation/masquage des PANs en GUI

