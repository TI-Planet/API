<?php

// include the db config file

include("connect_pdo.php");
include("functions.php");

define('TIME_START',microtime());

$results = array("Status" => NULL, "Message" => NULL, "Results" => 0);

$output_type = "json";
if (@ishere($_REQUEST["output"])) {
    $output_type = strtolower($_REQUEST["output"]);
}
$useGZ = false;
if (@ishere($_REQUEST["gz"]) && $_REQUEST["gz"] == "1") {
    $useGZ = true;
}

if (@ishere($_REQUEST["key"])) {
    $apiKey = $_REQUEST["key"];

    if (checkApiKEY($pdo, $apiKey)) {
        $reqType = "none";
        $resCount = 0;
        if (@ishere($_REQUEST['req'])) {
            $reqType = $_REQUEST['req'];
            switch ($reqType) {
                case "arc":
                    if (@ishere($_REQUEST['arcID'])) {
                        $arcID = (int)$_REQUEST['arcID'];

                        $cols = "archives.id as arcID, nom AS title, date AS upload_date, author, author2, author3, author4, "
                              . "categorie AS category, categorie2 AS category2, categorie3 AS category3, categorie4 AS category4, private, "
                              . "capture AS screenshot, fichier AS url, hit AS dlcount, os AS nspire_os, licence.title AS license";

                        $req = $pdo->prepare('SELECT ' . $cols . ' FROM archives, licence WHERE archives.id = :arcID AND licence.id=archives.licence');
                        $req->execute(array(':arcID' => $arcID));
                        $req->setFetchMode(PDO::FETCH_ASSOC);

                        $needStatus = true;
                        foreach ($req as $item) {
                            $resCount++;
                            if ($item["private"] === "1") {
                                output_status(32, "This archive is private.");
                                $needStatus = false;
                            } else {
                                $item = improve($item);
                                output($item);
                            }
                        }
                        if ($resCount == 0) {
                            output_status(31, "This archive does not exist !");
                        } else {
                            if ($needStatus) {
                                output_resultsNumber($resCount);
                                output_status(0, "Request successful (" . round((microtime() - TIME_START), 4) . " s.)");
                            }
                        }
                    } else {
                        output_status(30, "No arcID given !");
                    }
                    break;
                case "search":
                    if (@ishere($_REQUEST['name']) || @ishere($_REQUEST['author'])) {

                        $filterName = $filterAuthor = $filterPlatform = "";
                        $params = array();

                        if (@ishere($_REQUEST['platform'])) {
                            $filterPlatform = "AND (categorie REGEXP CONCAT('(.*) ', :platform1 , '$')"
                                            . " OR categorie2 REGEXP CONCAT('(.*) ', :platform2 , '$')"
                                            . " OR categorie3 REGEXP CONCAT('(.*) ', :platform3 , '$')"
                                            . " OR categorie4 REGEXP CONCAT('(.*) ', :platform4 , '$')) ";
                            $params[":platform1"] = $params[":platform2"] = $params[":platform3"]= $params[":platform4"] = $_REQUEST['platform'];
                        }

                        if (@ishere($_REQUEST['name'])) {
                            if (strlen($_REQUEST['name']) < 5) {
                                $filterName = "AND nom REGEXP CONCAT( :name , '(.*)') " . $filterPlatform;
                            } else {
                                $filterName = "AND nom REGEXP CONCAT('(.*)', :name , '(.*)') " . $filterPlatform;
                            }
                            $params[":name"] = $_REQUEST['name'];
                        }

                        if (@ishere($_REQUEST['author'])) {
                            if (strlen($_REQUEST['author']) <= 5) {
                                $filterAuthor = "AND author = :author " . $filterPlatform;
                            } else {
                                $filterAuthor = "AND author REGEXP CONCAT('(.*)', :author , '(.*)') " . $filterPlatform;
                            }
                            $params[":author"] = $_REQUEST['author'];
                        }

                        $cols = "archives.id as arcID, nom AS title, categorie AS category";

                        $req = $pdo->prepare('SELECT ' . $cols . ' FROM archives WHERE private=0 ' . $filterAuthor . $filterName );
                        $req->execute($params);
                        $req->setFetchMode(PDO::FETCH_ASSOC);

                        foreach ($req as $item) {
                            $resCount++;
                            put_platform($item);
                            unset($item["category"]);
                            output($item);
                            if ($resCount == 500) {
                                $results = array("Alert" => "Max results (500) reached. Consider filtering (more).") + $results;
                                break;
                            }
                        }
                        output_resultsNumber($resCount);
                        output_status(0, "Request successful (" . round((microtime() - TIME_START), 4) . " s.)");
                    } else {
                        output_status(20, "Neither 'name' nor 'author' parameter given !");
                    }
                    break;
                default:
                    output_status(11, "Unrecognized request type : '" . $reqType . "' !");
                    break;
            }
        } else {
            output_status(10, "No request type given !");
        }
        logQueryMetadata($pdo, $apiKey, $reqType, $resCount);
    } else {
        output_status(2, "Invalid API key ! ");
    }
} else {
    output_status(1, "No API key given !");
}

if ($output_type == "xml") {
    $xml = new SimpleXMLElement('<APIResponse/>');
    array_to_xml($results, $xml);
    finalOutput($xml->asXML(), 'Content-Type: application/xml; charset=utf-8');
} elseif ($output_type == "php") {
    finalOutput(serialize($results), 'Content-Type: application/vnd.php.serialized; charset=utf-8');
} elseif ($output_type == "phpdebug") {
    finalOutput($results, 'Content-Type: text/plain; charset=utf-8');
} else {
    if ($output_type != "json")
        $results = array("Alert" => "Unrecognized output type '" . $output_type . "' ; defaulting to json.") + $results;
    finalOutput(json_encode($results), 'Content-Type: application/json; charset=utf-8');
}

error_reporting(0);
ini_set('display_errors', '0');

?>
