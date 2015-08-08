Rolling Curl
============

RollingCurl allows you to process multiple HTTP requests in parallel using CURL PHP library.

Released under the Apache License 2.0.

Authors
-------
- Was originally written by [Josh Fraser](joshfraser.com).
- Currently maintained by [Alexander Makarov](http://rmcreative.ru/).
- Received significant updates and patched from [LionsAd](http://github.com/LionsAd/rolling-curl).

Overview
--------
RollingCurl is a more efficient implementation of curl_multi() curl_multi is a great way to process multiple HTTP requests in parallel in PHP.
curl_multi is particularly handy when working with large data sets (like fetching thousands of RSS feeds at one time). Unfortunately there is
very little documentation on the best way to implement curl_multi. As a result, most of the examples around the web are either inefficient or
fail entirely when asked to handle more than a few hundred requests.

The problem is that most implementations of curl_multi wait for each set of requests to complete before processing them. If there are too many requests
to process at once, they usually get broken into groups that are then processed one at a time. The problem with this is that each group has to wait for
the slowest request to download. In a group of 100 requests, all it takes is one slow one to delay the processing of 99 others. The larger the number of
requests you are dealing with, the more noticeable this latency becomes.

The solution is to process each request as soon as it completes. This eliminates the wasted CPU cycles from busy waiting. Also there is a queue of
cURL requests to allow for maximum throughput. Each time a request is completed, a new one is added from the queue. By dynamically adding and removing
links, we keep a constant number of links downloading at all times. This gives us a way to throttle the amount of simultaneous requests we are sending.
The result is a faster and more efficient way of processing large quantities of cURL requests in parallel.

Callbacks
---------

Each of requests usually do have a callback to process results that is being executed when request is done
(both successfully or not).

Callback accepts three parameters and can look like the following one:
~~~
[php]
function request_callback($response, $info, $request){
    // doing something with the data received
}
~~~

- $response contains received page body.
- $info is an associative array that holds various information about response such as HTTP response code, content type,
time taken to make request etc.
- $request contains RollingCurlRequest that was used to make request.

Examples
--------
### Hello world

~~~
[php]
// an array of URL's to fetch
$urls = array("http://www.google.com",
              "http://www.facebook.com",
              "http://www.yahoo.com");

// a function that will process the returned responses
function request_callback($response, $info, $request) {
	// parse the page title out of the returned HTML
	if (preg_match("~<title>(.*?)</title>~i", $response, $out)) {
		$title = $out[1];
	}
	echo "<b>$title</b><br />";
	print_r($info);
	echo "<hr>";
}

// create a new RollingCurl object and pass it the name of your custom callback function
$rc = new RollingCurl("request_callback");
// the window size determines how many simultaneous requests to allow.
$rc->window_size = 20;
foreach ($urls as $url) {
    // add each request to the RollingCurl object
    $request = new RollingCurlRequest($url);
    $rc->add($request);
}
$rc->execute();
~~~


### Setting custom options

Set custom options for EVERY request:

~~~
[php]
$rc = new RollingCurl("request_callback");
$rc->options = array(CURLOPT_HEADER => true, CURLOPT_NOBODY => true);
$rc->execute();
~~~

Set custom options for A SINGLE request:

~~~
[php]
$rc = new RollingCurl("request_callback");
$request = new RollingCurlRequest($url);
$request->options = array(CURLOPT_HEADER => true, CURLOPT_NOBODY => true);
$rc->add($request);
$rc->execute();
~~~

### Shortcuts

~~~
[php]
$rc = new RollingCurl("request_callback");
$rc->get("http://www.google.com");
$rc->get("http://www.yahoo.com");
$rc->execute();
~~~

### Class callbacks

~~~
[php]
class MyInfoCollector {
    private $rc;

    function __construct(){
        $this->rc = new RollingCurl(array($this, 'processPage'));
    }

    function processPage($response, $info, $request){
      //...
    }

    function run($urls){
        foreach ($urls as $url){
            $request = new RollingCurlRequest($url);
            $this->rc->add($request);
        }
        $this->rc->execute();
    }
}

$collector = new MyInfoCollector();
$collector->run(array(
    'http://google.com/',
    'http://yahoo.com/'
));
~~~

### Using RollingCurlGroup

~~~
[php]
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
~~~

The same function (add) can be used both for adding requests and groups of requests.
The "callback" in request and groups is:

process($output, $info)

and

process($output, $info, $request)

Also you can override RollingCurlGroup::finished() that will be executed right after finishing group processing.

$Id$