<?php

///////////////////////////////////////////////////////////////////////////////
$runningProjects = $PM->getRunningProjects();
appendToLog(LG_MAIN, LG_INFO, "# running projects", count($runningProjects));

///////////////////////////////////////////////////////////////////////////////
foreach ($runningProjects as $runningProject) {
    appendToLog(LG_MAIN, LG_INFO, "name : " . $runningProject->projectInfos->name, "uri : ".$runningProject->projectInfos->URI,"environment : " . $runningProject->environment, "revision : " . $runningProject->revision, "ports : " . json_encode($runningProject->projectInfos->ports), "container ID : " . $runningProject->id);
}