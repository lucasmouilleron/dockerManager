<?php

///////////////////////////////////////////////////////////////////////////////
$projectName = getArgument($COMMAND, $ARGUMENTS, 0);
$revision = getArgument($COMMAND, $ARGUMENTS, 1);

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "setting project", $projectName);
$PM->setProject($projectName);

///////////////////////////////////////////////////////////////////////////////
$repositoryInfos = $PM->getProjectInfos($projectName);

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "starting docker", $PM->dockerManager->dockerMachineName . "@" . $PM->dockerManager->hostURI);
$PM->startDocker();

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "build  project image for project", $projectName);
$PM->buildProject($projectName);
appendToLog(LG_MAIN, LG_INFO, "start container for project", $projectName, "for revision", $revision);
$PM->startProject($projectName, $revision);