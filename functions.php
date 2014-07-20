<?php

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
            $child = $xml->addChild(is_numeric($key) ? "item$key" : "$key");
            $child->value = "$value";
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
    if (isset($array["screenshot"]) && substr($array["screenshot"], 0, 4) !== "http") {
        $array["screenshot"] = "https://tiplanet.org/" . $array["screenshot"];
        $array["screenshot"] = str_replace("org/../", "org/", $array["screenshot"]);
    }
    if (isset($array["url"]) && substr($array["url"], 0, 4) !== "http")
        $array["url"] = "https://tiplanet.org/modules/archives/download.php?id=" . $array["arcID"];
}

function put_platform(&$array)
{
    $array["platform"] = array();
    foreach ($array["category"] as $key => $value) {
        array_push($array["platform"], trim(substr($value, strrpos($value, " "))));
        $array["category"][$key] = substr($value, 0, strrpos($value, " ")); // removing last word
    }
}

function improve_categories(&$array)
{
    $array["category"] = array($array["category"], $array["category2"], $array["category3"], $array["category4"]);
    unset($array["category2"], $array["category3"], $array["category4"]);
    $array["category"] = array_filter($array["category"]); // remove empty elements (keeping the ones with value "0")

    foreach ($array["category"] as $value) {
        if (strpos($value, 'Nspire') !== false) {
            if (@ishere($array["nspire_os"]) && $array["nspire_os"] == '') // try to guess the OS according to the upload date (the timestamp is a bit after 3.1's release)
                $array["nspire_os"] = ($array["upload_date"] > 1317420000 ? "3.1+ (?)" : "<= 3.0 (?)");
            break;
        }
    }

    put_platform($array);

    $array["category"] = array_unique($array["category"]);
}

function improve($array)
{
    $tmp = array($array["author"], $array["author2"], $array["author3"], $array["author4"]);
    unset($array["author2"], $array["author3"], $array["author4"]);
    $array["author"] = array_filter($tmp);

    improve_categories($array);

    $array = array_filter($array, 'clean'); // remove empty elements (keeping the ones with value "0")
    unset($array["private"]);

    if (@ishere($array["license"]) && strpos($array["license"], "Non spécifiée") !== false) $array["license"] = "Unknown / included";
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

function finalOutput($data, $header)
{
    header($header);
    echo $data;
}

?>