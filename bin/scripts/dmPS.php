<?php

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "starting docker", $DM->dockerMachineName);
$PM->startDocker();

///////////////////////////////////////////////////////////////////////////////
$runningProjects = $PM->getRunningProjects();
appendToLog(LG_MAIN, LG_INFO, "# projects running", count($runningProjects));
foreach ($runningProjects as $runningProject) {
    appendToLog(LG_MAIN, LG_INFO, "name : " . $runningProject->projectInfos->name, "environment : " . $runningProject->environment, "revision : " . $runningProject->revision, "ports : " . json_encode($runningProject->projectInfos->ports), "container ID : ".$runningProject->id);
}