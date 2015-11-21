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
    public $os;

    ///////////////////////////////////////////////////////////////////////////////
    protected static $envVarPattern = '#export (.*?)\=\"(.*?)\"#';

    ///////////////////////////////////////////////////////////////////////////////
    function __construct($os = "linux", $docherMachineName = "default")
    {
        $this->os = strtolower($os);
        $this->dockerMachineName = $docherMachineName;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function start()
    {
        if ($this->os == "macos") {
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
    }

    ///////////////////////////////////////////////////////////////////////////////
    function stop()
    {
        $this->stopAllContainers();
        if ($this->os == "macos") {
            $result = run(makeCommand("docker-machine", "stop", $this->dockerMachineName));
            if (!$result->success) throw new Exception("Can't stop docker machine : " . $result->output);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function stopAllContainers()
    {
        $runningContainers = $this->listRunningContainers();
        $nbStoped = 0;
        foreach ($runningContainers as $runningContainer) {
            $this->stopAndRemoveContainer($runningContainer->containerName);
            $nbStoped++;
        }
        return $nbStoped;
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
        return $this->imageName;
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
            $containerName = $bits[count($bits) - 1];
            $containerInfos = $this->getContainerInfos($containerName);
            $envs = $containerInfos->Config->Env;
            $containers[] = arrayToObject(array("id" => $bits[0], "envs" => $envs, "imageName" => $bits[1], "containerName" => $containerName));
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
        return $this->containerName;
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
            return true;
        } else {
            return false;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function removeOldContainers()
    {
        $result = run(makeCommand("docker", "ps", "-aq", "-f", "status=exited"));
        if (!$result->success) throw new Exception(message("Can't remove old containers", $result->output));
        if (count($result->output) > 0) {
            $result = run(makeCommand("docker", "rm", "$(docker ps -aq -f status=exited)"));
            if (!$result->success) throw new Exception(message("Can't remove old containers", $result->output));
            return $result->output;
        } else {
            return 0;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getContainerID($containerName)
    {
        $this->setContainerName($containerName);
        $infos = $this->getContainerInfos($this->containerName);
        if (count($infos) == 0) return 0;
        return $infos->Id;
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
        if ($infos->State->Running) {
            return true;
        } else {
            return false;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getContainerInfos($containerName)
    {
        $this->setContainerName($containerName);
        $result = run(makeCommand("docker", "inspect", $this->containerName));
        return json_decode($result->rawOutput, false)[0];
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