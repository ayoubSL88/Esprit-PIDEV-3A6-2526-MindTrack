# Compte rendu de soutenance Web - Dashboard et Exercices

## 1. Mon perimetre de travail

Je me suis concentre sur deux parties de MindTrack :

1. **Le dashboard et l'accueil** ;
2. **La gestion des exercices**, cote front-office et cote back-office.

Je ne presente pas ici les autres modules fonctionnels de l'application. Ils existent dans le projet global, mais ils ne font pas partie de mon perimetre de demonstration.

## 2. Presentation courte de MindTrack

MindTrack est une application Web developpee avec Symfony 6.4. Son objectif est de proposer des exercices de developpement personnel et de permettre a l'utilisateur de consulter, rechercher et pratiquer ces exercices.

Mon travail apporte :

- une page d'accueil claire ;
- un dashboard utilisateur dedie aux exercices ;
- un dashboard administrateur pour superviser les exercices et les statistiques ;
- un CRUD complet sur l'entite `Exercice` ;
- une interface front de consultation et de recherche ;
- une interface back-office de creation, modification, consultation et suppression ;
- des controles de saisie metier ;
- une integration EasyAdmin comme bundle externe ;
- des recommandations d'exercices basees sur la difficulte, l'humeur et l'historique.

## 3. Architecture de mon travail

### Controleurs principaux

- `src/Controller/Front/HomeController.php`
  - route `/` ;
  - redirection vers `/login` si l'utilisateur n'est pas connecte ;
  - affichage de l'accueil utilisateur.

- `src/Controller/Front/GestionExercices/ExerciceController.php`
  - route `/app/exercices/` ;
  - accueil de la partie exercices ;
  - liste paginee ;
  - recherche ;
  - filtre par difficulte ;
  - affichage du detail ;
  - recommandations selon l'humeur et l'historique.

- `src/Controller/Admin/GestionExercices/AdminHomeController.php`
  - route `/admin/gestion-exercices/` ;
  - dashboard administrateur de la partie exercices ;
  - statistiques ;
  - filtres par utilisateur et par periode ;
  - repartition par difficulte et par type.

- `src/Controller/Admin/GestionExercices/AdminExerciceController.php`
  - route `/admin/exercices/` ;
  - CRUD classique de l'exercice ;
  - recherche par nom ou type ;
  - filtre par difficulte ;
  - creation, modification, affichage et suppression.

- `src/Controller/Admin/EasyAdminDashboardController.php`
  - route `/admin/easy` ;
  - dashboard genere avec EasyAdmin ;
  - acces rapide aux exercices et aux donnees d'administration.

### Templates principaux

- `templates/front/home/index_new.html.twig` : accueil front ;
- `templates/front/gestion_exercices/home_new.html.twig` : dashboard exercices front ;
- `templates/front/gestion_exercices/index_new.html.twig` : liste front ;
- `templates/front/gestion_exercices/show_new.html.twig` : detail front ;
- `templates/admin/dashboard/index_new.html.twig` : dashboard admin general ;
- `templates/admin/gestion_exercices/home_new.html.twig` : dashboard exercices admin ;
- `templates/admin/gestion_exercices/index_new.html.twig` : liste admin ;
- `templates/components/exercises/ExerciseCard.html.twig` : carte reutilisable d'exercice ;
- `templates/components/exercises/ExerciseFilters.html.twig` : composant de recherche et filtre.

## 4. Fonctionnalites du dashboard et de l'accueil

### 4.1 Accueil utilisateur

L'utilisateur arrive sur une interface qui lui permet de comprendre rapidement les possibilites de la plateforme :

- acces aux exercices ;
- acces aux objectifs ;
- acces aux habitudes ;
- acces a l'humeur ;
- acces a son profil.

La navigation est basee sur des composants Twig reutilisables :

- sidebar ;
- top bar ;
- navigation mobile ;
- cartes statistiques ;
- boutons et badges.

La page racine est securisee : un visiteur non connecte est redirige vers la page de connexion, tandis qu'un utilisateur connecte accede a son accueil.

### 4.2 Dashboard front des exercices

Route :

```text
/app/exercices/
```

Le dashboard front affiche :

- les exercices recents ;
- les statistiques de l'utilisateur ;
- le nombre de seances terminees ;
- le temps total pratique ;
- la progression moyenne ;
- des recommandations selon l'historique ;
- une selection selon le niveau d'humeur ;
- des acces rapides vers la liste, l'historique, la progression et les videos.

### 4.3 Dashboard admin des exercices

Routes :

```text
/admin/gestion-exercices/
/admin/gestion-exercices/dashboard
```

