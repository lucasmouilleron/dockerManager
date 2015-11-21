<?php

////////////////////////////////////////////////////////////////
require_once __DIR__ . "/vendor/autoload.php";
////////////////////////////////////////////////////////////////
define("LG_PATH", __DIR__ . "/../../logs");
define("LG_FNE", "FNE");
define("LG_INFO", "NFO");
define("LG_WARNING", "WRN");
define("LG_SEVERE", "SVR");
define("LG_MAIN", "main");

////////////////////////////////////////////////////////////////
date_default_timezone_set("Europe/Paris");
use Colors\Color;

////////////////////////////////////////////////////////////////
function appendToLog($logger, $level, $message)
{
    $args = func_get_args();
    $logger = array_shift($args);
    $level = array_shift($args);
    $message = messageFromArgs($args);
    $message = date("Y/m/d H:i:s") . " - [" . $level . "] - " . $message . "\r\n";
    echo colorize($message, $level);
    file_put_contents(LG_PATH . "/" . $logger . ".log", $message, FILE_APPEND);
}

///////////////////////////////////////////////////////////////////////////////
function colorize($text, $level)
{
    $c = new Color();
    switch ($level) {
        case LG_INFO:
            return $c($text)->blue();
            break;
        case LG_SEVERE:
            return $c($text)->red()->bold();
            break;
        case LG_WARNING:
            return $c($text)->yellow()->bold();
            break;
        case LG_FNE:
            return $c($text)->magenta();
            break;
        default:
            return $c($text)->white();
            break;
    }
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
    //ob_start();
    $moreOutput = exec($command, $output, $code);
    //$moremoreoutput = ob_get_clean();
    $ouput[] = $moreOutput;
    $rawOutput = "";
    foreach ($output as $outputLine) {
        $rawOutput .= $outputLine . "\n";
    }
    //$ouput[] = $moremoreoutput;
    return arrayToObject(array("code" => $code, "output" => $output, "rawOutput" => $rawOutput, "success" => ($code == 0)));
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
function objectToJsonFile($object, $filePath)
{
    return file_put_contents($filePath, json_encode($object, JSON_PRETTY_PRINT));
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

///////////////////////////////////////////////////////////////////////////////
function startsWith($haystack, $needle)
{
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}