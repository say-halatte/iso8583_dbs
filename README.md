# ISO 8583 Parser & Manager API

[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-%3E%3D5.7-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

> API REST en PHP pour parser, stocker et gérer les messages ISO 8583 avec authentification JWT et chiffrement sécurisé du PAN.

## 📋 Description

Test de recrutement de **Digital Business Solutions SA** - Poste d'Ingénieur Support Monétique.

Cette solution comprend une API REST complète pour traiter les messages ISO 8583 selon les spécifications du test, avec authentification JWT, chiffrement du PAN et documentation Swagger interactive.

**Architecture modulaire :** L'API et l'interface web sont indépendantes - l'API peut être déployée séparément et utilisée par n'importe quelle application front-end.

## 🌐 Liens d'accès

| Service | URL |
|---------|-----|
| **Interface Web** | http://localhost/iso8583_dbs/index.html |
| **API Endpoint** | http://localhost/iso8583_dbs/api/index.php |
| **Documentation Swagger** | http://localhost/iso8583_dbs/api/docs |

## ✨ Fonctionnalités

### 🎯 Fonctionnalités principales

| Fonctionnalité | Description |
|----------------|-------------|
| ✅ **Parser XML** | Analyse et validation des fichiers XML contenant des messages ISO 8583 |
| ✅ **API RESTful** | Endpoints complets pour CRUD des messages |
| ✅ **Pagination** | Pagination côté serveur pour la liste des messages |
| ✅ **Base de données** | Stockage structuré en MySQL |
| ✅ **Validation** | Validation complète des champs obligatoires |

### 🔐 Sécurité (Bonus)

| Fonctionnalité | Description |
|----------------|-------------|
| 🔑 **Authentification JWT** | Protection des APIs avec Bearer Token |
| 🛡️ **Chiffrement AES-256** | Chiffrement du PAN en base de données |
| 🌍 **CORS** | Configuration pour les appels cross-origin |

### 📋 Documentation

| Fonctionnalité | Description |
|----------------|-------------|
| 📖 **Swagger UI** | Interface interactive pour tester l'API |
| 📝 **OpenAPI 3.0** | Spécification complète de l'API |

## 🛠️ Prérequis techniques

### Environnement serveur
- **PHP** 7.4 ou supérieur
- **MySQL** 5.7 ou supérieur
- **Serveur web** Apache/Nginx ou PHP built-in server

### Extensions PHP requises
```
pdo, pdo_mysql, openssl, json, simplexml
```

### Configuration PHP recommandée
```ini
upload_max_filesize = 10M
post_max_size = 10M
extension=openssl  ; Requis pour le chiffrement
```

## 📁 Structure du projet

```
iso8583_dbs/
├── api/
│   ├── index.php              # Point d'entrée de l'API
│   ├── swagger.yaml           # Spécification OpenAPI
│   ├── swagger-ui.html        # Interface Swagger UI
│   └── swagger-config.php     # Configuration serveur Swagger
├── config/
│   ├── database.php           # Configuration base de données
│   ├── auth.php              # Configuration authentification
│   └── encryption.php        # Configuration chiffrement
├── models/
│   └── IsoMessage.php        # Modèle de données ISO 8583
├── utils/
│   └── XmlParser.php         # Utilitaire de parsing XML
├── exemples/
│   ├── sample_msg3.xml       # Exemples de messages
│   ├── sample_msg4.xml
│   └── sample_msg5.xml
├── index.html                # Interface web principale
├── database.sql              # Script de création BDD
├── .htaccess                # Configuration Apache
└── README.md
```

## 🚀 Installation

### 1. Configuration de la base de données

**Option A - Script automatique (recommandé) :**
Les tables seront créées automatiquement au premier lancement de l'API.

**Option B - Script manuel :**
```sql
-- Exécuter le script database.sql dans votre SGBD MySQL
source database.sql
```

### 2. Configuration de l'API

1. **Télécharger le projet**
   ```bash
   git clone [URL_DU_REPO]
   cd iso8583_dbs
   ```

2. **Configurer la base de données**
   
   Modifier les paramètres dans `config/database.php` :
   ```php
   private $host = 'localhost';
   private $db_name = 'iso8583_db';    // Votre base MySQL
   private $username = 'root';         // Votre utilisateur MySQL  
   private $password = '';             // Votre mot de passe MySQL
   ```

### 3. Déploiement

**Serveur web (Apache/Nginx) :**
1. Placer le dossier `iso8583_dbs` dans le répertoire web du serveur
2. Configurer un virtual host (optionnel)
3. Vérifier que les permissions sont correctes

**PHP Built-in Server (développement) :**
```bash
cd iso8583_dbs
php -S localhost:8000
```

## 📝 Format XML requis

```xml
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
```

## 🔑 Tokens d'authentification pour les tests

### Token Admin (accès complet)
```
bearer_token_example_123456789
```

### Token Service (accès API)
```  
api_key_iso8583_secure_2024
```

> **Note:** Utilisez ces tokens avec l'en-tête `Authorization: Bearer <token>`

## 🧪 Tests de l'application

### Procédure de test complète

1. **Démarrer le serveur** Apache/PHP avec HTTP/HTTPS
2. **Accéder à l'interface** [http://localhost/iso8583_dbs/index.html](http://localhost/iso8583_dbs/index.html)
3. **S'authentifier** avec un token valide (voir tokens ci-dessus)
4. **Téléverser** un fichier XML d'exemple depuis le dossier `exemples/`
5. **Vérifier** le chiffrement des PANs en base de données
6. **Tester** la révélation/masquage des PANs dans l'interface

### Tests API via Swagger

1. Accéder à [http://localhost/iso8583_dbs/api/docs](http://localhost/iso8583_dbs/api/docs)
2. Utiliser le bouton "Authorize" avec un Bearer Token
3. Tester les différents endpoints disponibles

## 📚 Documentation API

La documentation complète est disponible via Swagger UI à l'adresse :  
**http://localhost/iso8583_dbs/api/docs**

### Endpoints principaux

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/messages` | Upload et traitement de fichiers XML |
| `GET` | `/api/messages` | Liste paginée des messages |
| `GET` | `/api/messages/{id}` | Détails d'un message |
| `PUT` | `/api/messages/{id}` | Mise à jour d'un message |
| `DELETE` | `/api/messages/{id}` | Suppression d'un message |

## 🛡️ Sécurité

- **Chiffrement AES-256** du PAN avant stockage
- **Authentification JWT** obligatoire pour tous les endpoints
- **Validation** stricte des données d'entrée
- **Protection CORS** configurée

## 🤝 Contribution

Ce projet a été développé par **@say-halatte** dans le cadre d'un test de recrutement pour **Digital Business Solutions SA**.

---

**Développé pour Digital Business Solutions SA**  
*Test de recrutement - Poste d'Ingénieur Support Monétique*
