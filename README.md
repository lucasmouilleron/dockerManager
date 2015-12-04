dockerManager
=============

dockerManager is a local and remote Docker deployment tool for git versionned projects.
Ideal for web agency testing.

![Screenshot](http://grabs.lucasmouilleron.com/Screen%20Shot%202015-11-23%20at%2019.33.15.png)

Concepts
--------
- Local and remote Docker deployment for github projects
- Project ports and shared folder configuration 
- Project environment variable for runtime environment context (and not at buildtime)
- Project add wizard (auto port offseting)
- Export containers files or folders of projects to the dockerManager host (see `exportCommnds` and `exportFilesAndFolders`)

Installation
------------
- Install Docker on target hosts (on MacOS, install `docker-machine` and install a machine named `config/config.json > defaultDockerMachineName`)
- Install php and composer
- Create ssh key : `ssh-keygen -f ./config/id_rsa` and then give to repository provider (eg. github)
- `composer install`
- Edit `config/config.json` if needed
- `bin/dm install`

Project compliancy
------------------
- `Dockerfile` must be in `PROJECT_ROOT/docker` folder
- Project should be cloned at the runtime of the docker container. The revision is passed in the `REVISION` env variable.
- Project environment variable is by default passed in the `ENVIRONMENT` env variable, which can be override in project configuration attribute `environmentVariable`
- Containers are ran as daemons
- Containers should not expose ports above `config/config.json > publicAutoPortOffset`
- Project can be deloyed localy (`"URI":"local"` in `config/projects.jon`, or remotely : `"URI":"user@host"`)
- Project `github.com:lucasmouilleron/dockeranagerTest` can be used for reference
- Projects are configured in `config/projects.json`

How to use
----------
- `bin/dm` and follow instructions
- Run the test project : `bin/dm run test`, `bin/dm run test REVISION_NUMBER` 
- API, TODO
    
Thinking
--------
- For the Dockerfile and/or the docker container to be able to git clone the project, an ssh key must be provided. One solution is to embed the key in the repository. The key can be then associated to a user account (or to the repository deployment keys, which is a per project configuration) on the git repository provider.
- On tester / client machines, use /etc/hosts or GasMask or HostMan so the production domain / URL points to the Docker server
- Webapps : App environment at runtime ?
- Websites : Avoid absolute urls, so the port is forwarded to the next page.

TODO
----
- Explain export
- Improve dm commands output
- API :
    - REST
    - tokens
    - start and stop projects
    - list running projects
- Sharedfolder in buildImage
- multiple git provider (github, some gitlabs)