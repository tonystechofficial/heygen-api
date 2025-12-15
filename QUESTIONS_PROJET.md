# Questions Projet HeyGen API

Réponds directement dans ce fichier en remplaçant les `[ ]` ou en complétant les lignes.

---

## 🔑 HeyGen API

### 1. Clé API HeyGen
- As-tu une clé API HeyGen ? `[x] Oui` `[ ] Non`
- Si oui, où la stocker ? `[x] Fichier .env` `[ ] Config PHP` `[ ] Autre: ___________`

### 2. Type de vidéo à générer
- `[x] Avatar parlant` (talking avatar avec un script texte)
- `[ ] Template vidéo` (remplir un template existant)
- `[ ] Avatar + audio uploadé`
- `[ ] Autre: ___________`

### 3. Avatar
- `[x] Utiliser les avatars publics HeyGen
- `[ ] J'ai un avatar personnalisé (ID: ___________)

### 4. Voix
- `[x] Lister les voix disponibles via l'API
- `[ ] Utiliser une voix spécifique (ID: ___________)
- `[ ] Les deux options

---

## 🗄️ MySQL

on pourra créer la bdd avec docker compose
### 5. Paramètres de connexion
```
Host: ___________
User: ___________
Password: ___________
Database: ___________
```

### 6. Base de données
- `[x] Créer le schéma complet (tables, migrations)
- `[ ] La base existe déjà (préciser les tables existantes: ___________)

---

## 🔒 Sécurité

### 7. Authentification des endpoints
- `[x] Pas d'authentification (API publique/interne)
- `[ ] API Key simple
- `[ ] JWT
- `[ ] Session PHP classique
- `[ ] Autre: ___________

---

## 🖥️ Interface Frontend

### 8. Interface web souhaitée
- `[x] Oui, interface complète avec Vue.js 3
- `[ ] Non, juste les endpoints API

Si oui, quelles fonctionnalités ?
- `[x] Formulaire pour lancer une génération
- `[x] Liste/historique des vidéos générées
- `[x] Affichage du statut en temps réel
- `[x] Configuration avatar/voix
- `[x] Lecteur vidéo intégré
- `[ ] Autre: ___________

---

## ⚙️ Technique

### 9. Serveur web
- `[ ] Apache (avec .htaccess)
- `[ ] Nginx
- `[ ] Autre: ___________

### 10. Webhooks HeyGen
- `[x] Oui, implémenter un endpoint webhook pour recevoir les notifications
- `[ ] Non, vérification manuelle du statut uniquement

### 11. URL de base production
```
URL: http://localhost/heygen-api
```
(ex: https://monsite.com/api ou http://localhost/heygen-api)

---

## 📹 Paramètres de génération vidéo

### 12. Paramètres à contrôler
- `[x] Dimension (1080p, 720p, 4K)
- `[x] Orientation (paysage, portrait, carré)
- `[x] Qualité vidéo
- `[x] Background/fond personnalisé
- `[x] Durée maximale
- `[x] Vitesse de parole
- `[ ] Autre: ___________

### 13. Dimensions par défaut souhaitées
- `[x] 1920x1080 (Full HD paysage)
- `[x] 1080x1920 (Full HD portrait)
- `[x] 1280x720 (HD paysage)
- `[x] 1080x1080 (Carré)
- `[ ] Autre: ___________

---

## 📝 Notes supplémentaires

Ajoute ici toute information ou besoin spécifique:

```
___________
___________
___________
```

---

**Une fois complété, dis-moi et je commencerai le plan détaillé + implémentation !**

