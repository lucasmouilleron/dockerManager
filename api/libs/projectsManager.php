<?php

///////////////////////////////////////////////////////////////////////////////
require_once __DIR__ . "/tools.php";
require_once __DIR__ . "/dockerManager.php";
require_once __DIR__ . "/reposManager.php";

class projectsManager
{
    ///////////////////////////////////////////////////////////////////////////////
    public $dockerManager;
    public $reposManager;
    public $projects;

    ///////////////////////////////////////////////////////////////////////////////
    function __construct($reposManager, $dockerManager, $projects)
    {
        $this->dockerManager = $dockerManager;
        $this->reposManager = $reposManager;
        $this->projects = $projects;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function startDocker()
    {
        $this->dockerManager->start();
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getProjects()
    {
        return $this->projects;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getProjectInfos($name)
    {
        foreach ($this->projects as $project) {
            if ($project->name == $name) {
                $this->reposManager->setRepository($project->repository);
                return arrayToObject(array("name" => $project->name, "repository" => $project->repository, "cloneFolder" => $this->reposManager->cloneFolder, "ports" => $project->ports, "dockerFile" => makePath($this->reposManager->cloneFolder, $this->reposManager->dockerFolder, "DockerFile")));
            }
        }
        throw new Exception(message("Project does not exist", $name));
    }

    ///////////////////////////////////////////////////////////////////////////////
    function isProjectRunning($name, $environment)
    {
        $projectsRunning = $this->getRunningProjects();
        foreach ($projectsRunning as $projectRunning) {
            if ($projectRunning->environment == $environment && $projectRunning->projectInfos->name == $name)
                return true;
        }
        return false;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getRunningProjects()
    {
        $runningProjects = array();
        $runningContainers = $this->dockerManager->listRunningContainers();
        foreach ($runningContainers as $runningContainer) {
            $projectName = $this->getProjectNameFromImageName($runningContainer->imageName);
            $environment = $this->getEnvironmentFromImageName($runningContainer->imageName);
            $runningProjects[] = arrayToObject(array("projectInfos" => $this->getProjectInfos($projectName), "environment" => $environment));
        }
        return $runningProjects;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getImageNameFromProjet($name, $environment)
    {
        $this->dockerManager->setImageName($name . ":" . $environment);
        return $this->dockerManager->imageName;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getContainerNameFromProjet($name, $environment)
    {
        $this->dockerManager->setContainerName($name . ":" . $environment);
        return $this->dockerManager->containerName;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getProjectNameFromImageName($imageName)
    {
        return explode(":", $imageName)[0];
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getEnvironmentFromImageName($imageName)
    {
        return explode(":", $imageName)[1];
    }

    ///////////////////////////////////////////////////////////////////////////////
    function cloneRepository($name)
    {
        $infos = $this->getProjectInfos($name);
        $this->reposManager->cloneRepository($infos->repository);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function buildImage($name, $environment)
    {
        $infos = $this->getProjectInfos($name);
        $imageName = $this->getImageNameFromProjet($name, $environment);
        $this->dockerManager->buildImageFromDockerFile($infos->dockerFile, $imageName);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function startContainer($name, $environment)
    {
        $infos = $this->getProjectInfos($name);
        $imageName = $this->getImageNameFromProjet($name, $environment);
        $containerName = $this->getContainerNameFromProjet($name, $environment);
        $this->dockerManager->startContainer($imageName, $containerName, array(array("ENVIRONMENT", $environment)), $infos->ports);
    }
}