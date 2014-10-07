<?php

function load_settings()
{
    $GLOBALS["feed_settings"] = [
        
        // RSS feed settings - map to <channel> child elements
        "title" => "Feed Name",
        "link" => "http://th.adde.us/quips?format=rss",
        "description" => "stuff",
        "language" => "en-us",
        "copyright" => "Copyright (C) 2014 Thaddeus Ternes",
        
        // timezone to use for pubDate values
        "timezone" => "America/Chicago",

        "basicauth" => [
            // list of user/password pairs
            "thaddeus" => "password",
        ],
        
        // data directories for the app
        "paths" => [
            
            // data directory (text files) for posts
            "posts" => "/tmp/quips",
        ],
        
        // number of posts to appear in the RSS feed
        "post_limit_count" => 10,
        
        // development values - use false for both in production
        "pretty" => false,
        "debug" => false,
    ];
    
    if(file_exists('settings.inc.php'))
        require_once('settings.inc.php');
}

function is_debug()
{
    global $feed_settings;
    $result = false;

    if(isset($feed_settings["debug"]) && $feed_settings["debug"] == true)
        $result = true;
    
    return $result;
}

function generate_rss_document()
{
    header("Content-Type: application/rss+xml");
    
    $rss = new SimpleXMLElement('<rss/>');
    $rss->AddAttribute("version", "2.0");
    
    global $feed_settings;
    
    $channel = $rss->addChild("channel");
    $channel->AddChild("title", $feed_settings["title"]);
    $channel->AddChild("link", $feed_settings["link"]);
    $channel->AddChild("description", $feed_settings["description"]);
    $channel->AddChild("language", $feed_settings["language"]);
    $channel->AddChild("copyright", $feed_settings["copyright"]);

    $path_posts = $feed_settings["paths"]["posts"];
    $post_limit_count = $feed_settings["post_limit_count"];
    $files = array_slice(array_diff(scandir($path_posts, SCANDIR_SORT_DESCENDING), array('..', '.')), 0, $post_limit_count);
    foreach($files as $file)
    {
        $fullpath = $path_posts . "/" . $file;
        $post_contents = file_get_contents($fullpath);
        $post = json_decode($post_contents);

        $item = $channel->AddChild("item");
        $item->AddChild("title", $post->title);
        $item->AddChild("description", $post->description);
        $item->AddChild("pubDate", $post->date);
        $item->AddChild("link");
        
        if(is_debug())
            $item->AddChild("path", $fullpath);
    }

    // flat output
    $pretty = $feed_settings["pretty"];
    if($pretty || isset($_GET["pretty"]))
    {
        $dom = dom_import_simplexml($rss)->ownerDocument;
        $dom->formatOutput = true;
        echo $dom->saveXML();
    }
    else
    {
        echo $rss->asXml();
    }
}

function generate_basicauth_response()
{
    header('WWW-Authenticate: Basic realm="Quips"');
    header('HTTP/1.0 401 Unauthorized');
}

function create_post_from_current_request()
{
    global $feed_settings;

    $body = @file_get_contents('php://input');
    
    // if the post is plain text, create a post object
    // or, if the post is JSON, store it verbatim
    $post = json_decode($body);
    
    if($post == null)
    {
        $post = [
            "description" => $body,
        ];
    }
    
    // make sure there's a date
    if(!isset($post->date))
    {
        // pubDate format
        $post["date"] = date("D, d M Y H:i:s O");
    }
    
    print_r($post);
    
    // write json file to /path/to/posts/YYYYMMDDhhmm.txt
    $path_posts = $feed_settings["paths"]["posts"];
    $fullpath = $path_posts . "/" . date("YmdHis") . ".txt";

    print_r($fullpath);
    $fp = fopen($fullpath, "w") or die("unable to open output file");
    fwrite($fp, json_encode($post));
    fclose($fp);
}

function verify_authentication()
{
    // verify authentication
    $authenticated = false;
    if(!isset($_SERVER["PHP_AUTH_USER"]) || !isset($_SERVER["PHP_AUTH_PW"]))
    {
        $authenticated = false;
    }
    else
    {
        // verify credentials from settings
        $authenticated = false;
        
        $request_user = $_SERVER["PHP_AUTH_USER"];
        $request_pass = $_SERVER["PHP_AUTH_PW"];

        $users = $GLOBALS["feed_settings"]["basicauth"];
        foreach($users as $username => $password)
        {
            if($username === $request_user && $password === $request_pass)
            {
                $authenticated = true;
                break;
            }
        }
    }
    
    return $authenticated;
}

// ------------------------------------------------------------------------------------
// Setup
// ------------------------------------------------------------------------------------
// enable output buffering, since we fiddle with headers in here
ob_start();

load_settings();

global $feed_settings;
isset($feed_settings["timezone"]) or die("timezone must be specified");
date_default_timezone_set($feed_settings["timezone"]) or die("timezone is invalid");

// ------------------------------------------------------------------------------------
// Run
// ------------------------------------------------------------------------------------
if(php_sapi_name() == "cli")
{
    // command line
    global $feed_settings;
    $feed_settings["debug"] = true;
    $feed_settings["post_limit_count"] = 2;
    generate_rss_document();
}
else if($_SERVER["REQUEST_METHOD"] == "POST")
{
    $authenticated = verify_authentication();
    if($authenticated)
    {
        create_post_from_current_request();
        print("Post Created\n"); // todo
    }
    else
    {
        generate_basicauth_response();        
        print("Authentication Required\n");
    }
}
else if($_SERVER["REQUEST_METHOD"] == "GET")
{
    generate_rss_document();
}

?>
