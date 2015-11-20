<?php

///////////////////////////////////////////////////////////////////////////////
$projectName = @$ARGUMENTS[0];
$repository = @$ARGUMENTS[1];
$ports = @explode(":", $ARGUMENTS[2]);
$environmentVariable = @$ARGUMENTS[3];

///////////////////////////////////////////////////////////////////////////////
if ($PM->addProject($projectName, $repository, $ports, $environmentVariable)) {
    appendToLog(LG_MAIN, LG_INFO, "project added", $projectName, $repository, $ports, $environmentVariable);
} else {
    appendToLog(LG_MAIN, LG_INFO, "project not added", $projectName, $repository, $ports, $environmentVariable);
}