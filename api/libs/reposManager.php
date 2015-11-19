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
        $result = run(makeCommand("ssh-keyscan", $this->repositoriesBase, ">>", "~/.ssh/known_hosts"));
        if (!$result->success) throw new Exception("Can't add repositories base to know hosts : " . $result->output);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function cloneRepository($repository)
    {
        $this->setRepository($repository);
        if (file_exists($this->cloneFolder)) {
            removeDir($this->cloneFolder);
        }
        $result = run(makeCommand("ssh-agent", "$(ssh-add " . $this->idRSAFile . "; git clone --verbose --progress --depth=1 git@" . $this->repositoriesBase . ":" . $repository . " " . $this->cloneFolder . ")"));
        if (!$result->success) throw new Exception("Can't clone repository : " . $result->output);
    }

    ///////////////////////////////////////////////////////////////////////////////
    function setRepository($repository)
    {
        $this->repository = $repository;
        $this->cloneFolder = makePath($this->workBaseFolder, $repository);
    }
}