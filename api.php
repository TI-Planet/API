<?php

/*   Adrien 'Adriweb' Bertrand
 *   v1.3 - July 20th, 2014
 *   tiplanet.org
 */

include("connect_pdo.php");
include("functions.php");

define('TIME_START', microtime());

$results = array("Status" => NULL, "Message" => NULL, "Results" => 0);

$output_type = "json";
if (@ishere($_REQUEST["output"])) {
    $output_type = strtolower($_REQUEST["output"]);
}

if (@ishere($_REQUEST["key"])) {
    $apiKey = $_REQUEST["key"];

    $debug = false;
	
    if (checkApiKEY($pdo, $apiKey)) {
        $reqType = "none";
        $resCount = 0;
        if (@ishere($_REQUEST['req'])) {
            $reqType = $_REQUEST['req'];
            switch ($reqType) {
                case "arc":
                    if (@ishere($_REQUEST['arcID'])) {
                        $arcID = (int)$_REQUEST['arcID'];

                        if ($arcID > 0) {
                            $cols = "archives.id as arcID, nom AS name, date AS upload_date, author, author2, author3, author4, "
                                . "categorie AS category, categorie2 AS category2, categorie3 AS category3, categorie4 AS category4, "
                                . "capture AS screenshot, fichier AS url, hit AS dlcount, os AS nspire_os, licence.title AS license, "
                                . "private, deleted";

                            $req = $pdo->prepare('SELECT ' . $cols . ' FROM archives, licence WHERE archives.id = :arcID AND licence.id=archives.licence');
                            $req->execute(array(':arcID' => $arcID));
                            $req->setFetchMode(PDO::FETCH_ASSOC);

                            $needStatus = true;
                            foreach ($req as $item) {
                                $resCount++;
                                if ($item["deleted"]) {
                                    output_status(32, "This archive has been deleted.");
                                    $needStatus = false;
                                } else if ($item["private"] != "0") {
                                    output_status(32, "This archive is not public.");
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
                                    output_status(0, "Request successful (" . abs(round((microtime() - TIME_START), 4)) . " s.)");
                                }
                            }
                        } else {
                            output_status(30, "No (valid) archive id ('arcID') given !");
                        }
                    } else {
                        output_status(30, "No (valid) archive id ('arcID') given !");
                    }
                    break;
                case "list":
                    $cols = "archives.id as arcID, nom AS name, categorie AS category";
                    $req = $pdo->prepare('SELECT ' . $cols . ' FROM archives WHERE private=0 AND generator=0 AND deleted IS NULL ');
                    $req->execute();
                    $req->setFetchMode(PDO::FETCH_ASSOC);

                    foreach ($req as $item) {
                        $resCount++;
                        output($item);
                    }
                    output_resultsNumber($resCount);
                    output_status(0, "Request successful (listing all public uploads with their primary category). " . ($debug ? " (" . abs(round((microtime() - TIME_START), 4)) . " s.)" : ""));
                    break;
                case "search":
                    if (@ishere($_REQUEST['name']) || @ishere($_REQUEST['platform']) || @ishere($_REQUEST['author']) || @ishere($_REQUEST['category'])) {

                        $filterName = $filterAuthor = $filterPlatform = $filterCategory = "";
                        $params = array();

                        if (@ishere($_REQUEST['platform'])) {
                            $filterPlatform = "AND (categorie REGEXP CONCAT('(.*) ', :platform1 , '$')"
                                            . " OR categorie2 REGEXP CONCAT('(.*) ', :platform2 , '$')"
                                            . " OR categorie3 REGEXP CONCAT('(.*) ', :platform3 , '$')"
                                            . " OR categorie4 REGEXP CONCAT('(.*) ', :platform4 , '$')) ";
                            $params[":platform1"] = $params[":platform2"] = $params[":platform3"] = $params[":platform4"] = $_REQUEST['platform'];
                        }

                        if (@ishere($_REQUEST['category'])) {
                            $filterCategory = "AND (categorie REGEXP CONCAT( :cat1 , '(.*) ')"
                                            . " OR categorie2 REGEXP CONCAT( :cat2 , '(.*) ')"
                                            . " OR categorie3 REGEXP CONCAT( :cat3 , '(.*) ')"
                                            . " OR categorie4 REGEXP CONCAT( :cat4 , '(.*) ')) ";
                            $params[":cat1"] = $params[":cat2"] = $params[":cat3"] = $params[":cat4"] = $_REQUEST['category'];
                        }

                        if (@ishere($_REQUEST['name'])) {
                            if (strlen($_REQUEST['name']) < 5) {
                                $filterName = "AND nom REGEXP CONCAT( :name , '(.*)') ";
                            } else {
                                $filterName = "AND nom REGEXP CONCAT('(.*)', :name , '(.*)') ";
                            }
                            $params[":name"] = $_REQUEST['name'];
                        }

                        if (@ishere($_REQUEST['author'])) {
                            if (strlen($_REQUEST['author']) <= 5) {
                                $filterAuthor = "AND author = :author ";
                            } else {
                                $filterAuthor = "AND author REGEXP CONCAT('(.*)', :author , '(.*)') ";
                            }
                            $params[":author"] = $_REQUEST['author'];
                        }

                        $cols = "archives.id as arcID, nom AS name, categorie AS category, categorie2 AS category2, categorie3 AS category3, categorie4 AS category4";

                        $req = $pdo->prepare('SELECT ' . $cols . ' FROM archives WHERE private=0 AND deleted IS NULL ' . $filterPlatform . $filterCategory . $filterAuthor . $filterName);
                        $req->execute($params);
                        $req->setFetchMode(PDO::FETCH_ASSOC);

                        foreach ($req as $item) {
                            $resCount++;
                            improve_categories($item); // (platform)
                            unset($item["category"], $item["nspire_os"]);
                            output($item);
                        }
                        output_resultsNumber($resCount);
                        output_status(0, "Request successful" . ($debug ? " (" . abs(round((microtime() - TIME_START), 4)) . " s.)" : ""));
                    } else {
                        output_status(20, "At least 1 search filter ('name', 'author', 'category', 'platform') has to be given !");
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
    header('Content-Type: text/plain; charset=utf-8');
    print_r($results);
} else {
    if ($output_type != "json" && $output_type != "prettyjson")
        $results = array("Alert" => "Unrecognized output type '" . $output_type . "' ; defaulting to json.") + $results;
    finalOutput(json_encode($results, $output_type == "prettyjson" ? JSON_PRETTY_PRINT : 0), 'Content-Type: application/json; charset=utf-8');
}

error_reporting(0);
ini_set('display_errors', '0');

?>
