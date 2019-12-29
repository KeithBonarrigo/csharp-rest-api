<?php
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class Conversion extends clientFunctions{
	public $clientId; //this is the EFS-side client id for this profile and the switch for how we will rearrange the upload data
	public $conversionType; //this is a string that will tell us if it is a collection account or a payment file to bring down the amount owed
	public $notes; //notes...just arbitrary strings
	public $fileData; //this is the raw data from the upload file
	public $recallIds = array(); //this is an array of client ids that we want to potentially check against to recall and put into another file
	public $recalledIds; //this is an array of client ids that we want to potentially check against to recall and put into another file

	public $fileName; //this is the name passed from the data upload file
	public $interestFileName; //this is the name passed from the data upload file

	public $accessType; #NEW for api access
	public $noteFileName; //this is the name passed from the data upload file
	public $fileDest; //this is the base path for the finished export file
	public $exportData; //this is the data we'll create the FACS import file with
	public $convertedData; #NEW this is for the api to send back
	public $noteData; //this is extra data that won't be allowed into the system that we can store for display when the staff uploads
	public $errors = array(); //container for any errors that come up

/////////////////////////////////////////////////////////////////////////////
////builds conversion object
/////////////////////////////////////////////////////////////////////////////
	function __construct($data, $fileName, $interestFileName, $fileDest, $recallIds, $accessType="browser"){
		global $UploadClientId; //this is the client id passed via cache
		global $conversion; //this is the type of conversion like "New Accout File" or "Payment File"
		global $clientnotes; //notes passed in via cache
		global $data; //raw file data
		global $recallIds;

		$this->accessType = $accessType; #New for api access
		$this->clientId = $UploadClientId;
		$this->conversionType = $conversion;
		$this->notes = $clientnotes;
		$this->fileData = $data; //take the raw data from the file
		$this->fileName = $fileName; //take the raw data from the file
		$this->interestFileName = $interestFileName; //take the raw data from the file
		if(is_array($recallIds)){
			$this->recallIds = $this->processRecallIds($recallIds); //take the recall ids and add them to the object if they are populated as an array
		}
		$this->recalledIds = array(); //this is empty for now and will be populated with recalled client data as we run through the file
		$this->noteFileName = "NOTES_".$fileName; //take the raw data from the file
		$this->fileDest = $fileDest; //this is the base path for the finished export
		$this->exportData = array(); //set up an array for each record that we'll process
		$this->noteData = array(); //set up an array for each record that we'll process
		//$this->convertedData = ""; #NEW - placeholder for the converted filedata to send back via the API
		$this->convertedData = array(); #NEW - placeholder for the converted filedata to send back via the API
		$this->findConversion(); //get the conversion name we're going to use to convert the data for import
		$myfile = fopen('start.txt', "w+");
		ob_start();
		print_r($this);
		$stuff = ob_get_contents();
		ob_end_clean();
		fwrite($myfile, $stuff);
		fclose($myfile);
	}
/////////////////////////////////////////////////////////////////////////////
//tests the current client id to see if we need to switch it to something else
//accepts string
//returns string
/////////////////////////////////////////////////////////////////////////////
function setFlags($clientid){
	if($clientid == "Y6149"){
		$clientid = "Y9275";
	}
	return $clientid;
}
/////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
////finds conversion based on client id and upload type
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function findConversion(){
		//set our flags
		$this->clientId = $this->setFlags($this->clientId);
		if($this->conversionType == "New Account File"){ //data is going in for a collection as opposed to going out for a payment file
			switch($this->clientId){ //get the client id profile and react accordingly
				case "Y2532":
				#############################################
				foreach($this->fileData as $k=>$v){
					$data = explode(',', $v);
					$isValid = false;
					$strlenLastName = strlen(trim($data[1]));
					$strlenFirstName = strlen(trim($data[2]));
					$strlenFirstCompany = strlen(trim($data[3]));

					if( is_numeric($data[0]) && ( ($strlenLastName>0 && $strlenFirstName>0) ||  $strlenFirstCompany>0  ) ) { $isValid = true; }

					if($isValid){ //we've hit a new record - process it
						$recordNumber = $data[0];
						if(!array_key_exists($recordNumber,$this->exportData)){ //set up a new data record since it doesn't exist
							$this->exportData[$recordNumber] = array(); //set up the record
							$this->exportData = $this->setUpFacsArray($recordNumber); //this sets up the basic series of variables that we'll populate
							
							//name check
							if($strlenLastName>0 && $strlenFirstName>0){ //determine if this is a person or a company
								$this->exportData[$recordNumber][01]['debtorName'] = $data[1].",".$data[2];
							}elseif($strlenFirstCompany > 0){
								$this->exportData[$recordNumber][01]['debtorName'] = $data[3];
							}
							$this->exportData = $this->convert_Y2532($recordNumber, $v, $data);
						}
					}
				}
				#############################################
				break;
				case "Y3432":
					$xmlFileToOpen = "/home/nobody/".$this->clientId."/".$this->fileName;
					$this->exportData = $this->processXml($this->clientId, $xmlFileToOpen);
					$this->exportData = $this->massageY3432Data();
				break;
				case "Y9155": //this is Columbia Basin Hospital
					foreach($this->fileData as $k=>$v){
						$isValid = $this->checkValidLine_Meditech($v); //run through the file and parse it out - start by checking the header to see if this is the start of a record
					
						if($isValid){ //we've hit a new record - process it
							$recordNumber = substr($v, 0, 9);
							if(!array_key_exists($recordNumber,$this->exportData)){ //set up a new data record since it doesn't exist
								$this->exportData[$recordNumber] = array(); //set up the record
								$this->exportData = $this->setUpFacsArray($recordNumber); //this sets up the basic series of variables that we'll populate
								$this->exportData = $this->populateRecord_Meditech($k, $recordNumber, $medicareFlag, $this->clientId); //populate the raw data fields without any massage
								$this->exportData[$recordNumber] = $this->massageDataShared($this->exportData[$recordNumber]); //massage the data through our shared function								
							} //end if
						} //end if
					} //end for

					$this->exportData = $this->testForInterestFile(); //we've processed the base data into an array - let's see if we have an interest file available. if so, update that interest info for the 980 file
				break;
				case "EBS15": //this is Columbia Basin Hospital's pediatric division
					foreach($this->fileData as $k=>$v){
						$isValid = $this->checkValidLine_Meditech($v, $this->clientId); //run through the file and parse it out - start by checking the header  do see if this is the start of a record

						if($isValid){ //we've hit a new record - process it
							$recordNumber = substr($v, 0, 9);
							if(!array_key_exists($recordNumber,$this->exportData)){ //set up a new data record since it doesn't exist
								$this->exportData[$recordNumber] = array(); //set up the record
								$this->exportData = $this->setUpFacsArray($recordNumber); //this sets up the basic series of variables that we'll populate
								$this->exportData = $this->populateRecord_Meditech($k, $recordNumber, $medicareFlag, $this->clientId); //populate the raw data fields without any massage
								$this->exportData[$recordNumber] = $this->massageDataShared($this->exportData[$recordNumber]);
							} //end if
						} //end if
					} //end for

					$this->exportData = $this->testForInterestFile(); //we've processed the base data into an array - let's see if we have an interest file available. if so, update that info for the 980 file
				break;
				case "Y6051":
					foreach($this->fileData as $k=>$v){
						$lineEx = explode("\t", $v); //break the line out on the tabs
						$recordNumber = preg_replace("/[^0-9,.]/", "", $lineEx[8]);

						$isValid = $this->checkValidLine_Y6051($recordNumber);

						if($isValid == 1){
							//now test for duplicate record number
							$original = ''; //reset original key variable for later use
							if(array_key_exists($recordNumber,$this->exportData)){
								$original = $recordNumber;
								$theseKeys = array_keys($this->exportData);
								////////////////////////////
								$instanceCounter = 0;
								foreach($theseKeys as $tk=>$tv){ //check for duplicate keys
									if(strstr($tv, $recordNumber)){
										$instanceCounter++;
									}
								}
								$recordNumber = $recordNumber."-".$instanceCounter; //append the instance count to make it unique
								////////////////////////////
							} //end if
							//end test
						} //end if

						if($isValid){ //we've hit a new record - process it
							if(!array_key_exists($recordNumber,$this->exportData)){ //set up a new data record since it doesn't exist
								$this->exportData[$recordNumber] = array(); //set up the record
								$this->exportData = $this->setUpFacsArray($recordNumber); //this sets up the basic series of variables that we'll populate
								$this->exportData = $this->populateRecord_Y6051($lineEx, $recordNumber, $original); //populate the raw data fields without any massage
								$this->exportData[$recordNumber] = $this->massageDataShared($this->exportData[$recordNumber]);
							} //end if
						} //end if

					} //end for

					//now do one more loop and adjust any ids that we've added a hyphen to due to mulitple account ids
					foreach($this->exportData as $k=>$v){
						if(strstr($k, "-")){
							$thisSplit = explode("-",$k);
							$this->exportData[$k][1]['recordId'] = $thisSplit[0];
							$this->exportData[$k][1]['debtorNumber'] = $thisSplit[0];
						}
					}
				break;
				case "Y3210": //Medical Associates of Yakima
					$this->clientId = 'Y9263';
					$ssNumbers = array(); //this is the placeholder array for repeated patient stats - we'll go back and reference this as we get new id numbers that apply to the same person
					$fieldsRaw = $this->fileData[1];
					$fields = explode("\t", $fieldsRaw);
					$ssNumber = ""; //placeholder for the social security number that we'll encounter when we loop through the data
					global $controlNumber;
					
					foreach($this->fileData as $k=>$v){
						$lineEx = explode("\t", $v);
						$idNumber = preg_replace("/[^0-9,.]/", "", $lineEx[26]);
						if(strlen($lineEx[0])>0){
							$controlNumber = $lineEx[0];
						}

						$ssNumber = preg_replace("/[^0-9,.]/", "", $lineEx[20]);
						$isValid = $this->checkValidLine_Y3210($idNumber);

						if($isValid){ //we've hit a new record - process it
							$ssNumbers = $this->populateSsNumbers($ssNumbers, $ssNumber, $lineEx, $fields, 'Y3210'); //we have a new record so we need to see if the SSN is already populated

							if(!array_key_exists($idNumber,$this->exportData)){ //set up a new id record since it doesn't exist
								$this->exportData[$idNumber] = array(); //set up the record
								$this->exportData = $this->setUpFacsArray($idNumber); //this sets up the basic series of variables that we'll populate
								$this->exportData = $this->populateRecord_Y3210($lineEx, $idNumber, $ssNumbers, $ssNumber, $controlNumber); //populate the raw data fields without any massage
								$this->exportData[$idNumber] = $this->massageDataShared($this->exportData[$idNumber]);
							}
						}
					}
				break;
				case "Y9083": //Providence Medical
					$this->exportData = array();
					foreach($this->fileData as $k=>$v){
						if(strstr($v, "|")){
							$lineEx = explode("|", trim($v));
							$this->exportData = $this->checkDuplicateIncomingData($this->clientId, $lineEx); //see if we've have this person's account on file already
						}
					}
				break;
				case "Y9275": //Sunnyside Community Hospital (SCH)
					if(strstr($this->fileData[4], "MC-BD")){
						$medicareFlag = 1; //we need to track the medicare flag to know if we need to populate the 90 window
					}

					foreach($this->fileData as $k=>$v){
						$isValid = $this->checkValidLine_Meditech($v); //run through the file and parse it out - start by checking the header  do see if this is the start of a record

						if($isValid){ //we've hit a new record - process it
							$recordNumber = substr($v, 0, 9);
							////////////////////////////////////
							if(strstr($recordNumber, "BS")){ //this is a special case for Y9275 where we want to take 10 digits instead of 9
								$recordNumber = substr($v, 0, 10);
							}
							////////////////////////////////////
							if(!array_key_exists($recordNumber,$this->exportData)){ //set up a new data record since it doesn't exist
								$this->exportData[$recordNumber] = array(); //set up the record
								$this->exportData = $this->setUpFacsArray($recordNumber); //this sets up the basic series of variables that we'll populate
								$this->exportData = $this->populateRecord_Meditech($k, $recordNumber, $medicareFlag, $this->clientId, -3); //populate the raw data fields without any massage
								$this->exportData[$recordNumber] = $this->massageDataShared($this->exportData[$recordNumber]);
							} //end if
						} //end if

					} //end for
					$this->exportData = $this->testForInterestFile(); //we've processed the base data into an array - let's see if we have an interest file available. if so, update that info for the 980 file
				break;
				case "Y3373": //Lourdes export files

					foreach($this->fileData as $k=>$v){
						$isValid = $this->checkValidLine_Meditech($v, $clientId = "Y3373");  //run through the file and parse it out - start by checking the header  do see if this is the start of a record

						if($isValid){ //we've hit a new record - process it
							$recordNumber = substr($v, 0, 10);
							if(!array_key_exists($recordNumber,$this->exportData)){ //set up a new data record since it doesn't exist
								$this->exportData[$recordNumber] = array(); //set up the record
								$this->exportData = $this->setUpFacsArray($recordNumber); //this sets up the basic series of variables that we'll populate
								$this->exportData = $this->populateRecord_Meditech($k, $recordNumber, $medicareFlag, $this->clientId, -3); //populate the raw data fields without any massage
								$this->exportData[$recordNumber] = $this->massageDataShared($this->exportData[$recordNumber]);
							} //end if
						} //end if

					} //end for

				break;
				case "Y9650": //Virginia Mason Memorial (formerly Yakima Valley Memorial Hospital)
					$counter = 0;
					foreach($this->fileData as $k=>$v){
						$isValid = $this->checkValidLine_VirginiaMason($v, $this->clientId);

						if($isValid){ //we've hit a new record - process it
							$counter++;
							$recordExploded = explode('|', $v);
							$recordNumber = $recordExploded[20]."_".$recordExploded[19]."_".$recordExploded[21];

							////////////////////
							$dateSplitRaw = $recordExploded[77]; //should be drawing from the 78th field - BZ - BlPerStpDate
							$dosSplit = explode(" ", $dateSplitRaw);
							$debtorDos = explode("-", $dosSplit[0]);
							$ageOfService = $this->getAge($debtorDos[1], $debtorDos[2], $debtorDos[0]); //send dob info in m,d,y
							////////////////////
							if(!array_key_exists($recordNumber,$this->exportData)){ //set up a new data record since it doesn't exist
								$this->exportData[$recordNumber] = array(); //set up the record
								$this->exportData = $this->setUpFacsArray($recordNumber); //this sets up the basic series of variables that we'll populate
							}
							
							$this->exportData = $this->populateRecord_VirginiaMason($k, $recordNumber, $this->clientId, $this->exportData, $recordExploded, $debtorDos); //populate the raw data fields without any massage
							if($ageOfService > 6){ //it is too old - remove the entry and place it in the notes object
								$this->noteData[$recordNumber] = $this->exportData[$recordNumber];
								unset($this->exportData[$recordNumber]);
							}
						} //end if

						if($this->exportData[$recordNumber][3]["Medicare"]==1){
							$this->exportData = $this->populateWindow($recordNumber,104, "04", "MEDICARE");
						}
					} //end for
					
					foreach($this->exportData as $k=>$v){ //now loop through and split out the notes
						$this->exportData[$k][3]["Note1"]["text"] = "Guarantor ID: ".$this->exportData[$k][3]["Note1"]["guarantorId"]." | ".$this->exportData[$k][3]["Note1"]["treatmentType"].": ".$this->exportData[$k][3]["Note1"]["encounterName"]." | Location: ".$this->exportData[$k][3]["Note1"]["encounterLocation"]."  | ".$this->exportData[$k][3]["Note1"]["insCompany"]." | Charges: ".$this->exportData[$k][3]["Note1"]["charges"]." Adjs: ".$this->exportData[$k][3]["Note1"]["adjs"]." Pmts: ".$this->exportData[$k][3]["Note1"]["pmts"]." | Gender: ".$this->exportData[$k][3]["Note1"]["gender"];
						if($this->exportData[$k][3]["Note1"]["deceased"]==1) $this->exportData[$k][3]["Note1"]["text"] .= " | Deceased";
						if(strlen($this->exportData[$k][3]["Note1"]["debtorDob"])>0) $this->exportData[$k][3]["Note1"]["text"] =  $this->exportData[$k][3]["Note1"]["text"]." | PatientDOB: ".$this->exportData[$k][3]["Note1"]["debtorDob"];
						if(strlen($this->exportData[$k][3]["Note1"]["debtorPoeInfo"])>13){
							$this->exportData[$k][3]["Note1"]["text"] .= " | ".$this->exportData[$k][3]["Note1"]["debtorPoeInfo"];
						}
						$this->exportData = $this->splitNotes($this->exportData[$k][3]["Note1"]['text'], $k, 'exportData');
					}
					if(count($this->noteData)>0){ 
						//we have accounts that have been excluded due to their age...put the in a separate notes object to be written to its own file
						foreach($this->noteData as $k=>$v){
							$this->noteData = $this->splitNotes($this->noteData[$k][3]["Note1"]['text'], $k, 'noteData');
						}
						
					}
				break;
				case "Y3401": //EPIC system for Olympic Medical Center
					$this->exportData = $this->processEpic();	//this is an EPIC filesystem client so we can use the shared function
				break;
				case "Y9658": //temporary for PHS WASHINGTON
					$this->exportData = $this->processEpic();	//this is an EPIC filesystem client so we can use the shared function
					$this->createRecallFile();
				break;
				case "Y9234":
					foreach($this->fileData as $k=>$v){
						$lineData = str_getcsv ($v);
						$isValid = false;
							$recordSuffix = $lineData[34]."/". $lineData[35]; //to test for validity
							$isValid = $this->checkY9234Valid($recordSuffix);
							if($isValid){ //we've hit a new record - process it
								$recordNumber = $recordSuffix;
									$this->exportData[$recordNumber] = array(); //set up the record
									$this->exportData = $this->setUpFacsArray($recordNumber); //this sets up the basic series of variables that we'll populate
									$this->exportData = $this->populateRecord_Y9234($k, $recordNumber, $lineData); //populate the raw data fields without any message
							} //end if
					}
					foreach($this->exportData as $k=>$v){ //put the address on two lines if too long
						$this->exportData = $this->splitAddress($k, $this->exportData);
					}
				break;
			} //end switch
			/////////////////////
			$this->create980FileNotes(); //we've processed the raw data - now write out the export file in FACS software format
			/////////////////////
			if($this->noteData){
				$this->create980FileNotes('note');
			}
		}elseif($this->conversionType == "Payment"){//end if
			switch($this->clientId){ //get the client id profile and react accordingly
				case "Y9650": //YVMH
					$this->processFacsPaymentFile();
				break;
				case "Y7501": //CNG
					$this->processFacsPaymentFile();
				break;	
			}
			
			/*$this->create980FileNotes(); //we've processed the raw data - now write out the export file in FACS software format
			/////////////////////
			if($this->noteData){
				$this->create980FileNotes('note');
			}*/
		}//end if
	}
/////////////////////////////////////////////////////////////////////////////
//reads Y9650 Payment File
/////////////////////////////////////////////////////////////////////////////
/*function readFacsPaymentFile($k, $record){
			$this->exportData[$k]['Fill']				=	"";
			$this->exportData[$k]['RecordTypeBD']		=	"BD";
			$this->exportData[$k]['DebtorNumber']		=	substr($record,     3,    7);
			$this->exportData[$k]['ClientNumber']		=	substr($record,    10,    6);
			$this->exportData[$k]['ClientDebtorNumber']	=	substr($record,	   16,	 20); #only grabbing first 13 characters of 24 available
			$this->exportData[$k]['DebtorName']					=	substr($record,    40,	 36);
			$this->exportData[$k]['PaymentDate']				=	substr($record,	   76, 	  6);
			$this->exportData[$k]['PaymentType']				=	substr($record,	   82, 	  3);
			$this->exportData[$k]['Balance']					=	substr($record,	   85,	  8);
			$this->exportData[$k]['AppliedPrincipal']			=	substr($record,	   93, 	  8);
			$this->exportData[$k]['AppliedInterest']			=	substr($record,	  101,	  6);
			$this->exportData[$k]['AppliedCC']					=	substr($record,	  113,	  6);
			$this->exportData[$k]['AppliedAttorney']			=	substr($record,	  119,	  6);
			$this->exportData[$k]['PaidAgency']					=	substr($record,	  125,	  8);
			$this->exportData[$k]['SignFieldPaidAgency']		=	substr($record,	  133,    1);
			$this->exportData[$k]['PaidClient']					=	substr($record,	  134,    8);
			$this->exportData[$k]['SignFieldPaidClient']		=	substr($record,	  142,	  1);
			$this->exportData[$k]['DueAgency']					=	substr($record,	  143,	  8);
			$this->exportData[$k]['SignFieldDueAgency']			=	substr($record,	  151,	  1);
			$this->exportData[$k]['DueClient']					=	substr($record,	  152,	  8);
			$this->exportData[$k]['SignFieldDueClient']			=	substr($record,	  160,	  1);
			$this->exportData[$k]['AppliedMisc1	']			=	substr($record,	  161,	  7);
			$this->exportData[$k]['AppliedPostJudgmentInt']		=	substr($record,	  168,    7);
			$this->exportData[$k]['AppliedList3']				=	substr($record,	  175,	  7);
			$this->exportData[$k]['AppliedList4']				=	substr($record,	  182,	  7);
			$this->exportData[$k]['ListDate']					=	substr($record,	  189,	  6);
			$this->exportData[$k]['ReferenceNumber']			=	substr($record,	  195,	  8);
			$this->exportData[$k]['DateOfService']				=	substr($record,	  203,	  6);
			$this->exportData[$k]['AppliedCreditBalance']		=	substr($record,	  209,	 10);
			$this->exportData[$k]['Unused']						=	substr($record,	  219,	 31);
			$this->exportData[$k]['ReceivableGroupID']		=	"";
			$this->exportData[$k]['BillingPeriodSequence']	=	"";
			$this->exportData[$k]['ResponsibleParty']		=	"";
			$this->exportData[$k]['ReferenceNumber']			=	$this->exportData[$k]['ClientDebtorNumber']; 
} */
/////////////////////////////////////////////////////////////////////////////
//reads Y9650 Payment File
/////////////////////////////////////////////////////////////////////////////
/*function checkfor_valid_Facs_payment_line($line){
		//echo $line;
		$valid = 0;
		$recordBeginning = substr($line, 0, 2);
		if($recordBeginning == 11){
			$valid = 1;
		}else { $valid = 0; }
		return $valid;
}*/
/////////////////////////////////////////////////////////////////////////////
/*function addDecimalForPaymentFile($keyNum, $numberToSplit, $fieldName){
	$firstPart = substr( $numberToSplit, 0, strlen($numberToSplit)-2 );
	//$firstPart = 1234;
	$secondPart = substr($numberToSplit, strlen($numberToSplit)-2, 2);
	//$secondPart = 567;
	$newNumber = $firstPart.".".$secondPart;
	$this->exportData[$keyNum][$fieldName] = $newNumber;
}*/
/////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
//sets up the data for the production of the payment file
/////////////////////////////////////////////////////////////////////////////
/*function massageFacsPaymentData(){
	$accountsSkipped = 0; //this is a flag for accounts that don't have a principal
	$accountsProcessed = 0; //this is a flag to track the records we have processed on the file
	$dp = 0;
	$dpTotal = 0; //flag to track total amount collected(?)
	$totalAccountsNotListed = 0; //number of accounts not listed
	$detailRecordNum = 0;
	$paidAgencyPrinc = 0; //this is the amount the collection agency has collected
	$totalPaidClient = 0;
	$totalPaidAgency = 0;
	$totalDueAgency = 0;
	$totalPayments = 0;
	$maxlines = count($this->exportData); 
	$date = date("Ymd");
	$paymentFileName = "/home/nobody/Y9650/GuarPmt_EFS_".$date.".txt";
	$exportDataReplace = ""; //this is a string we will use to build the text for the export file
	
	foreach($this->exportData as $k=>$v){
		$this->addDecimalForPaymentFile($k, $this->exportData[$k]['AppliedPrincipal'], 'AppliedPrincipal');
		if($this->exportData[$k]['DueAgency'] > 0){ $this->addDecimalForPaymentFile($k, $this->exportData[$k]['DueAgency'], 'DueAgency'); }	
		if($this->exportData[$k]['PaidAgency'] > 0){ $this->addDecimalForPaymentFile($k, $this->exportData[$k]['PaidAgency'], 'PaidAgency'); }	
		if($this->exportData[$k]['Balance'] > 0){ $this->addDecimalForPaymentFile($k, $this->exportData[$k]['Balance'], 'Balance'); }	

		$vals = explode("_", $this->exportData[$k]['ReferenceNumber']);
		$this->exportData[$k]['ReceivableGroupID']	=	$vals[0];
		$this->exportData[$k]['BillingPeriodSequence']	=	$vals[1];
		$this->exportData[$k]['ResponsibleParty']		=	trim($vals[2]);
		//$this->exportData[$k]['ClientDebtorNumber'] = substr($this->exportData[$k]['ClientDebtorNumber'],0,8);
		$this->exportData[$k]['ClientDebtorNumber'] = $this->exportData[$k]['ReceivableGroupID'];
	}
}*/
/////////////////////////////////////////////////////////////////////////////
/*function writeFacsPaymentFile(){
	$accountsSkipped = 0; //this is a flag for accounts that don't have a principal
	$accountsProcessed = 0; //this is a flag to track the records we have processed on the file
	$dp = 0;
	$dpTotal = 0; //flag to track total amount collected(?)
	$totalAccountsNotListed = 0; //number of accounts not listed
	$detailRecordNum = 0;
	$paidAgencyPrinc = 0; //this is the amount the collection agency has collected
	$totalPaidClient = 0;
	$totalPaidAgency = 0;
	$totalDueAgency = 0;
	$totalPayments = 0;
	$maxlines = count($this->exportData); 
	$date = date("Ymd");
	$paymentFileName = "/home/nobody/Y9650/GuarPmt_EFS_".$date.".txt";
	$exportDataReplace = ""; //this is a string we will use to build the text for the export file

	$index = 0;
	$num_skipped_records = 0;

	$paymentContent = "";
	foreach($this->exportData as $k=>$v){
			/////////////////////////////////////////////
			$skip_record =	"N";
			$dpFlag	= "N";
			$num_header_lines =	1;
			$num_trailer_lines = 1;
			if ($this->exportData[$k]['AppliedPrincipal'] <.01)	{	
				$skip_record = "Y"; $accountsSkipped++; 
			}else{
				$accountsProcessed++;
			}
	
			if ($this->exportData[$k]['PaymentType'] == "DPzzdf ") {
				$dpFlag	= "Y";
				$skip_record="Y";
				$dpTotal = $dpTotal + $this->exportData[$k]['AppliedPrincipal'];
				$dp++;
				$totalAccountsNotListed= $totalAccountsNotListed + $dpTotal;
				$detailRecordNum++;
			}else{							
				$paidAgencyPrinc = $paidAgencyPrinc + $this->exportData[$k]['$AppliedPrincipal'];
			}
	
			if ($this->exportData[$k]['SignFieldPaidAgency'] == "-") {
				$totalPaidAgency = $totalPaidAgency - $this->exportData[$k]['PaidAgency'];
			}
			else{
				$totalPaidAgency = $totalPaidAgency + $this->exportData[$k]['PaidAgency'];
			}
	
			if ($this->exportData[$k]['SignFieldPaidClient'] == "-") {
				$totalPaidClient = $totalPaidClient - $this->exportData[$k]['PaidClient'];
			}else{
				$totalPaidClient = $totalPaidClient + $this->exportData[$k]['PaidClient'];
			}
	
			if ($this->exportData[$k]['SignFieldDueAgency'] == "-")	{
				$totalDueAgency = $totalDueAgency - $this->exportData[$k]['DueAgency'];
			}else{
				$totalDueAgency = $totalDueAgency + $this->exportData[$k]['DueAgency'];
			}
	
			if ($this->exportData[$k]['SignFieldDueClient'] == "-")	{
				$totalDueClient = $totalDueClient - $this->exportData[$k]['DueClient'];
			}else{
				$totalDueClient = $totalDueClient + $this->exportData[$k]['DueClient'];
			}
			
			/////////////////////////////////////////////////////////////
			$fileDate = date("Ymd");
			$fileID	= $fileDate . ".1";
			$batchNumber = substr(time(), 0, 6);
			$batchSubDate =	$fileDate;
			$depositDate = $fileDate;
			
			if ( $skip_record == "Y" || $this->exportData[$k]['PaymentType'] == "DP") {
				$lastLine = $index + 1 + $num_skipped_records + $num_header_lines + $num_trailer_lines;
				if ($lastLine == $maxlines) { echo "Found the last account, writing trailer for massage_YVMH_payment\n\n"; #index starts with zero
					//open (RESULTFILE, ">>$filename") || file_creation_failed("from write_cng_payment_record $filename");	#OPENS ACCOUNT WITH AN APPEND
					##############################################################################
					########## Footer******* #####################################################
					##############################################################################
					//print RESULTFILE ("GPT|PMT|TRUE|TRUE|Evergreen Financial|||||5"); #this is the footer specified by YVMH
					$paymentContent .= "GPT|PMT|TRUE|TRUE|Evergreen Financial|||||5";
				}
			}elseif ($skip_record == "N") { 	
				$BD++;
				$totalPayments 		= 	$totalPayments + $this->exportData[$k]['AppliedPrincipal'];
				$accounts_processed	=	$index + $accountsNotListed+1; #index starts with zero
				$accountsNotListed 	= 	$accountsSkipped + $dp;
				$paymentContent .= "GPT|PMT|False|True|Evergreen Financial|";
				$paymentContent .= trim($this->exportData[$k]['ClientDebtorNumber'])."^".trim($this->exportData[$k]['BillingPeriodSequence'])."^".trim($this->exportData[$k]['ResponsibleParty']);
				$paymentContent .= "|11|";
				$paymentContent .= trim($this->exportData[$k]['ClientDebtorNumber']);
				$paymentContent .= "|";
				$paymentContent .= $fileDate;
					
					if($this->exportData[$k]['PaymentType'] == 'COR' || $this->exportData[$k]['PaymentType'] == 'NSF'){
						$paymentContent .= "|6|";
						$posNeg = "";
						$autoVal = "AutoEFSA";
					}else{
						$paymentContent .= "|5|";
						$posNeg = "-";
						$autoVal = "AutoEFS";
					}

				$paymentContent .= $autoVal;
				$paymentContent .= "|";
				$applied = ltrim($this->exportData[$k]['AppliedPrincipal'], '0');
				$due = ltrim($this->exportData[$k]['DueAgency'], '0'); 
				if(!$due || $due < .01) $due = "0";
				$paymentContent .= $posNeg.$applied."|";
				$paymentContent .= trim($this->exportData[$k]['PaymentType']);
				$paymentContent .= "|";
				$paymentContent .= $due.'#r#n';



				//$paymentContent .= " adding $totalBalance plus ".$this->exportData[$k]['Balance'];
				$totalBalance 		= 	$totalBalance + $this->exportData[$k]['Balance'];
				//$paymentContent .= " for ".$totalBalance." at ".$lastLine." | 
				//";
				
				
			}
			////////////////////////////////////////////////////////////
			$index++;
	}
	$paymentContent .= "GPT|PMT|TRUE|TRUE|Evergreen Financial|||||5";
	$this->convertedData = $paymentContent;
}*/
/////////////////////////////////////////////////////////////////////////////
// creates a file with recall data
/////////////////////////////////////////////////////////////////////////////
	function createRecallFile(){
		$export = $this->fileDest.$this->clientId.'_RECALLED_DATA.txt';
		$myf = fopen($export, "w") or die("Unable to open recall file!");
		$dataToPlace = "";//this is the data we'll put in the file we write
		foreach($this->recalledIds as $k=>$v){
			$raw = $v;
			$breaks = array("<br />","<br>","<br/>");
			$rawNewline = str_ireplace($breaks, "\n", $raw);
			//$rawNewline = $raw;
			$dataToPlace .= $rawNewline; //apend the data
		}
		$written = fwrite($myf, $dataToPlace);
		if($written){
			chmod($export, 0777);
			if($this->accessType != "api") echo "File written to $export<br /><br />";
		}
		fclose($myf);
	}
/////////////////////////////////////////////////////////////////////////////
// this is for a recall file to sort out the ids we'll be looking to pull out
// sometimes the file have a slash in them that we need to remove. it should split on the hyphen and add the number before the hyphen as the array key
// returns array
/////////////////////////////////////////////////////////////////////////////
	function processRecallIds($recallIds){
		$ids = array();
		foreach($recallIds as $k=>$v){
			$raw = explode("/", $v);
			if(is_numeric($raw[0])){
				$ids[$raw[0]]=$raw[1];
			}
		}
		return $ids; //return the list of raw ids
	}
/////////////////////////////////////////////////////////////////////////////
//added for facilities sharing the EPIC filesystem
//accepts nothing - takes fileData info from the object itself
//returns populated exportData
/////////////////////////////////////////////////////////////////////////////
	function processEpic(){
		$this->exportData = array();
		$counter=0;
			$sendToRecalls = 0; //this is a flag to determine if a given client id is in the recall list. if it is, we should put it in a different batch of files

			foreach($this->fileData as $k=>$v){
				$lineEx = explode("^", trim($v));
				$thisNumber 	= 	$lineEx[1]; //this is the account number sent from the hospital that we will append to in order to create the base account id
				$testDash		=	$lineEx[211]; //a simple flag testing for a dash to indicate that this is the first line of the account number and therefore where we'll find the debtor data

				$numberToTest = $thisNumber;

				if(array_key_exists($numberToTest, $this->recallIds) && !in_array($numberToTest,$this->recalledIds)){ //this is a recall id so we need to route this data to another file
					$sendToRecalls = 1; //flag this one to separate out to the recalls files
					$this->recalledIds[$numberToTest] = array();
					unset($this->recallIds[$numberToTest]); //remove the key from the pending recallId array so it doesn't get checked again
				}

				$isValid = $this->testRecordEpic($numberToTest, $lineEx[150], $this->clientId, $testDash);

					if($isValid == 1 && $sendToRecalls == 0){ //it's valid and we're not recalling it - process it to a regular FACS file
						if(!is_array($this->exportData[$numberToTest])){
							$this->exportData = $this->setUpFacsArray($numberToTest); //this sets up the basic series of variables that we'll populate
						}
						$this->exportData = $this->populateRecordEpic($lineEx, $numberToTest, $this->exportData, $this->fileData, $counter );
					}elseif($isValid == 1 && $sendToRecalls == 1){
						$this->recalledIds[$numberToTest] = $this->grabRecallInfo($numberToTest, $k); //this function goes through the data file and strips out the recalled client data, keeping their EPIC data format
					}
					$sendToRecalls = 0; //reset the flag
					$counter++;

			}
			return $this->exportData;
	}
/////////////////////////////////////////////////////////////////////////////
// this grabs the data chunk for a recalled account and returns the chunk to be set into the recalled array
/////////////////////////////////////////////////////////////////////////////
	function grabRecallInfo($numberToTest, $lineNumber){
		$stillChecking = 1; //this is the flag we'll use to keep looping through the file until we hit the ^1 flag to know we're at the next account
		$recallData = ""; //container for the data we'll record to send back to the object's recall object
		$lineToCheck = $lineNumber + 1;
		$recallData .= "<br />".$this->fileData[$lineNumber]."<br />"; //we have to grab the first line of data before we loop since we increment the line when we begin the loop
		while($stillChecking = 1){
			 $lineEx = explode("^", trim($this->fileData[$lineToCheck]));
			$thisNumber 	= 	$lineEx[0]; //this is the account number sent from the hospital that we will append to in order to create the base account id

			if($thisNumber != 01){
				$recallData .= substr($this->fileData[$lineToCheck], 0, strlen($this->fileData[$lineToCheck])-1); //add the subsequent data to the recall data
				$lineToCheck++;
			}else{
				$stillChecking = 0; //to stop the while loop
				return $recallData;
			}
		}

	}
/////////////////////////////////////////////////////////////////////////////
////this is a shared function that we'll use to see if this account has been listed under the same number..i.e. if we need to combine balance totals
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function checkDuplicateIncomingData($clientId, $lineEx){
		switch($clientId){
			case "Y9083":
				$recordNumber = $lineEx[0];
				if(!array_key_exists($recordNumber, $this->exportData) && $recordNumber != 'ACCOUNT #'){
					$this->exportData[$recordNumber] = array();
					$this->setUpFacsArray($recordNumber);
					$this->exportData = $this->populateRecord_Y9083($lineEx, $recordNumber);
					$this->exportData[$recordNumber] = $this->massageDataShared($this->exportData[$recordNumber]);
				}elseif($recordNumber != 'ACCOUNT #'){
					$balanceToAdd = str_replace('.', '', $lineEx[13]);
					$this->exportData[$recordNumber][01]['balance'] += $balanceToAdd;
					$dateOfService = $this->checkDateWithSlashes($lineEx[12]);
					$this->exportData[$recordNumber][01]['dateOfService'] = $dateOfService;
					$noteString = $this->exportData[$recordNumber][03]["Note1"].$this->exportData[$recordNumber][03]["Note2"].$this->exportData[$recordNumber][03]["Note3"].$this->exportData[$recordNumber][03]["Note4"];
					$noteString .= "Ep#:".trim($lineEx[14])." DOS:".str_replace("/", "", $lineEx[12])." ".$lineEx[13]."|";

					$this->exportData = $this->splitNotes($noteString, $recordNumber, 'exportData');
					$this->exportData[$recordNumber] = $this->massageDataShared($this->exportData[$recordNumber]);
				}
			break;
		}
		return $this->exportData;
	}
/////////////////////////////////////////////////////////////////////////////
////prep the upload data for formatting to FACS with a series of common data formatting quidelines
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function massageDataShared($v){
			$v[01]['debtorPhone'] = preg_replace("/[^0-9,.]/", "", $v[01]['debtorPhone']);
			$lastDigit = substr($v[01]['debtorPhone'], 9, 1);
			$v[01]['debtorPhone'] = substr($v[01]['debtorPhone'], 0, 10);
			$v[01]['debtorDob'] = preg_replace("/[^0-9,.]/", "", $v[01]['debtorDob']);
			$v[01]['debtorSs'] = preg_replace("/[^0-9,.]/", "", $v[01]['debtorSs']);
			$v[01]['dateLastPayment'] = preg_replace("/[^0-9,.]/", "", $v[01]['dateLastPayment']);
			$v[01]['listDate'] = preg_replace("/[^0-9,.]/", "", $v[01]['listDate']);
			$v[01]['dateOfService'] = preg_replace("/[^0-9,.]/", "", $v[01]['dateOfService']);
			$v[01]['rpSs'] = preg_replace("/[^0-9,.]/", "", $v[01]['rpSs']);
			$v[01]['balance'] = preg_replace("/[^0-9]/", "", $v[01]['balance']);

			if($v[01]['debtorPhone'] == $v[01]['rpPhone']){
				$v[01]['rpPhone'] = ""; //clear the RP number if they're the same
			}
			$addrOneLen = strlen($v[01]['debtorAddress1']);
			if($addrOneLen > 19){ //we have more than 19 characters in the first address line - cut at each word and put the remaining data on the second address line to avoid FACS import cutoff

				//////////////////////////////// this function cuts at the words
				$longString = $v[01]['debtorAddress1'];
				$arrayWords = explode(' ', $longString);

				// Max size of each line
				$maxLineLength = 19;
				// Auxiliar counters, foreach will use them
				$currentLength = 0;
				$index = 0;

				foreach ($arrayWords as $word) { //loop through the words we've cut and place as many as you can on the first line
					// +1 because the word will receive back the space in the end that it loses in explode()
					$wordLength = strlen($word) + 1;

					if (($currentLength + $wordLength) <= $maxLineLength) { //keep adding words if we're not there yet
						$arrayOutput[$index] .= $word. ' ';
						$currentLength += $wordLength;
					} else {
						$index += 1;
						$currentLength = $wordLength;
						$arrayOutput[$index] = $word;
					}
				}
				$v[01]['debtorAddress1'] = trim($arrayOutput[0]); //now place the cut words in the final data address fields
				$v[01]['debtorAddress2'] = trim($arrayOutput[1]);
				////////////////////////////////
			}

			$v[01]['rpPhone'] = preg_replace("/[^0-9,.]/", "", $v[01]['rpPhone']);
			$v[02]['debtorPoePhone'] = preg_replace("/[^0-9,.]/", "", $v[02]['debtorPoePhone']);

			//strip out any redundant guarantor data
				if(trim($v[01]['debtorName']) == trim($v[01]['rpName'])){
					$v[01]['rpName'] = "";
				}
				if($v[01]['debtorSs'] == $v[01]['rpSs']){
					$v[01]['rpSs'] = "";
				}
				if($v[01]['debtorPhone'] == $v[01]['rpPhone']){
					$v[01]['rpPhone'] = "";
				}

		return $v;
	}
/////////////////////////////////////////////////////////////////////////////
////finds and returns address data
////returns integer or null
/////////////////////////////////////////////////////////////////////////////
	function findAddressLineIndex($counter,$start,$end){
		$found = 0; //this is flag to tell if we found an address line or not
		for($i=0; $i<7; $i++){ //now reach down below the line that we're processing to see if where we hit the address data
			$current = $counter+$i;
			$stringToCheck = substr($this->fileData[$current], $start, $end);

			if(trim($stringToCheck) == "UNK" || trim($stringToCheck) == "UNLISTED"){ //this is an unknown address but we still want to start the loop here to get the rest
				return $current;
				break;
			}else{
				//$stringTrimmed = preg_replace("/[^0-9,.]/", "", $stringToCheck);
				$stringTrimmed = preg_replace("/[^0-9]/", "", $stringToCheck);

				if(is_numeric($stringTrimmed)){ //we've found the first line containing numbers - this should be the address line - record and return the index
					$found = 1;
					return $current;
					break;
				} //end if
			}
		} //end for
		if($found==0) return null; //return nothing
	}
/////////////////////////////////////////////////////////////////////////////
////this is the basic structure of the FACS format - this is what we will insert the client uploaded data to as we process it
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function setUpFacsArray($recordNumber){
		$line1 = array(
			'recordId'=>$recordNumber,
			'clientId'=>$this->clientId,
			'debtorNumber'=>$recordNumber,
			'debtorName'=>"",
			'debtorAddress1'=>"",
			'debtorAddress2'=>"",
			'debtorCity'=>"",
			'debtorState'=>"",
			'debtorZip'=>"",
			'debtorSs'=>"",
			'debtorPhone'=>"",
			'workPhoneFlag'=>"",
			'rpName'=>"",
			'rpSs'=>"",
			'rpPhone'=>"",
			'debtorDob'=>"",
			'dateOfService'=>"",
			'listDate'=>"",
			'balance'=>"",
			'interest'=>"",
			'dateLastPayment'=>"",
			'copiesOnFile'=>"",
			'intPercentAllow'=>"",
			'interestType'=>"",
			'percentIntKeep'=>"",
			'agencyPaysCc'=>"",
			'misc1'=>"",
			'agencyPaysAty'=>""
		);
		$line2 = array(
			'recordId'=>$recordNumber,
			'debtorPoe'=>"",
			'debtorPoeAddress'=>"",
			'debtorPoeCity'=>"",
			'debtorPoeState'=>"",
			'debtorPoeZip'=>"",
			'debtorPoePhone'=>"",
			'debtorSalary'=>"",
			'spouseName'=>"",
			'spouseAddress1'=>"",
			'spouseAddress2'=>"",
			'spouseCity'=>"",
			'spouseState'=>"",
			'spouseZip'=>"",
			'spouseDob'=>"",
			'spousePoe'=>"",
			'spousePoeAddress1'=>"",
			'spousePoeAddress2'=>"",
			'spousePoeCity'=>"",
			'spousePoeState'=>"",
			'spousePoeZip'=>"",
			'spouseSs'=>"",
			'spousePhone'=>"",
			'spouseDl'=>"",
			'delinquencyDate'=>""
		);
		$line3 = array(
			'recordId'=>$recordNumber,
			'Note1type'=>"",
			'Note2type'=>"",
			'Note3type'=>"",
			'Note4type'=>"",
			'Note1'=>"",
			'Note2'=>"",
			'Note3'=>"",
			'Note4'=>""
		);
		$line4 = array(
			'recordId'=>$recordNumber,
			'PL95flag'=>"",
			'BadAddrFlag'=>"",
			'CancelCode'=>"",
			'NumberCalls'=>"",
			'NumberContacts'=>"",
			'DateLastContact'=>"",
			'DateLastWorked'=>"",
			'NumberLettersSnt'=>"",
			'NumberPmnts'=>"",
			'AttyID'=>"",
			'AmtAtAtty'=>"",
			'DateFinalOrder'=>"",
			'DateFiled'=>"",
			'CauseNumber'=>"",
			'JudgmentDate'=>"",
			'JudgmentAmt'=>"",
			'ForwardAgencyID'=>"",
			'DateListedForward'=>"",
			'DateAssigmntRet'=>"",
			'CountySuit'=>"",
			'DateListedAtty'=>"",
			'AttyPercent'=>"",
			'ForwardPercent'=>"",
			'LegalType'=>"",
			'CreditRptFlag'=>"",
			'DateLastRptedCB'=>"",
			'RateTable'=>"",
			'DateCanceled'=>"",
			'ForwardInClient'=>"",
			'AssignmentSntDte'=>"",
			'AmountCanceled'=>"",
			'DateIntAssedThru'=>"",
			'AccumPostJudgInt'=>"",
			'InitFilingFees'=>"",
			'AccumPreJdgCosts'=>"",
			'AccumPostJdgCosts'=>"",
			'AmtPdAgnstJdg'=>"",
			'PrinWithAtty'=>"",
			'IntWithAtty'=>"",
			'AccumIntWithAtty'=>"",
			'AttyFeeAmtWithAtty'=>"",
			'Misc1WithAtty'=>"",
			'List3WithAtty'=>"",
			'List4WithAtty'=>"",
			'InitBalList3'=>"",
			'InitBalList4'=>""
		);
		$line5 = array(
			'recordId'=>$recordNumber,
			'BankName'=>"",
			'BankAddr'=>"",
			'BankCity'=>"",
			'BankState'=>"",
			'BankZip'=>"",
			'BankPhone'=>"",
			'BankAcctNum'=>"",
			'Property'=>"",
			'PropertyAddr'=>"",
			'PropertyCity'=>"",
			'PropertyState'=>"",
			'PropertyZip'=>"",
			'ChargeCard'=>"",
			'CardNumber'=>"",
			'CardExpiration'=>"",
			'DebtorLic'=>"",
			'BankNameCheck'=>"",
			'BankCheckAcctNum'=>"",
			'CheckNumber'=>"",
			'ReasonCheckRtrn'=>""
		);
		$line6 = array(
			'recordId'=>$recordNumber,
			'CoMakerName'=>"",
			'CoMakerAddr1'=>"",
			'CoMakerAddr2'=>"",
			'CoMakerCity'=>"",
			'CoMakerState'=>"",
			'CoMakerZip'=>"",
			'CoMakerPOE'=>"",
			'CoMakerPOEaddr'=>"",
			'CoMakerPOEcity'=>"",
			'CoMakerPOEstate'=>"",
			'CoMakerPOEzip'=>"",
			'CoMakerPOEphone'=>"",
			'CoMakerSSN'=>"",
			'CoMakerPhone'=>"",
			'CoMakerMiscInfo'=>"",
			'CoMakerDrvLic'=>"",
			'CoMakerRespPartyFl'=>""
		);

		$line11 = array(
		);

		$line90 = array(
			'recordId'=>$recordNumber,
			'winNum1'=>"",
			'fieldNum1'=>"",
			'fieldNum1Data'=>"",
			'fieldNum2'=>"",
			'fieldNum2Data'=>"",
			'fieldNum3'=>"",
			'fieldNum3Data'=>"",
			'fieldNum4'=>"",
			'fieldNum4Data'=>"",
			'fieldNum5'=>"",
			'fieldNum5Data'=>"",
			'fieldNum6'=>"",
			'fieldNum6Data'=>"",
			'fieldNum7'=>"",
			'fieldNum7Data'=>"",
			'fieldNum8'=>"",
			'fieldNum8Data'=>"",
			'fieldNum9'=>"",
			'fieldNum9Data'=>"",
			'fieldNum10'=>"",
			'fieldNum10Data'=>"",
			'fieldNum11'=>"",
			'fieldNum11Data'=>"",
			'fieldNum12'=>"",
			'fieldNum12Data'=>"",
			'fieldNum13'=>"",
			'fieldNum13Data'=>"",
			'fieldNum14'=>"",
			'fieldNum14Data'=>""
		);
		$line90b = array(
			'recordId'=>$recordNumber,
			'winNum1_b'=>"",
			'fieldNum1_b'=>"",
			'fieldNum1Data_b'=>"",
			'fieldNum2_b'=>"",
			'fieldNum2Data_b'=>"",
			'fieldNum3_b'=>"",
			'fieldNum3Data_b'=>"",
			'fieldNum4_b'=>"",
			'fieldNum4Data_b'=>"",
			'fieldNum5_b'=>"",
			'fieldNum5Data_b'=>"",
			'fieldNum6_b'=>"",
			'fieldNum6Data_b'=>"",
			'fieldNum7_b'=>"",
			'fieldNum7Data_b'=>"",
			'fieldNum8_b'=>"",
			'fieldNum8Data_b'=>"",
			'fieldNum9_b'=>"",
			'fieldNum9Data_b'=>"",
			'fieldNum10_b'=>"",
			'fieldNum10Data_b'=>"",
			'fieldNum11_b'=>"",
			'fieldNum11Data_b'=>"",
			'fieldNum12_b'=>"",
			'fieldNum12Data_b'=>"",
			'fieldNum13_b'=>"",
			'fieldNum13Data_b'=>"",
			'fieldNum14_b'=>"",
			'fieldNum14Data_b'=>""
		);
		/*$line55 = array(
			'recordId'=>$recordNumber,
			'delinquencyDate'=>""
		);*/
		$this->exportData[$recordNumber][01]=$line1;
		$this->exportData[$recordNumber][02]=$line2;
		$this->exportData[$recordNumber][03]=$line3;
		$this->exportData[$recordNumber][04]=$line4;
		$this->exportData[$recordNumber][05]=$line5;
		$this->exportData[$recordNumber][06]=$line6;
		$this->exportData[$recordNumber][11]=$line11;
		$this->exportData[$recordNumber][90]=$line90;
		$this->exportData[$recordNumber][91]=$line90b;
		//$this->exportData[$recordNumber][55]=$line55;
		return $this->exportData;
	}
////////////////////////////////////////////////////////////////////////////////////
/// writes out the FACS export file
/// accepts nothing or string to indicate whether the notes file is being processed since records were omitted due to DOS
/// returns nothing and writes out file
////////////////////////////////////////////////////////////////////////////////////
	function create980FileNotes($sourceString = 'export'){ //this actually writes out the FACS export file
		$dataTarget = $sourceString."Data";
		$source = $this->$dataTarget;
		$exportText = '';
		if($sourceString == 'note'){
			//echo "yeah";
			//print_rd($this->$dataTarget);
		}
		//line 1
		foreach($source as $k=>$v){
			if(strlen($source[$k][1]["debtorName"])>0){
					$exportText .= sprintf("%-2.2s", "01");
					$exportText .= sprintf("%-6.6s", $source[$k][1]["clientId"]);
					$exportText .= sprintf("%-24.24s", $source[$k][1]["debtorNumber"]);

					$exportText .= sprintf("%-3.3s", "");
					$exportText .= sprintf("%-36.36s", $source[$k][1]["debtorName"]);
					$exportText .= sprintf("%-20.20s", $source[$k][1]["debtorAddress1"]);

					$exportText .= sprintf("%-20.20s", $source[$k][1]["debtorAddress2"]);
					$exportText .= sprintf("%-20.20s", $source[$k][1]["debtorCity"]);
					$exportText .= sprintf("%-2.2s", $source[$k][1]["debtorState"]);

					$exportText .= sprintf("%-9.9s", $source[$k][1]["debtorZip"]);
					$exportText .= sprintf("%-9.9s", $source[$k][1]["debtorSs"]);
					$exportText .= sprintf("%-10.10s", $source[$k][1]["debtorPhone"]);

					$exportText .= sprintf("%-1.1s", " ");	//home phone flag
					$exportText .= sprintf("%-36.36s", $source[$k][1]["rpName"]);
					$exportText .= sprintf("%-9.9s", $source[$k][1]["rpSs"]);

					$exportText .= sprintf("%-10.10s", $source[$k][1]["rpPhone"]);
					$exportText .= sprintf("%-6.6s", $source[$k][1]["debtorDob"]);
					$exportText .= sprintf("%-6.6s", $source[$k][1]["dateOfService"]);

					$exportText .= sprintf("%-6.6s", $source[$k][1]["listDate"]);
					$exportText .= sprintf("%08.8s", $source[$k][1]["balance"]);
					$exportText .= sprintf("%08.8s", $source[$k][1]["interest"]);

					$exportText .= sprintf("%-8.8s", ""); //unused2
					$exportText .= sprintf("%-8.8s", ""); //unused3
					$exportText .= sprintf("%-6.6s", ""); //unused4

					$exportText .= sprintf("%-6.6s", ""); //unused5
					$exportText .= sprintf("%-6.6s", ""); //unused6
					$exportText .= sprintf("%-6.6s", ""); //unused7

					$exportText .= sprintf("%-6.6s", ""); //unused8
					$exportText .= sprintf("%-8.8s", ""); //unused9
					$exportText .= sprintf("%-3.3s", ""); //unused10

					$exportText .= sprintf("%-6.6s", $source[$k][1]["dateLastPayment"]);
					$exportText .= sprintf("%-1.1s", $source[$k][1]["copiesOnFile"]);
					$exportText .= sprintf("%05.5s", $source[$k][1]["intPercentAllow"]);

					$exportText .= sprintf("%-1.1s", $source[$k][1]["interestType"]);
					$exportText .= sprintf("%5.5s", $source[$k][1]["percentIntKeep"]);
					$exportText .= sprintf("%-1.1s", $source[$k][1]["agencyPaysCc"]);

					$exportText .= sprintf("%-8.8s", ""); //unused11
					$exportText .= sprintf("%7.7s", $source[$k][1]["misc1"]); //misc11
					$exportText .= sprintf("%07.7s", ""); //unused12
					$exportText .= sprintf("%-1.1s", $source[$k][1]["agencyPaysAty"]);

					//line 2
					$exportText .= sprintf("%-2.2s", "02");
					$exportText .= sprintf("%-20.20s", $source[$k][2]["debtorPoe"]);
					$exportText .= sprintf("%-20.20s", $source[$k][2]["debtorPoeAddress"]);
					$exportText .= sprintf("%-20.20s", $source[$k][2]["debtorPoeCity"]);
					$exportText .= sprintf("%-2.2s", $source[$k][2]["debtorPoeState"]);
					$exportText .= sprintf("%-9.9s", $source[$k][2]["debtorPoeZip"]);
					$exportText .= sprintf("%-10.10s", $source[$k][2]["debtorPoePhone"]);
					$exportText .= sprintf("%-10.10s", $source[$k][2]["debtorSalary"]);
					$exportText .= sprintf("%-36.36s", $source[$k][2]["spouseName"]);

					$exportText .= sprintf("%-20.20s", $source[$k][2]["spouseAddress1"]);
					$exportText .= sprintf("%-20.20s", $source[$k][2]["spouseAddress2"]);
					$exportText .= sprintf("%-20.20s", $source[$k][2]["spouseCity"]);

					$exportText .= sprintf("%-2.2s", $source[$k][2]["spouseState"]);
					$exportText .= sprintf("%-9.9s", $source[$k][2]["spouseZip"]);
					$exportText .= sprintf("%-6.6s", $source[$k][2]["spouseDob"]);

					$exportText .= sprintf("%-20.20s", $source[$k][2]["spousePoe"]);
					$exportText .= sprintf("%-20.20s", $source[$k][2]["spousePoeAddress1"]);
					$exportText .= sprintf("%-20.20s", $source[$k][2]["spousePoeCity"]);

					$exportText .= sprintf("%-2.2s", $source[$k][2]["spousePoeState"]);
					$exportText .= sprintf("%-10.10s", $source[$k][2]["spousePoePhone"]);
					$exportText .= sprintf("%-10.10s", $source[$k][2]["spouseSalary"]);

					$exportText .= sprintf("%-1.1s", $source[$k][2]["spouseRp"]);
					$exportText .= sprintf("%-9.9s", $source[$k][2]["spousePoeZip"]);
					$exportText .= sprintf("%-9.9s", $source[$k][2]["spouseSs"]);

					$exportText .= sprintf("%-10.10s", $source[$k][2]["spousePhone"]);
					$exportText .= sprintf("%-15.15s", $source[$k][2]["spouseDl"]);
					$exportText .= sprintf("%-10.10s", ""); //unused 13
					$exportText .= sprintf("%-8.8s", $source[$k][2]["delinquencyDate"]);

					//line 3
					$exportText .= sprintf("%-2.2s", "03");
					$exportText .= sprintf("%-1.1s", $source[$k][3]["Note1type"]);
					$exportText .= sprintf("%-1.1s", $source[$k][3]["Note2type"]);

					$exportText .= sprintf("%-1.1s", $source[$k][3]["Note3type"]);
					$exportText .= sprintf("%-1.1s", $source[$k][3]["Note4type"]);
					$exportText .= sprintf("%-59.59s", $source[$k][3]["Note1"]);
					$exportText .= sprintf("%-59.59s", $source[$k][3]["Note2"]);

					$exportText .= sprintf("%-59.59s", $source[$k][3]["Note3"]);
					$exportText .= sprintf("%-59.59s", $source[$k][3]["Note4"]);
					$exportText .= sprintf("%-108.108s", "");

					//line 4
					$exportText .= sprintf("%-2.2s", "04");
					$exportText .= sprintf("%1.1s", $source[$k][4]["PL95flag"]);
					$exportText .= sprintf("%1.1s", $source[$k][4]["BadAddrFlag"]);

					$exportText .= sprintf("%-3.3s", $source[$k][4]["CancelCode"]);
					$exportText .= sprintf("%3.3s", $source[$k][4]["NumberCalls"]);
					$exportText .= sprintf("%3.3s", $source[$k][4]["NumberContacts"]);

					$exportText .= sprintf("%6.6s", $source[$k][4]["DateLastContact"]);
					$exportText .= sprintf("%6.6s", $source[$k][4]["DateLastWorked"]);
					$exportText .= sprintf("%3.3s", $source[$k][4]["NumberLettersSnt"]);

					$exportText .= sprintf("%3.3s", $source[$k][4]["NumberPmnts"]);
					$exportText .= sprintf("%5.5s", $source[$k][4]["AttyID"]);
					$exportText .= sprintf("%8.8s", $source[$k][4]["AmtAtAtty"]);

					$exportText .= sprintf("%6.6s", $source[$k][4]["DateFinalOrder"]);
					$exportText .= sprintf("%6.6s", $source[$k][4]["DateFiled"]);
					$exportText .= sprintf("%-16.16s", $source[$k][4]["CauseNumber"]);

					$exportText .= sprintf("%6.6s", $source[$k][4]["JudgmentDate"]);
					$exportText .= sprintf("%8.8s", $source[$k][4]["JudgmentAmt"]);
					$exportText .= sprintf("%6.6s", $source[$k][4]["ForwardAgencyID"]);

					$exportText .= sprintf("%6.6s", $source[$k][4]["DateListedForward"]);
					$exportText .= sprintf("%6.6s", $source[$k][4]["DateAssigmntRet"]);
					$exportText .= sprintf("%-16.6s", $source[$k][4]["CountySuit"]);

					$exportText .= sprintf("%6.6s", $source[$k][4]["DateListedAtty"]);
					$exportText .= sprintf("%5.5s", $source[$k][4]["AttyPercent"]);
					$exportText .= sprintf("%5.5s", $source[$k][4]["ForwardPercent"]);

					$exportText .= sprintf("%1.1s", $source[$k][4]["LegalType"]);
					$exportText .= sprintf("%1.1s", $source[$k][4]["CreditRptFlag"]);
					$exportText .= sprintf("%6.6s", $source[$k][4]["DateLastRptedCB"]);

					$exportText .= sprintf("%4.4s", "0");
					$exportText .= sprintf("%6.6s", $source[$k][4]["DateCanceled"]);
					$exportText .= sprintf("%-35.35s", $source[$k][4]["ForwardInClient"]);

					$exportText .= sprintf("%6.6s", $source[$k][4]["AssignmentSntDte"]);
					$exportText .= sprintf("%8.8s", $source[$k][4]["AmountCanceled"]);
					$exportText .= sprintf("%6.6s", $source[$k][4]["DateIntAssedThru"]);

					$exportText .= sprintf("%7.7s", $source[$k][4]["AccumPostJudgInt"]);
					$exportText .= sprintf("%7.7s", $source[$k][4]["InitFilingFees"]);
					$exportText .= sprintf("%7.7s", $source[$k][4]["AccumPreJdgCosts"]);

					$exportText .= sprintf("%7.7s", $source[$k][4]["AccumPostJdgCosts"]);
					$exportText .= sprintf("%7.7s", $source[$k][4]["AmtPdAgnstJdg"]);
					$exportText .= sprintf("%7.7s", $source[$k][4]["PrinWithAtty"]);

					$exportText .= sprintf("%7.7s", $source[$k][4]["IntWithAtty"]);
					$exportText .= sprintf("%7.7s", $source[$k][4]["AccumIntWithAtty"]);
					$exportText .= sprintf("%7.7s", $source[$k][4]["AttyFeeAmtWithAtty"]);

					$exportText .= sprintf("%7.7s", $source[$k][4]["Misc1WithAtty"]);
					$exportText .= sprintf("%8.8s", $source[$k][4]["List3WithAtty"]);
					$exportText .= sprintf("%8.8s", $source[$k][4]["List4WithAtty"]);

					$exportText .= sprintf("%8.8s", $source[$k][4]["InitBalList3"]);
					$exportText .= sprintf("%8.8s", "");
					$exportText .= sprintf("%8.8s", $source[$k][4]["InitBalList4"]);
					$exportText .= sprintf("%8.8s", "");
					$exportText .= sprintf("%23.23s", "");

					//line 5
					$exportText .= sprintf("%-2.2s", "05");
					$exportText .= sprintf("%-40.40s", $source[$k][5]["BankName"]);
					$exportText .= sprintf("%-25.25s", $source[$k][5]["BankAddr"]);

					$exportText .= sprintf("%-20.20s", $source[$k][5]["BankCity"]);
					$exportText .= sprintf("%-2.2s", $source[$k][5]["BankState"]);
					$exportText .= sprintf("%-9.9s", $source[$k][5]["BankZip"]);

					$exportText .= sprintf("%-10.10s", $source[$k][5]["BankPhone"]);
					$exportText .= sprintf("%-15.15s", $source[$k][5]["BankAcctNum"]);
					$exportText .= sprintf("%-25.25s", $source[$k][5]["Property"]);

					$exportText .= sprintf("%-25.25s", $source[$k][5]["PropertyAddr"]);
					$exportText .= sprintf("%-20.20s", $source[$k][5]["PropertyCity"]);
					$exportText .= sprintf("%-2.2s", $source[$k][5]["PropertyState"]);

					$exportText .= sprintf("%-9.9s", $source[$k][5]["PropertyZip"]);
					$exportText .= sprintf("%-20.20s", $source[$k][5]["ChargeCard"]);
					$exportText .= sprintf("%-20.20s", $source[$k][5]["CardNumber"]);

					$exportText .= sprintf("%-6.6s", $source[$k][5]["CardExpiration"]);
					$exportText .= sprintf("%-15.15s", $source[$k][5]["DebtorLic"]);
					$exportText .= sprintf("%-15.15s", $source[$k][5]["BankNameCheck"]);

					$exportText .= sprintf("%-15.15s", $source[$k][5]["BankCheckAcctNum"]);
					$exportText .= sprintf("%-8.8s", $source[$k][5]["CheckNumber"]);
					$exportText .= sprintf("%-20.20s", $source[$k][5]["ReasonCheckRtrn"]);
					$exportText .= sprintf("%-27.27s", "");

					//line 6
					$exportText .= sprintf("%-2.2s", "06");
					$exportText .= sprintf("%-36.36s", $source[$k][6]["coMakerName"]);
					$exportText .= sprintf("%-20.20s", $source[$k][6]["coMakerAddr1"]);

					$exportText .= sprintf("%-20.20s", $source[$k][6]["coMakerAddr2"]);
					$exportText .= sprintf("%-20.20s", $source[$k][6]["coMakerCity"]);
					$exportText .= sprintf("%-2.2s", $source[$k][6]["coMakerState"]);

					$exportText .= sprintf("%-9.9s", $source[$k][6]["coMakerZip"]);
					$exportText .= sprintf("%-20.20s", $source[$k][6]["coMakerPoe"]);
					$exportText .= sprintf("%-20.20s", $source[$k][6]["coMakerPoeAddr"]);

					$exportText .= sprintf("%-20.20s", $source[$k][6]["coMakerPoeCity"]);
					$exportText .= sprintf("%-2.2s", $source[$k][6]["coMakerPoeState"]);
					$exportText .= sprintf("%-9.9s", $source[$k][6]["coMakerPoeZip"]);

					$exportText .= sprintf("%-10.10s", $source[$k][6]["coMakerPoePhone"]);
					$exportText .= sprintf("%-9.9s", $source[$k][6]["coMakerSsn"]);
					$exportText .= sprintf("%-10.10s", $source[$k][6]["coMakerPhone"]);

					$exportText .= sprintf("%-12.12s", $source[$k][6]["coMakerName"]);
					$exportText .= sprintf("%-15.15s", $source[$k][6]["coMakerDrvLic"]);
					$exportText .= sprintf("%-1.1s", $source[$k][6]["coMakerRespPartyFl"]);
					$exportText .= sprintf("%-113.113s", $source[$k][6][""]);

					//line11
					if(count($source[$k][11])>0){ //line 11 is an array that can have multiple instances so we test to see if it's empty or not
						foreach($source[$k][11] as $k1=>$v1){
							$exportText .= sprintf("%-2.2s", "11");
							$exportText .= sprintf("%-16.16s", $v1['Name']);
							$exportText .= sprintf("%-3.3s", $v1['Relation']);
							$exportText .= sprintf("%-33.33s", $v1['Address']);
							$exportText .= sprintf("%-13.13s", $v1['Phone']);
							$exportText .= sprintf("%-1.1s", $v1['PhoneFlag']);
							$exportText .= sprintf("%-1.1s", $v1['LengthOfResidence']);
							$exportText .= sprintf("%-281.281s", $v1['Unused']);
						}
					}

					//line90
					$exportText .= sprintf("%-2.2s", "90");
					$exportText .= sprintf("%3.3s", $source[$k][90]["winNum1"] );
					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum1"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum1Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum2"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum2Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum3"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum3Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum4"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum4Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum5"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum5Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum6"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum6Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum7"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum7Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum8"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum8Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum9"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum9Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum10"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum10Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum11"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum11Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum12"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum12Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum13"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum13Data"] );

					$exportText .= sprintf("%2.2s", $source[$k][90]["fieldNum14"] );
					$exportText .= sprintf("%-22.22s", $source[$k][90]["fieldNum14Data"] );

					$exportText .= sprintf("%9.9s", "" );

					//line90b
					$exportText .= sprintf("%-2.2s", "91");
					$exportText .= sprintf("%3.3s", $source[$k][91]["winNum1_b"] );

					$exportText .= sprintf("%2.2s", $source[$k][91]["fieldNum1_b"] );
					$exportText .= sprintf("%-61.61s", $source[$k][91]["fieldNum1Data_b"] );

					$exportText .= sprintf("%2.2s", $source[$k][91]["fieldNum2_b"] );
					$exportText .= sprintf("%-61.61s", $source[$k][91]["fieldNum2Data_b"] );

					$exportText .= sprintf("%2.2s", $source[$k][91]["fieldNum3_b"] );
					$exportText .= sprintf("%-61.61s", $source[$k][91]["fieldNum3Data_b"] );

					$exportText .= sprintf("%2.2s", $source[$k][91]["fieldNum4_b"] );
					$exportText .= sprintf("%-61.61s", $source[$k][91]["fieldNum4Data_b"] );

					$exportText .= sprintf("%2.2s", $source[$k][91]["fieldNum5_b"] );
					$exportText .= sprintf("%-61.61s", $source[$k][91]["fieldNum5Data_b"] );

					$exportText .= sprintf("%30.30s", "" );
			}
		}
		$exportText = strtoupper($exportText);
		switch($sourceString){
		case 'export':
			$export = $this->fileDest.$this->fileName;
			$this->convertedData['accountData'] = $exportText;
		break;
		case 'note':
			$export = $this->fileDest.$this->noteFileName;
			$this->convertedData['noteData'] = $exportText;
		break;
		}
		
		/*
		//now write the converted file
		if($sourceString == 'export'){
			$export = $this->fileDest.$this->fileName;
		}elseif($sourceString == 'note'){
			$export = $this->fileDest.$this->noteFileName;
		}*/

		$myf = fopen($export, "w") or die("Unable to open notes file!");
		$written = fwrite($myf, $exportText);
		if($written){
			chmod($export, 0777);
			if($this->accessType != "api") echo "<b>".ucfirst($sourceString)."</b> file written to $export<br />";
		}
		fclose($myf);
	}
