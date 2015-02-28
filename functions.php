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

function checkApiKEY(PDO $pdo, $key)
{
    $req = $pdo->prepare('SELECT count(*) FROM `api_users` WHERE `apikey` = :apiKey');
    $req->execute(array(':apiKey' => $key));
    $nbr = $req->fetchColumn(0);
    return ($nbr != 0);
}

function logQueryMetadata(PDO $pdo, $key, $type = "invalid", $results = 9001)
{
    $req = $pdo->prepare('INSERT INTO `api_logs` (`time`, `type`, `results`, `apikey`, `ip`) VALUES ( :theTime , :type , :results , :apiKey , :ip )');
    $req->execute(array(':apiKey' => $key, ":theTime" => time(), ":type" => $type, ":results" => $results, ":ip" => $_SERVER["REMOTE_ADDR"]));
    // stuff to do : detect too many queries from the same IP for auto-temp-ban, etc.
}

function AM_to_API($arc, $light = false)
{
    $authors = $categories = $platforms = $targets = [];

    foreach ($arc->categories as &$categorie)
        $categories[] = $categorie->name;
    foreach ($arc->platforms as $platform)
        $platforms[] = $platform->name;
    foreach ($arc->targets as $target)
        $targets[] = $target->name;
    $new = [];
    $new['arcID']       = (string)$arc->id;
    $new['name']        = $arc->name;
    $new['category']    = $categories;
    $new['target']      = $targets;
    $new['platform']    = $targets;
    $new['actual_platform'] = $platforms;

    if (!$light) {
        $new['page'] = "http://ti-pla.net/a" . $arc->id;
        $new['dlcount'] = (string)$arc->hits;
        $new['url'] = $arc->dl_link;
        $new['upload_date'] = (string)$arc->upload_date;
        $new['update_date'] = (string)$arc->update_date;
        $new['uploader'] = $arc->uploader->name;
        $new['file_size'] = (string)$arc->file_size;
        $new['last_dl'] = (string)$arc->last_hit_date;
        $new['screenshot'] = $arc->screenshots[0];
        $new['license'] = $arc->license->name;

        foreach ($arc->targets as $target) {
            if (strpos($target->name, 'Nspire') !== false) {
                $new["nspire_os"] = ($arc->upload_date > 1317420000 ? "3.1+ (?)" : "<= 3.0 (?)");
                break;
            }
        }
        foreach ($arc->authors as $author)
            $authors[] = $author->name;
        $new['author'] = $authors;
    }

    return $new;
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