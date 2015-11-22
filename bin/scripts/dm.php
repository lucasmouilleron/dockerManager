<?php

///////////////////////////////////////////////////////////////////////////////
require_once __DIR__ . "/../../api/libs/vendor/autoload.php";
require_once __DIR__ . "/../../api/libs/dockerManager.php";
require_once __DIR__ . "/../../api/libs/reposManager.php";
require_once __DIR__ . "/../../api/libs/projectsManager.php";
require_once __DIR__ . "/../../api/libs/tools.php";

///////////////////////////////////////////////////////////////////////////////
$CONFIG_FOLDER = __DIR__ . "/../../config";
$CONFIG_FILE = __DIR__ . "/../../config/config.json";
$PROJECTS_CONFIG_FILE = __DIR__ . "/../../config/projects.json";

///////////////////////////////////////////////////////////////////////////////
$CONFIG = jsonFileToObject($CONFIG_FILE);
$DM = new dockerManager($CONFIG->os, $CONFIG->dockerMachineName, $CONFIG->guestExportFolder, $CONFIG->hostExportFolder);
$RM = new reposManager($CONFIG->repositoryBaseURL, makePath($CONFIG_FOLDER, "id_rsa"), $CONFIG->workBaseFolder, $CONFIG->dockerFolder);
$PM = new projectsManager($RM, $DM, $PROJECTS_CONFIG_FILE, $CONFIG->defaultProjectEnvironmentVariable, $CONFIG->publicAutoPortOffset);

///////////////////////////////////////////////////////////////////////////////
array_shift($argv);
$COMMANDS = jsonFileToObject(__DIR__ . "/commands.json");
$inputCommand = array_shift($argv);
$ARGUMENTS = $argv;
$COMMAND = getCommand($COMMANDS, $inputCommand);

if (!isset($inputCommand) || $inputCommand == "") {
    require_once __DIR__ . "/dmHelp.php";
} else if ($COMMAND == null) {
    appendToLog(LG_MAIN, LG_SEVERE, "command does not exist", $inputCommand);
    require_once __DIR__ . "/dmHelp.php";
} else if (!testArguments($COMMAND, $ARGUMENTS)) {
    displayCommandHelp($COMMAND, "argument(s) missing");
} else {
    require_once __DIR__ . "/dm" . ucwords($COMMAND->name) . ".php";
}

///////////////////////////////////////////////////////////////////////////////
function getCommand($commands, $inputCommand)
{
    foreach ($commands as $commandToTest) {
        if ($inputCommand == $commandToTest->name) {
            return $commandToTest;
        }
    }
    return null;
}

///////////////////////////////////////////////////////////////////////////////
function testArguments($command, $arguments)
{
    $nbMandatories = 0;
    foreach ($command->arguments as $argument) {
        if ($argument->mandatory) {
            $nbMandatories++;
        } else {
            break;
        }

    }
    return (count($arguments) >= $nbMandatories);
}

///////////////////////////////////////////////////////////////////////////////
function getArgument($command, $arguments, $argumentIndex)
{
    if ($argumentIndex >= count($command->arguments)) {
        throw new Exception(message("argument not expected", $argumentIndex, $command));
    }
    $arg = @$arguments[$argumentIndex];
    if ((!isset($arg) || $arg == "") && $command->arguments[$argumentIndex]->mandatory) {
        throw new Exception(message("argument not found", $argumentIndex, $command->arguments[$argumentIndex]));
    } else if ((!isset($arg) || $arg == "") && !$command->arguments[$argumentIndex]->mandatory) {
        return $command->arguments[$argumentIndex]->defaultValue;
    } else {
        return $arguments[$argumentIndex];
    }
}

///////////////////////////////////////////////////////////////////////////////
function displayCommandHelp($command, $reason = null)
{
    if ($reason != null) {
        appendToLog(LG_MAIN, LG_SEVERE, "cant't run command", $command->name, $reason);
    }
    appendToLog(LG_MAIN, LG_INFO, "command", $command->name);
    appendToLog(LG_MAIN, LG_INFO, "description", $command->description);
    appendToLog(LG_MAIN, LG_INFO, "# arguments", count($command->arguments));
    $i = 1;
    foreach ($command->arguments as $argument) {
        appendToLog(LG_MAIN, LG_INFO, "argument " . $i, $argument);
        $i++;
    }
}