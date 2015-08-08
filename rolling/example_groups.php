<?php
require 'RollingCurl.php';
require 'RollingCurlGroup.php';

class TestCurlRequest extends RollingCurlGroupRequest {
    public $test_verbose = true;

    function process($output, $info) {
        echo "Processing " . $this->url . "\n";
        if ($this->test_verbose)
            print_r($info);

        parent::process($output, $info);
    }
}

class TestCurlGroup extends RollingCurlGroup {
    function process($output, $info, $request) {
        echo "Group CB: Progress " . $this->name . " (" . ($this->finished_requests + 1) . "/" . $this->num_requests . ")\n";
        parent::process($output, $info, $request);
    }

    function finished() {
        echo "Group CB: Finished" . $this->name . "\n";
        parent::finished();
    }
}

$group = new TestCurlGroup("High");
$group->add(new TestCurlRequest("www.google.de"));
$group->add(new TestCurlRequest("www.yahoo.de"));
$group->add(new TestCurlRequest("www.newyorktimes.com"));
$reqs[] = $group;

$group = new TestCurlGroup("Normal");
$group->add(new TestCurlRequest("twitter.com"));
$group->add(new TestCurlRequest("www.bing.com"));
$group->add(new TestCurlRequest("m.facebook.com"));
$reqs[] = $group;

$reqs[] = new TestCurlRequest("www.kernel.org");

// No callback here, as its done in Request class
$rc = new GroupRollingCurl();

foreach ($reqs as $req)
    $rc->add($req);

$rc->execute();