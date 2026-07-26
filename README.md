<div align="center">

# 🌿 GreenShop

**Boutique en ligne de plantes d'intérieur** — un projet e-commerce complet développé avec Symfony.

Catalogue, panier avec réservation de stock, commandes, espace client, back-office et API REST sécurisée.

</div>

---

## Aperçu

GreenShop est une boutique de plantes pensée comme un vrai projet e-commerce, du catalogue public jusqu'à la validation de commande en passant par un back-office d'administration et une API REST authentifiée par jeton.

*(Captures d'écran en bas de page.)*

---

## Fonctionnalités

### Côté client
- **Catalogue** avec recherche, filtre par prix et fiches produit détaillées
- **Stock en temps réel** : le disponible affiché tient compte des réservations en cours, de façon cohérente sur toutes les pages
- **Panier** réservé aux membres connectés, avec réservation de stock d'une heure fixée à l'ajout du premier article
- **Ajustement des quantités** directement depuis le panier (avec revérification du stock)
- **Validation de commande** en transaction atomique : le stock est revérifié et décrémenté, la commande créée et les réservations libérées — le tout ou rien
- **Espace compte** : tableau de bord, historique des commandes, détail avec suivi de statut, gestion du profil

### Côté administration
- **Gestion des produits** : création, édition, suppression, avec téléversement d'images
- **Protection de l'historique** : un produit déjà commandé est dépublié plutôt que supprimé
- **Gestion des commandes** : changement de statut, action rapide « étape suivante », archivage, filtres et tri par colonne

### API REST
- Endpoints de lecture publics, écriture réservée aux administrateurs
- Groupes de sérialisation (aucune donnée sensible exposée)
- Validation des données avec codes HTTP appropriés (201, 400, 404, 422…)
- **Authentification par JWT** (JSON Web Token)

### Sécurité
- Authentification par formulaire, inscription avec politique de mot de passe stricte (validée en temps réel via Stimulus)
- Hiérarchie de rôles, `remember me`, limitation des tentatives de connexion
- Protection CSRF sur toutes les actions sensibles
- Contrôle d'accès par **Voter** (protection anti-IDOR sur les commandes)

---

## Stack technique

| Domaine | Technologie |
|---|---|
| Framework | Symfony 8.1 |
| Langage | PHP 8.4 |
| Base de données | MySQL 8 / Doctrine ORM |
| Assets | AssetMapper (sans build) |
| Interactivité | Stimulus (Hotwired) |
| API | Contrôleurs manuels + LexikJWTAuthenticationBundle |
| Templates | Twig |

---

## Installation

### Prérequis
- PHP 8.4 avec les extensions `bcmath`, `pdo_mysql`, `intl`
- Composer
- MySQL 8
- OpenSSL (pour générer les clés JWT)

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/RemiPIERRE/GreenShop.git
cd GreenShop

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
```

Dans `.env.local`, renseignez votre connexion à la base et une passphrase JWT :

```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/greenshop?serverVersion=8.0&charset=utf8mb4"
JWT_PASSPHRASE=votre_passphrase_locale
```

```bash
# 4. Créer la base et son schéma
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Générer les clés JWT (utilise la passphrase du .env.local)
php bin/console lexik:jwt:generate-keypair

# 6. Charger les données de démonstration
php bin/console doctrine:fixtures:load

# 7. Lancer le serveur
symfony serve
```

Le site est alors accessible sur `https://127.0.0.1:8000`.

---

## Comptes de démonstration

Les fixtures créent les comptes suivants :

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@greenshop.fr` | `admin1234` |
| Client | `alice@example.com` | `password123` |
| Client | `bob@example.com` | `password123` |
| Client | `chloe@example.com` | `password123` |

---

## À noter

- Le dossier `public/uploads/products/` est ignoré par Git : après un premier chargement des fixtures, les plantes s'affichent avec l'initiale de leur nom en repli, jusqu'à ce que des images soient téléversées depuis le back-office.
- Les clés JWT (`config/jwt/`) et le fichier `.env.local` ne sont pas versionnés : ils sont générés localement à l'installation.

---

## Tester l'API

Obtenir un jeton :

```bash
curl -X POST https://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@greenshop.fr","password":"admin1234"}'
```

Utiliser le jeton pour une requête protégée :

```bash
curl -X POST https://127.0.0.1:8000/api/products \
  -H "Authorization: Bearer VOTRE_JETON" \
  -H "Content-Type: application/json" \
  -d '{"name":"Nouvelle plante","price":"19.90","description":"...","stock":10,"categoryId":1}'
```

---

## Captures d'écran

### Accueil
![Page d'accueil](docs/screenshots/01-accueil.png)

### Catalogue
Grille des plantes avec stock disponible calculé en temps réel.

![Catalogue](docs/screenshots/02-catalogue.png)

### Fiche produit
![Fiche produit](docs/screenshots/03-fiche-produit.png)

### Espace client
Tableau de bord avec statistiques, dernière commande et suivi de statut.

![Tableau de bord client](docs/screenshots/04-compte.png)

### Panier
Ajustement des quantités et réservation de stock.

![Panier](docs/screenshots/05-panier.png)

### Back-office — commandes
Gestion des statuts, action rapide, archivage et filtres.

![Administration des commandes](docs/screenshots/06-admin-commandes.png)

---

<div align="center">

Projet réalisé dans le cadre d'un apprentissage approfondi de Symfony.

</div>
