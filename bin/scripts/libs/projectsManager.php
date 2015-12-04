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
    public $config;
    public $defaultDockerMachineName;

    ///////////////////////////////////////////////////////////////////////////////
    function __construct($projectsFile, $configFile)
    {
        $this->configFile = $configFile;
        $this->projectsFile = $projectsFile;
        $this->config = jsonFileToObject($this->configFile);
        $this->projects = jsonFileToObject($this->projectsFile);
        $this->reposManager = new reposManager($this->config->repositoryBaseURL, makePath($this->configFile, "id_rsa"), $this->config->workBaseFolder, $this->config->dockerFolder);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function setProject($name)
    {
        $infos = $this->getProjectInfos($name);
        $this->dockerManager = new dockerManager($infos->URI, $infos->OS, $this->config->defaultDockerMachineName, $this->config->guestExportFolder, $this->config->hostExportFolder);
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
                $projectEnvironmentVariableValue = "";
                if (isset($project->environmentVariableValue)) {
                    $projectEnvironmentVariableValue = $project->environmentVariableValue;
                }
                $OS = $this->config->defaultOS;
                if (isset($project->OS)) {
                    $OS = $project->OS;
                }
                $URI = $this->config->defaultURI;
                if (isset($project->URI)) {
                    $URI = $project->URI;
                }
                $projectEnvironmentVariable = $this->config->defaultEnvironmentVariable;
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
                return arrayToObject(array("name" => $project->name, "repository" => $project->repository, "cloneFolder" => $this->reposManager->cloneFolder, "ports" => $project->ports, "dockerFile" => makePath($this->reposManager->cloneFolder, $this->reposManager->dockerFolder, "DockerFile"), "environmentVariable" => $projectEnvironmentVariable, "environmentVariableValue" => $projectEnvironmentVariableValue, "exportCommands" => $projectExportCommands, "exportFilesAndFolders" => $projectExportFilesAndFolders, "URI" => $URI, "OS" => $OS));
            }
        }
        throw new Exception(message("Project does not exist", $name));
    }

    ///////////////////////////////////////////////////////////////////////////////
    function isProjectRunning($name)
    {
        //TODO OPTIIMZIE GET RUNNING PROECT FOR URI
        $projectsRunning = $this->getRunningProjects();
        foreach ($projectsRunning as $projectRunning) {
            if ($projectRunning->projectInfos->name == $name) {
                return true;
            }
        }
        return false;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function addProject($name, $repository, $ports = array(), $environmentVariable = "", $environmentVariableValue = "", $URI = "", $OS = "")
    {
        $name = strtolower($name);
        foreach ($this->projects as $project) {
            if ($project->name == $name) {
                return false;
            }
        }
        if ($URI == "") {
            $URI = $this->config->defaultURI;
        }
        if ($OS == "") {
            $OS = $this->config->defaultOS;
        }
        if ($environmentVariable == "") {
            $environmentVariable = $this->config->defaultEnvironmentVariable;
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
        $nextPort = $this->config->publicAutoPortOffset;
        if (count($usedPorts) != 0) {
            $nextPort = $usedPorts[count($usedPorts) - 1] + 1;
        }
        $portsAndTranslations = array();
        foreach ($ports as $port) {
            $portsAndTranslations[] = array($nextPort, $port);
            $nextPort++;
        }
        $this->projects[] = arrayToObject(array("name" => $name, "repository" => $repository, "ports" => $portsAndTranslations, "environmentVariable" => $environmentVariable, "environmentVariableValue" => $environmentVariableValue, "URI" => $URI, "OS" => $OS));
        $this->saveProjects();
        return true;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getRunningProjects()
    {
        $lastPojectOfURIMap = array();
        foreach ($this->projects as $project) {
            $infos = $this->getProjectInfos($project->name);
            $lastPojectOfURIMap[$infos->URI] = $project;
        }
        $runningProjectsMap = array();
        foreach (array_values($lastPojectOfURIMap) as $project) {
            $infos = $this->getProjectInfos($project->name);
            $tempDM = new dockerManager($infos->URI, $infos->OS, $this->config->defaultDockerMachineName, $this->config->guestExportFolder, $this->config->hostExportFolder);
            $runningContainers = $tempDM->listRunningContainers();
            foreach ($runningContainers as $runningContainer) {
                $projectName = $this->getProjectNameFromImageName($runningContainer->imageName);
                $projectInfos = $this->getProjectInfos($projectName);
                $runningProjectsMap[$projectName] = arrayToObject(array("projectInfos" => $projectInfos, "id" => $runningContainer->id, "environment" => $this->getEnvVariable($projectInfos->environmentVariable, $runningContainer->envs), "revision" => $this->getEnvVariable("REVISION", $runningContainer->envs)));
            }
        }
        return array_values($runningProjectsMap);
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
        $this->cloneRepository($name);
        if ($infos->URI != "local") {
            $result = run($infos->URI, makeCommand("mkdir", "-p", $infos->cloneFolder));
            if (!$result->success) throw new Exception(message("Can't create clone folder", $result->output));
            $result = runLocal(makeCommand("scp", "-r", makePath($infos->cloneFolder, $this->reposManager->dockerFolder), $infos->URI . ":" . makePath($infos->cloneFolder, $this->reposManager->dockerFolder)));
            if (!$result->success) throw new Exception(message("Can't copy docker folder", $result->output));
        }
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
    function startProject($name, $revision)
    {
        $infos = $this->getProjectInfos($name);
        $imageName = $this->getImageNameFromProjet($name);
        $containerName = $this->getContainerNameFromProjet($name);
        $this->dockerManager->startContainer($imageName, $containerName, array(array($infos->environmentVariable, $infos->environmentVariableValue), array("REVISION", $revision)), $infos->ports);
        return true;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function stopProject($name)
    {
        $containerName = $this->getContainerNameFromProjet($name);
        return $this->dockerManager->stopAndRemoveContainer($containerName);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function projectExists($name)
    {
        foreach ($this->projects as $project) {
            if ($project->name == $name) {
                return true;
            }
        }
        return false;
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