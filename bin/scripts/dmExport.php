<?php

///////////////////////////////////////////////////////////////////////////////
$projectName = getArgument($COMMAND, $ARGUMENTS, 0);

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "setting project", $projectName);
$PM->setProject($projectName);

///////////////////////////////////////////////////////////////////////////////
$zipFile = $PM->exportProject($projectName);
if ($zipFile !== false) {
    appendToLog(LG_MAIN, LG_INFO, "project exported", $projectName, $zipFile);
} else {
    appendToLog(LG_MAIN, LG_SEVERE, "project not exported", $projectName);
}