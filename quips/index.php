<?php

function load_settings()
{
    // TODO: load from external file
    $GLOBALS["feed_settings"] = [
        "title" => "Feed Name",
        "url" => "http://th.adde.us/quips/feed.rss",
        "description" => "stuff",
        "language" => "en-us",
        "copyright" => "Copyright (C) 2014 Thaddeus Ternes"
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
    // echo $rss->asXml();
    
    // pretty output
    $dom = dom_import_simplexml($rss)->ownerDocument;
    $dom->formatOutput = true;
    echo $dom->saveXML();
}

if(php_sapi_name() == "cli")
{
    // command line
    generate_rss_document();
}
else if($_REQUEST["METHOD"] == "POST")
{
    // do post
}
else if($_REQUEST["METHOD"] == "GET")
{
    generate_rss_document();
}

?>