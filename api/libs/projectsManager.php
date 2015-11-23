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
    function stopDocker()
    {
        $this->dockerManager->stop();
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
                $projectExportCommands = array();
                if (isset($project->exportCommands)) {
                    $projectExportCommands = $project->exportCommands;
                }
                $projectExportFilesAndFolders = array();
                if (isset($project->exportFilesAndFolders)) {
                    $projectExportFilesAndFolders = $project->exportFilesAndFolders;
                }
                return arrayToObject(array("name" => $project->name, "repository" => $project->repository, "cloneFolder" => $this->reposManager->cloneFolder, "ports" => $project->ports, "dockerFile" => makePath($this->reposManager->cloneFolder, $this->reposManager->dockerFolder, "DockerFile"), "environmentVariable" => $projectEnvironmentVariable, "exportCommands" => $projectExportCommands, "exportFilesAndFolders" => $projectExportFilesAndFolders));
            }
        }
        throw new Exception(message("Project does not exist", $name));
    }

    ///////////////////////////////////////////////////////////////////////////////
    function isProjectRunning($name)
    {
        $projectsRunning = $this->getRunningProjects();
        foreach ($projectsRunning as $projectRunning) {
            if ($projectRunning->projectInfos->name == $name) {
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
            $projectInfos = $this->getProjectInfos($projectName);
            $runningProjects[] = arrayToObject(array("projectInfos" => $projectInfos, "environment" => $this->getEnvVariable($projectInfos->environmentVariable, $runningContainer->envs), "revision" => $this->getEnvVariable("REVISION", $runningContainer->envs)));
        }
        return $runningProjects;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getImageNameFromProjet($name)
    {
        $this->dockerManager->setImageName("dm:" . $name);
        return $this->dockerManager->imageName;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getContainerNameFromProjet($name)
    {
        $this->dockerManager->setContainerName("dm:" . $name);
        return $this->dockerManager->containerName;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getProjectNameFromImageName($imageName)
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
    function buildProject($name)
    {
        $infos = $this->getProjectInfos($name);
        $imageName = $this->getImageNameFromProjet($name);
        $this->dockerManager->buildImageFromDockerFile($infos->dockerFile, $imageName);
        return true;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function exportProject($name)
    {
        if ($this->isProjectRunning($name)) {
            $containerName = $this->getContainerNameFromProjet($name);
            $infos = $this->getProjectInfos($name);
            foreach ($infos->exportCommands as $exportCommand) {
                $this->dockerManager->executeCommand($containerName, $exportCommand);
            }
            foreach ($infos->exportFilesAndFolders as $exportFileOrFolder) {
                $this->dockerManager->exportFileOfFolder($containerName, $exportFileOrFolder);
            }
            return $this->dockerManager->compressAndGetExportFolder($containerName);
        } else {
            throw new Exception(message("Project is not running", $name));
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function startProject($name, $environment, $revision)
    {
        $infos = $this->getProjectInfos($name);
        $imageName = $this->getImageNameFromProjet($name);
        $containerName = $this->getContainerNameFromProjet($name);
        $this->dockerManager->startContainer($imageName, $containerName, array(array($infos->environmentVariable, $environment), array("REVISION", $revision)), $infos->ports);
        return true;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function stopProject($name)
    {
        $containerName = $this->getContainerNameFromProjet($name);
        return $this->dockerManager->stopAndRemoveContainer($containerName);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function stopAllProjects()
    {
        return $this->dockerManager->stopAllContainers();
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getEnvVariable($envName, $envs)
    {
        foreach ($envs as $env) {
            if (startsWith($env, $envName . "=")) {
                return trim(str_replace($envName . "=", "", $env));
            }
        }
        return "";
    }
}