Le dashboard administrateur permet de superviser le contenu et l'utilisation des exercices :

- nombre total d'exercices ;
- repartition par difficulte ;
- repartition par type ;
- nombre de sessions ;
- sessions terminees et sessions en cours ;
- temps total pratique ;
- progression moyenne ;
- activite des sept derniers jours ;
- exercices les plus pratiques ;
- filtrage par utilisateur ;
- filtrage par semaine, mois, annee ou dates personnalisees.

Le dashboard ne se limite donc pas a afficher des compteurs : il transforme les donnees en indicateurs utiles pour la supervision.

## 5. CRUD de l'entite Exercice

### 5.1 Attributs de l'entite

L'entite `Exercice` contient :

- `idEx` : identifiant ;
- `nom` ;
- `type` ;
- `duree` ;
- `difficulte` ;
- `description` ;
- `demarche` ;
- `date_creation` ;
- `date_modification`.

L'entite utilise Doctrine ORM et des callbacks de cycle de vie :

- `PrePersist` pour initialiser les dates ;
- `PreUpdate` pour actualiser la date de modification.

### 5.2 Operations CRUD

Dans `AdminExerciceController`, les operations sont :

- **Create** : `/admin/exercices/new` ;
- **Read** : `/admin/exercices/{idEx}` ;
- **Update** : `/admin/exercices/{idEx}/edit` ;
- **Delete** : `/admin/exercices/{idEx}` avec methode POST et CSRF.

La liste admin accepte :

- une recherche par nom ou type ;
- un filtre par difficulte ;
- un tri par identifiant decroissant.

Cote front, l'utilisateur peut :

- consulter la liste ;
- rechercher un exercice ;
- filtrer par difficulte ;
- consulter le detail ;
- lancer un exercice ;
- recevoir une recommandation.

## 6. Controle de saisie de l'exercice

Le formulaire est defini dans `src/Form/ExerciceType.php`.

### Nom

- obligatoire ;
- minimum 3 caracteres ;
- maximum 50 caracteres ;
- caracteres controles par expression reguliere.

### Type

- obligatoire ;
- minimum 2 caracteres ;
- maximum 50 caracteres.

### Duree

- obligatoire ;
- nombre positif ;
- comprise entre 1 et 90 minutes.

### Difficulte

Valeurs autorisees :

- `FACILE` ;
- `MOYEN` ;
- `DIFFICILE`.

### Description

- obligatoire ;
- entre 10 et 500 caracteres.

### Demarche

- obligatoire ;
- entre 20 et 1000 caracteres.

Les contraintes sont appliquees cote serveur avec Symfony Validator. Les erreurs sont renvoyees au formulaire et affichees a l'utilisateur.

## 7. Jointure a expliquer au jury

La partie exercices est liee aux sessions de pratique.

```text
Exercice 1 ---- plusieurs Sessions
Utilisateur 1 ---- plusieurs Sessions
```

Dans Doctrine :

- `Exercice` possede une relation `OneToMany` vers `Session` ;
- `Session` possede une relation `ManyToOne` vers `Exercice` ;
- `Session` possede aussi une relation vers `Utilisateur`.

Cette jointure permet au dashboard de calculer :

- le nombre de sessions par exercice ;
- la progression moyenne par exercice ;
- le temps total pratique ;
- les exercices les plus utilises ;
- l'activite par jour.

C'est ce lien qui transforme un simple catalogue d'exercices en application de suivi.

## 8. Bundle externe integre

J'ai integre **EasyAdminBundle**, un bundle externe Symfony.

Acces :

```text
https://127.0.0.1:8000/admin/easy
```

EasyAdmin fournit :

- une interface d'administration ;
- des listes paginees ;
- des formulaires CRUD ;
- des recherches ;
- des actions standard ;
- une navigation admin configurable.

Le menu EasyAdmin contient notamment :

- Utilisateurs ;
- Exercices ;
- Sessions ;
- lien vers l'administration personnalisee existante.

La partie importante a expliquer est que je n'ai pas seulement installe le bundle : je l'ai configure avec un dashboard et des controleurs CRUD adaptes au projet.

## 9. Fonctionnalites avancees de mon perimetre

### 9.1 Dashboard analytique admin

Le dashboard exercices admin applique plusieurs filtres a une requete Doctrine :

- utilisateur ;
- periode ;
- date de debut ;
- date de fin.

Ensuite, il calcule les statistiques et prepare les donnees du graphique des sept derniers jours.

C'est une fonctionnalite avancee car elle combine :

