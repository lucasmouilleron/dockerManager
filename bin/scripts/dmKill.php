<?php

///////////////////////////////////////////////////////////////////////////////
$projectName = @$ARGUMENTS[0];
$environment = @$ARGUMENTS[1];

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "starting docker", $DM->dockerMachineName);
$PM->startDocker();

///////////////////////////////////////////////////////////////////////////////
if ($PM->stopProject($projectName, $environment)) {
    appendToLog(LG_MAIN, LG_INFO, "project stopped", $projectName, $environment);
} else {
    appendToLog(LG_MAIN, LG_WARNING, "project NOT stopped", $projectName, $environment);
}