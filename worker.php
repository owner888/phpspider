<?php

echo "Starting\n";

$gmworker = new GearmanWorker();
$gmworker->addServer('10.10.10.238');
$gmworker->addFunction("reverse", "reverse_fn");

print "Waiting for job...\n";
while($gmworker->work())
{
    if ($gmworker->returnCode() != GEARMAN_SUCCESS)
    {
        echo "return_code: " . $gmworker->returnCode() . "\n";
        break;
    }
    //break;
}

function reverse_fn($job)
{
    sleep(3);
    echo $job->workload()."\n";
    return strrev($job->workload());
}


echo "hello\n";
?>



