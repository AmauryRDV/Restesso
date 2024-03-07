# Restesso
Un repas, un café, un Restesso :coffee:

## Description
Restesso est une API réalisée dans le cadre d'une formation API à Ynov. 
Elle tourne autour du café, de ses catégories, des types et différents grains de cafés.

Pour utiliser l'API, il vous faudra vous connecter à l'aide d'un compte public ou d'un compte admin, les identifiants de ce dernier peuvent être modifer dans le .env.
Pour accéder à la documentation de l'API, lancer Symfony serve et ajouter ce chemin à votre localhost : /api/v1/doc

ou en cliquant [ici](http://localhost:8000/api/v1/doc) une fois le serveur démarré

## Restriction des accès
Un utilisateur non authentifié a seulement accès aux endpoints suivant :
- `/api/v1/login_check`
- `/api/v1/token/refresh?refresh_token={value}`

Accès aux méthodes avec un compte public :unlock::
- `GET`

Accès aux méthodes avec un compte admin :closed_lock_with_key:: 
- `GET, PUT, POST, DELETE`

## Setup de l'environnement
Afin de lancer l'API, il est recommandé de créer un nouveau fichier .env.local en copie du fichier .env et de remplacer les valeurs ***change-me*** par des valeurs réelles.

Attention à bien indiquer le JWT_PASSPHRASE correspondant après avoir générer les clés public et private.

## Commandes à réaliser :
### Genération des clés public et privée
```bash
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```
### Mise en place de l'environnement
```bash
composer install
composer update
php bin/console d:s:u --force
php bin/console d:f:l
```
### Lancement du serveur
```bash
symfony serve
```

PS : Fut un temps où les postes PUT de coffee fonctionnaient, mais depuis un commit dont seul dieu en connait le hash, ces derniers ne fonctionnent plus. En cause la liaison avec nos tables. Nous avons testé, essayé, supprimé, réécrit moult fois nos lignes et nos esprits mais rien n'y fait nous restâmes bloqués à jamais.

## Auteurs
Réalisés par :
[Sacha](https://github.com/SachaBarbet) et [Amaury](https://github.com/AmauryRDV)
