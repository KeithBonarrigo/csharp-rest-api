<?php
error_reporting(E_ERROR); //error reporting control
// required headers
header("Access-Control-Allow-Origin: *");
//header("Content-Type: multipart/form-data; charset=UTF-8");
header("Content-Type: application/json; charset=UTF-8");
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); //convert JSON into array

$mode = "dev";

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
$myfile = fopen($fileNameFull, "w+");
fwrite($myfile, $input['fileContent']);
fclose($myfile);

/*if(strlen($input['interestContent']) > 0){
    $myInterestFile = fopen($interestFileNameFull, "w+");
    fwrite($myInterestFile, $input['interestContent']);
    fclose($myInterestFile); 
}*/

$recallIds = null;

global $UploadClientId;
global $conversion;
$UploadClientId = $input['clientId'];
$conversion = $input['conversionType'];

//include our classes and conversion functions
require_once('../includes/classes/class.clientFunctionsAPI.php'); //specific to individual client IDs
require_once('../includes/classes/class.conversionAPI.php'); //shared throughout program

/*
$myfile = fopen('input3.txt', "w+");
ob_start();
print_r($input);
$stuff = ob_get_contents();
ob_end_clean();
fwrite($myfile, $stuff);
fclose($myfile);
*/

$data = file($fileNameFull); 

$thisConversion = new Conversion($data, $fileName, $interestFileNameFull, $destinationfilepath, $recallIds, $input['accessType'], $input['interestContent'], $mode); //run the data through the conversion process

$myfile = fopen('conversionObject.txt', "w+");
ob_start();
print_r($thisConversion);
$stuff = ob_get_contents();
ob_end_clean();
fwrite($myfile, $stuff);
fclose($myfile);

//$results = print_r($thisConversion, true); // $results now contains output from print_r

/*
	$product_item=array(
            "id" => $input['clientId'],
            "fileContent" => $input['fileContent'],
            "fileName" => $fileName,
            "interestFileName" => $interestFileName,
            "extension" => $input['fileExtension'],
            "conversionType" => $input['conversionType']
    );
 */   
    // set response code - 200 OK
    http_response_code(200);

    $myfile = fopen('lastJsonResponse.txt', "w+");
    fwrite($myfile, json_encode($thisConversion->convertedData));
    fclose($myfile);

    // show products data in json format
    if( strlen($thisConversion->convertedData['accountData'])>0 || strlen($thisConversion->convertedData['noteData'])>0){
        echo json_encode($thisConversion->convertedData);
        
    }
?>