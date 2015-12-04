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
    public $guestExportFolder;
    public $hostExportFolder;
    public $dockerBinFolder;

    ///////////////////////////////////////////////////////////////////////////////
    protected static $envVarPattern = '#export (.*?)\=\"(.*?)\"#';
    protected static $tmpFile = "/tmp/dm";

    ///////////////////////////////////////////////////////////////////////////////
    function __construct($os = "linux", $docherMachineName = "default", $guestExportFolder = "/tmp/export", $hostExportFolder = "/tmp/export")
    {
        $this->os = strtolower($os);
        $this->dockerMachineName = $docherMachineName;
        $this->guestExportFolder = $guestExportFolder;
        $this->hostExportFolder = $hostExportFolder;
        //$this->dockerBinFolder = "/usr/local/bin";
        $this->dockerBinFolder = "";
    }

    ///////////////////////////////////////////////////////////////////////////////
    function makeDockerCommand()
    {
        if ($this->dockerBinFolder == "") {
            $baseCommand = "docker";
        } else {
            $baseCommand = makePath($this->$this->dockerBinFolder, "docker");
        }
        if ($this->os == "macos" || $this->os == "windows") {
            return ". " . dockerManager::$tmpFile . ";" . $baseCommand;
        } else {
            return $baseCommand;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function makeDockerMachineCommand()
    {
        if ($this->dockerBinFolder == "") {
            return "docker-machine";
        } else {
            return makePath($this->$this->dockerBinFolder, "docker-machine");
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function start()
    {
        if ($this->os == "macos" || $this->os == "windows") {
            $result = run(makeCommand($this->makeDockerMachineCommand(), "start", $this->dockerMachineName));
            if (!$result->success) throw new Exception("Can't start docker machine : " . $result->output);
            $result = run(makeCommand($this->makeDockerMachineCommand(), "env " . $this->dockerMachineName, ">", dockerManager::$tmpFile));
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
            $result = run(makeCommand($this->makeDockerMachineCommand(), "stop", $this->dockerMachineName));
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
        $result = run(makeCommand("cd", $this->dockerFolder, ";", $this->makeDockerCommand(), "build", "-t", $this->imageName, "."));
        if (!$result->success) throw new Exception(message("Can't build docker image", $result->output));
        return $this->imageName;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function listRunningContainers()
    {
        $containers = array();
        $result = run(makeCommand($this->makeDockerCommand(), "ps"));
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
        if ($this->containerIsRunning($this->containerName)) {
            $this->stopAndRemoveContainer($this->containerName);
        }
        $result = run(makeCommand($this->makeDockerCommand(), "run", "--name", $this->containerName, "-ti", "-d", $portsCommand, $envsCommand, $this->imageName));
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
                $result = run(makeCommand($this->makeDockerCommand(), "kill", $this->containerID));
                if (!$result->success) throw new Exception(message("Can't kill docker container", $this->containerName, $this->containerID, $result->output));
            }
            $result = run(makeCommand($this->makeDockerCommand(), "rm", $this->containerID));
            if (!$result->success) throw new Exception(message("Can't remove docker container", $this->containerName, $this->containerID, $result->output));
            return true;
        } else {
            return false;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function executeCommand($containerName, $command)
    {
        $this->setContainerName($containerName);
        $this->containerID = $this->getContainerID($this->containerName);
        $result = run(makeCommand($this->makeDockerCommand(), "exec", $this->containerID, "sh -c \"" . $command . "\""));
        if (!$result->success) throw new Exception(message("Can't execute command on container", $this->containerName, $this->containerID, $command, $result->output));
        return $result;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function exportFileOfFolder($containerName, $fileOrFolder)
    {
        $this->containerID = $this->getContainerID($this->containerName);
        $result = run(makeCommand($this->makeDockerCommand(), "exec", $this->containerID, "mkdir", "-p", $this->guestExportFolder));
        if (!$result->success) throw new Exception(message("Can't create export folder on container", $this->containerName, $this->containerID, $result->output));
        return $this->executeCommand($containerName, "cp -r " . $fileOrFolder . " " . $this->guestExportFolder);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function compressAndGetExportFolder($containerName)
    {
        $this->setContainerName($containerName);
        $this->containerID = $this->getContainerID($this->containerName);
        $zipFileName = $this->containerName . "Export.tgz";
        $tmpZipFile = makePath("tmp", $zipFileName);
        $finalZipFile = makePath($this->guestExportFolder, $zipFileName);
        $hostZipFile = makePath($this->hostExportFolder, $zipFileName);
        $result = $this->executeCommand($containerName, "tar -czf " . $tmpZipFile . " " . $this->guestExportFolder);
        if (!$result->success) throw new Exception(message("Can't zip export folder on container", $this->containerName, $this->containerID, $result->output));
        $result = $this->executeCommand($containerName, "rm -rf " . $this->guestExportFolder . "/*");
        if (!$result->success) throw new Exception(message("Can't clean export folder on container", $this->containerName, $this->containerID, $result->output));
        $result = $this->executeCommand($containerName, "mv " . $tmpZipFile . " " . $this->guestExportFolder . "/");
        if (!$result->success) throw new Exception(message("Can't move export folder on container", $this->containerName, $this->containerID, $result->output));
        $result = run(makeCommand("mkdir", "-p", $this->hostExportFolder));
        if (!$result->success) throw new Exception(message("Can't make export dir on host", $this->hostExportFolder));
        $result = run(makeCommand($this->makeDockerCommand(), "cp", $this->containerID . ":" . $finalZipFile, $hostZipFile));
        if (!$result->success) throw new Exception(message("Can't copy export zip file from container", $this->containerName, $this->containerID, $result->output));
        return $hostZipFile;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function removeOldContainers()
    {
        $result = run(makeCommand($this->makeDockerCommand(), "ps", "-aq", "-f", "status=exited"));
        if (!$result->success) throw new Exception(message("Can't remove old containers", $result->output));
        if (count($result->output) > 0) {
            $result = run(makeCommand($this->makeDockerCommand(), "rm", "$(docker ps -aq -f status=exited)"));
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
        if (isset($infos) && isset($infos->State) && $infos->State->Running) {
            return true;
        } else {
            return false;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    function getContainerInfos($containerName)
    {
        $this->setContainerName($containerName);
        $result = run(makeCommand($this->makeDockerCommand(), "inspect", $this->containerName));
        return @json_decode($result->rawOutput, false)[0];
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