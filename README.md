dockerManager
=============

dockerManager is a Docker deployment tool for git versionned projects.
Ideal for web agency testing.

Concepts
--------
- Local Docker deployment for github projects
- Project ports and shared folder configuration 
- Project environment variable for runtime environment context (and not at buildtime)
- Project add wizard (auto port offseting)
- TODO

Installation
------------
- Install Docker
- Install php and composer
- Create ssh key : `ssh-keygen -f ./config/id_rsa` and then give to repository provider (eg. github)
- Edit `config/config.json` if needed
- `composer install`
- `php scripts/install.php`

Project compliancy
------------------
- `Dockerfile` must be in `PROJECT_ROOT/docker` folder
- Project environment variable is by default passed in `ENVIRONMENT` env variable, which can be override in project configuration attribute `environmentVariable`
- Containers are ran as daemons 

TODO
----
- cp files from container to host : docker cp CONTAINER:PATH HOSTPATH
- API :
    - REST
    - tokens
    - start and stop projects
    - list running projects
- list images
- Sharedfolder in buildImage
- multiple git provider (github, some gitlabs)
    
Thinking
--------
- On tester / client machines, use /etc/hosts or GasMask or HostMan so the production domain / URL points to the Docker server
- Websites : 
- Webapps : App environment at runtime ?