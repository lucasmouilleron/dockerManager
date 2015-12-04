<?php

///////////////////////////////////////////////////////////////////////////////
$projects = $PM->getProjects();
appendToLog(LG_MAIN, LG_INFO, "# projects", count($projects));
foreach ($projects as $project) {
    appendToLog(LG_MAIN, LG_INFO, "name : " . $project->name, "repository : " . $project->repository, "ports : " . json_encode($project->ports), "uri : ".@$project->URI);
}