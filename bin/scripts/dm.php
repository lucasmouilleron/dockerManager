<?php

///////////////////////////////////////////////////////////////////////////////
require_once __DIR__ . "/../../api/libs/vendor/autoload.php";
require_once __DIR__ . "/../../api/libs/dockerManager.php";
require_once __DIR__ . "/../../api/libs/reposManager.php";
require_once __DIR__ . "/../../api/libs/projectsManager.php";
require_once __DIR__ . "/../../api/libs/tools.php";

///////////////////////////////////////////////////////////////////////////////
array_shift($argv);
$SUB_COMMAND = array_shift($argv);
$ARGUMENTS = $argv;

///////////////////////////////////////////////////////////////////////////////
$CONFIG_FOLDER = __DIR__ . "/../../config";
$CONFIG_FILE = __DIR__ . "/../../config/config.json";
$PROJECTS_CONFIG_FILE = __DIR__ . "/../../config/projects.json";

///////////////////////////////////////////////////////////////////////////////
$CONFIG = jsonFileToObject($CONFIG_FILE);
$DM = new dockerManager($CONFIG->os, $CONFIG->dockerMachineName);
$RM = new reposManager($CONFIG->repositoryBaseURL, makePath($CONFIG_FOLDER, "id_rsa"), $CONFIG->workBaseFolder, $CONFIG->dockerFolder);
$PM = new projectsManager($RM, $DM, $PROJECTS_CONFIG_FILE, $CONFIG->defaultProjectEnvironmentVariable, $CONFIG->publicAutoPortOffset);

///////////////////////////////////////////////////////////////////////////////
switch ($SUB_COMMAND) {
    case "run":
        require_once __DIR__ . "/dmRun.php";
        break;
    case "kill":
        require_once __DIR__ . "/dmKill.php";
        break;
    case "install":
        require_once __DIR__ . "/dmInstall.php";
        break;
    case "add":
        require_once __DIR__ . "/dmAdd.php";
        break;
    case "ps":
        require_once __DIR__ . "/dmPs.php";
        break;
    case "projects":
        require_once __DIR__ . "/dmProjects.php";
        break;
    default:
        require_once __DIR__ . "/dmHelp.php";
        break;
}