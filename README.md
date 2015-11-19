githubManagementDocker
======================

Concepts
--------
- Docker deployment from github
- App environment at runtime (and not at buildtime)
 - TODO

Installation
------------
- Install Docker
- Install php and composer
- Create ssh key : `ssh-keygen -f ./config/id_rsa` and then give to repository provider (eg. github)
- Edit `config/config.json` if needed
- `composer install`
- `php scripts/install.php`

TODO
----
- list images
- cleanup old containers and images
- configs per repo for port forwarding (one repos.json ?)
- configs per repo for data shared folder (one repos.json ?)
- Paths in buildImage
- API :
    - start and stop projects
    - list running projects
    - add project (and auto port finding)
    
Thinking
--------
- App environment at runtime for frontend webapps