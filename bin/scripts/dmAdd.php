<?php

///////////////////////////////////////////////////////////////////////////////
$projectName = getArgument($COMMAND, $ARGUMENTS, 0);
$repository = getArgument($COMMAND, $ARGUMENTS, 1);
$URI = getArgument($COMMAND, $ARGUMENTS, 2);
$OS = getArgument($COMMAND, $ARGUMENTS, 3);
$environmentVariable = getArgument($COMMAND, $ARGUMENTS, 4);
$environmentVariableValue = getArgument($COMMAND, $ARGUMENTS, 5);

$ports = array();
$portsArg = getArgument($COMMAND, $ARGUMENTS, 6);
if ($portsArg != "") $ports = explode(":", $portsArg);


///////////////////////////////////////////////////////////////////////////////
if ($PM->addProject($projectName, $repository, $ports, $environmentVariable, $environmentVariableValue, $URI, $OS)) {
    appendToLog(LG_MAIN, LG_INFO, "project added", $projectName, $repository, $ports, $environmentVariable, $environmentVariableValue, $URI, $OS);
} else {
    appendToLog(LG_MAIN, LG_SEVERE, "project not added", $projectName, $repository, $ports, $environmentVariable, $environmentVariableValue, $URI, $OS);
}