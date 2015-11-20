<?php

///////////////////////////////////////////////////////////////////////////////
$projectName = getArgument($COMMAND, $ARGUMENTS, 0);
$repository = getArgument($COMMAND, $ARGUMENTS, 1);
$environmentVariable = getArgument($COMMAND, $ARGUMENTS, 3);
$ports = array();
$portsArg = getArgument($COMMAND, $ARGUMENTS, 2);
if ($portsArg != "") $ports = explode(":", $portsArg);


///////////////////////////////////////////////////////////////////////////////
if ($PM->addProject($projectName, $repository, $ports, $environmentVariable)) {
    appendToLog(LG_MAIN, LG_INFO, "project added", $projectName, $repository, $ports, $environmentVariable);
} else {
    appendToLog(LG_MAIN, LG_SEVERE, "project not added", $projectName, $repository, $ports, $environmentVariable);
}