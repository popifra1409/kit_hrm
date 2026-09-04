# API Mobile — KIT GRH

Documentation de référence pour l'application mobile. Une documentation interactive est aussi disponible sur `/docs/api` (voir section Documentation interactive).

**Base URL** : `https://votre-domaine.com/api`

## Authentification

Toutes les routes protégées utilisent un token **Bearer** (Laravel Sanctum). Une fois connecté, incluez ce header sur chaque requête :

```
Authorization: Bearer {token}
Accept: application/json
```

---

## 1. Activer un compte (première connexion)

Les comptes sont créés par les Ressources Humaines avec un mot de passe temporaire. L'employé doit d'abord **activer** son compte avant de pouvoir se connecter normalement.

```
POST /api/auth/activate
```

**Corps de la requête (JSON)**

| Champ | Type | Requis | Description |
|---|---|---|---|
| `matricule` | string | ✅ | Matricule de l'employé |
| `temporary_password` | string | ✅ | Mot de passe temporaire donné par les RH |
| `password` | string | ✅ | Nouveau mot de passe (8 caractères min.) |
| `password_confirmation` | string | ✅ | Confirmation du nouveau mot de passe |

**Exemple**

```bash
curl -X POST https://votre-domaine.com/api/auth/activate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "matricule": "98240812A",
    "temporary_password": "TempPass123",
    "password": "MonNouveauMotDePasse123",
    "password_confirmation": "MonNouveauMotDePasse123"
  }'
```

**Réponse 200**

```json
{
  "message": "Compte activé avec succès.",
  "token": "1|abcdef123456...",
  "user": {
    "id": 1,
    "name": "Jean Dupont",
    "email": "jean.dupont@example.com",
    "roles": ["employee"],
    "employee": {
      "id": 12,
      "matricule": "98240812A",
      "full_name": "DUPONT Jean",
      "photo": null
    }
  }
}
```

**Erreurs possibles**

| Code | Cas |
|---|---|
| 401 | Mot de passe temporaire incorrect |
| 404 | Aucun compte lié à ce matricule |
| 409 | Compte déjà activé (utiliser `/auth/login`) |
| 422 | Validation échouée (voir `errors`) |

---

## 2. Se connecter

Pour un compte déjà activé.

```
POST /api/auth/login
```

**Corps de la requête (JSON)**

| Champ | Type | Requis |
|---|---|---|
| `email` | string | ✅ |
| `password` | string | ✅ |

**Exemple**

```bash
curl -X POST https://votre-domaine.com/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email": "jean.dupont@example.com", "password": "MonNouveauMotDePasse123"}'
```

**Réponse 200** — même format que l'activation (`token` + `user`).

**Erreurs possibles**

| Code | Cas |
|---|---|
| 401 | Email ou mot de passe incorrect |
| 403 | Compte pas encore activé → rediriger vers l'écran d'activation |

---

## 3. Profil de l'utilisateur connecté

```
GET /api/auth/me
```

**Headers requis** : `Authorization: Bearer {token}`

**Exemple**

```bash
curl https://votre-domaine.com/api/auth/me \
  -H "Authorization: Bearer 1|abcdef123456..." \
  -H "Accept: application/json"
```

**Réponse 200**

```json
{
  "user": {
    "id": 1,
    "name": "Jean Dupont",
    "email": "jean.dupont@example.com",
    "roles": ["employee"],
    "employee": {"id": 12, "matricule": "98240812A", "full_name": "DUPONT Jean", "photo": null}
  }
}
```

---

## 4. Se déconnecter

```
POST /api/auth/logout
```

**Headers requis** : `Authorization: Bearer {token}`

Révoque uniquement le token utilisé pour cette requête — les autres appareils connectés restent actifs.

**Réponse 200**

```json
{"message": "Déconnecté avec succès."}
```

---

## Gestion des erreurs générales

| Code HTTP | Signification |
|---|---|
| 401 | Non authentifié — token absent, invalide ou expiré |
| 403 | Authentifié mais action non autorisée |
| 404 | Ressource introuvable |
| 422 | Erreur de validation — voir le champ `errors` |
| 500 | Erreur serveur — contacter l'équipe backend |

---

## Documentation interactive

Une documentation générée automatiquement à partir du code (toujours à jour) est disponible à :

- **UI interactive** : `/docs/api`
- **Spécification OpenAPI (JSON)** : `/docs/api.json` — importable dans Postman, Insomnia, etc.

---

## À venir

Les prochains lots de l'API mobile (congés, présence, notifications...) seront ajoutés à ce document au fur et à mesure de leur développement.