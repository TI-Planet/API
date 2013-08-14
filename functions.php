<?php

// function defination to convert array to xml
function array_to_xml($results, &$xml)
{
    foreach ($results as $key => $value) {
        if (is_array($value)) {
            if (!is_numeric($key)) {
                $subnode = $xml->addChild("$key");
                array_to_xml($value, $subnode);
            } else {
                $subnode = $xml->addChild("result$key");
                array_to_xml($value, $subnode);
            }
        } else {
            $xml->addChild(is_numeric($key) ? "item$key" : "$key", "$value");
        }
    }
}

function ishere($thing = NULL)
{
    return isset($thing) && ($thing != '');
}

function checkApiKEY(PDO $pdo, $key)
{
    $isGood = false;

    $req = $pdo->prepare('SELECT `name` FROM `api_users` WHERE `apikey` = :apiKey');
    $req->execute(array(':apiKey' => $key));

    foreach ($req as $_) $isGood = true;

    return $isGood;
}

function logQueryMetadata(PDO $pdo, $key, $type = "invalid", $results = 9001)
{
    $req = $pdo->prepare('INSERT INTO `api_logs` (`time`, `type`, `results`, `apikey`, `ip`) VALUES ( :theTime , :type , :results , :apiKey , :ip )');
    $req->execute(array(':apiKey' => $key, ":theTime" => time(), ":type" => $type, ":results" => $results, ":ip" => $_SERVER["REMOTE_ADDR"]));
    // stuff to do : detect too many queries from the same IP for auto-temp-ban, etc.
}

function clean($var)
{
    return (gettype($var) == "array") ? sizeof($var) : @strlen($var);
}

function improve_urls(&$array)
{
    if (isset($array["screenshot"]) && substr($array["screenshot"], 0, 4) !== "http")
        $array["screenshot"] = "https://tiplanet.org/" . $array["screenshot"];
    if (isset($array["url"]) && substr($array["url"], 0, 4) !== "http")
        $array["url"] = "https://tiplanet.org/modules/archives/" . $array["url"];
}

function put_platform(&$array)
{
    $array["platform"] = "Unknown";
    switch (gettype($array["category"])) {
        case "string":
            $array["platform"] = trim(substr($array["category"], strrpos($array["category"], ' ')));
            break;
        case "array":
            $array["platform"] = array();
            foreach ($array["category"] as $item)
                array_push($array["platform"], trim(substr($item, strrpos($item, ' '))));
            break;
    }
}

function improve_categories(&$array) {
    if (gettype($array["category"]) == "string") {
        $array["category"] = substr($array["category"], 0, strrpos($array["category"], " ")); // removing last word
    } else { // array
        foreach ($array["category"] as $key => $value) {
            $array["category"][$key] = substr($value, 0, strrpos($value, " ")); // removing last word
        }
        $array["category"] = array_unique($array["category"]);
        if (sizeof($array["category"]) == 1) $array["category"] = $array["category"][0];
    }
}

function improve($array)
{
    $tmp = $array["category"] . "~;~" . $array["category2"] . "~;~" . $array["category3"] . "~;~" . $array["category4"];
    if (strpos($tmp, 'Nspire') !== false) { // string contained in another string...
        if ($array["nspire_os"] == '') // try to guess the OS according to the upload date (the timestamp is a bit after 3.1's release)
            $array["nspire_os"] = ($array["upload_date"] > 1317420000 ? "3.1+ (?)" : "<= 3.0 (?)");
    }
    if (@ishere($array["category2"])) {
        $array["category"] = array_filter(explode("~;~", $tmp));
        $array["category2"] = $array["category3"] = $array["category4"] = NULL;
    }

    $tmp = $array["author"] . "~;~" . $array["author2"] . "~;~" . $array["author3"] . "~;~" . $array["author4"];
    if (@ishere($array["author2"])) {
        $array["author"] = array_filter(explode("~;~", $tmp));
        $array["author2"] = $array["author3"] = $array["author4"] = NULL;
    }

    put_platform($array);

    improve_categories($array);

    $array = array_filter($array, 'clean'); // remove empty elements (keeping the ones with value "0")
    unset($array["private"]);

    if (@ishere($array["license"]) && $array["license"] == "Non spécifiée / Incluse") $array["license"] = "Unknown / included";
    $array["page"] = "https://tiplanet.org/forum/archives_voir.php?id=" . $array["arcID"];

    improve_urls($array);

    return $array;
}

function output($element)
{
    global $results;
    array_push($results, $element);
}

function output_status($type, $msg = NULL)
{
    global $results;
    $results["Status"] = (int)$type;
    if (isset($msg)) $results["Message"] = $msg;
}

function output_resultsNumber($nbr)
{
    global $results;
    $results["Results"] = $nbr;
}

?>