- QueryBuilder Doctrine ;
- jointures ;
- filtres dynamiques ;
- agregation en PHP ;
- affichage graphique dans Twig.

### 9.2 Recommandation selon la difficulte et l'humeur

Depuis l'espace exercices, l'utilisateur choisit un niveau d'humeur de 1 a 10. Le service de suggestion peut alors proposer un exercice adapte.

Les couleurs permettent une lecture rapide :

- niveaux faibles : couleurs chaudes ;
- niveaux moyens : couleurs intermediaires ;
- niveaux eleves : couleurs vertes, bleues ou violettes.

### 9.3 Filtrage et pagination front

La liste front utilise `KnpPaginatorBundle` et permet :

- recherche par nom ou type ;
- filtre par difficulte ;
- pagination de 12 exercices par page ;
- tri controle par les champs autorises.

## 10. Demonstration conseillee

### Etape 1 - Accueil

1. ouvrir `/` ;
2. montrer la redirection vers `/login` si necessaire ;
3. se connecter ;
4. montrer la sidebar et le dashboard front ;
5. expliquer les cartes et les acces rapides.

### Etape 2 - Consultation front

1. ouvrir `/app/exercices/` ;
2. montrer les cartes d'exercices ;
3. montrer les couleurs de difficulte ;
4. rechercher un exercice ;
5. filtrer par `Facile`, `Moyen` ou `Difficile` ;
6. ouvrir le detail d'un exercice.

### Etape 3 - CRUD admin

1. ouvrir `/admin/exercices/` ;
2. creer un exercice ;
3. provoquer une erreur de duree ou de description ;
4. expliquer la validation ;
5. corriger et enregistrer ;
6. modifier la difficulte ;
7. verifier le rendu cote front ;
8. supprimer un exercice en expliquant le CSRF.

### Etape 4 - Dashboard admin

1. ouvrir `/admin/gestion-exercices/` ;
2. montrer les statistiques ;
3. filtrer par utilisateur ;
4. filtrer par periode ;
5. montrer les graphiques et les exercices les plus pratiques ;
6. expliquer que les donnees viennent des relations entre sessions et exercices.

### Etape 5 - Bundle externe

1. ouvrir `/admin/easy` ;
2. montrer le menu EasyAdmin ;
3. ouvrir Exercices ;
4. montrer la recherche et les actions CRUD ;
5. expliquer la difference entre le back-office personnalise et EasyAdmin.

## 11. Pitch oral centre sur mon travail

> Mon travail porte sur le dashboard et la gestion des exercices de MindTrack. Cote front-office, j'ai mis en place une page d'accueil et un espace exercices permettant de consulter, rechercher et filtrer les exercices. Cote back-office, j'ai developpe un CRUD complet pour creer, consulter, modifier et supprimer les exercices.
>
> Les formulaires sont securises par des controles de saisie Symfony : nom, type, duree, difficulte, description et demarche sont verifies cote serveur. La difficulte est limitee a trois valeurs et la duree est limitee entre 1 et 90 minutes.
>
> La partie exercices est reliee aux sessions par Doctrine ORM. Cette relation permet au dashboard administrateur de calculer le nombre de sessions, la progression moyenne, le temps total et les exercices les plus pratiques. J'ai aussi ajoute des filtres par utilisateur et par periode.
>
> Enfin, j'ai integre EasyAdminBundle comme bundle externe. Il fournit une seconde interface d'administration configurable avec des CRUD, une recherche et une navigation dediee. La fonctionnalite avancee principale de mon travail est le dashboard analytique combine aux recommandations d'exercices selon la difficulte et l'humeur.

## 12. Questions techniques probables et reponses

### Pourquoi avoir choisi Symfony ?

Symfony fournit une structure MVC, le routing, l'injection de dependances, Doctrine, Twig, Validator et Security. Cela permet de separer clairement le controleur, la logique metier, les donnees et l'interface.

### Quelle est la difference entre front-office et back-office ?

Le front-office est destine a l'utilisateur final : consulter et pratiquer les exercices. Le back-office est destine a l'administrateur : gerer les exercices et analyser leur utilisation.

### Ou se trouve le CRUD ?

Le CRUD classique se trouve dans `AdminExerciceController`. Les vues sont dans `templates/admin/gestion_exercices`. Une version complementaire est disponible avec EasyAdmin.

### Comment fonctionne la validation ?

Le formulaire `ExerciceType` declare des contraintes Symfony Validator. Lorsque le formulaire est soumis, Symfony transforme la requete, applique les contraintes et n'enregistre l'entite que si `isValid()` retourne vrai.

### Pourquoi valider cote serveur ?

