<?php
error_reporting(E_ALL & ~E_NOTICE); //error reporting control
// required headers
header("Access-Control-Allow-Origin: *");
//header("Content-Type: multipart/form-data; charset=UTF-8");
header("Content-Type: application/json; charset=UTF-8");
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); //convert JSON into array

$fileNameRaw = $input['fileName'];
$fileNameEx = explode("\\", $fileNameRaw);
$fileNameCount = count($fileNameEx);
$fileName = $fileNameEx[$fileNameCount-1];

$interestFileNameRaw = $input['interestFileName'];
$interestFileNameEx = explode("\\", $interestFileNameRaw);
$interestFileNameCount = count($interestFileNameEx);
$interestFileName = $interestFileNameEx[$interestFileNameCount-1];

$destinationfilepath = "/usr/xfer/";
$destinationfilepath = 'C:\\xampp\\htdocs\\efs\\xfer\\';

$rawFilepath = 'C:\\xampp\\htdocs\\efs';
$fileNameFull = $rawFilepath."\\".$input['clientId']."\\".$fileName;
$interestFileNameFull = $rawFilepath."\\".$input['clientId']."\\".$interestFileName;

//$fileNameFull = "/home/nobody/".$input['clientId']."/".$fileName;
$myfile = fopen($fileNameFull, "rw");
fwrite($myfile, $input['fileContent']);
fclose($myfile);

//if(strlen($input['interestFileContent']) > 0){
    $myInterestFile = fopen($interestFileNameFull, "rw");
    fwrite($myInterestFile, $input['interestFileContent']);
    fclose($myInterestFile); 
//}

$recallIds = null;

global $UploadClientId;
global $conversion;
$UploadClientId = $input['clientId'];
$conversion = $input['conversionType'];

//include our classes and conversion functions
require_once('../includes/classes/class.clientFunctions.php'); //specific to individual client IDs
require_once('../includes/classes/class.conversion.php'); //shared throughout program

$data = file($fileNameFull);
$thisConversion = new Conversion($data, $fileName, $destinationfilepath, $recallIds, $input['accessType']); //run the data through the conversion process

	$product_item=array(
            "id" => $input['clientId'],
            "fileContent" => $input['fileContent'],
            "fileName" => $fileName,
            "extension" => $input['fileExtension'],
            "conversionType" => $input['conversionType']
    );
    
    // set response code - 200 OK
    http_response_code(200);
 
    // show products data in json format
    echo json_encode($thisConversion->convertedData);
?>