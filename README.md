## Casa Home (PHP + MySQL)

Prérequis: XAMPP (Apache + MySQL + PHP 8+).

Installation rapide:
- Copier ce dossier dans `htdocs` (ex: `C:\xampp\htdocs\casa-home`).
- Créer la base `casa_home` et exécuter `database/schema.sql`.
- Configurer `config/config.php` (identifiants MySQL, `GOOGLE_MAPS_API_KEY`).
- Accéder à `http://localhost/casa-home/`.

Routes principales:
- `/` Accueil
- `/families` Liste
- `/family/{id}` Détail + réservation
- `/search` Recherche avec filtres
- `/login`, `/register`, POST `/logout`
- `/admin` Dashboard admin

Notes:
- Les uploads sont prévus dans `public/assets/uploads` (à créer avec permissions d'écriture).
- Le placeholder d'image est inclus.