La validation JavaScript peut etre contournee. La validation serveur est obligatoire car elle protege la base de donnees, meme si la requete est envoyee manuellement.

### Comment eviter une injection SQL dans la recherche ?

La recherche utilise Doctrine QueryBuilder et des parametres nommes comme `:search` et `:difficulte`. Les valeurs ne sont donc pas concatenees directement dans une requete SQL brute.

### Comment fonctionne le filtre par difficulte ?

Le controleur lit le parametre `difficulte`, puis ajoute une condition `e.difficulte = :difficulte`. Le formulaire limite deja les choix aux valeurs autorisees.

### Pourquoi utiliser un repository ?

Le repository centralise l'acces aux donnees. Cela evite de disperser les requetes dans les templates et facilite la maintenance ou l'ajout de filtres.

### Quelle est la relation entre Exercice et Session ?

Un exercice peut etre pratique dans plusieurs sessions. Chaque session est associee a un seul exercice. C'est donc une relation `OneToMany` / `ManyToOne`.

### Comment le dashboard calcule-t-il la progression moyenne ?

Il recupere les sessions filtrees, additionne leurs progressions, puis divise la somme par le nombre de sessions. Le resultat est ensuite arrondi avant affichage.

### Pourquoi utiliser QueryBuilder dans le dashboard admin ?

Parce qu'il faut construire une requete dynamique selon plusieurs filtres : utilisateur, periode et dates personnalisees. QueryBuilder permet d'ajouter ces conditions progressivement.

### Pourquoi avoir choisi EasyAdmin ?

EasyAdmin permet de construire rapidement une interface CRUD professionnelle et maintenable. Il complete le back-office personnalise, qui reste utile lorsque l'on veut une interface metier avec un design et des statistiques specifiques.

### Qu'est-ce qui est vraiment personnalise dans EasyAdmin ?

Le dashboard, le titre, le menu, les entites exposees, les champs, les choix de difficulte, les recherches et les pages CRUD sont configures dans les controleurs EasyAdmin du projet.

### Comment proteger le back-office ?

Les controleurs admin utilisent `#[IsGranted('ROLE_ADMIN')]`. Un utilisateur sans ce role ne peut pas acceder aux routes d'administration.

### Quelle amelioration proposeriez-vous ?

Je proposerais d'ajouter davantage de tests fonctionnels sur le CRUD, de mettre les requetes statistiques dans des methodes de repository et d'ajouter un cache pour les indicateurs du dashboard.

## 13. Reponses courtes en cas de question difficile

- **Pourquoi cette couleur ?** : elle permet d'identifier rapidement le niveau de difficulte sans lire toute la carte.
- **Pourquoi une pagination ?** : elle evite de charger et d'afficher tous les exercices en une seule fois.
- **Pourquoi deux back-offices ?** : l'un est personnalise pour le metier et l'autre montre l'integration d'un bundle externe reutilisable.
- **Que se passe-t-il si aucune donnee n'existe ?** : le dashboard utilise des valeurs par defaut et les templates affichent un etat vide explicite.
- **Que se passe-t-il si un filtre est vide ?** : la condition correspondante n'est pas ajoutee et la liste complete est retournee.
- **Quel est ton apport personnel ?** : la construction des interfaces, le CRUD exercice, les filtres, le dashboard analytique, la navigation et l'integration EasyAdmin.

## 14. Points a preparer avant la soutenance

1. Tester le scenario complet sur une base propre.
2. Verifier la creation, modification et suppression d'un exercice.
3. Verifier un cas d'erreur de validation.
4. Preparer un utilisateur admin et un utilisateur classique.
5. Preparer deux ou trois exercices avec des difficultes differentes.
6. Connaitre les routes principales : `/`, `/app/exercices/`, `/admin/exercices/`, `/admin/gestion-exercices/`, `/admin/easy`.
7. Ne pas presenter les autres modules comme faisant partie de ton travail.
8. Si l'appel YouTube est montre, utiliser une cle stockee dans l'environnement et non une cle ecrite dans le code.

## 15. Conclusion

Mon travail repond aux exigences Web sur un perimetre precis : dashboard/accueil et gestion des exercices. Il combine une interface front-office, un back-office, un CRUD valide, une relation Doctrine avec les sessions, des filtres, une pagination, un dashboard analytique et l'integration d'un bundle externe.

Phrase finale a retenir :

> J'ai transforme un simple catalogue d'exercices en une fonctionnalite complete : l'administrateur gere et analyse les exercices, tandis que l'utilisateur les consulte et les utilise dans un parcours front clair et personnalise.
