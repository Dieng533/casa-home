# Guide des Administrateurs - Casa Home

## Vue d'ensemble

Le système d'administration de Casa Home utilise un fichier JSON pour gérer les identifiants des administrateurs. Cela permet une gestion flexible et sécurisée des accès administratifs.

## Fichier de Configuration

### Emplacement
```
public/data/admin_users.json
```

### Structure du Fichier

```json
{
  "admins": [
    {
      "id": 1,
      "email": "admin@casahome.com",
      "password": "admin123",
      "role": "admin",
      "nom": "Admin",
      "prenom": "Principal",
      "telephone": "+221777777777",
      "permissions": ["all"],
      "status": "active",
      "created_at": "2024-01-01T00:00:00Z",
      "last_login": null,
      "avatar": "../assets/images/admin-avatar.png"
    }
  ],
  "permissions": {
    "all": "Accès complet à toutes les fonctionnalités",
    "user_management": "Gestion des utilisateurs",
    "content_moderation": "Modération du contenu",
    "family_validation": "Validation des familles d'accueil"
  },
  "settings": {
    "max_login_attempts": 5,
    "session_timeout": 3600,
    "password_expiry_days": 90
  }
}
```

## Comptes Administrateurs Disponibles

### 1. Administrateur Principal
- **Email :** `admin@casahome.com`
- **Mot de passe :** `admin123`
- **Permissions :** Accès complet (`all`)
- **Statut :** Actif

### 2. Super Administrateur
- **Email :** `superadmin@casahome.com`
- **Mot de passe :** `superadmin2024`
- **Permissions :** Accès complet + gestion système
- **Statut :** Actif

### 3. Modérateur de Contenu
- **Email :** `moderateur@casahome.com`
- **Mot de passe :** `moderateur123`
- **Permissions :** Modération, validation familles, gestion réservations
- **Statut :** Actif

### 4. Support Client
- **Email :** `support@casahome.com`
- **Mot de passe :** `support123`
- **Permissions :** Support utilisateur, gestion réservations, rapports de base
- **Statut :** Actif

## Système de Permissions

### Permissions Disponibles

| Permission | Description |
|------------|-------------|
| `all` | Accès complet à toutes les fonctionnalités |
| `user_management` | Gestion des utilisateurs (création, modification, suppression) |
| `system_config` | Configuration du système |
| `content_moderation` | Modération du contenu |
| `family_validation` | Validation des familles d'accueil |
| `reservation_management` | Gestion des réservations |
| `user_support` | Support utilisateur |
| `basic_reports` | Rapports de base |
| `financial_management` | Gestion financière |
| `analytics` | Analyses et statistiques |

### Attribution des Permissions

```json
"permissions": ["all"]  // Accès complet
"permissions": ["content_moderation", "family_validation"]  // Permissions spécifiques
```

## API d'Authentification

### Endpoint
```
POST /public/api/admin-auth.php
```

### Requête de Connexion
```json
{
  "action": "login",
  "email": "admin@casahome.com",
  "password": "admin123"
}
```

### Réponse de Succès
```json
{
  "success": true,
  "admin": {
    "id": 1,
    "email": "admin@casahome.com",
    "role": "admin",
    "nom": "Admin",
    "prenom": "Principal",
    "permissions": ["all"],
    "status": "active",
    "login_timestamp": "2024-01-15 10:30:00"
  },
  "message": "Connexion réussie"
}
```

### Réponse d'Erreur
```json
{
  "error": "Mot de passe incorrect"
}
```

## Gestion des Comptes

### Ajouter un Nouvel Administrateur

1. **Ouvrir le fichier** `public/data/admin_users.json`
2. **Ajouter un nouvel objet** dans le tableau `admins` :

```json
{
  "id": 5,
  "email": "nouveau@casahome.com",
  "password": "nouveau123",
  "role": "admin",
  "nom": "Nouveau",
  "prenom": "Admin",
  "telephone": "+221777777781",
  "permissions": ["content_moderation", "family_validation"],
  "status": "active",
  "created_at": "2024-01-15T00:00:00Z",
  "last_login": null,
  "avatar": "../assets/images/admin-avatar.png"
}
```

### Désactiver un Compte

Changer le statut de `"active"` à `"inactive"` :

```json
{
  "status": "inactive"
}
```

### Modifier les Permissions

```json
{
  "permissions": ["user_support", "basic_reports"]
}
```

## Sécurité

### Recommandations

1. **Mots de passe forts** : Utiliser des mots de passe complexes
2. **Permissions minimales** : Donner seulement les permissions nécessaires
3. **Rotation des mots de passe** : Changer régulièrement les mots de passe
4. **Surveillance des connexions** : Vérifier les logs de connexion
5. **Désactivation des comptes inutilisés** : Désactiver les comptes non utilisés

### Paramètres de Sécurité

```json
"settings": {
  "max_login_attempts": 5,        // Tentatives de connexion max
  "session_timeout": 3600,        // Timeout de session (secondes)
  "password_expiry_days": 90,     // Expiration des mots de passe
  "require_2fa": false,           // Authentification à deux facteurs
  "log_admin_actions": true       // Log des actions admin
}
```

## Accès à l'Interface Admin

### URL de Connexion
```
/public/admin-dashbord/admin-login.html
```

### Redirection Automatique
Après connexion réussie, l'administrateur est redirigé vers :
```
/public/admin-dashbord/admin.html
```

## Fonctionnalités Avancées

### Gestion des Sessions
- Sessions sécurisées avec timeout automatique
- Nettoyage automatique des données de session
- Log des connexions et déconnexions

### Fallback Local
Si l'API PHP n'est pas disponible, le système utilise un fallback local avec le fichier JSON directement.

### Logs d'Activité
Toutes les actions administratives sont loggées pour audit :
- Connexions et déconnexions
- Modifications de données
- Actions de modération
- Gestion des utilisateurs

## Dépannage

### Problèmes Courants

1. **Erreur "Fichier de configuration non trouvé"**
   - Vérifier que `admin_users.json` existe dans `public/data/`
   - Vérifier les permissions du fichier

2. **Erreur "Compte désactivé"**
   - Vérifier le statut du compte dans le fichier JSON
   - Changer `"status": "inactive"` vers `"status": "active"`

3. **Erreur "Permissions insuffisantes"**
   - Vérifier les permissions attribuées au compte
   - Ajouter les permissions nécessaires

4. **Erreur de connexion API**
   - Vérifier que PHP est activé sur le serveur
   - Vérifier les permissions du fichier `admin-auth.php`

### Test de Connexion

Pour tester la connexion, utiliser un des comptes de test :

```bash
# Test avec curl
curl -X POST http://localhost/casa-home/public/api/admin-auth.php \
  -H "Content-Type: application/json" \
  -d '{"action":"login","email":"admin@casahome.com","password":"admin123"}'
```

## Maintenance

### Sauvegarde
- Sauvegarder régulièrement le fichier `admin_users.json`
- Conserver un historique des modifications

### Mise à Jour
- Vérifier régulièrement les permissions
- Mettre à jour les mots de passe
- Réviser les comptes inactifs

### Monitoring
- Surveiller les tentatives de connexion échouées
- Vérifier les logs d'activité
- Contrôler les permissions des comptes
