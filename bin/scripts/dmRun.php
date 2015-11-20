<?php

///////////////////////////////////////////////////////////////////////////////
$projectName = @$ARGUMENTS[0];
$environment = @$ARGUMENTS[1];


///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "starting docker", $DM->dockerMachineName);
$PM->startDocker();

///////////////////////////////////////////////////////////////////////////////
$repositoryInfos = $PM->getProjectInfos($projectName);

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "cloning repository", $repositoryInfos->repository, "for projcet", $projectName);
$PM->cloneRepository($projectName);

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "build  project image for project", $projectName, "for environment", $environment);
$PM->buildProject($projectName, $environment);
appendToLog(LG_MAIN, LG_INFO, "start container for project", $projectName, "for environment", $environment);
$PM->startProject($projectName, $environment);