<?php

///////////////////////////////////////////////////////////////////////////////
require_once __DIR__ . "/../api/libs/vendor/autoload.php";
require_once __DIR__ . "/../api/libs/dockerManager.php";
require_once __DIR__ . "/../api/libs/reposManager.php";
require_once __DIR__ . "/../api/libs/projectsManager.php";
require_once __DIR__ . "/../api/libs/tools.php";

///////////////////////////////////////////////////////////////////////////////
$CONFIG_FOLDER = __DIR__ . "/../config";
$CONFIG_FILE = __DIR__ . "/../config/config.json";
$PROJECTS_CONFIG_FILE = __DIR__ . "/../config/projects.json";

///////////////////////////////////////////////////////////////////////////////
$config = jsonFileToObject($CONFIG_FILE);
$projectsConfig = jsonFileToObject($PROJECTS_CONFIG_FILE);
$projectName = $argv[1];
$environment = $argv[2];

///////////////////////////////////////////////////////////////////////////////
$dockerManager = new dockerManager($config->dockerMachineName);
$reposManager = new reposManager($config->repositoryBaseURL, makePath($CONFIG_FOLDER, "id_rsa"), $config->workBaseFolder, $config->dockerFolder);
$projectsManager = new projectsManager($reposManager, $dockerManager, $projectsConfig);
$repositoryInfos = $projectsManager->getProjectInfos($projectName);

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "starting docker", $dockerManager->dockerMachineName);
$projectsManager->startDocker();

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "cloning repository", $repositoryInfos->repository, "for projcet", $projectName);
$projectsManager->cloneRepository($projectName);

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "build  project image for project", $projectName, "for environment", $environment);
$projectsManager->buildImage($projectName, $environment);
appendToLog(LG_MAIN, LG_INFO, "start container for project", $projectName, "for environment", $environment);
$projectsManager->startContainer($projectName, $environment);