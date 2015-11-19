<?php

///////////////////////////////////////////////////////////////////////////////
require_once __DIR__ . "/../api/libs/vendor/autoload.php";
require_once __DIR__ . "/../api/libs/dockerManager.php";
require_once __DIR__ . "/../api/libs/gitManager.php";
require_once __DIR__ . "/../api/libs/tools.php";

///////////////////////////////////////////////////////////////////////////////
$CONFIG_FOLDER = __DIR__ . "/../config";
$CONFIG_FILE = __DIR__ . "/../config/config.json";

///////////////////////////////////////////////////////////////////////////////
$config = jsonFileToObject($CONFIG_FILE);
$repository = $argv[1];
$environment = $argv[2];

///////////////////////////////////////////////////////////////////////////////
$workFolder = makePath($config->workBaseFolder, $repository);
$gitManager = new gitManager($config->repositoryBaseURL, $CONFIG_FOLDER . "/id_rsa");

///////////////////////////////////////////////////////////////////////////////
$imageName = $containerName = $repository . ":" . $environment;
$dockerFile = makePath($workFolder, $config->dockerFolder, "Dockerfile");
$dockerManager = new dockerManager($config->dockerMachineName);

///////////////////////////////////////////////////////////////////////////////
appendToLog("main", LG_INFO, "cloning repository", $repository);
$gitManager->cloneRepository($repository,$workFolder);

///////////////////////////////////////////////////////////////////////////////
appendToLog("main", LG_INFO, "starting docker machine", $dockerManager->dockerMachineName);
$dockerManager->start();
appendToLog("main", LG_INFO, "build docker image from dockerfile", $dockerFile);
$dockerManager->buildImageFromDockerFile($dockerFile, $imageName);
appendToLog("main", LG_INFO, "run docker image", $containerName);
$dockerManager->runImage($imageName, $containerName);
//$dockerManager->runImage($imageName, $containerName, $envs, $ports, $paths);