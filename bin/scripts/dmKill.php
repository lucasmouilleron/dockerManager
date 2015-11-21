<?php

///////////////////////////////////////////////////////////////////////////////
$projectName = getArgument($COMMAND,$ARGUMENTS,0);

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "starting docker", $DM->dockerMachineName);
$PM->startDocker();

///////////////////////////////////////////////////////////////////////////////
if ($PM->stopProject($projectName)) {
    appendToLog(LG_MAIN, LG_INFO, "project stopped", $projectName);
} else {
    appendToLog(LG_MAIN, LG_WARNING, "project NOT stopped", $projectName);
}