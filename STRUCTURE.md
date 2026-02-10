# EduPlay – Structure du projet (Plateforme éducation enfants)

## Types d'utilisateurs
- **Admin** : gestion complète (produits, commandes, utilisateurs)
- **Teacher** : contenu pédagogique, cours, événements
- **Parent** : achats (shop), suivi des enfants, inscriptions

---

## Architecture globale

### Backend (Symfony)
- **Emplacement** : `src/`
- **Contrôleurs** : `src/Controller/`
  - **FrontOffice** : `Controller/Front/` (site public : accueil, shop, panier)
  - **BackOffice** : `Controller/Back/` (admin : CRUD Product, Commande, etc.)
- **Entités** : `src/Entity/` (Product, Commande, User, …)
- **Formulaires** : `src/Form/` (ProductType, CommandeType, validation)
- **Repositories** : `src/Repository/`

### Frontend (Templates Twig)
- **Emplacement** : `templates/`
- **FrontOffice** (site public) :
  - `templates/base.html.twig` – layout principal
  - `templates/front/` – pages accueil, shop, détail produit, commande
- **BackOffice** (administration) :
  - `templates/back/base.html.twig` – layout admin
  - `templates/back/product/` – CRUD Product
  - `templates/back/commande/` – CRUD Commande

---

## Routes (résumé)

| Zone        | Préfixe   | Exemples                                      |
|------------|-----------|-----------------------------------------------|
| FrontOffice| `/`       | `/` (accueil), `/shop`, `/shop/{id}`          |
| BackOffice | `/admin`  | `/admin`, `/admin/product`, `/admin/commande` |

---

## Contrôle d’accès (à configurer dans security.yaml)
- `/admin/*` → **ROLE_ADMIN**
- `/teacher/*` → **ROLE_TEACHER**
- Shop / commande → **ROLE_PARENT** ou **ROLE_USER**

---

## CRUD et contrôle de saisie
- **Product** : name, price, description, availability (validation PHP + Assert sur l’entité, formulaire ProductType).
- **Commande** : user, product, quantity, dateCommande, totalAmount (validation PHP + Assert, formulaire CommandeType).
- Contrôle de saisie : contraintes Symfony Validator (Assert) + validation dans les formulaires.
