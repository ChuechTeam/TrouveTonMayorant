# TrouveTonMayorant

Le site de rencontre pour matheux qui souhaitent trouver leur mayorant. (Aussi appelé TTM)

## Prérequis

- **Linux** (Debian, Ubuntu, ...)
  - Paquet `php`
- **Windows**
  - [XAMPP](https://www.apachefriends.org/fr/index.html)
  - Beaucoup de courage

## Lancement sur Linux

1. Cloner le dépôt TrouveTonMayorant dans le dossier de votre choix :
    ```bash
    git clone https://github.com/ChuechTeam/TrouveTonMayorant.git
    cd TrouveTonMayorant
    ```
2. Exécuter le script de lancement `./run.sh` :
    ```bash
    ./run.sh
    ```
3. C'est tout, le site est accessible à l'adresse `http://localhost:8080`.

## Comptes administrateur

Lors du premier lancement, TTM crée un compte utilisateur administrateur par défaut :
- **E-mail** : admin@ttm.fr
- **Mot de passe** : admin

Il est possible de créer un compte administrateur avec le script PHP `scripts/createAdminAccount.php`.
Celui-ci prend en entrée standard (optionnellement) un fichier JSON avec les informations de l'utilisateur, 
avec les propriétés suivantes :
```json5
{
    "firstName": "Mister",
    "lastName": "Egg",
    "email": "admin@ttm.fr",
    "password": "admin",
    "birthDate": "01/01/2000"
}
```

Si une propriété n'est pas remplie, la valeur par défaut (voir ci-dessus) est utilisée. Pour lancer le script PHP,
il existe deux façons de le faire :
1. `php scripts/createAdminAccount.php < admin.json`
2. `./scripts/createAdminAccount.php < admin.json`

## Gestion des données

TTM stocke les données textuelles dans plusieurs fichiers JSON (les « bases de données »).
Chaque "module" possède différentes méthodes de stockage :
- **Utilisateurs** : `users.json`
- **Modération** : `moderation.json`
- **Conversations** : `conversations/[identifiant].json`
- **Stats de profil** : `views/[identifiant].json`
- **Images mises en ligne** : `src/user-image-db/[identifiant]/[nom].{jpg|jpeg|png|gif}`

Lorsqu'une nouvelle version de TTM nécessitant une mise à jour
des bases de données est déployée, il est recommandé de fermer le serveur et lancer
le script de mise à jour `scripts/updateDatabases.php`.

Si ce n'est pas possible, les bases de données se mettent automatiquement à jour durant les
requêtes les sollicitant.

> [!NOTE]
> Lorsqu'un utilisateur est supprimé, ses images mises en ligne ne sont pas supprimées pour le moment.
> Un nettoyage des fichiers inutilisés doit être fait manuellement.

Les sessions PHP sont stockées dans le dossier `sessions`.

## Limitations

TTM est sujet à plusieurs limitations :
- **Stockage** : Le stockage de TTM reposant sur des fichiers JSON et non une base de
  données SQL, le site a des difficultés à gérer un grand nombre d'utilisateurs
  (avoir 100 utilisateurs inscrits entraîne une latence de 80ms par requête !).  
  À l'avenir, il sera envisageable de simplement modifier les fonctions de `userDB.php`, `conversationDB.php`, etc.
  afin d'utiliser des requêtes SQL.
- **Support du navigateur** : TTM utilise des technologies Web récentes (Custom Elements, CSS Nesting, `:has`, Container Queries, ES6, etc.)
  ce qui le rend incompatible avec les navigateurs plus anciens que :
  - Firefox 117 (Firefox 115 supporté si le flag layout.css.nesting.enabled est activé) 
  - Chromium 120
  - Safari 17.2
- **Conversations** : Il est impossible de supprimer ou masquer des conversations, ni
  de connaître leur date de création.

## Ressources externes

TTM utilise trois ressources externes :
- **Material Symbols** : La police d'icônes de Google (dans `/src/assets/matsym-rounded.woff2`)
- **MathJax** : Permet d'afficher des formules mathématiques bien formatées
- **Police Computer Modern** : Pour utiliser la police iconique de LaTeX