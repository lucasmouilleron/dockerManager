<?php

///////////////////////////////////////////////////////////////////////////////
require_once __DIR__ . "/tools.php";

class reposManager
{
    ///////////////////////////////////////////////////////////////////////////////
    public $repository;
    public $idRSAFile;
    public $repositoriesBase;
    public $workBaseFolder;
    public $cloneFolder;
    public $dockerFolder;

    ///////////////////////////////////////////////////////////////////////////////
    function __construct($repositoriesBase, $idRSAFile, $workBaseFolder, $dockerFolder)
    {
        $this->dockerFolder = $dockerFolder;
        $this->workBaseFolder = $workBaseFolder;
        $this->repositoriesBase = $repositoriesBase;
        $this->idRSAFile = $idRSAFile;
    }

    ///////////////////////////////////////////////////////////////////////////////
    function setup()
    {
        $result = runLocal(makeCommand("eval", "`ssh-agent -s`"));
        if (!file_exists($this->idRSAFile)) throw new Exception(message("SSH key doest not exist", $this->idRSAFile, "generate the key and add it to the repository provider"));
        runLocal(makeCommand("ssh-agent", "$(ssh-add " . $this->idRSAFile . ")"));
        if (!$result->success) throw new Exception(message("Can't start ssh agent", $result->output));
        $result = runLocal(makeCommand("ssh-keyscan", $this->repositoriesBase, ">>", "~/.ssh/known_hosts"));
        if (!$result->success) throw new Exception(message("Can't add repositories base to know hosts", $result->output));
    }

    ///////////////////////////////////////////////////////////////////////////////
    function cloneRepository($repository)
    {
        $this->setRepository($repository);
        if (file_exists($this->cloneFolder)) {
            removeDir($this->cloneFolder);
        }
        $result = runLocal(makeCommand("ssh-agent", "$(git clone --verbose --progress --depth=1 git@" . $this->repositoriesBase . ":" . $repository . " " . $this->cloneFolder . ")"));
        if (!$result->success) throw new Exception(message("Can't clone repository", $result->output));
    }

    ///////////////////////////////////////////////////////////////////////////////
    function setRepository($repository)
    {
        $this->repository = $repository;
        $this->cloneFolder = makePath($this->workBaseFolder, $repository);
    }
}