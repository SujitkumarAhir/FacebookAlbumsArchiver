<?php

require_once __DIR__ . '../../Processes.php';

if (isset($_POST["Id"]) && isset($_POST["userId"]) && isset($_POST["type"])) {

    require 'BackgroundProcess.php';
    $proc = new BackgroundProcess();
    $processes = new Processes();
    $res = $processes->getProcess($_POST["Id"]);
    $pid = $res["background_process_id"];
    $proc->setProcessId($pid);
    if ($pid != '0') {
        $proc->stop();
    }
    $processes->deleteProcess($_POST["Id"]);
    echo "Process Stopped..";
}
?>