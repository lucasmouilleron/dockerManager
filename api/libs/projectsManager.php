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
    public $projectsFile;
    public $defaultEnvironmentVariable;
    public $publicAutoPortOffset;

    ///////////////////////////////////////////////////////////////////////////////
    function __construct($reposManager, $dockerManager, $projectsFile, $defaultEnvironmentVariable, $publicAutoPortOffset)
    {
        $this->dockerManager = $dockerManager;
        $this->reposManager = $reposManager;
        $this->projectsFile = $projectsFile;
        $this->projects = jsonFileToObject($this->projectsFile);
        $this->defaultEnvironmentVariable = $defaultEnvironmentVariable;
        $this->publicAutoPortOffset = $publicAutoPortOffset;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function saveProjects()
    {
        objectToJsonFile($this->projects, $this->projectsFile);
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
                $projectEnvironmentVariable = $this->defaultEnvironmentVariable;
                if (isset($project->environmentVariable)) {
                    $projectEnvironmentVariable = $project->environmentVariable;
                }
                return arrayToObject(array("environmentVariable" => $projectEnvironmentVariable, "name" => $project->name, "repository" => $project->repository, "cloneFolder" => $this->reposManager->cloneFolder, "ports" => $project->ports, "dockerFile" => makePath($this->reposManager->cloneFolder, $this->reposManager->dockerFolder, "DockerFile")));
            }
        }
        throw new Exception(message("Project does not exist", $name));
    }

    ///////////////////////////////////////////////////////////////////////////////
    function isProjectRunning($name, $environment)
    {
        $projectsRunning = $this->getRunningProjects();
        foreach ($projectsRunning as $projectRunning) {
            if ($projectRunning->environment == $environment && $projectRunning->projectInfos->name == $name) {
                return true;
            }
        }
        return false;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function addProject($name, $repository, $ports = array(), $environmentVariable = "")
    {
        $name = strtolower($name);
        foreach ($this->projects as $project) {
            if ($project->name == $name) {
                return false;
            }
        }
        if ($environmentVariable == "") {
            $environmentVariable = $this->defaultEnvironmentVariable;
        }
        $usedPorts = array();
        foreach ($this->projects as $project) {
            foreach ($project->ports as $port) {
                if (count($port) == 2) {
                    $usedPorts[] = $port[0];
                }
            }
        }
        sort($usedPorts);
        $nextPort = $this->defaultEnvironmentVariable;
        if (count($usedPorts) != 0) {
            $nextPort = $usedPorts[count($usedPorts) - 1] + 1;
        }
        $portsAndTranslations = array();
        foreach ($ports as $port) {
            $portsAndTranslations[] = array($nextPort, $port);
            $nextPort++;
        }
        $this->projects[] = arrayToObject(array("name" => $name, "repository" => $repository, "ports" => $portsAndTranslations, "environmentVariable" => $environmentVariable));
        $this->saveProjects();
        return true;
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
    function buildProject($name, $environment)
    {
        $infos = $this->getProjectInfos($name);
        $imageName = $this->getImageNameFromProjet($name, $environment);
        $this->dockerManager->buildImageFromDockerFile($infos->dockerFile, $imageName);
        return true;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function startProject($name, $environment)
    {
        $infos = $this->getProjectInfos($name);
        $imageName = $this->getImageNameFromProjet($name, $environment);
        $containerName = $this->getContainerNameFromProjet($name, $environment);
        $this->dockerManager->startContainer($imageName, $containerName, array(array($infos->environmentVariable, $environment)), $infos->ports);
        return true;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function stopProject($name, $environment)
    {
        $containerName = $this->getContainerNameFromProjet($name, $environment);
        return $this->dockerManager->stopAndRemoveContainer($containerName);
    }
}