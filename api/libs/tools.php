<?php

////////////////////////////////////////////////////////////////
define("LG_PATH", __DIR__ . "/../../logs");
define("LG_FNE", "FNE");
define("LG_INFO", "NFO");
define("LG_WARNING", "WRN");
define("LG_SEVERE", "SVR");
define("LG_MAIN", "main");

////////////////////////////////////////////////////////////////
date_default_timezone_set("Europe/Paris");

////////////////////////////////////////////////////////////////
function appendToLog($logger, $level, $message)
{
    $args = func_get_args();
    $logger = array_shift($args);
    $level = array_shift($args);
    $message = messageFromArgs($args);
    $message = date("Y/m/d H:i:s") . " - [" . $level . "] - " . $message . "\r\n";
    echo $message;
    file_put_contents(LG_PATH . "/" . $logger . ".log", $message, FILE_APPEND);
}

////////////////////////////////////////////////////////////////
function messageFromArgs($args)
{
    array_walk($args, function (&$val) {
        if (is_object($val) || is_array($val)) {
            $val = json_encode($val);
        }
    });
    $message = implode(" / ", $args);
    return str_replace("\n", " ", $message);
}

////////////////////////////////////////////////////////////////
function message()
{
    $args = func_get_args();
    return messageFromArgs($args);
}

////////////////////////////////////////////////////////////////
function run($command)
{
    $output = array();
    $code = -1;
    $args = func_get_args();
    if (count($args) > 1) $command = implode(" ", $args);
    $command .= " 2>&1";
    appendToLog(LG_MAIN, LG_FNE, "running command", $command);
    ob_start();
    $moreOutput = exec($command, $output, $code);
    $moremoreoutput = ob_get_clean();
    $ouput[] = $moreOutput;
    $ouput[] = $moremoreoutput;
    return arrayToObject(array("code" => $code, "output" => $output, "success" => ($code == 0)));
}

///////////////////////////////////////////////////////////////////////////////
function cleanRepositoryName($name)
{
    return str_replace(array("/"), "", $name);
}

///////////////////////////////////////////////////////////////////////////////
function jsonFileToObject($filePath)
{
    return json_decode(file_get_contents($filePath), false);
}

///////////////////////////////////////////////////////////////////////////////
function arrayToObject($array)
{
    return json_decode(json_encode($array), false);
}

///////////////////////////////////////////////////////////////////////////////
function makePath()
{
    return implode("/", func_get_args());
}

////////////////////////////////////////////////////////////////
function makeCommand()
{
    return implode(" ", func_get_args());
}

///////////////////////////////////////////////////////////////////////////////
function removeDir($dir)
{
    if (file_exists($dir)) {
        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) continue;
            if (is_dir("$dir/$file")) removeDir("$dir/$file");
            else unlink("$dir/$file");
        }
        rmdir($dir);
    }
}