# 📋 Plan d'implémentation - HeyGen API

## 📁 Architecture du projet

```
heygen-api/
├── docker-compose.yml          # MySQL + PHPMyAdmin
├── .env                        # Variables d'environnement (API keys, DB)
├── .env.example                # Template .env
├── .htaccess                   # Réécriture URL pour Apache
│
├── api/                        # Backend PHP
│   ├── index.php               # Router principal
│   ├── config/
│   │   └── Database.php        # Connexion MySQL (Singleton)
│   │
│   ├── services/
│   │   └── HeygenService.php   # Client API HeyGen (SOLID)
│   │
│   ├── repositories/
│   │   ├── VideoRepository.php # CRUD vidéos
│   │   ├── AvatarRepository.php# Cache avatars
│   │   └── VoiceRepository.php # Cache voix
│   │
│   ├── controllers/
│   │   ├── VideoController.php # Endpoints vidéos
│   │   ├── AvatarController.php# Endpoints avatars
│   │   ├── VoiceController.php # Endpoints voix
│   │   └── WebhookController.php# Webhook HeyGen
│   │
│   └── migrations/
│       └── init.sql            # Schéma BDD
│
├── public/                     # Frontend
│   ├── index.html              # App Vue.js 3 (CDN)
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js              # Logique Vue.js
│
└── QUESTIONS_PROJET.md
```

---

## 🔌 Endpoints API

### Vidéos (Principal)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/videos/generate` | **Lancer une génération** |
| `GET` | `/api/videos/{id}/status` | **Voir le statut** |
| `GET` | `/api/videos` | Liste des vidéos |
| `GET` | `/api/videos/{id}` | Détail d'une vidéo |
| `DELETE` | `/api/videos/{id}` | Supprimer une vidéo |

### Avatars

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/avatars` | Liste des avatars disponibles |
| `POST` | `/api/avatars/sync` | Synchroniser depuis HeyGen |

### Voix

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/voices` | Liste des voix disponibles |
| `POST` | `/api/voices/sync` | Synchroniser depuis HeyGen |

### Webhook

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/webhook/heygen` | Callback HeyGen (vidéo prête) |

---

## 📝 Paramètres POST `/api/videos/generate`

```json
{
  "title": "Ma vidéo",
  "script": "Bonjour, bienvenue sur notre chaîne !",
  "avatar_id": "josh_lite3_20230714",
  "voice_id": "fr-FR-DeniseNeural",
  "settings": {
    "width": 1920,
    "height": 1080,
    "background_color": "#FFFFFF",
    "background_image_url": null,
    "speed": 1.0
  }
}
```

---

## 🗄️ Schéma MySQL

### Table `video_generations`
- `id`, `heygen_video_id`, `title`, `script`
- `avatar_id`, `voice_id`, `status`
- `video_url`, `thumbnail_url`, `duration`
- `width`, `height`, `error_message`
- `created_at`, `updated_at`

### Table `avatars_cache`
- `id`, `heygen_avatar_id`, `name`, `preview_url`, `gender`, `cached_at`

### Table `voices_cache`
- `id`, `heygen_voice_id`, `name`, `language`, `gender`, `preview_url`, `cached_at`

---

## ✅ Liste des tâches

### Phase 1 : Infrastructure
- [ ] 1.1 Créer `docker-compose.yml` (MySQL + PHPMyAdmin)
- [ ] 1.2 Créer `.env` et `.env.example`
- [ ] 1.3 Créer le schéma SQL (`migrations/init.sql`)

### Phase 2 : Backend PHP (SOLID)
- [ ] 2.1 Créer `config/Database.php` (Singleton)
- [ ] 2.2 Créer `services/HeygenService.php` (Client API)
- [ ] 2.3 Créer les Repositories (Video, Avatar, Voice)
- [ ] 2.4 Créer les Controllers
- [ ] 2.5 Créer le Router (`api/index.php`)
- [ ] 2.6 Créer `.htaccess` pour la réécriture URL

### Phase 3 : Frontend Vue.js 3
- [ ] 3.1 Créer `public/index.html` avec Vue.js CDN
- [ ] 3.2 Créer `public/css/style.css`
- [ ] 3.3 Créer `public/js/app.js` (Composition API)
- [ ] 3.4 Implémenter les composants :
  - Formulaire de génération
  - Liste des vidéos avec statut temps réel
  - Sélecteur avatar/voix
  - Lecteur vidéo

### Phase 4 : Tests
- [ ] 4.1 Tester les endpoints avec curl
- [ ] 4.2 Tester l'interface

---

## 🚀 Prêt à implémenter ?

Dis **"Go"** et je commence l'implémentation complète !

