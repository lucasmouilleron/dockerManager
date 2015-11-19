<?php

///////////////////////////////////////////////////////////////////////////////
require_once __DIR__ . "/tools.php";

class dockerManager
{
    ///////////////////////////////////////////////////////////////////////////////
    public $dockerMachineName;
    public $dockerFile;
    public $dockerFolder;
    public $imageName;
    public $containerName;
    public $containerID;
    ///////////////////////////////////////////////////////////////////////////////
    protected static $envVarPattern = '#export (.*?)\=\"(.*?)\"#';

    ///////////////////////////////////////////////////////////////////////////////
    function __construct($docherMachineName = "default")
    {
        $this->dockerMachineName = $docherMachineName;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function start()
    {
        $result = run(makeCommand("docker-machine", "start", $this->dockerMachineName));
        if (!$result->success) throw new Exception("Can't start docker machine : " . $result->output);
        $result = run("docker-machine env " . $this->dockerMachineName);
        if (!$result->success) throw new Exception(message("Can't start docker machine", $result->output));
        foreach ($result->output as $output) {
            preg_match(dockerManager::$envVarPattern, $output, $matches);
            if (count($matches) >= 3 && $matches[1] !== "") {
                putenv($matches[1] . "=" . $matches[2]);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function buildImageFromDockerFile($dockerFile, $imageName)
    {
        $this->dockerFile = $dockerFile;
        $this->dockerFolder = dirname($this->dockerFile);
        $this->setImageName($imageName);
        chdir($this->dockerFolder);
        $result = run(makeCommand("docker", "build", "-t", $this->imageName, "."));
        if (!$result->success) throw new Exception(message("Can't build docker image", $result->output));
    }

    ///////////////////////////////////////////////////////////////////////////////
    function listRunningContainers()
    {
        $containers = array();
        $result = run(makeCommand("docker", "ps"));
        $outputs = $result->output;
        array_shift($outputs);
        foreach ($outputs as $output) {
            $bits = preg_split('/[\s]+/', $output);
            $containers[] = arrayToObject(array("id" => $bits[0], "imageName" => $bits[1], "containerName" => $bits[count($bits)-1]));
        }
        return $containers;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function startContainer($imageName, $containerName, $envs = array(), $ports = array(), $paths = array())
    {
        $this->setContainerName($containerName);
        $this->setImageName($imageName);
        $portsCommand = "";
        foreach ($ports as $port) {
            if (count($port) == 2) {
                $portsCommand .= " -p " . $port[0] . ":" . $port[1];
            }
        }
        $envsCommand = "";
        foreach ($envs as $env) {
            if (count($env) == 2) {
                $envsCommand .= " -e " . $env[0] . "=" . $env[1];
            }
        }
        $this->stopAndRemoveContainer($this->containerName);
        $result = run(makeCommand("docker", "run", "--name", $this->containerName, "-ti", "-d", $portsCommand, $envsCommand, $this->imageName));
        if (!$result->success) throw new Exception(message("Can't run docker container", $this->containerName, $result->output));
    }

    ///////////////////////////////////////////////////////////////////////////////
    function stopAndRemoveContainer($containerName)
    {
        $this->setContainerName($containerName);
        $this->containerID = $this->getContainerID($this->containerName);
        if ($this->containerID !== 0) {
            if ($this->containerIsRunning($this->containerName)) {
                $result = run(makeCommand("docker", "kill", $this->containerID));
                if (!$result->success) throw new Exception(message("Can't kill docker container", $this->containerName, $this->containerID, $result->output));
            }
            $result = run(makeCommand("docker", "rm", $this->containerID));
            if (!$result->success) throw new Exception(message("Can't remove docker container", $this->containerName, $this->containerID, $result->output));
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getContainerID($containerName)
    {
        $this->setContainerName($containerName);
        $infos = $this->getContainerInfos($this->containerName);
        if (count($infos) == 0) return 0;
        return $infos[0]->Id;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function containerExists($containerName)
    {
        $this->setContainerName($containerName);
        return $this->getContainerID($this->containerName) !== 0;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function containerIsRunning($containerName)
    {
        $this->setContainerName($containerName);
        if (!$this->containerExists($this->containerName)) return false;
        $infos = $this->getContainerInfos($this->containerName);
        foreach ($infos as $info) {
            if ($info->State->Running) {
                return true;
            }
        }
        return false;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getContainerInfos($containerName)
    {
        $this->setContainerName($containerName);
        $result = run(makeCommand("docker", "inspect", $this->containerName));
        return json_decode($result->rawOutput, false);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function setContainerName($containerName)
    {
        $this->containerName = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $containerName));
    }

    ///////////////////////////////////////////////////////////////////////////////
    function setImageName($imageName)
    {
        $this->imageName = strtolower(preg_replace('/[^A-Za-z0-9\:]/', '', $imageName));
    }

}