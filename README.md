# 🌟 EduPlay - Plateforme Éducative Web (Symfony)

![Symfony](https://img.shields.io/badge/Symfony-6.4-black?style=for-the-badge&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript)

> **EduPlay** est un écosystème numérique innovant dédié à l'éducation des enfants. Cette version web, propulsée par Symfony, offre une interface riche pour les parents, les enseignants et les administrateurs.

---

## 📌 Sommaire
1. [Aperçu du Projet](#-aperçu-du-projet)
2. [Fonctionnalités Détaillées](#-fonctionnalités-détaillées)
3. [Technologies & APIs](#-technologies--apis)
4. [Architecture](#-architecture)
5. [Installation & Configuration](#-installation--configuration)
6. [Topics & Mots Clés](#-topics--mots-clés)

---

## 🔍 Aperçu du Projet
EduPlay Web est le centre névralgique de la plateforme. Il permet de gérer le catalogue pédagogique, de faciliter les interactions entre parents et enseignants, et de proposer une boutique de produits éducatifs. L'accent est mis sur l'interactivité et l'accessibilité.

## ✨ Fonctionnalités Détaillées

### 🎓 Gestion Pédagogique
- **Catalogue de Cours** : Un système de filtrage ultra-performant (Vanilla JS) permettant de trier par niveau, durée et enseignant avec recherche en temps réel.
- **Tableau de bord Enseignant** : Création de contenus, suivi des inscriptions et gestion des ressources.
- **Calendrier Interactif** : Intégration de `tattali/calendar-bundle` pour la planification des événements scolaires.

### 🛒 E-Commerce & Logistique
- **Boutique en ligne** : Catalogue de produits physiques et numériques (livres, jouets éducatifs).
- **Paiements Stripe** : Intégration sécurisée pour les transactions bancaires.
- **Facturation Automatisée** : Génération de documents PDF professionnels pour chaque commande.

### 🤖 Intelligence Artificielle & Services Cloud
- **NLP (Natural Language Processing)** : Analyse du contenu via Google Cloud Language pour assurer la qualité pédagogique.
- **Géolocalisation** : Détection de la position des utilisateurs pour adapter les offres locales.
- **Mails Automatisés** : Notifications système via Google Mailer.

---

## 🛠️ Technologies & APIs

- **Core** : Symfony 6.4, PHP 8.1+, Doctrine ORM
- **Frontend** : Twig, Webpack Encore, Tailwind CSS, JavaScript (ES6+)
- **Sécurité** : JWT (optionnel), reCAPTCHA v3, Symfony Security
- **APIs Tierces** :
  - **Stripe** (Paiements)
  - **Google Cloud** (Language API, Calendar API)
  - **Abstract API** (Géolocalisation)
  - **Cloudinary** (Gestion des médias)

---

## 📂 Architecture

```bash
EduPlay/
├── assets/             # Styles et Scripts sources
├── config/             # Configuration des services et de la sécurité
├── migrations/         # Historique de la base de données
├── src/                # Logique métier (Controllers, Entities, Services)
├── templates/          # Vues Twig (Front/Back/Layouts)
└── tests/              # Tests automatisés
```

---

## ⚙️ Installation & Configuration

1. **Clonage & Dépendances** :
   ```bash
   composer install
   npm install && npm run build
   ```
2. **Environnement** : Configurez le `.env.local` avec vos clés API (Stripe, Google, Database).
3. **Base de données** :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
4. **Serveur** : `symfony serve`

---

## 🏷️ Topics & Mots Clés

### **Topics (GitHub Style)**
`#symfony` `#php` `#education` `#ecommerce` `#web-platform` `#javascript` `#ai` `#nlp` `#stripe` `#tailwindcss`

### **Mots Clés**
- **Secteur** : EdTech, Éducation, Enfants, Plateforme Scolaire.
- **Technique** : Symfony Framework, Doctrine, Twig, Full-stack Web.
- **Fonctionnel** : Shop, Cours, Recommandations IA, Facturation PDF, QR Code.

---
⭐ *EduPlay Web - Façonner l'éducation par la technologie.*
