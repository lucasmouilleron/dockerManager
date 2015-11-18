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
        if (!$result->success) throw new Exception("Can't start docker machine : " . $result->output);
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
        if (!$result->success) throw new Exception("Can't build docker image : " . $result->output);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function runImage($imageName, $containerName, $envs = array(), $ports = array(), $paths = array())
    {
        $this->setContainerName($containerName);
        $this->setImageName($imageName);
        $result = run(makeCommand("docker", "run", "--name", $this->containerName, "--rm", "-ti", "-d", $this->imageName));
        if (!$result->success) throw new Exception("Can't run docker container : " . $result->output);
        //docker run--rm--name "$CONTAINER_NAME" - e TSDC_ENVIRONMENT = $ENVIRONMENT - ti - p 8080:8080 - p 5601:5601 "$REPOSITORY_NAME"
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