////////////////////////////////////////////////////////////////////////////////////
//// writes out the FACS data to an export file
//// creates export file
////////////////////////////////////////////////////////////////////////////////////
	function create980File(){ //this actually writes out the FACS export file
		$exportText = '';

		//line 1
		foreach($this->exportData as $k=>$v){

			if(strlen($this->exportData[$k][1]["debtorName"])>0){
					$exportText .= sprintf("%-2.2s", "01");
					$exportText .= sprintf("%-6.6s", $this->exportData[$k][1]["clientId"]);
					$exportText .= sprintf("%-24.24s", $this->exportData[$k][1]["debtorNumber"]);

					$exportText .= sprintf("%-3.3s", "");
					$exportText .= sprintf("%-36.36s", $this->exportData[$k][1]["debtorName"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][1]["debtorAddress1"]);

					$exportText .= sprintf("%-20.20s", $this->exportData[$k][1]["debtorAddress2"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][1]["debtorCity"]);
					$exportText .= sprintf("%-2.2s", $this->exportData[$k][1]["debtorState"]);

					$exportText .= sprintf("%-9.9s", $this->exportData[$k][1]["debtorZip"]);
					$exportText .= sprintf("%-9.9s", $this->exportData[$k][1]["debtorSs"]);
					$exportText .= sprintf("%-10.10s", $this->exportData[$k][1]["debtorPhone"]);

					$exportText .= sprintf("%-1.1s", $this->exportData[$k][1]["workPhoneFlag"]);	//home phone flag
					$exportText .= sprintf("%-36.36s", $this->exportData[$k][1]["rpName"]);
					$exportText .= sprintf("%-9.9s", $this->exportData[$k][1]["rpSs"]);

					$exportText .= sprintf("%-10.10s", $this->exportData[$k][1]["rpPhone"]);
					$exportText .= sprintf("%-6.6s", $this->exportData[$k][1]["debtorDob"]);
					$exportText .= sprintf("%-6.6s", $this->exportData[$k][1]["dateOfService"]);

					$exportText .= sprintf("%-6.6s", $this->exportData[$k][1]["listDate"]);
					$exportText .= sprintf("%08.8s", $this->exportData[$k][1]["balance"]);
					$exportText .= sprintf("%08.8s", $this->exportData[$k][1]["interest"]);

					$exportText .= sprintf("%-8.8s", ""); //unused2
					$exportText .= sprintf("%-8.8s", ""); //unused3
					$exportText .= sprintf("%-6.6s", ""); //unused4

					$exportText .= sprintf("%-6.6s", ""); //unused5
					$exportText .= sprintf("%-6.6s", ""); //unused6
					$exportText .= sprintf("%-6.6s", ""); //unused7

					$exportText .= sprintf("%-6.6s", ""); //unused8
					$exportText .= sprintf("%-8.8s", ""); //unused9
					$exportText .= sprintf("%-3.3s", ""); //unused10

					$exportText .= sprintf("%-6.6s", $this->exportData[$k][1]["dateLastPayment"]);
					$exportText .= sprintf("%-1.1s", $this->exportData[$k][1]["copiesOnFile"]);
					$exportText .= sprintf("%05.5s", $this->exportData[$k][1]["intPercentAllow"]);

					$exportText .= sprintf("%-1.1s", $this->exportData[$k][1]["interestType"]);
					$exportText .= sprintf("%5.5s", $this->exportData[$k][1]["percentIntKeep"]);
					$exportText .= sprintf("%-1.1s", $this->exportData[$k][1]["agencyPaysCc"]);

					$exportText .= sprintf("%-8.8s", ""); //unused11
					$exportText .= sprintf("%7.7s", $this->exportData[$k][1]["misc1"]); //misc11
					$exportText .= sprintf("%07.7s", ""); //unused12
					$exportText .= sprintf("%-1.1s", $this->exportData[$k][1]["agencyPaysAty"]);

					//line 2
					$exportText .= sprintf("%-2.2s", "02");
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][2]["debtorPoe"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][2]["debtorPoeAddress"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][2]["debtorPoeCity"]);
					$exportText .= sprintf("%-2.2s", $this->exportData[$k][2]["debtorPoeState"]);
					$exportText .= sprintf("%-9.9s", $this->exportData[$k][2]["debtorPoeZip"]);

					$exportText .= sprintf("%-10.10s", $this->exportData[$k][2]["debtorPoePhone"]);
					$exportText .= sprintf("%-10.10s", $this->exportData[$k][2]["debtorSalary"]);
					$exportText .= sprintf("%-36.36s", $this->exportData[$k][2]["spouseName"]);

					$exportText .= sprintf("%-20.20s", $this->exportData[$k][2]["spouseAddress1"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][2]["spouseAddress2"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][2]["spouseCity"]);

					$exportText .= sprintf("%-2.2s", $this->exportData[$k][2]["spouseState"]);
					$exportText .= sprintf("%-9.9s", $this->exportData[$k][2]["spouseZip"]);
					$exportText .= sprintf("%-6.6s", $this->exportData[$k][2]["spouseDob"]);

					$exportText .= sprintf("%-20.20s", $this->exportData[$k][2]["spousePoe"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][2]["spousePoeAddr"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][2]["spousePoeCity"]);

					$exportText .= sprintf("%-2.2s", $this->exportData[$k][2]["spousePoeState"]);
					$exportText .= sprintf("%-10.10s", $this->exportData[$k][2]["spousePoePhone"]);
					$exportText .= sprintf("%-10.10s", $this->exportData[$k][2]["spouseSalary"]);

					$exportText .= sprintf("%-1.1s", $this->exportData[$k][2]["spouseRp"]);
					$exportText .= sprintf("%-9.9s", $this->exportData[$k][2]["spousePoeZip"]);
					$exportText .= sprintf("%-9.9s", $this->exportData[$k][2]["spouseSs"]);

					$exportText .= sprintf("%-10.10s", $this->exportData[$k][2]["spousePhone"]);
					$exportText .= sprintf("%-15.15s", $this->exportData[$k][2]["spouseDl"]);
					$exportText .= sprintf("%-10.10s", ""); //unused 13
					$exportText .= sprintf("%-8.8s", $this->exportData[$k][2]["delinquencyDate"]);

					//line 3
					$exportText .= sprintf("%-2.2s", "03");
					$exportText .= sprintf("%-1.1s", $this->exportData[$k][3]["Note1type"]);
					$exportText .= sprintf("%-1.1s", $this->exportData[$k][3]["Note2type"]);

					$exportText .= sprintf("%-1.1s", $this->exportData[$k][3]["Note3type"]);
					$exportText .= sprintf("%-1.1s", $this->exportData[$k][3]["Note4type"]);
					$exportText .= sprintf("%-59.59s", $this->exportData[$k][3]["Note1"]);
					$exportText .= sprintf("%-59.59s", $this->exportData[$k][3]["Note2"]);

					$exportText .= sprintf("%-59.59s", $this->exportData[$k][3]["Note3"]);
					$exportText .= sprintf("%-59.59s", $this->exportData[$k][3]["Note4"]);
					$exportText .= sprintf("%-108.108s", "");

					//line 4
					$exportText .= sprintf("%-2.2s", "04");
					$exportText .= sprintf("%1.1s", $this->exportData[$k][4]["PL95flag"]);
					$exportText .= sprintf("%1.1s", $this->exportData[$k][4]["BadAddrFlag"]);

					$exportText .= sprintf("%-3.3s", $this->exportData[$k][4]["CancelCode"]);
					$exportText .= sprintf("%3.3s", $this->exportData[$k][4]["NumberCalls"]);
					$exportText .= sprintf("%3.3s", $this->exportData[$k][4]["NumberContacts"]);

					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateLastContact"]);
					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateLastWorked"]);
					$exportText .= sprintf("%3.3s", $this->exportData[$k][4]["NumberLettersSnt"]);

					$exportText .= sprintf("%3.3s", $this->exportData[$k][4]["NumberPmnts"]);
					$exportText .= sprintf("%5.5s", $this->exportData[$k][4]["AttyID"]);
					$exportText .= sprintf("%8.8s", $this->exportData[$k][4]["AmtAtAtty"]);

					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateFinalOrder"]);
					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateFiled"]);
					$exportText .= sprintf("%-16.16s", $this->exportData[$k][4]["CauseNumber"]);

					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["JudgmentDate"]);
					$exportText .= sprintf("%8.8s", $this->exportData[$k][4]["JudgmentAmt"]);
					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["ForwardAgencyID"]);

					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateListedForward"]);
					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateAssigmntRet"]);
					$exportText .= sprintf("%-16.6s", $this->exportData[$k][4]["CountySuit"]);

					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateListedAtty"]);
					$exportText .= sprintf("%5.5s", $this->exportData[$k][4]["AttyPercent"]);
					$exportText .= sprintf("%5.5s", $this->exportData[$k][4]["ForwardPercent"]);

					$exportText .= sprintf("%1.1s", $this->exportData[$k][4]["LegalType"]);
					$exportText .= sprintf("%1.1s", $this->exportData[$k][4]["CreditRptFlag"]);
					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateLastRptedCB"]);

					$exportText .= sprintf("%4.4s", "0");
					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateCanceled"]);
					$exportText .= sprintf("%-35.35s", $this->exportData[$k][4]["ForwardInClient"]);

					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["AssignmentSntDte"]);
					$exportText .= sprintf("%8.8s", $this->exportData[$k][4]["AmountCanceled"]);
					$exportText .= sprintf("%6.6s", $this->exportData[$k][4]["DateIntAssedThru"]);

					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["AccumPostJudgInt"]);
					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["InitFilingFees"]);
					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["AccumPreJdgCosts"]);

					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["AccumPostJdgCosts"]);
					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["AmtPdAgnstJdg"]);
					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["PrinWithAtty"]);

					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["IntWithAtty"]);
					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["AccumIntWithAtty"]);
					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["AttyFeeAmtWithAtty"]);

					$exportText .= sprintf("%7.7s", $this->exportData[$k][4]["Misc1WithAtty"]);
					$exportText .= sprintf("%8.8s", $this->exportData[$k][4]["List3WithAtty"]);
					$exportText .= sprintf("%8.8s", $this->exportData[$k][4]["List4WithAtty"]);

					$exportText .= sprintf("%8.8s", $this->exportData[$k][4]["InitBalList3"]);
					$exportText .= sprintf("%8.8s", "");
					$exportText .= sprintf("%8.8s", $this->exportData[$k][4]["InitBalList4"]);
					$exportText .= sprintf("%8.8s", "");
					$exportText .= sprintf("%23.23s", "");

					//line 5
					$exportText .= sprintf("%-2.2s", "05");
					$exportText .= sprintf("%-40.40s", $this->exportData[$k][5]["BankName"]);
					$exportText .= sprintf("%-25.25s", $this->exportData[$k][5]["BankAddr"]);

					$exportText .= sprintf("%-20.20s", $this->exportData[$k][5]["BankCity"]);
					$exportText .= sprintf("%-2.2s", $this->exportData[$k][5]["BankState"]);
					$exportText .= sprintf("%-9.9s", $this->exportData[$k][5]["BankZip"]);

					$exportText .= sprintf("%-10.10s", $this->exportData[$k][5]["BankPhone"]);
					$exportText .= sprintf("%-15.15s", $this->exportData[$k][5]["BankAcctNum"]);
					$exportText .= sprintf("%-25.25s", $this->exportData[$k][5]["Property"]);

					$exportText .= sprintf("%-25.25s", $this->exportData[$k][5]["PropertyAddr"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][5]["PropertyCity"]);
					$exportText .= sprintf("%-2.2s", $this->exportData[$k][5]["PropertyState"]);

					$exportText .= sprintf("%-9.9s", $this->exportData[$k][5]["PropertyZip"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][5]["ChargeCard"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][5]["CardNumber"]);

					$exportText .= sprintf("%-6.6s", $this->exportData[$k][5]["CardExpiration"]);
					$exportText .= sprintf("%-15.15s", $this->exportData[$k][5]["DebtorLic"]);
					$exportText .= sprintf("%-15.15s", $this->exportData[$k][5]["BankNameCheck"]);

					$exportText .= sprintf("%-15.15s", $this->exportData[$k][5]["BankCheckAcctNum"]);
					$exportText .= sprintf("%-8.8s", $this->exportData[$k][5]["CheckNumber"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][5]["ReasonCheckRtrn"]);
					$exportText .= sprintf("%-27.27s", "");

					//line 6
					$exportText .= sprintf("%-2.2s", "06");
					$exportText .= sprintf("%-36.36s", $this->exportData[$k][6]["coMakerName"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][6]["coMakerAddr1"]);

					$exportText .= sprintf("%-20.20s", $this->exportData[$k][6]["coMakerAddr2"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][6]["coMakerCity"]);
					$exportText .= sprintf("%-2.2s", $this->exportData[$k][6]["coMakerState"]);

					$exportText .= sprintf("%-9.9s", $this->exportData[$k][6]["coMakerZip"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][6]["coMakerPoe"]);
					$exportText .= sprintf("%-20.20s", $this->exportData[$k][6]["coMakerPoeAddr"]);

					$exportText .= sprintf("%-20.20s", $this->exportData[$k][6]["coMakerPoeCity"]);
					$exportText .= sprintf("%-2.2s", $this->exportData[$k][6]["coMakerPoeState"]);
					$exportText .= sprintf("%-9.9s", $this->exportData[$k][6]["coMakerPoeZip"]);

					$exportText .= sprintf("%-10.10s", $this->exportData[$k][6]["coMakerPoePhone"]);
					$exportText .= sprintf("%-9.9s", $this->exportData[$k][6]["coMakerSsn"]);
					$exportText .= sprintf("%-10.10s", $this->exportData[$k][6]["coMakerPhone"]);

					$exportText .= sprintf("%-12.12s", $this->exportData[$k][6]["coMakerName"]);
					$exportText .= sprintf("%-15.15s", $this->exportData[$k][6]["coMakerDrvLic"]);
					$exportText .= sprintf("%-1.1s", $this->exportData[$k][6]["coMakerRespPartyFl"]);
					$exportText .= sprintf("%-113.113s", $this->exportData[$k][6][""]);

					//line11
					if(count($this->exportData[$k][11])>0){ //line 11 is an array that can have multiple instances so we test to see if it's empty or not
						foreach($this->exportData[$k][11] as $k1=>$v1){
							//print_r($v);
							$exportText .= sprintf("%-2.2s", "11");
							$exportText .= sprintf("%-16.16s", $v1['Name']);
							$exportText .= sprintf("%-3.3s", $v1['Relation']);
							$exportText .= sprintf("%-33.33s", $v1['Address']);
							$exportText .= sprintf("%-13.13s", $v1['Phone']);
							$exportText .= sprintf("%-1.1s", $v1['PhoneFlag']);
							$exportText .= sprintf("%-1.1s", $v1['LengthOfResidence']);
							$exportText .= sprintf("%-281.281s", $v1['Unused']);
						}
					}

					//line90
					$exportText .= sprintf("%-2.2s", "90");
					$exportText .= sprintf("%3.3s", $this->exportData[$k][90]["winNum1"] );
					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum1"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum1Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum2"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum2Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum3"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum3Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum4"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum4Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum5"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum5Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum6"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum6Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum7"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum7Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum8"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum8Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum9"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum9Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum10"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum10Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum11"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum11Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum12"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum12Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum13"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum13Data"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][90]["fieldNum14"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][90]["fieldNum14Data"] );

					$exportText .= sprintf("%9.9s", "" );

					//line90b
					$exportText .= sprintf("%-2.2s", "91");
					$exportText .= sprintf("%3.3s", $this->exportData[$k][91]["winNum1_b"] );

					/*$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum1_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum1Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum2_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum2Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum3_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum3Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum4_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum4Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum5_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum5Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum6_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum6Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum7_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum7Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum8_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum8Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum9_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum9Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum10_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum10Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum11_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum11Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum12_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum12Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum13_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum13Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum14_b"] );
					$exportText .= sprintf("%-22.22s", $this->exportData[$k][91]["fieldNum14Data_b"] );
					
					$exportText .= sprintf("%9.9s", "" );
					*/
					
					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum1_b"] );
					echo $this->exportData[$k][91]["fieldNum1Data_b"]."<br />";
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum1Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum2_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum2Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum3_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum3Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum4_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum4Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum5_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum5Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum6_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum6Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum7_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum7Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum8_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum8Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum9_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum9Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum10_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum10Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum11_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum11Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum12_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum12Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum13_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum13Data_b"] );

					$exportText .= sprintf("%2.2s", $this->exportData[$k][91]["fieldNum14_b"] );
					$exportText .= sprintf("%-61.61s", $this->exportData[$k][91]["fieldNum14Data_b"] );

					$exportText .= sprintf("%30.30s", "" );
			}
		}

		//now write the converted file
		$export = $this->fileDest.$this->fileName;
		$myf = fopen($export, "w") or die("Unable to open export file!");
		$written = fwrite($myf, $exportText);
		if($written){
			chmod($export, 0777);
			echo "File written to $export<br /><br />";
		}
		fclose($myf);
	}
	///////////////////////////////////////
} //end class
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
?>
