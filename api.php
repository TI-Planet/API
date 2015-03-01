<?php

/*   Adrien 'Adriweb' Bertrand
 *   v2.0 - Feb. 27th, 2015
 *   tiplanet.org
 */

/* @var $pdo    PDO */
global $pdo;

include_once "/data/web/vhosts/tiplanet.org/ROOT/archivesv2/ArchiveManager.php";
include_once "functions.php";

/* @var $arcMan ArcMan_v1 */
$arcMan = ArchiveManager::create(AM_Mode::Read, AM_User::$DummyUserData, AM_Version::v1);

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
            switch ($reqType)
            {
                case "arc":
                case "info":
                    if (@ishere($_REQUEST['arcID']))
                    {
                        $arcID = (int)$_REQUEST['arcID'];
                        if ($arcID > 0)
                        {
                            $arcMan->select_archive($arcID);
                            $arc = $arcMan->get_archive_info();

                            if ($arc === null) {
                                output_status(31, "This archive does not exist !");
                            } else {
                                if ($arc->deleted !== false) {
                                    output_status(32, "This archive has been deleted.");
                                } elseif ($arc->private === 1 && $arc->name === null) { // private and not allowed (user is not uploader, for instance)
                                    output_status(32, "This archive is private.");
                                } else {
                                    output_resultsNumber(1);
                                    output_status(0, "Request successful (" . abs(round((microtime() - TIME_START), 4)) . " s.)");
                                    output(AM_to_API($arc));
                                }
                            }
                        } else {
                            output_status(30, "Invalid archive id ('arcID') given !");
                        }
                    } else {
                        output_status(30, "No archive id ('arcID') given !");
                    }
                    break;

                case "list":
                    $archives = $arcMan->search([["private","=","0"], ["deleted","IS","NULL"], ["generator","=","0"]], ["id", "name", "categories"], ["hitsD"], 99999);

                    foreach ($archives as $arc) {
                        output(AM_to_API($arc, true));
                    }
                    output_resultsNumber(count($archives));
                    output_status(0, "Request successful (listing all public uploads with their categories). " . ($debug ? " (" . abs(round((microtime() - TIME_START), 4)) . " s.)" : ""));
                    break;

                case "search":
                    if (@ishere($_REQUEST['name']) || @ishere($_REQUEST['platform']) || @ishere($_REQUEST['author']) || @ishere($_REQUEST['category'])) {

                        $filters = [ ["private","=","0"], ["deleted","IS","NULL"], ["generator","=","0"] ];

                        if (@ishere($_REQUEST['platform'])) {
                            $platform = $_REQUEST['platform'];
                            array_push($filters, ["platform", "REGEXP", ".*{$platform}.*"]);
                        }
                        if (@ishere($_REQUEST['category'])) {
                            $category = $_REQUEST['category'];
                            array_push($filters, ["category", "REGEXP", ".*{$category}.*"]);
                        }
                        if (@ishere($_REQUEST['name'])) {
                            $name = $_REQUEST['name'];
                            array_push($filters, (strlen($name) <= 5) ? ["name", "=", $name] : ["name", "REGEXP", ".*{$name}.*"]);
                        }
                        if (@ishere($_REQUEST['author'])) {
                            $author = $_REQUEST['author'];
                            array_push($filters, (strlen($author) <= 5) ? ["author", "=", $author] : ["author", "REGEXP", ".*{$author}.*"]);
                        }

                        $archives = $arcMan->search($filters, ["id", "name", "categories"], ["hitsD"], 99999);

                        foreach ($archives as $arc) {
                            output(AM_to_API($arc, true));
                        }
                        output_resultsNumber(count($archives));
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