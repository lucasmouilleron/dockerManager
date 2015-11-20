<?php

///////////////////////////////////////////////////////////////////////////////
appendToLog(LG_MAIN, LG_INFO, "# available commands", count($COMMANDS));
foreach ($COMMANDS as $command) {
    echo PHP_EOL;
    displayCommandHelp($command);
}
