<?php

function load_settings()
{
    // TODO: load from external file
    $GLOBALS["feed_settings"] = [
        "title" => "Feed Name",
        "url" => "http://th.adde.us/quips/feed.rss",
        "description" => "stuff",
        "language" => "en-us",
        "copyright" => "Copyright (C) 2014 Thaddeus Ternes",

        "basicauth" => [
            // list of user/password pairs
            "thaddeus" => "password",
        ],
        
        "paths" => [
            "posts" => "/tmp/quips",
        ],
        
        "pretty" => true,
    ];
}


function generate_rss_document()
{
    load_settings();
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

    // TODO: load real data
    for($i=0; $i<10; $i++)
    {
        $item = $channel->AddChild("item");
        $item->AddChild("title", ""); // no title in Snippets
        $item->AddChild("description", "little thing");
        $item->AddChild("link");
        // $item->AddChild("pubDate", date("D, d M Y H:i:s O", strtotime($date)));
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
    $body = @file_get_contents('php://input');
    $post = json_decode($body);
    print_r($post);
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


// ------------------------------------------------------------------------------------
// Run
// ------------------------------------------------------------------------------------
if(php_sapi_name() == "cli")
{
    // command line
    generate_rss_document();
}
else if($_SERVER["REQUEST_METHOD"] == "POST")
{    

    load_settings();
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
