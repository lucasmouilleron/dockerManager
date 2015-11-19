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

///////////////////////////////////////////////////////////////////////////////
$dockerManager = new dockerManager($config->dockerMachineName);
$reposManager = new reposManager($config->repositoryBaseURL, makePath($CONFIG_FOLDER, "id_rsa"), $config->workBaseFolder, $config->dockerFolder);
$projectsManager = new projectsManager($reposManager, $dockerManager, $projectsConfig);

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "seting up repo manager");
$projectsManager->reposManager->setup();