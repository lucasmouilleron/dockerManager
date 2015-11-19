<?php

///////////////////////////////////////////////////////////////////////////////
require_once __DIR__ . "/../api/libs/vendor/autoload.php";
require_once __DIR__ . "/../api/libs/dockerManager.php";
require_once __DIR__ . "/../api/libs/reposManager.php";
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
$reposManager = new reposManager($config->repositoryBaseURL, makePath($CONFIG_FOLDER, "id_rsa"), $projectsConfig, $config->workBaseFolder);
$repositoryInfos = $reposManager->getRepositoryInfos($projectName);

///////////////////////////////////////////////////////////////////////////////
appendToLog("main", LG_INFO, "cloning repository", $repositoryInfos->repository);
$reposManager->cloneRepository($repositoryInfos->repository);

///////////////////////////////////////////////////////////////////////////////
$imageName = $containerName = $projectName . ":" . $environment;
$dockerFile = makePath($repositoryInfos->cloneFolder, $config->dockerFolder, "Dockerfile");
$dockerManager = new dockerManager($config->dockerMachineName);

///////////////////////////////////////////////////////////////////////////////
appendToLog("main", LG_INFO, "starting docker machine", $dockerManager->dockerMachineName);
$dockerManager->start();
appendToLog("main", LG_INFO, "build docker image from dockerfile", $dockerFile);
$dockerManager->buildImageFromDockerFile($dockerFile, $imageName);
appendToLog("main", LG_INFO, "run docker image", $containerName);
$dockerManager->startContainer($imageName, $containerName, array(array("ENVIRONMENT", $environment)), $repositoryInfos->ports);
