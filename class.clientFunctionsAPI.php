<?php
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
class clientFunctions{
/////////////////////////////////////////////////////////////////////////////
// reads and processes FACS remittance file
/////////////////////////////////////////////////////////////////////////////
function processFacsPaymentFile(){
	
	$counter = 0;
	$strCount = 0;
	$paymentData = [];
	$paymentDataLen = strlen($this->fileData[0]);

	for ($counter = 0; $strCount < $paymentDataLen; $counter++) {
		$paymentData[$counter] = substr($this->fileData[0], $strCount, 250);
		$strCount += 250;
	}

	foreach($paymentData as $k=>$v){
		$valid = $this->checkfor_valid_Facs_payment_line($v);
		if($valid == 1){
			$this->readFacsPaymentFile($k, $v);
		}
	}
	$this->massageFacsPaymentDataAPI();
	$this->writeFacsPaymentFileAPI();
		
}
/////////////////////////////////////////////////////////////////////////////
//checks payment file for validity to process
/////////////////////////////////////////////////////////////////////////////
function checkfor_valid_Facs_payment_line($line){
	$valid = 0;
	$recordBeginning = substr($line, 0, 2);
	if($recordBeginning == 11){
		$valid = 1;
	}else { $valid = 0; }
	return $valid;
}
/////////////////////////////////////////////////////////////////////////////
//reads Y9650 Payment File
/////////////////////////////////////////////////////////////////////////////
function readFacsPaymentFile($k, $record){
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
}
/////////////////////////////////////////////////////////////////////////////
function readFacsPaymentFileAPI($k, $record){
	$this->convertedData['accountData'][$k]['Fill']				=	"";
	$this->convertedData['accountData'][$k]['RecordTypeBD']		=	"BD";
	$this->convertedData['accountData'][$k]['DebtorNumber']		=	substr($record,     3,    7);
	$this->convertedData['accountData'][$k]['ClientNumber']		=	substr($record,    10,    6);
	$this->convertedData['accountData'][$k]['ClientDebtorNumber']	=	substr($record,	   16,	 20); #only grabbing first 13 characters of 24 available
	$this->convertedData['accountData'][$k]['DebtorName']				=	substr($record,    40,	 36);
	$this->convertedData['accountData'][$k]['PaymentDate']				=	substr($record,	   76, 	  6);
	$this->convertedData['accountData'][$k]['PaymentType']				=	substr($record,	   82, 	  3);
	$this->convertedData['accountData'][$k]['Balance']					=	substr($record,	   85,	  8);
	$this->convertedData['accountData'][$k]['AppliedPrincipal']			=	substr($record,	   93, 	  8);
	$this->convertedData['accountData'][$k]['AppliedInterest']			=	substr($record,	  101,	  6);
	$this->convertedData['accountData'][$k]['AppliedCC']				=	substr($record,	  113,	  6);
	$this->convertedData['accountData'][$k]['AppliedAttorney']			=	substr($record,	  119,	  6);
	$this->convertedData['accountData'][$k]['PaidAgency']				=	substr($record,	  125,	  8);
	$this->convertedData['accountData'][$k]['SignFieldPaidAgency']		=	substr($record,	  133,    1);
	$this->convertedData['accountData'][$k]['PaidClient']				=	substr($record,	  134,    8);
	$this->convertedData['accountData'][$k]['SignFieldPaidClient']		=	substr($record,	  142,	  1);
	$this->convertedData['accountData'][$k]['DueAgency']				=	substr($record,	  143,	  8);
	$this->convertedData['accountData'][$k]['SignFieldDueAgency']		=	substr($record,	  151,	  1);
	$this->convertedData['accountData'][$k]['DueClient']				=	substr($record,	  152,	  8);
	$this->convertedData['accountData'][$k]['SignFieldDueClient']		=	substr($record,	  160,	  1);
	$this->convertedData['accountData'][$k]['AppliedMisc1	']			=	substr($record,	  161,	  7);
	$this->convertedData['accountData'][$k]['AppliedPostJudgmentInt']	=	substr($record,	  168,    7);
	$this->convertedData['accountData'][$k]['AppliedList3']				=	substr($record,	  175,	  7);
	$this->convertedData['accountData'][$k]['AppliedList4']				=	substr($record,	  182,	  7);
	$this->convertedData['accountData'][$k]['ListDate']					=	substr($record,	  189,	  6);
	$this->convertedData['accountData'][$k]['ReferenceNumber']			=	substr($record,	  195,	  8);
	$this->convertedData['accountData'][$k]['DateOfService']			=	substr($record,	  203,	  6);
	$this->convertedData['accountData'][$k]['AppliedCreditBalance']		=	substr($record,	  209,	 10);
	$this->convertedData['accountData'][$k]['Unused']					=	substr($record,	  219,	 31);
	$this->convertedData['accountData'][$k]['ReceivableGroupID']		=	"";
	$this->convertedData['accountData'][$k]['BillingPeriodSequence']	=	"";
	$this->convertedData['accountData'][$k]['ResponsibleParty']			=	"";
	$this->convertedData['accountData'][$k]['ReferenceNumber']			=	$this->convertedData['accountData'][$k]['ClientDebtorNumber']; 
}
/////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
//sets up the data for the production of the payment file
/////////////////////////////////////////////////////////////////////////////
function massageFacsPaymentData(){
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
	
	foreach($this->exportData['accountData'] as $k=>$v){
		$this->addDecimalForPaymentFile($k, $this->exportData['accountData'][$k]['AppliedPrincipal'], 'AppliedPrincipal');
		if($this->exportData['accountData'][$k]['DueAgency'] > 0){ $this->addDecimalForPaymentFile($k, $this->exportData[$k]['DueAgency'], 'DueAgency'); }	
		if($this->exportData['accountData'][$k]['PaidAgency'] > 0){ $this->addDecimalForPaymentFile($k, $this->exportData[$k]['PaidAgency'], 'PaidAgency'); }	
		if($this->exportData['accountData'][$k]['Balance'] > 0){ $this->addDecimalForPaymentFile($k, $this->exportData[$k]['Balance'], 'Balance'); }	

		$vals = explode("_", $this->exportData['accountData'][$k]['ReferenceNumber']);
		$this->exportData['accountData'][$k]['ReceivableGroupID']	=	$vals[0];
		$this->exportData['accountData'][$k]['BillingPeriodSequence']	=	$vals[1];
		$this->exportData['accountData'][$k]['ResponsibleParty']		=	trim($vals[2]);
		$this->exportData['accountData'][$k]['ClientDebtorNumber'] = $this->exportData[$k]['ReceivableGroupID'];
	}
}
/////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
function massageFacsPaymentDataAPI(){
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
	$maxlines = count($this->convertedData['accountData']); 
	$date = date("Ymd");
	$paymentFileName = "/home/nobody/Y9650/GuarPmt_EFS_".$date.".txt";
	$exportDataReplace = ""; //this is a string we will use to build the text for the export file
	
	foreach($this->convertedData['accountData'] as $k=>$v){
		$this->addDecimalForPaymentFile($k, $this->convertedData['accountData'][$k]['AppliedPrincipal'], 'AppliedPrincipal');
		if($this->convertedData['accountData'][$k]['DueAgency'] > 0){ $this->addDecimalForPaymentFile($k, $this->convertedData['accountData'][$k]['DueAgency'], 'DueAgency'); }	
		if($this->convertedData['accountData'][$k]['PaidAgency'] > 0){ $this->addDecimalForPaymentFile($k, $this->convertedData['accountData'][$k]['PaidAgency'], 'PaidAgency'); }	
		if($this->convertedData['accountData'][$k]['Balance'] > 0){ $this->addDecimalForPaymentFile($k, $this->convertedData['accountData'][$k]['Balance'], 'Balance'); }	

		$vals = explode("_", $this->convertedData['accountData'][$k]['ReferenceNumber']);
		$this->convertedData['accountData'][$k]['ReceivableGroupID']	=	$vals[0];
		$this->convertedData['accountData'][$k]['BillingPeriodSequence']	=	$vals[1];
		$this->convertedData['accountData'][$k]['ResponsibleParty']		=	trim($vals[2]);
		$this->convertedData['accountData'][$k]['ClientDebtorNumber'] = $this->exportData[$k]['ReceivableGroupID'];
	}
}
/////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
function addDecimalForPaymentFile($keyNum, $numberToSplit, $fieldName){
	$firstPart = substr( $numberToSplit, 0, strlen($numberToSplit)-2 );
	$secondPart = substr($numberToSplit, strlen($numberToSplit)-2, 2);
	$newNumber = $firstPart.".".$secondPart;
	$this->convertedData['accountData'][$keyNum][$fieldName] = $newNumber;
}
/////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
function writeFacsPaymentFile(){
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
	$lastLine = 0;
	$date = date("Ymd");
	$paymentFileName = "/home/nobody/".$this->clientId."/GuarPmt_EFS_".$date.".txt";
	//$paymentFileName = "C:\\xampp\\htdocs\\efs\\Y9650\\"."GuarPmt_EFS_New_".$date.".txt";

	$exportDataReplace = ""; //this is a string we will use to build the text for the export file
	$index = 0;
	$num_skipped_records = 0;

	$paymentContent = "";
	foreach($this->exportData as $k=>$v){
			echo $k;
			/////////////////////////////////////////////
			$index++;
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
			}else{
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
				//$lastLine = $index + $num_skipped_records + $num_header_lines + $num_trailer_lines;
					##############################################################################
					########## Footer******* #####################################################
					##############################################################################
					//print RESULTFILE ("GPT|PMT|TRUE|TRUE|Evergreen Financial|||||5"); #this is the footer specified by YVMH
				//}
			}elseif ($skip_record == "N") { 	
				//$lastLine++;
				$BD++;
				$totalPayments 		= 	$totalPayments + $this->exportData[$k]['AppliedPrincipal'];
				$accounts_processed	=	$index + $accountsNotListed+1; #index starts with zero
				$accountsNotListed 	= 	$accountsSkipped + $dp;
				$paymentContent .= "GPT|PMT|False|True|Evergreen Financial|";				
				$paymentContent .= trim($this->exportData[$k]['ClientDebtorNumber'])."_".trim($this->exportData[$k]['BillingPeriodSequence'])."_".trim($this->exportData[$k]['ResponsibleParty']);
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
				$totalBalance 		= 	$totalBalance + $this->exportData[$k]['Balance'];							
			}
			////////////////////////////////////////////////////////////
	
	}
	$paymentContent .= "GPT|PMT|TRUE|TRUE|Evergreen Financial|||||5";
	if ($index == $maxlines) { 
		//echo "$paymentFileName Found the last account, writing trailer for massage_YVMH_payment\n\n"; #index starts with zero
		$localContent = str_replace('#r#n', "\r\n", $paymentContent);
		$myfile = fopen($paymentFileName, "w") or die("Unable to open $paymentFileName");
		fwrite($myfile, $localContent);
		fclose($myfile);
	}
	$this->convertedData['accountData'] = $paymentContent;
}
///////////////////////////////////////////////////////////////////////////////////
function writeFacsPaymentFileAPI(){
	
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
	$totalDueClient = 0;
	$totalPayments = 0;
	$maxlines = count($this->exportData); 
	$lastLine = 0;
	$date = date("Ymd");
	$paymentFileName = "/home/nobody/".$this->clientId."/GuarPmt_EFS_".$date.".txt";
	//$paymentFileName = "C:\\xampp\\htdocs\\efs\\Y9650\\"."GuarPmt_EFS_New_".$date.".txt";

	$exportDataReplace = ""; //this is a string we will use to build the text for the export file
	$index = 0;
	$num_skipped_records = 0;

	$paymentContent = "";
	
	foreach($this->exportData as $k=>$v){
			//echo $k;
			/////////////////////////////////////////////
			$index++;
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
			}else{
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
				//$lastLine = $index + $num_skipped_records + $num_header_lines + $num_trailer_lines;
					##############################################################################
					########## Footer******* #####################################################
					##############################################################################
					//print RESULTFILE ("GPT|PMT|TRUE|TRUE|Evergreen Financial|||||5"); #this is the footer specified by YVMH
				//}
			}elseif ($skip_record == "N") { 	
				//$lastLine++;
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
				$totalBalance 		= 	$totalBalance + $this->exportData[$k]['Balance'];							
			}
			////////////////////////////////////////////////////////////
	
	}
	
	$paymentContent .= "GPT|PMT|TRUE|TRUE|Evergreen Financial|||||5";
	
	$localContent = str_replace('#r#n', "\r\n", $paymentContent);
	$myfile = fopen($paymentFileName, "w") or die("Unable to open $paymentFileName");
	fwrite($myfile, $localContent);
	fclose($myfile);

	//$this->exportData = $paymentContent;
	$this->convertedData['accountData'] = $paymentContent;
	
}
///////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////
function writeFacsPaymentFileAPI_2(){
	/*
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
	$totalDueClient = 0;
	$totalPayments = 0;
	$maxlines = count($this->exportData); 
	$lastLine = 0;
	$date = date("Ymd");
	//$paymentFileName = "/home/nobody/".$this->clientId."/GuarPmt_EFS_".$date.".txt";
	$paymentFileName = "C:\\xampp\\htdocs\\efs\\Y9650\\"."GuarPmt_EFS_New_".$date.".txt";

	$exportDataReplace = ""; //this is a string we will use to build the text for the export file
	$index = 0;
	$num_skipped_records = 0;

	$paymentContent = "";
	
	foreach($this->exportData as $k=>$v){
			//echo $k;
			/////////////////////////////////////////////
			$index++;
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
			}else{
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
				//$lastLine = $index + $num_skipped_records + $num_header_lines + $num_trailer_lines;
					##############################################################################
					########## Footer******* #####################################################
					##############################################################################
					//print RESULTFILE ("GPT|PMT|TRUE|TRUE|Evergreen Financial|||||5"); #this is the footer specified by YVMH
				//}
			}elseif ($skip_record == "N") { 	
				//$lastLine++;
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
				$totalBalance 		= 	$totalBalance + $this->exportData[$k]['Balance'];							
			}
			////////////////////////////////////////////////////////////
	
	}
	*/
	$paymentContent .= "GPT|PMT|TRUE|TRUE|Evergreen Financial|||||5";
	/*
	if ($index == $maxlines) { 
		//echo "$paymentFileName Found the last account, writing trailer for massage_YVMH_payment\n\n"; #index starts with zero
		$localContent = str_replace('#r#n', "\r\n", $paymentContent);
		//$myfile = fopen($paymentFileName, "w") or die("Unable to open $paymentFileName");
		//fwrite($myfile, $localContent);
		//fclose($myfile);
	}*/
	$this->exportData = $paymentContent;
	
}
///////////////////////////////////////////////////////////////////////////////////
function checkAgeWithTimestamp($doborig, $dateorig){ //accepts 2 dates in YYYY-MM-DD format. returns int years old
	$datetime1 = new DateTime($doborig); //create new date object with the timestamp
	$datetime2 = new DateTime($dateorig); //create new date object with the timestamp
	$interval = $datetime1->diff($datetime2); //get the difference
	$age = $interval->format('%y years'); //this is the age of the patient
	return $age;
}
///////////////////////////////////////////////////////////////////////////////////
function convert_Y2532($recordNumber, $v, $data){ //Benton PUD
		
		$balanceEx = explode('.', $data[9]);
		$num = $balanceEx[1];
		$numlength = strlen((string)$num);
			
			if($numlength!=2){ //quick fix to add trailing zero
				if($numlength==1){ 
					$balanceEx[1] = $balanceEx[1]."0";  
				}elseif($numlength==0){
					$balanceEx[1] = "00";
				}
			} 

		$this->exportData[$recordNumber][01]['balance'] = $balanceEx[0].$balanceEx[1];
		$ssRaw = str_replace('-', '', $data[4]);

		if(substr($ssRaw, 0, 9) == 999999999 || substr($ssRaw, 0, 9) == 555555555) { //this is a client-specific filter to strip ss numbers of pure 9's or 5's
			//don't list anything
		}elseif(substr($ssRaw, 0, 5) == 99999 || substr($ssRaw, 0, 5) == 55555) { //this is a client-specific filter to add numeric strings of 5 9's or 5 5's to the notes
			$this->exportData[$recordNumber][03]['Note1'] = "SSN: ".$ssRaw." | "; 
		}else{
			$this->exportData[$recordNumber][01]['debtorSs'] = $ssRaw; //we don't have the 9 or 5 patterns - just list it as a regular ss number
		}
		
		$dateOfServiceFormatted = $this->checkDateWithSlashes($data[6], 0); //format date for FACS entry
		$dateOfServiceFormatted = str_replace("/", "", $dateOfServiceFormatted);
		$this->exportData[$recordNumber][01]['dateOfService'] = $dateOfServiceFormatted;
		$this->exportData[$recordNumber][01]['debtorPhone'] = preg_replace("/[^0-9]/", "", $data[12] );
		$debtorDobFormatted = $this->checkDateWithSlashes($data[24], 0); //format date for FACS entry
		$this->exportData[$recordNumber][01]['debtorDob'] = $debtorDobFormatted;
		
		$this->exportData[$recordNumber][01]['debtorAddress1'] = $data[13];
		$this->exportData[$recordNumber][01]['debtorCity'] = $data[14];
		$this->exportData[$recordNumber][01]['debtorState'] = $data[15];
		$this->exportData[$recordNumber][01]['debtorZip'] = $data[16];

		$alt_phone = $data[19].$data[20];
		if($alt_phone != $this->exportData[$recordNumber][01]['debtorPhone']) $this->exportData[$recordNumber][01]['rpPhone'] = $alt_phone; //put the alt phone in the rp phone since we really don't have minors on this account so we don't have to worry about minor checks
		$this->exportData[$recordNumber][03]['Note1'] = $this->exportData[$recordNumber][03]['Note1']."Con Date: ".$data[5]." | Disc Date: ".$data[6]." | Service Add: ".$data[10]." ".$data[11];
		
			if( strlen($data[25])>0) { $this->exportData[$recordNumber][03]['Note1'] = $this->exportData[$recordNumber][03]['Note1']." | Driver's Lic: (".trim($data[25])." ".$data[26].") "; }
		
		$data[24] = trim($data[24]);
		$data[28] = trim($data[28]);
		$data[29] = trim($data[29]);
		$data[30] = trim($data[30]);

			if( strlen($data[24])>0 || strlen($data[28])>0 || strlen($data[29])>0 || strlen($data[30])>0 ){ 
				$this->exportData[$recordNumber][03]['Note1'] = $this->exportData[$recordNumber][03]['Note1']."| Add'l Info: (".$data[24]." ".$data[28]." ".$data[29]." ".$data[30].")"; 
				//echo $this->exportData[$recordNumber][03]['Note1'];
				//echo "<br />";
			}
		
		$extra_address = explode('WA', $data[11]); //split off the city from the address for line 104
		//populate the service address to the 104 and 154 lines via the 90/91 FACS file lines
		$this->exportData = $this->populateWindow($recordNumber,104, "01", $data[10], 'fieldNum1', 'fieldNum1Data', 90, 'winNum1');
		$this->exportData = $this->populateWindow($recordNumber,104, "12", $extra_address[0], 'fieldNum2', 'fieldNum2Data', 90, 'winNum1');
		$this->exportData = $this->populateWindow($recordNumber,154, "01", $data[10], 'fieldNum1_b', 'fieldNum1Data_b', 91, 'winNum1_b');
		$this->exportData = $this->populateWindow($recordNumber,154, "02", $data[11], 'fieldNum2_b', 'fieldNum2Data_b', 91, 'winNum1_b');
		
		$spouseNameRaw = $data[17];
		$spouseNameEx = explode(" ", $spouseNameRaw);
			if( count($spouseNameEx)>2 ){ 
				$spouseNameNew = $spouseNameEx[2].",".$spouseNameEx[0]." ".$spouseNameEx[1]; 
			}else{
				$spouseNameNew = $spouseNameEx[1].",".$spouseNameEx[0]; 
			}

		$this->exportData[$recordNumber][02]['spouseName'] = $spouseNameNew;
		$this->exportData[$recordNumber][02]['spouseSs'] = str_replace('-', '', $data[18]);
		$spouseDob = $this->checkDateWithSlashes($date[27], 0);
		$this->exportData[$recordNumber][02]['spouseDob'] = $spouseDob;
		$this->exportData[$recordNumber][02]['spousePoe'] = $date[28];
		$this->exportData[$recordNumber][02]['spousePoe'] = $date[29];
		
		$spPhone = $data[19].$data[20];
			
			if(strlen($spPhone) == 10 && $this->exportData[$recordNumber][01]['debtorPhone'] != $spPhone){ $this->exportData[$recordNumber][01]['rpPhone'] = $spPhone;  }
		
		$this->exportData[$recordNumber][02]['debtorPoe'] = $data[23];
		$this->exportData[$recordNumber][02]['debtorPoePhone'] = $data[21].$data[22];
		
		$delinquencyDateFormatted = $this->checkDateWithSlashes($data[7], 0);
		$this->exportData[$recordNumber][02]['delinquencyDate'] = $delinquencyDateFormatted;

		$this->splitNotes($this->exportData[$recordNumber][03]['Note1'], $recordNumber);
		$this->exportData[$recordNumber][01]['debtorAddress1'];
		$this->exportData = $this->splitAddress($recordNumber, $this->exportData);
		return $this->exportData;
}
///////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////
function checkY9234Valid($numberToTest){
	$testArray = explode("/", $numberToTest);
	if(is_numeric($testArray[0]) && $testArray[0]>0 && is_numeric($testArray[1]) && $testArray[1]>0) return true;
	return false;
}
///////////////////////////////////////////////////////////////////////////////////
function prependYear($yearshort){ //super basic year prefix filler for later timestamp conversion to determine the patient's age - should be reviewed
	if( $yearshort <= 70  && $yearshort > 18){
		$year = '19'.$yearshort; 
	}elseif($yearshort >= 70){
		$year = '19'.$yearshort;
	}elseif($yearshort < 70  && $yearshort <= 18){
		$year = '20'.$yearshort;
	}else{
		$year = '20'.$yearshort;
	}
	//end basic year prefix filler
	return $year;
}
///////////////////////////////////////////////////////////////////////////////////
function populateRecord_Y9234($k, $recordNumber, $v){
	$this->exportData[$recordNumber][1]['debtorName'] = $v[36];
	$balance = preg_replace('/\D/', '', $v[48]); //strip non-numeric
	$this->exportData[$recordNumber][01]['balance'] = $balance;
	$this->exportData[$recordNumber][01]['debtorAddress1'] = $v[37];
	$this->exportData[$recordNumber][01]['debtorAddress2'] = $v[38];
	$this->exportData[$recordNumber][01]['debtorCity'] = $v[39];
	$this->exportData[$recordNumber][01]['debtorState'] = $v[40];
	$this->exportData[$recordNumber][01]['debtorZip'] = $v[41];
	$this->exportData[$recordNumber][01]['debtorSs'] = preg_replace('/\D/', '', $v[42]); //strip non-numeric 
	
	$dobraw = $v[43];
		if(strlen($dobraw)<6) $dobraw = "0".$dobraw; //add the zero to the beginning of the date for correct formatting
	$firsthalfdob = substr($dobraw, 0, 2);
	$secondhalfdob = substr($dobraw, 2, 2);
	$yearraw = substr($dobraw, 4, 2);
	$yeardob = $this->prependYear($yearraw);

	$doborig = $yeardob."-".$firsthalfdob."-".$secondhalfdob; //formatted for timestamp
	$this->exportData[$recordNumber][01]['debtorDob'] = $dobraw; //set the bdate with the format before it was reformatted for the timestamp conversion
	$this->exportData[$recordNumber][01]['debtorPhone'] = preg_replace('/\D/', '', $v[44]); //strip non-numeric 
	$this->exportData[$recordNumber][01]['dateOfService'] = preg_replace('/\D/', '', $v[47]); //strip non-numeric
	$this->exportData[$recordNumber][01]['listDate'] = $this->exportData[$recordNumber][01]['dateOfService'];
	
	$dateorig = $this->exportData[$recordNumber][01]['listDate'];
		if(strlen($dateorig)<6) $dateorig = "0".$dateorig; //add the zero to the beginning of the date for correct formatting
	$firstdateorig = substr($dateorig, 0, 2);
	$seconddateorig = substr($dateorig, 2, 2);
	$yeardate = substr($dateorig, 4, 2);
	$yeardate = '20'.$yeardate; //append for correct formatting
	$dateorig = $yeardate."-".$firstdateorig."-".$seconddateorig; //formatted for timestamp

	$age = $this->checkAgeWithTimestamp($doborig, $dateorig); //accepts 2 dates in YYYY-MM-DD format. returns int years old
	$poeExceptions = array('Unemployed', 'Retired', 'Student', 'Disabled');
	
		if(!in_array($v[49], $poeExceptions)){ //go ahead and put it in the regular POE data field
			if($v[49] != 'Unknown') $this->exportData[$recordNumber][02]['debtorPoe'] = $v[49];
		}else{ //it is in our exception array - put it in the debtor salary field instead
			$this->exportData[$recordNumber][02]['debtorSalary'] = $v[49];
		}

	$this->exportData[$recordNumber][02]['debtorPoeAddress'] = $v[50];
	$this->exportData[$recordNumber][02]['debtorPoeCity'] = $v[51];
	$this->exportData[$recordNumber][02]['debtorPoeState'] = $v[52];
	$this->exportData[$recordNumber][02]['debtorPoeZip'] = $v[53];
	$this->exportData[$recordNumber][02]['debtorPoePhone'] = preg_replace('/\D/', '', $v[54]); //strip non-numeric ;
	$this->exportData[$recordNumber][03]['Note1'] = "CBHA PT#".$v[35]; //add CBHA number to the notes

	$relationship = $v[46]; //string - to test with for address data
	$minorFlag = 0; //for the later RP address check
	$spouseFlag = 0; //for the later RP address check
	
	$secondPhone = preg_replace('/\D/', '', $v[65]); //strip non-numeric
		if(strlen($secondPhone)==10 && $this->exportData[$recordNumber][01]['debtorPhone'] != $secondPhone){ $this->exportData[$recordNumber][01]['rpPhone'] = $secondPhone; }

		if($age < 18) { //we have a minor - out their info in notes and replace with RP info
			$minorFlag = 1; //set the flag and collect RP info
			$this->exportData[$recordNumber][01]['rpName'] = $v[55];
			$this->exportData[$recordNumber][01]['rpSs'] = preg_replace('/\D/', '', $v[56]); //strip non-numeric 
				if(strlen($secondPhone) > 0 && $this->exportData[$recordNumber][01]['debtorPhone'] != $secondPhone) $this->exportData[$recordNumber][01]['debtorPhone'] = $secondPhone;
			$this->exportData[$recordNumber][02]['rpPoe'] = $v[58];
			
			if(!in_array($v[58], $poeExceptions)){ //go ahead and put it in the regular POE data field
				if($v[58] != 'Unknown') $this->exportData[$recordNumber][02]['debtorPoe'] = $v[58];
			}else{ //it is in our exception array - put it in the debtor salary field instead
				$this->exportData[$recordNumber][02]['debtorSalary'] = $v[58];
			}
			
			$this->exportData[$recordNumber][03]['Note1'] = $this->exportData[$recordNumber][3]['Note1']." | PT SSn:".$this->exportData[$recordNumber][01]['debtorSs'];
				if($v[58] !='Unknown') $this->exportData[$recordNumber][02]['debtorPoe'] = $v[58];
			$this->exportData[$recordNumber][02]['debtorPoeAddress'] = $v[59];
			$this->exportData[$recordNumber][02]['debtorPoeCity'] = $v[60];
			$this->exportData[$recordNumber][02]['debtorPoeState'] = $v[61];
			$this->exportData[$recordNumber][02]['debtorPoeZip'] = $v[62];
			$this->exportData[$recordNumber][02]['debtorPoeZip'] = $v[62];
			$this->exportData[$recordNumber][02]['debtorPoePhone'] = preg_replace('/\D/', '', $v[63]); //strip non-numeric;
			$this->exportData[$recordNumber][03]['Note1'] = $this->exportData[$recordNumber][3]['Note1']." | PT DOB:".$this->exportData[$recordNumber][01]['debtorDob'];
			$rpdob = $v[64];
				if(strlen($rpdob)<6) $rpdob = "0".$rpdob;
			$this->exportData[$recordNumber][01]['debtorDob'] =  $rpdob;

		}elseif($relationship == 'Spouse'){ //this is the spouse - we should populate the spouse info in the appropriate fields
			$spouseFlag = 1; //set the flag and fill spouse info
			$this->exportData[$recordNumber][02]['spouseName'] = $v[55];
			$this->exportData[$recordNumber][02]['spouseSs'] = preg_replace('/\D/', '', $v[56]); //strip non-numeric
			$spousePhone = preg_replace('/\D/', '', $v[57]); //strip non-numeric
				if($spousePhone != $rpPhone) $this->exportData[$recordNumber][02]['spousePhone'] = $spousePhone;
			$this->exportData[$recordNumber][02]['spousePoe'] = $v[58];
			$this->exportData[$recordNumber][02]['spousePoeAddress1'] = $v[59];
			$this->exportData[$recordNumber][02]['spousePoeCity'] = $v[60];
			$this->exportData[$recordNumber][02]['spousePoeState'] = $v[61];
			$this->exportData[$recordNumber][02]['spousePoeZip'] = $v[62];
			$this->exportData[$recordNumber][02]['spousePoePhone'] = preg_replace('/\D/', '', $v[63]); //strip non-numeric
			$this->exportData[$recordNumber][02]['spouseDob'] = $v[64];

			if(strlen($this->exportData[$recordNumber][02]['spouseDob'])==5)$this->exportData[$recordNumber][02]['spouseDob'] = "0".$this->exportData[$recordNumber][02]['spouseDob']; 
		}elseif($relationship != 'Self'){ //this is not a minor or a spouse but we have another name in the RP field - put it in the notes
			$this->exportData[$recordNumber][3]['Note1'] = $this->exportData[$recordNumber][3]['Note1']." | RP Name:".$v[55]." | PT Relation:".$v[46]; 
		}

	$this->exportData = $this->splitNotes($this->exportData[$recordNumber][3]['Note1'], $recordNumber); //now split the notes to fit on the 4 note lines in the FACS file
	return $this->exportData; //send back the updated array with this data
}
///////////////////////////////////////////////////////////////////////////////////
////test for valid line
////returns true or false
///////////////////////////////////////////////////////////////////////////////////
	function checkValidLine_Y6051($acct){
			if( strlen($acct)==9 && is_numeric($acct) ){
				return true;
			}else{
				return false;
			}
	}
///////////////////////////////////////////////////////////////////////////////////
////test for valid line
////returns true or false
///////////////////////////////////////////////////////////////////////////////////
	function checkValidLine_Y3210($acct){
			if( strlen($acct)>0){
				return true;
			}else{
				return false;
			}
	}
///////////////////////////////////////////////////////////////////////////////////
////test for the SS number and store any subsequent info in a nested array
////returns array
///////////////////////////////////////////////////////////////////////////////////
	function populateSsNumbers($ssNumbers, $ssNumber, $record, $fields, $clientId){
		$controlNo = $record[0];

		$controlNew = '';
			$controlArray = str_split($controlNo);
			foreach($controlArray as $k=>$v){
				if(ctype_alpha($v))$v = strtoupper($v);
				$controlNew .= $v;
			}
			$controlNo = $controlNew;

		if($clientId == 'Y3210'){
			if(!array_key_exists($controlNo, $ssNumbers)){ //we don't have this ssn set up yet
				$ssNumbers[$controlNo] = array();
				foreach($fields as $k=>$v){ //run throught the fields array and grab the field info from the active record
					$ssNumbers[$controlNo][$v] = $record[$k];
				} // end for
			}
		} //end if
		return $ssNumbers;
	}
///////////////////////////////////////////////////////////////////////////////////
////populateWindow
///////////////////////////////////////////////////////////////////////////////////
	function populateWindow($recordNumber, $windowNumber, $windowField, $windowContent, $fieldNumValue='fieldNum1', $fieldNumData='fieldNum1Data', $line=90, $winNumber='winNum1'){
		//90 line for user defined data for the control number - this inserts data to the FACS window 103
		$this->exportData[$recordNumber][$line][$winNumber] = $windowNumber;
		$this->exportData[$recordNumber][$line][$fieldNumValue] = $windowField;
		$this->exportData[$recordNumber][$line][$fieldNumData] = $windowContent;
		return $this->exportData;
	}
///////////////////////////////////////////////////////////////////////////////////
////tests for the presence of an active hospital account number and a dash later in the file to indicate this is the line to take client data from - returns boolean
////returns boolean
///////////////////////////////////////////////////////////////////////////////////
	function testRecordEpic($recordNumber, $secondNumber , $exportData, $testDash){
		if( strlen($recordNumber)>3 && !array_key_exists( $recordNumber, $exportData ) ){
			return 1;
		}elseif(strstr($testDash, "-") && strlen($secondNumber)>0){
			return 1;
		}else{
			return 0;
		}
	}
///////////////////////////////////////////////////////////////////////////////////
////epic filesystem conversion
////returns populated object with FACS array
///////////////////////////////////////////////////////////////////////////////////
	function populateRecordEpic($lineEx, $recordNumber, $exportData, $fileData, $counter){
		if(strlen($lineEx[150])>0){ //we know that we have the larger HAL number in field number 1 - populate the record
			//////////////////////////////////////////////
			//check for duplicate phone numbers
			//for 'debtor phone' and 'phone at discharge'
			$debtorPhone = "";
			$rpPhone = "";
			$debtorPoePhone = "";

			$debtorPhone = preg_replace("/[^0-9,.]/", "", $lineEx[23]);
			$this->exportData[$recordNumber][01]['debtorPhone'] = $debtorPhone;

			/*if( $debtorPhone != $rpPhone ){ //these numbers are different so we need to check to see if the other non-debtor phones match between rpPhone and POE
				$debtorPoePhone = preg_replace("/[^0-9,.]/", "", $lineEx[162]);
				if( preg_replace("/[^0-9,.]/", "", $lineEx[24]) != preg_replace("/[^0-9,.]/", "", $lineEx[162]) ){ //these numbers are not the same so we need to populate the RP phone as well
					$rpPhone = preg_replace("/[^0-9,.]/", "", $lineEx[24]);
				}
			}*/
			if( $debtorPhone != $rpPhone ){ //these numbers are different so we need to check to see if the other non-debtor phones match between rpPhone and POE
				$debtorPoePhone = preg_replace("/[^0-9,.]/", "", $lineEx[171]);
				if( preg_replace("/[^0-9,.]/", "", $lineEx[24]) != preg_replace("/[^0-9,.]/", "", $lineEx[171]) ){ //these numbers are not the same so we need to populate the RP phone as well
					$rpPhone = preg_replace("/[^0-9,.]/", "", $lineEx[24]);
				}
			}
			/////////////////////////////////////////////
			$nameRaw = strtoupper($lineEx[6]);
			$nameRawEx = explode(",", $nameRaw);
			$nameRaw = $nameRawEx[0].",".$nameRawEx[1];
				if(strlen($nameRawEx[1])>0){ //we have a middle initial so list it
					$nameRaw .= " ".$nameRawEx[2];
				}
			$this->exportData[$recordNumber][01]['debtorName'] = $nameRaw;
			$this->exportData[$recordNumber][01]['debtorSs'] = $lineEx[7];
			if($this->exportData[$recordNumber][01]['debtorSs'] == '123456789'){ $this->exportData[$recordNumber][01]['debtorSs'] = 999999999; }
			$this->exportData[$recordNumber][01]['debtorAddress1'] = strtoupper($lineEx[17]);
			$this->exportData[$recordNumber][01]['debtorAddress2'] = strtoupper($lineEx[18]);
			$this->exportData[$recordNumber][01]['debtorCity'] = strtoupper($lineEx[19]);
			$this->exportData[$recordNumber][01]['debtorState'] = strtoupper($lineEx[20]);
			$this->exportData[$recordNumber][01]['debtorZip'] = $lineEx[21];
			$this->exportData[$recordNumber][01]['debtorNumber'] .= "/".$lineEx[150];
			$this->exportData[$recordNumber][01]['debtorDob'] = $lineEx[8];
			$this->exportData[$recordNumber][01]['balance'] += $lineEx[73];
			$balance = str_replace(".", "", $lineEx[73]);
			$balance = str_replace(",", "", $balance);
			$this->exportData[$recordNumber][01]['balance'] = $balance;
			$this->exportData[$recordNumber][01]['dateOfService'] = $lineEx[14];
			$this->exportData[$recordNumber][01]['dateOfService'] = $this->processEpicDate($this->exportData[$recordNumber][01]['dateOfService']);
			$this->exportData[$recordNumber][01]['listDate'] = $lineEx[14];
			$this->exportData[$recordNumber][01]['listDate'] = $this->processEpicDate($this->exportData[$recordNumber][01]['listDate']);


			//prep info for notes
			if(strstr($lineEx[16], "WSM PROVIDENCE SAINT MARY MEDICAL CENTER")){
				$lineEx[16] = 'prov-st-mary';
			}


				//if(strstr($lineEx[69], "Medicare")) { echo $lineEx[69]."<br />"; }

				$noteString	=	$lineEx[66];
				$noteString	.=	"/CL:".$lineEx[69];

				if(trim($lineEx[69])=="Medicare"){
					$this->exportData = $this->populateWindow($recordNumber,104, "04", "MEDICARE");
				}


				$noteString	.=	" Charge:".number_format( preg_replace("/[^0-9.]/", "", $lineEx[70]), 2);
				$noteString	.=	" Pmt:".number_format( preg_replace("/[^0-9.]/", "", $lineEx[242]), 2);
				$noteString	.=	" Adj:".number_format( preg_replace("/[^0-9.]/", "", $lineEx[243]), 2);
				$noteString	.=	" Bal:".number_format( preg_replace("/[^0-9.]/", "",  $lineEx[73]), 2);

				$noteString	.=	" Provider:".strtoupper($lineEx[213]);
				$noteString	.=	" Facility:".strtoupper($lineEx[16]);
				$noteString	.=	" Guar Ph:".preg_replace("/[^0-9,.]/", "", $lineEx[87]);

				if(strlen($lineEx[164])>0) $noteString	.=	" Guar POE:".ucfirst(str_replace("OTHER (", "", str_replace(")","",$lineEx[164])));
				if(strlen($lineEx[165])>0) $noteString	.=	", ".$lineEx[165];
				if(strlen($lineEx[166])>0) $noteString	.=	", ".$lineEx[166];
				if(strlen($lineEx[167])>0) $noteString	.=	", ".$lineEx[167];
				if(strlen($lineEx[168])>0) $noteString	.=	", ".$lineEx[168];
				if(strlen($lineEx[169])>0) $noteString	.=	", ".$lineEx[169];

			$this->exportData[$recordNumber][02]['debtorPoe'] = str_replace("OTHER (", "", strtoupper($lineEx[164]));
			$this->exportData[$recordNumber][02]['debtorPoeAddress'] = strtoupper($lineEx[165]);
			$this->exportData[$recordNumber][02]['debtorPoeCity'] = strtoupper($lineEx[167]);
			$this->exportData[$recordNumber][02]['debtorPoeState'] = strtoupper($lineEx[168]);
			$this->exportData[$recordNumber][02]['debtorPoeZip'] = $lineEx[169];
			$this->exportData[$recordNumber][02]['debtorPoePhone'] = $debtorPoePhone;

				if(strstr(strtolower($lineEx[27]), "spouse")){ //add spouse info to the data array
					$this->exportData[$recordNumber][02]['spouseName'] = strtoupper($lineEx[26]);
					if( $lineEx[28] != strtoupper($this->exportData[$recordNumber][01]['debtorAddress1']) ){
					 $this->exportData[$recordNumber][02]['spouseAddress1'] = strtoupper($lineEx[28]);
					 $this->exportData[$recordNumber][02]['spouseAddress2'] = strtoupper($lineEx[29]);
					 $this->exportData[$recordNumber][02]['spouseCity'] = strtoupper($lineEx[30]);
					 $this->exportData[$recordNumber][02]['spouseState'] = strtoupper($lineEx[31]);
					 $this->exportData[$recordNumber][02]['spouseZip'] = $lineEx[32];
				}
			}

			$debtorDobEx = explode("/", $this->exportData[$recordNumber][01]['debtorDob']);
			///////////////////////////////////////////////////////////
			$birthDateForAgeCheck = $debtorDobEx[2].$debtorDobEx[0].$debtorDobEx[1]; //this is for the new age function so we're shifting in a new variable - eventually we should eliminate the birthDate variable

			switch($this->clientId){ //we have differing formats between clients for client Dob so we need to fit the format to the client
				case "Y9658":
					$birthDateForAgeCheck = $debtorDobEx[2].$debtorDobEx[0].$debtorDobEx[1];
					$this->exportData[$recordNumber][01]['debtorDob'] = $this->processEpicDate($birthDateForAgeCheck);
				break;
				case "Y3401":
					$thisYearLastTwo = substr(date("Y"), 2, 2);
					$thisPersonsBirthDay = $this->exportData[$recordNumber][01]['debtorDob'];
					$thisMonth = substr($thisPersonsBirthDay, 0, 2);
					$thisDay = substr($thisPersonsBirthDay, 2, 2);
					$thisYear = substr($thisPersonsBirthDay, 4, 2);
						if($thisYear > $thisYearLastTwo){
							$thisYearNew = "19".$thisYear;
						}else{
							$thisYearNew = "20".$thisYear;

						}
					$birthDateForAgeCheck = $thisYearNew.$thisMonth.$thisDay;
				break;
			}
			///////////////////new date check to check the person's birthdate against the list date
			  $date = new DateTime($birthDateForAgeCheck);
			  $now = new DateTime($this->exportData[$recordNumber][01]['listDate']);
			  $interval = $now->diff($date);
			  $age = $interval->y;
			 ///////////////////
			  if($age < 18){ //this is a minor
				$noteString .= " PT BD:".$this->exportData[$recordNumber][01]['debtorDob']." "; //store minor DOB in the notestring
				$this->exportData[$recordNumber][01]['rpName'] = $lineEx[151];
				$this->exportData[$recordNumber][01]['rpSs'] = $lineEx[152];
					if($this->exportData[$recordNumber][01]['rpSs'] == '123456789'){ $this->exportData[$recordNumber][01]['rpSs'] = 999999999; }

				if($this->clientId == "Y9658"){ //case for Y9658 client
					$rpDobEx = explode("/", $lineEx[153]);
					$Y9658RPDOB = $rpDobEx[2].$rpDobEx[0].$rpDobEx[1];
					$this->exportData[$recordNumber][01]['debtorDob'] = $this->processEpicDate($Y9658RPDOB);
				}
				$this->exportData[$recordNumber][02]['debtorPoePhone'] =  trim($lineEx[171]);
				$this->exportData[$recordNumber][01]['rpPhone'] = $rpPhone;
				$this->exportData[$recordNumber][01]['rpNumber'] = $lineEx[150];
				$this->exportData[$recordNumber][01]['debtorAddress1'] = $lineEx[155];
				$this->exportData[$recordNumber][01]['debtorAddress2'] = $lineEx[156];
				$this->exportData[$recordNumber][01]['debtorCity'] = $lineEx[157];
				$this->exportData[$recordNumber][01]['debtorState'] = $lineEx[158];
				$this->exportData[$recordNumber][01]['debtorZip'] = $lineEx[159];
			  }
			///////////////////////////////////////////////////////////
			$this->exportData = $this->breakAddress($this->exportData, $recordNumber, 1); //splits the address to second line if longer than given length
			$this->exportData = $this->splitNotes($noteString, $recordNumber); //now split the notes to fit on the 4 note lines in the FACS file
			

			if(strlen($lineEx[26])>0){
				$thisRelative = array('recordId'=>$recordNumber,
					'Name'=>$lineEx[26],
					'Relation'=>"REL",
					'Address'=>$lineEx[27].", ".$lineEx[28]." ".$lineEx[30]." ".$lineEx[31],
					'Phone'=>str_replace("-", "", $lineEx[33]),
					'PhoneFlag'=>"",
					/*'LengthOfService'=>"",
					'Unused'=>""*/
				);
				array_push($this->exportData[$recordNumber][11], $thisRelative);

			}
			if(strlen($lineEx[36])>0){
				$thisRelative = array('recordId'=>$recordNumber,
					'Name'=>$lineEx[36],
					'Relation'=>"REL",
					'Address'=>$lineEx[37].", ".$lineEx[38]." ".$lineEx[40]." ".$lineEx[41],
					'Phone'=>str_replace("-", "", $lineEx[43]),
					'PhoneFlag'=>"",
					'LengthOfResidence'=>"",
					'Unused'=>""
				);
				array_push($this->exportData[$recordNumber][11], $thisRelative);
			}
			if(strlen($lineEx[46])>0){
				$thisRelative = array('recordId'=>$recordNumber,
					'Name'=>$lineEx[46],
					'Relation'=>"REL",
					'Address'=>$lineEx[47].", ".$lineEx[48]." ".$lineEx[50]." ".$lineEx[51],
					'Phone'=>str_replace("-", "", $lineEx[53]),
					'PhoneFlag'=>"",
					'LengthOfResidence'=>"",
					'Unused'=>""
				);
				array_push($this->exportData[$recordNumber][11], $thisRelative);
			}
		}
		return $this->exportData;
	}
///////////////////////////////////////////////////////////////////////////////////
//// accepts string
//// returns formatted date string
///////////////////////////////////////////////////////////////////////////////////
	function processEpicDate($dateString){
		if($this->clientId == "Y3401"){ //case for virginia mason hospital whose date is configured to euro format
			$dateYear = substr($dateString,2,2);
			$dateMonth = substr($dateString,4,2);
			$dateDay = substr($dateString,6,2);
			$newDate = $dateMonth.$dateDay.$dateYear;
			return $newDate;
		}elseif($this->clientId == "Y9658"){
			$dateYear = substr($dateString,2,2);
			$dateMonth = substr($dateString,4,2);
			$dateDay = substr($dateString,6,2);
			$newDate = $dateMonth.$dateDay.$dateYear;
			return $newDate;
		}else{
			return $dateString;
		} //do nothing and return the string
	}
///////////////////////////////////////////////////////////////////////////////////
////
///////////////////////////////////////////////////////////////////////////////////
function getAge($month, $day, $year){
	//date in mm/dd/yyyy format; or it can be in other formats as well
	  $birthDate = $month."/".$day."/".$year;
	  //explode the date to get month, day and year
	  $birthDate = explode("/", $birthDate);
	  //get age from date or birthdate
	  $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md")
		? ((date("Y") - $birthDate[2]) - 1)
		: (date("Y") - $birthDate[2]));
	  return $age;
}
///////////////////////////////////////////////////////////////////////////////////
////splits the address info in two
///////////////////////////////////////////////////////////////////////////////////
function splitAddress($recordNumber, $exportData){

	if(strlen($exportData[$recordNumber][01]['debtorAddress1'])>20){
		$diff = strlen($exportData[$recordNumber][01]['debtorAddress1'])-20;
		$addy1 = substr($exportData[$recordNumber][01]['debtorAddress1'], 0, 20);
		$addy2 = substr($exportData[$recordNumber][01]['debtorAddress1'], 20, $diff);
		$exportData[$recordNumber][01]['debtorAddress1'] = $addy1;
		$exportData[$recordNumber][01]['debtorAddress2'] = $addy2."".$exportData[$recordNumber][01]['debtorAddress2'];

	}
	return $exportData;
}
///////////////////////////////////////////////////////////////////////////////////
////this is the massage function specific to the epic file system exports
////returns populated and massaged object
///////////////////////////////////////////////////////////////////////////////////
	function massageEpicData($exportData){
		foreach($exportData as $k=>$v){

			$debtorNameEx = explode(",", $exportData[$k][01]['debtorName']);
			$debtorNameNew = $debtorNameEx[0].",".$debtorNameEx[1]." ".$debtorNameEx[2];
			$exportData[$k][01]['debtorName'] = $debtorNameNew;

			$debtorDobEx = explode("/", $exportData[$k][01]['debtorDob']);

			$debtorDobNew = $debtorDobEx[0].$debtorDobEx[1].substr($debtorDobEx[2],2,2);
			$exportData[$k][01]['debtorDob'] = $debtorDobNew;


				$exportData = $this->splitAddress($k, $exportData);

			//break up and reformat the dates
			$dateOfServiceYear = substr($exportData[$k][01]['dateOfService'], 2, 2);
			$dateOfServiceMonth = substr($exportData[$k][01]['dateOfService'], 4, 2);
			$dateOfServiceDay = substr($exportData[$k][01]['dateOfService'], 6, 2);
			$dateOfServiceNew = $dateOfServiceMonth.$dateOfServiceDay.$dateOfServiceYear;
			$exportData[$k][01]['dateOfService'] = $dateOfServiceNew;

			$listDateYear = substr($exportData[$k][01]['listDate'], 2, 2);
			$listDateMonth = substr($exportData[$k][01]['listDate'], 4, 2);
			$listDateDay = substr($exportData[$k][01]['listDate'], 6, 2);
			$listDateNew = $listDateMonth.$listDateDay.$listDateYear;
			$exportData[$k][01]['listDate'] = $listDateNew;
			$exportData[$k][01]['balance'] = str_replace(",", "", $exportData[$k][01]['balance']);
			$exportData[$k][01]['balance'] = str_replace(".", "", $exportData[$k][01]['balance']);
		}
		return $exportData;
	}
///////////////////////////////////////////////////////////////////////////////////
////this is the populate function for Y3210
////returns populated object
///////////////////////////////////////////////////////////////////////////////////
	function populateRecord_Y3210($lineEx, $recordNumber, $ssNumbers, $ssNumber, $controlNumber){
			if(strlen($lineEx)>0){
				$controlNo = $lineEx[0];
			}else{
				$controlNo = $controlNumber;
			}

			$controlNew = '';
			$controlArray = str_split($controlNo);
			foreach($controlArray as $k=>$v){
				if(ctype_alpha($v))$v = strtoupper($v);
				$controlNew .= $v;
			}
			$controlNo = $controlNew;
			$noteString = "";
			########################################################################################
			$this->exportData[$recordNumber][01]['debtorName'] = strtoupper($ssNumbers[$controlNo]['patlastname']).", ".strtoupper($ssNumbers[$controlNo]['patfirstname']);
			$this->exportData[$recordNumber][01]['debtorAddress1'] = strtoupper($ssNumbers[$controlNo]['pataddress1']);
			$this->exportData[$recordNumber][01]['debtorAddress2'] = strtoupper($ssNumbers[$controlNo]['pataddress2']);

			$this->exportData = $this->breakAddress($this->exportData, $recordNumber);
			$this->exportData[$recordNumber][01]['debtorCity'] = strtoupper($ssNumbers[$controlNo]['patcity']);
			$this->exportData[$recordNumber][01]['debtorState'] = $ssNumbers[$controlNo]['patstate'];
			$this->exportData[$recordNumber][01]['debtorZip'] = str_replace("-", "", $ssNumbers[$controlNo]['patzipcode']);
			$this->exportData[$recordNumber][01]['balance'] = $lineEx[29];
			$dateOfService = $this->checkDateWithSlashes($lineEx[28]);
			$this->exportData[$recordNumber][01]['dateOfService'] = str_replace("/", "", $dateOfService);
			$this->exportData[$recordNumber][01]['debtorDob'] = "";
			$this->exportData[$recordNumber][01]['debtorSs'] = str_replace("-", "", $ssNumbers[$controlNo]['patssn']);
			$rpName = strtoupper($ssNumbers[$controlNo]['grlastname']).", ".strtoupper($ssNumbers[$controlNo]['grfirstname']);

			if($rpName != $this->exportData[$recordNumber][01]['debtorName']){ $this->exportData[$recordNumber][01]['rpName'] = $rpName; }

			$this->exportData[$recordNumber][01]['rpSs'] = $ssNumber;
			$this->exportData[$recordNumber][01]['rpPhone'] = str_replace("-", "",$ssNumbers[$controlNo]['grphone']);
			$this->exportData[$recordNumber][01]['listDate'] = $this->checkDateWithSlashes($lineEx[27]);
			$this->exportData[$recordNumber][01]['dateLastPayment'] = $this->checkDateWithSlashes($lineEx[32]);
			$this->exportData[$recordNumber][02]['debtorPoe'] =  strtoupper($ssNumbers[$controlNo]['patemployername']);
			$this->exportData[$recordNumber][02]['debtorPoeCity'] =  $ssNumbers[$controlNo]['patemployercity'];
			$this->exportData[$recordNumber][02]['debtorPoeState'] =  $ssNumbers[$controlNo]['patemployerstate'];
			$this->exportData[$recordNumber][02]['debtorPoePhone'] = str_replace("-", "",$ssNumbers[$controlNo]['patworkphone']);

			//90 line for user defined data for the control number - this inserts data to the FACS window 103
			$this->exportData = $this->populateWindow($recordNumber, 103, "03", $controlNo);

			$noteString .= "Control No: ".$controlNo." | ";
			$noteString .= "Provider:  ".$lineEx[33].", ".$lineEx[34]." ";
			$this->exportData = $this->splitNotes($noteString, $recordNumber);
			return $this->exportData;
	}
/////////////////////////////////////////////////////////////////////////////
////evaluates the address lengths to see if the first line is more than a given set of characters.
////if it is longer than the 20 character FACS limit, the address should be split to 2 lines
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function breakAddress($thisObj, $recordNumber, $splitOnWords = 0){
		//echo "testing ".$thisObj[$recordNumber][01]['debtorName']."<br />";

		$lineBreakNumber = 20; //this is the character count where we want to break the address line
		$thisObj[$recordNumber][01]['debtorAddress1'] = trim($thisObj[$recordNumber][01]['debtorAddress1']);
		$thisObj[$recordNumber][01]['debtorAddress2'] = trim($thisObj[$recordNumber][01]['debtorAddress2']);

		if(strlen($thisObj[$recordNumber][01]['debtorAddress1']) > $lineBreakNumber && $splitOnWords == 0){
			$addySnippet = substr($thisObj[$recordNumber][01]['debtorAddress1'], 0 ,20);
			$restSnippet = substr($thisObj[$recordNumber][01]['debtorAddress1'], 20 , strlen($thisObj[$recordNumber][01]['debtorAddress1']) );
			$thisObj[$recordNumber][01]['debtorAddress1'] = $addySnippet;
			$thisObj[$recordNumber][01]['debtorAddress2'] = $restSnippet." ".$thisObj[$recordNumber][01]['debtorAddress2'];
		}elseif(strlen($thisObj[$recordNumber][01]['debtorAddress1']) > $lineBreakNumber && $splitOnWords == 1){ //someone
			$notesSplit = explode(" ", $thisObj[$recordNumber][01]['debtorAddress1']);
			//we need to loop through the word array to see where the words hit the character limit
			$characterCounter = 0; //this is the number of characters
			for($i=0; $i<count($notesSplit);$i++){
				$thisCount = 0;
				$thisCount = strlen($notesSplit[$i]);
				$characterCount += $thisCount;

					if($characterCount >= $lineBreakNumber){
						$wordToBreakAt = $i-1;
						//we've hit the limit so we need to reconstruct the word string up to the Nth character
							for($i1=0; $i1<=$wordToBreakAt; $i1++ ){
								$newLine1 .= $notesSplit[$i1]." ";
							} //end for
						//now put together the remainder of the second line to append to the second address line
							for($i2=$i1; $i2<=count($notesSplit); $i2++ ){
								$newLine2 .= $notesSplit[$i2]." ";
							} //end for
						$thisObj[$recordNumber][01]['debtorAddress1'] = $newLine1;
						$thisObj[$recordNumber][01]['debtorAddress2'] = $newLine2;
						break;
					} //end if
				$characterCount++; //remember that we have to count for the spaces so we need to add a charater count
			} //end for
		} //end if
		return $thisObj;
	} //end function
//////////////////////////////
//Y9155 - Columbia Basin Hospital specific functions
/////////////////////////////////////////////////////////////////////////////
////checks valid line
////returns true or false
/////////////////////////////////////////////////////////////////////////////
	function checkValidLine_Meditech($line, $clientid='Y9155'){
		//echo "checking $clientid<br />";
		$line = trim($line);
		$full = substr($line, 1, 9);
		$firstTwoChars = substr($line, 0, 2);
		$firstTenChars = substr($line, 0, 10);
		$lastSevenChars = substr($line, 2, 7);
		$valid = false;
		/*
		if($clientid=='Y9275'){
			if($firstTwoChars == "OS" || $firstTwoChars == "BS"){ //we're looking for "OS" followed by 7 digits to start populating a record
				if(is_numeric($lastSevenChars)){ $valid = true; }else{ $valid = false; }
			}else{
				$valid = false;
			} //end if
		}elseif($clientid=='Y3373'){
			$regex = "/[L]+\d+/"; //check for an 'L'followed by a number
			$valid = preg_match($regex, $firstTenChars); //check for the pattern
		}else{
			if($firstTwoChars == "CB"){ //we're looking for "CB" followed by 7 digits to start populating a record
				if(is_numeric($lastSevenChars)){ $valid = true; }else{ $valid = false; }
			}else{
				$valid = false;
			} //end if
		}*/

		switch($this->clientId){ //get the client id profile and react accordingly
			case "Y9275":
				if($firstTwoChars == "OS" || $firstTwoChars == "BS"){ //we're looking for "OS" followed by 7 digits to start populating a record
					if(is_numeric($lastSevenChars)){ $valid = true; }else{ $valid = false; }
				}else{
					$valid = false;
				} //end if
			break;
			case "Y3373":
				$regex = "/[A-Z]+\d+/"; //check for an 'L'followed by a number
				$valid = preg_match($regex, $firstTenChars); //check for the pattern
			break;
			default:
				if($firstTwoChars == "CB"){ //we're looking for "CB" followed by 7 digits to start populating a record
					if(is_numeric($lastSevenChars)){ $valid = true; }else{ $valid = false; }
				}else{
					$valid = false;
				} //end if
			break;
		}
		return $valid;
	}
/////////////////////////////////////////////////////////////////////////////
////checks for address and splits to separate variable components
////returns array
/////////////////////////////////////////////////////////////////////////////
	function checkIfAddress2_Meditech($addressToCheck){
			$aSplit = explode(",", $addressToCheck); //city
			$bSplit = explode(" ", trim($aSplit[1])); //state, zip

			switch($this->clientId){
				case "Y3373":
					if(strstr($addressToCheck,",")){ //if it contains a comma then it could be the second address line
						//if(strstr($bSplit[1], "-")){ $bSplit[2] = substr($bSplit[2],0,5); } //if the zip has a hyphen, strip it and use the first 5
						if(strstr($bSplit[1], "-")){ $bSplit[1] = substr($bSplit[1],0,5); } //if the zip has a hyphen, strip it and use the first 5

						if( strlen($bSplit[0])==2 && ( strlen($bSplit[1])==5 && is_numeric($bSplit[1]) || strlen($bSplit[2])==5 && is_numeric($bSplit[2]) ) ){ //this had to be expanded because they have a varying format for the poe which sometimes contains another space inbetween the state and zip
							$city = $aSplit[0];
							$state = $bSplit[0];
							if(strlen($bSplit[1])==5){ //we don't have the extra space
								$zip = $bSplit[1];
							}elseif(strlen($bSplit[2])==5){ //we do have the extra space
								$zip = $bSplit[2];
							}
							$rArray = array('cityStateZip'=>true, 'city'=>$city, 'state'=>$state, 'zip'=>$zip, 'address2'=>'');
							return $rArray;
						}else{
							$rArray = array('cityStateZip'=>false, 'city'=>'', 'state'=>'', 'zip'=>'', 'address2'=>$addressToCheck);
							return $rArray;
						} //end inner if
					}else{
						$rArray = array('error'=>true, 'cityStateZip'=>'', 'city'=>'', 'state'=>'', 'zip'=>'', 'address2'=>'');
						return $rArray;
					} //end outer if
				break;
				default:
					if(strstr($addressToCheck,",")){ //if it contains a comma then it could be the second address line
						if(strstr($bSplit[2], "-")){ $bSplit[2] = substr($bSplit[2],0,5); } //if the zip has a hyphen, strip it and use the first 5
						if( strlen($bSplit[0])==2 && strlen($bSplit[2])==5 && is_numeric($bSplit[2]) ){
							$city = $aSplit[0];
							$state = $bSplit[0];
							$zip = $bSplit[2];
							$rArray = array('cityStateZip'=>true, 'city'=>$city, 'state'=>$state, 'zip'=>$zip, 'address2'=>'');
							return $rArray;
						}else{
							$rArray = array('cityStateZip'=>false, 'city'=>'', 'state'=>'', 'zip'=>'', 'address2'=>$addressToCheck);
							return $rArray;
						} //end inner if
					}else{
						$rArray = array('error'=>true, 'cityStateZip'=>'', 'city'=>'', 'state'=>'', 'zip'=>'', 'address2'=>'');
						return $rArray;
					} //end outer if
				break;
			}
	}
/////////////////////////////////////////////////////////////////////////////
////checks for date format
////returns formatted date
/////////////////////////////////////////////////////////////////////////////
	function checkDateWithSlashes($date, $returnSlashes=1){
			$dExplode = explode("/", $date);
			for($i=0;$i<count($dExplode);$i++){
				if(strlen($dExplode[$i])==1)$dExplode[$i] = "0".$dExplode[$i];
				if(strlen($dExplode[$i])==4)$dExplode[$i] = substr($dExplode[$i],2,2);
			}
			if($returnSlashes==1) { 
				$dateToExport = $dExplode[0]."/".$dExplode[1]."/".$dExplode[2]; 
			}else{
				$dateToExport = $dExplode[0].$dExplode[1].$dExplode[2]; 

			}
			return $dateToExport;
	}
/////////////////////////////////////////////////////////////////////////////
////populates Y9083
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function populateRecord_Y9083($lineEx, $recordNumber){
		$this->exportData[$recordNumber][01]['debtorName'] = $lineEx[1];
		$this->exportData[$recordNumber][01]['debtorAddress1'] = $lineEx[5];
		$this->exportData[$recordNumber][01]['debtorAddress2'] = $lineEx[6];

		$this->exportData = $this->breakAddress($this->exportData, $recordNumber, 1);

		$this->exportData[$recordNumber][01]['debtorCity'] = $lineEx[7];
		$this->exportData[$recordNumber][01]['debtorState'] = $lineEx[8];
		$this->exportData[$recordNumber][01]['debtorZip'] = $lineEx[9];
		$this->exportData[$recordNumber][01]['balance'] = $lineEx[13];

		$this->exportData[$recordNumber][01]['rpName'] = $lineEx[4];
		$this->exportData[$recordNumber][01]['rpPhone'] = $lineEx[3];

		$dateOfService = $this->checkDateWithSlashes($lineEx[12]);
		$this->exportData[$recordNumber][01]['dateOfService'] = $dateOfService;
		$listDate = $this->checkDateWithSlashes($lineEx[11]);

		$this->exportData[$recordNumber][01]['listDate'] = $listDate;
		$this->exportData[$recordNumber][01]['debtorPhone'] = $lineEx[3];

		$this->exportData[$recordNumber][02]['debtorPoe'] = $lineEx[15];
		$this->exportData[$recordNumber][02]['debtorPoeAddress'] = $lineEx[16];
		$this->exportData[$recordNumber][02]['debtorPoeCity'] = $lineEx[18];
		$this->exportData[$recordNumber][02]['debtorPoeState'] = $lineEx[19];
		$this->exportData[$recordNumber][02]['debtorPoeZip'] = $lineEx[20];
		$this->exportData[$recordNumber][02]['debtorPoePhone'] = $lineEx[21];
		$noteString = $this->exportData[$recordNumber][03]["Note1"].$this->exportData[$recordNumber][03]["Note2"].$this->exportData[$recordNumber][03]["Note3"].$this->exportData[$recordNumber][03]["Note4"];
		$noteString .= "Ep#:".trim($lineEx[14])." ".str_replace("/", "", $lineEx[12])." ".$lineEx[13]."|";
		$this->exportData = $this->splitNotes($noteString, $recordNumber);
		return $this->exportData;
	}
/////////////////////////////////////////////////////////////////////////////
////populates Y6051
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function populateRecord_Y6051($lineEx, $recordNumber, $original){
			########################################################################################
			$this->exportData[$recordNumber][01]['debtorName'] = $lineEx[16].", ".$lineEx[17];
			$this->exportData[$recordNumber][01]['debtorAddress1'] = $lineEx[18];
			$this->exportData[$recordNumber][01]['debtorAddress2'] = $lineEx[19];

			///////////////////
			if(strlen($this->exportData[$recordNumber][01]['debtorAddress1']) > 20){
				$addySnippet = substr($this->exportData[$recordNumber][01]['debtorAddress1'], 0 ,20);
				$restSnippet = substr($this->exportData[$recordNumber][01]['debtorAddress1'], 20 , strlen($this->exportData[$recordNumber][01]['debtorAddress1']) );
				$this->exportData[$recordNumber][01]['debtorAddress1'] = $addySnippet;
				$this->exportData[$recordNumber][01]['debtorAddress2'] = $restSnippet." ".$this->exportData[$recordNumber][01]['debtorAddress2'];
			}
			///////////////////

			$this->exportData[$recordNumber][01]['debtorCity'] = $lineEx[20];
			$stateZip = explode(" ", $lineEx[21]);
			$this->exportData[$recordNumber][01]['debtorState'] = $stateZip[0];
			$zipEx = str_replace("-", "", $stateZip[2]);
			$this->exportData[$recordNumber][01]['debtorZip'] = $zipEx;

			$this->exportData[$recordNumber][01]['balance'] = number_format($lineEx[15], 2);

			$dateOfService = $this->checkDateWithSlashes($lineEx[11]);
			$this->exportData[$recordNumber][01]['dateOfService'] = $dateOfService;
			$this->exportData[$recordNumber][01]['debtorDob'] = $this->checkDateWithSlashes($lineEx[22]);

			$noteString = "";

			$this->exportData[$recordNumber][01]['rpPhone'] = str_replace("-", "", $lineEx[6]);
			$this->exportData[$recordNumber][01]['rpSs'] = str_replace("-", "", $lineEx[7]);

				if($lineEx[23]=="Y"){ //we have a minor - replace the RP info for the minor's
					$this->exportData[$recordNumber][01]['debtorAddress1'] = $lineEx[2];
					$this->exportData[$recordNumber][01]['debtorAddress2'] = $lineEx[3];
					$this->exportData[$recordNumber][01]['debtorCity'] = $lineEx[4];
					$this->exportData[$recordNumber][01]['debtorState'] = '';

					$this->exportData[$recordNumber][01]['rpName'] = $lineEx[0].", ".$lineEx[1];
					$this->exportData[$recordNumber][01]['rpPhone'] = str_replace("-", "", $lineEx[6]);
					$this->exportData[$recordNumber][01]['rpSs'] = str_replace("-", "", $lineEx[7]);

					/////
					$stateZip = explode(" ", $lineEx[5]);
					$zipEx = str_replace("-", "", $stateZip[2]);
					$this->exportData[$recordNumber][01]['debtorState'] = $stateZip[0];
					/////

					$this->exportData[$recordNumber][01]['debtorZip'] = $zipEx;
					if(strlen($lineEx[7])>0){
						$this->exportData[$recordNumber][01]['debtorSs'] = str_replace("-", "", $lineEx[0]);
					}
					$noteString = "PT DB:".$this->exportData[$recordNumber][01]['debtorDob']." | ";
				}

			$dateLastPayment = $this->checkDateWithSlashes($lineEx[12]);
			$this->exportData[$recordNumber][01]['dateLastPayment'] = $dateLastPayment;
			$this->exportData[$recordNumber][01]['listDate'] = $dateOfService;

			$this->exportData[$recordNumber][01]['debtorPhone'] = $lineEx[6];
			$noteString .= "Run Number: ".trim($lineEx[9])." | P/U: ".trim($lineEx[24],'"')." | DROP: ".trim($lineEx[25],'"');
			$this->exportData = $this->splitNotes($noteString, $recordNumber);
			return $this->exportData;
	}
/////////////////////////////////////////////////////////////////////////////
//this is the default behavior for splitting the notes - used if there is nothing custom happening there
//returns populated object with notes split up over the 4 lines
/////////////////////////////////////////////////////////////////////////////
function notesNormal($recordNumber, $noteString){
	$this->exportData[$recordNumber][03]["Note1"] = substr($noteString, 0, 58);
	$this->exportData[$recordNumber][03]["Note2"] = substr($noteString, 58, 59);
	$this->exportData[$recordNumber][03]["Note3"] = substr($noteString, 117, 59);
	$this->exportData[$recordNumber][03]["Note4"] = substr($noteString, 176, 59);
	return $this->exportData;
}
/////////////////////////////////////////////////////////////////////////////
////tests for note length and splits into separate lines if longer than given length
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function splitNotes($noteString, $recordNumber, $mode='exportData'){
		$noteString = trim($noteString);
		$noteString = str_replace('"', '', $noteString);
		$noteLength = strlen($noteString);
		$numToCheckAgainst = 58;
		$numSplit = number_format($noteLength/$numToCheckAgainst, 1);
		if($mode === 'exportData'){
			$dataToPopulate = $this->exportData;
		}elseif($mode === 'noteData'){
			$dataToPopulate = $this->noteData;
		}
		
		switch($this->clientId){
			case "Y3373": //we want to begin the RP POE data at line 2 of the notes if there is an rp poe and it differs from the debtor's listed poe
				if( trim(strlen($this->exportData[$recordNumber][01]['rpPoe'])) > 0 && trim($this->exportData[$recordNumber][02]['debtorPoeAddress']) != trim($this->exportData[$recordNumber][01]['rpPoeAddress'])){
					//the debtor poe and rp poe are different so put the rp poe in the notes
					$rpPoeNotes .= " RP POE: ".trim($this->exportData[$recordNumber][01]['rpPoe']);
					$rpPoeNotes .= " ".trim($this->exportData[$recordNumber][01]['rpPoeAddress']);
					$rpPoeNotes .= " ".trim($this->exportData[$recordNumber][01]['rpPoeCityData']);
					$rpPoeNotes .= " ".trim($this->exportData[$recordNumber][01]['rpPoePhone']);
					//get the length of notes to see if we need more than one line to put it on
					//if($numSplit < 1){
						$this->exportData[$recordNumber][03]["Note1"] = substr($noteString, 0, 58);
						$this->exportData[$recordNumber][03]["Note2"] = substr($rpPoeNotes, 0, 58);
						$this->exportData[$recordNumber][03]["Note3"] = substr($rpPoeNotes, 58, 59);
						$this->exportData[$recordNumber][03]["Note4"] = substr($rpPoeNotes, 117, 59);
					//}
				}else{ //we don't have a minor case, just load up the notes
					$this->exportData = $this->notesNormal($recordNumber, $noteString);
				}
			break;
			default:
				if($noteLength>$numToCheckAgainst){ //it's long enough that we have to split it over the lines
					$this->exportData = $this->notesNormal($recordNumber, $noteString); //nothing custom, just split it up
				}else{ //notes are short - don't split - just list out the notes
					//$this->$mode[$recordNumber][03]["Note1"] = $noteString;
					//$dataToPopulate[$recordNumber][03]["Note1"] = $noteString;
					//$this->exportData[$recordNumber][03]["Note1"] = $noteString;
					//print_r($this->$mode);
					if($mode == 'exportData') $this->exportData[$recordNumber][03]["Note1"] = $noteString;
					if($mode == 'noteData') $this->noteData[$recordNumber][03]["Note1"] = $noteString;
				}
			break;
		}
		//return $this->exportData;
		//return $this->$mode;
		//return $dataToPopulate;
		if($mode == 'exportData') return $this->exportData;
		if($mode == 'noteData') return $this->noteData;
	}
/////////////////////////////////////////////////////////////////////////////
////populates Y9155
////returns populated object
// accepts counter (interger), recordNumber(integer), medicareFlag(integer)
// returns populated data object
/////////////////////////////////////////////////////////////////////////////
	function populateRecord_Meditech($counter, $recordNumber, $medicareFlag=0, $clientId, $offset=0){
			########################################################################################
			//////////////////////////////////////////////////line 1
			$this->exportData[$recordNumber][01]['debtorName'] = str_replace(" NMI", "", substr($this->fileData[$counter],15,33));
			$this->exportData[$recordNumber][01]['debtorAddress1'] = strtoupper(trim(substr($this->fileData[$counter+1],15,33)));

			//check to see if we should take the city, state, zip info on the next line or skip to the next
			$lineToTest = $this->fileData[$counter+2];
			$fieldToTest =  substr($this->fileData[$counter+2],15,33);
			$thisIsAddressData = $this->checkIfAddress2_Meditech($fieldToTest);

				if($thisIsAddressData['cityStateZip']==true){ //this is the city, state, zip
					$this->exportData[$recordNumber][01]['debtorCity'] = strtoupper($thisIsAddressData['city']);
					$this->exportData[$recordNumber][01]['debtorState'] = strtoupper($thisIsAddressData['state']);
					$this->exportData[$recordNumber][01]['debtorZip'] = $thisIsAddressData['zip'];
					$linesToDrop = $counter+2;
				}else{ //if it's not address data, test the next line
					$fieldToTest2 =  substr($this->fileData[$counter+3],15,33);
					$thisIsAddressData2 = $this->checkIfAddress2_Meditech($fieldToTest2);

					if($thisIsAddressData2['cityStateZip']==true){ //this is the second line of address data
						$this->exportData[$recordNumber][01]['debtorAddress2'] = strtoupper($fieldToTest);

						if( strstr($this->exportData[$recordNumber][01]['debtorAddress2'], "MAIL RETURN" ) || strstr($this->exportData[$recordNumber][01]['debtorAddress2'], "MAI LRETURN" ) ){ //strip out the MAIL RETURN string from the address 2 if it exists
							$this->exportData[$recordNumber][01]['debtorAddress2'] = "";
						}

						$this->exportData[$recordNumber][01]['debtorCity'] = strtoupper($thisIsAddressData2['city']);
						$this->exportData[$recordNumber][01]['debtorState'] = strtoupper($thisIsAddressData2['state']);
						$this->exportData[$recordNumber][01]['debtorZip'] = $thisIsAddressData2['zip'];
						$linesToDrop = $counter+3;
					}

				}

			$this->exportData[$recordNumber][01]['debtorPhone'] = substr($this->fileData[$linesToDrop+1],15,33);
			$addOfficeNumberToNotes = ""; //this is a flag in case they have an office number listed to go into the notes
			$officeNumberTest = substr($this->fileData[$linesToDrop+2],15,33);
				if(strstr($officeNumberTest, "(O)")) $addOfficeNumberToNotes = "Other: ".preg_replace("/[^0-9,.]/", "", $officeNumberTest);
			$this->exportData[$recordNumber][01]['debtorSs'] = trim(substr($this->fileData[$counter+3],94,12));
				if(strstr($this->exportData[$recordNumber][01]['debtorSs'], "123456789")) $this->exportData[$recordNumber][01]['debtorSs'] = ""; //strip out the 123456789 string from the SS if it exists
			$this->exportData[$recordNumber][01]['dateOfService'] = substr($this->fileData[$counter+2],82,8);
				if(strlen(trim($this->exportData[$recordNumber][01]['dateOfService']))<1) $this->exportData[$recordNumber][01]['dateOfService'] = substr($this->fileData[$counter+1],82,8); //we have a date of service so take it

			$this->exportData[$recordNumber][01]['dateLastPayment'] = substr($this->fileData[$counter+1],94,12);
			$this->exportData[$recordNumber][01]['listDate'] = substr($this->fileData[$counter+2],82,8);
			$this->exportData[$recordNumber][01]['listDate'] = str_replace("/","",$this->exportData[$recordNumber][01]['listDate']);
				if(strlen($this->exportData[$recordNumber][01]['listDate']==0)) $this->exportData[$recordNumber][01]['listDate'] = substr($this->fileData[$counter+1],82,8);

			$this->exportData[$recordNumber][01]['balance'] = substr($this->fileData[$counter],119,13);
			if($this->clientId=="EBS15"){ $this->exportData[$recordNumber][01]['balance'] = substr($this->fileData[$counter],124,13); }
			//echo $this->exportData[$recordNumber][01]['balance']."<br />";

			$this->exportData[$recordNumber][01]['debtorDob'] = substr($this->fileData[$counter+3],82,8);
			/*--RP info---------------------------------------------------------------------------------*/
			//we need to account for whether they have the guar ss listed above the name and address info
			$rpLineOffsetVertically = $counter; //this is if we determine that we don't have SS info then we have to shift the line counter up one line to get the right info
			$guarLineToTest = substr($this->fileData[$counter],137 + $offset,19); //grab this chunk of text to look for a 'Guar SS#' listing
				if(!strstr($guarLineToTest, 'Guar #:')) $rpLineOffsetVertically--; //adjust the count since we don't have SS info listed

			$rpPoeNotes = "";
			$this->exportData[$recordNumber][01]['rpName'] = str_replace(" NMI", "", trim(substr($this->fileData[$rpLineOffsetVertically+1],137 + $offset,33)));
			$this->exportData[$recordNumber][01]['rpSs'] = trim(substr($this->fileData[$rpLineOffsetVertically],137 + $offset,19));
				if(strstr($this->exportData[$recordNumber][01]['rpSs'], "123456789")) $this->exportData[$recordNumber][01]['rpSs'] = ""; //strip out the 123456789 string from the SS if it exists

			$testWords = array("(FT)", "(PT)");
			$increaseIndex = 0; //this is to shift the info up or down based on the arrangement of the employer data which can vary
			$increaseAppend = ""; //this is a placeholder to add the fulltime or parttime status to append
				foreach($testWords as $k=>$v){
					$stringToTest = substr($this->fileData[$rpLineOffsetVertically],170 + $offset,33);
					if( strstr($stringToTest, $v) ) { $increaseIndex = 1; $increaseAppend = $v." "; } //we have to shift everything down one line since we found one of these header that we're looking for
				} //end for
			$ftTest = substr($this->fileData[$rpLineOffsetVertically],170 + $offset,33);
			//echo substr($this->fileData[$rpLineOffsetVertically + 1],170 + $offset,33)."<br />";
			//if( strstr("(FT)", substr($this->fileData[$rpLineOffsetVertically + 1],170 + $offset,33)) ){ }

			$this->exportData[$recordNumber][01]['rpPoe'] = $increaseAppend.trim(substr($this->fileData[$rpLineOffsetVertically + ($increaseIndex)],170 + $offset,33));
			$this->exportData[$recordNumber][01]['rpPoeAddress'] = strtoupper(trim(substr($this->fileData[$rpLineOffsetVertically + ($increaseIndex + 1)],170 + $offset,33)));
			$this->exportData[$recordNumber][01]['rpPoeCityData'] = strtoupper(trim(substr($this->fileData[$rpLineOffsetVertically + ($increaseIndex + 2)],170 + $offset,33)));

			for($i=1; $i<6; $i++){ //loop through the POE info, strip the nonnumeric chars and test the length to test for POE phone
						$lineToTestForPhone =  substr($this->fileData[$rpLineOffsetVertically+$i],137 + $offset,19); //capture the string
						$phoneTest = preg_replace("/[^0-9,.]/", "", $lineToTestForPhone);

						if(is_numeric($phoneTest) && strlen($phoneTest)==10){ //we have a phone number
							$this->exportData[$recordNumber][01]['rpPhone'] = $phoneTest;
							//since we know this is a phone number, we should test it to see if this is the home number
							if( !strstr($lineToTestForPhone,"(H)") ){ //set the home phone flag
								$this->exportData[$recordNumber][01]['workPhoneFlag'] = "N";
							}else{
								$this->exportData[$recordNumber][01]['workPhoneFlag'] = "Y";
							}
						}
			}//end for

			/*--RP info---------------------------------------------------------------------------------*/

				if( strlen(trim($this->exportData[$recordNumber][01]['debtorAddress1'])) >20 ){
					$addyFirst = substr( trim($this->exportData[$recordNumber][01]['debtorAddress1']), 0, 20);
					$addySecond = substr( trim($this->exportData[$recordNumber][01]['debtorAddress1']), 19, strlen(trim($this->exportData[$recordNumber][01]['debtorAddress1']))-20);
					$this->exportData[$recordNumber][01]['debtorAddress2'] = $addySecond.trim($this->exportData[$recordNumber][01]['debtorAddress2']);
				}
				if(strlen(trim($this->exportData[$recordNumber][01]['debtorAddress2']))>20){
					$addyFirst = substr( trim($this->exportData[$recordNumber][01]['debtorAddress2']), 0, 20);
					$addySecond = substr( trim($this->exportData[$recordNumber][01]['debtorAddress2']), 20, strlen($this->exportData[$recordNumber][01]['debtorAddress2'])-20);
					$this->exportData[$recordNumber][01]['debtorAddress2'] = $addyFirst;
					$this->exportData[$recordNumber][03]['Note1'] .= $addySecond;
				}
			//end line 1
			////////////////////////////////////////////////////
			//line 2
			////////////////////////////////////////////////////
			$noEmployer = array('UNEMPLOYED', 'RETIRED', 'DISABLED'); //these are the words we're looking for to determine if there is no POE info - if so, we should clear it
			$debtorPosition = trim(substr($this->fileData[$counter],48,34));
			$debtorPosition2 = substr($this->fileData[$counter+1],48,34);
			$clearEmploymentValues = 0; //set a flag to see if we need to clear a series of field in case this person is disabled or retired, etc

			foreach($noEmployer as $nk=>$nv){
				if(strstr($debtorPosition, $nv)){ $clearEmploymentValues = 1; $poeToUse = $debtorPosition; }
				if(strstr($debtorPosition2, $nv)){ $clearEmploymentValues = 1; $poeToUse = $debtorPosition2; }
			}


				//if( !in_array($debtorPosition, $noEmployer) &&  !in_array($debtorPosition2, $noEmployer) ){ //we have employment info so we should loop through and check the POE address
				if( $clearEmploymentValues == 0 ){ //we have employment info so we should loop through and check the POE address
					$this->exportData[$recordNumber][02]['debtorPoe'] = trim(substr($this->fileData[$counter+1],48,33));
					$this->exportData[$recordNumber][02]['debtorSalary'] = preg_replace('/\(|\)/','', trim(substr($this->fileData[$counter],48,33)));

					$testPoe = trim(substr($this->fileData[$rpLineOffsetVertically+1],170 + $offset,33));

					if(strlen($this->exportData[$recordNumber][02]['debtorPoe'])>0){
						$poeNotes = $this->exportData[$recordNumber][02]['debtorPoe']." "; //reset the poe notes bucket
					}

					$addressLineIndex = $this->findAddressLineIndex($counter, 48,34); //tests the info to see on which vertical line the address data starts on so we know where to begin processing it

					if(!is_null($addressLineIndex )){ //we found the address line - let's work from the index point to get the other info for the POE
						$this->exportData[$recordNumber][02]['debtorPoeAddress'] = trim(substr($this->fileData[$addressLineIndex],48,34));

						$poeNotes .= substr($this->fileData[$addressLineIndex],48,34);
						$poeFieldToTest2 =  substr($this->fileData[$addressLineIndex+1],48,34); //we're using the index we just returned for the address line to know where to work from
						$thisIsPoeAddressData2 = $this->checkIfAddress2_Meditech($poeFieldToTest2);

						if($thisIsPoeAddressData2['cityStateZip']==true){ //this is the second line of address data
							$this->exportData[$recordNumber][02]['debtorPoeCity'] = $thisIsPoeAddressData2['city'];
							$poeNotes .= " ".$thisIsPoeAddressData2['city'].",";
							$this->exportData[$recordNumber][02]['debtorPoeState'] = $thisIsPoeAddressData2['state'];
							$poeNotes .= " ".$thisIsPoeAddressData2['state']." ";
							$this->exportData[$recordNumber][02]['debtorPoeZip'] = $thisIsPoeAddressData2['zip'];
							$poeNotes .= $thisIsPoeAddressData2['zip']." ";
							$this->exportData[$recordNumber][02]['debtorPoePhone'] = substr($this->fileData[$addressLineIndex+2],48,34);
							$poeNotes .= substr($this->fileData[$addressLineIndex+2],48,34);
						} //end if

							//now clear our zero values
							if($this->exportData[$recordNumber][02]['debtorPoeAddress']==0) $this->exportData[$recordNumber][02]['debtorPoeAddress'] = ''; //clear this zero placeholder and reset this field
							if($this->exportData[$recordNumber][01]['rpPoeAddress']==0) $this->exportData[$recordNumber][01]['rpPoeAddress'] = ''; //clear this zero placeholder and reset this field

					}//end if

				}else{ //we don't have it so clear the POE values
					$this->exportData[$recordNumber][02]['debtorSalary'] = $poeToUse;
					$this->exportData[$recordNumber][02]['debtorPoe'] = "";
					$this->exportData[$recordNumber][02]['debtorPoeAddress'] = "";
					$this->exportData[$recordNumber][02]['debtorPoeCity'] = "";
					$this->exportData[$recordNumber][02]['debtorPoeState'] = "";
					$this->exportData[$recordNumber][02]['debtorPoeZip'] = "";
					$this->exportData[$recordNumber][02]['debtorPoePhone'] = "";
				} //end if
				$rpPoeNotes = ""; //container for rp data if it differs from the debtor's

			////////////////////////////////////////////////////
			//end line 2
			////////////////////////////////////////////////////
			########################################################################################
			$minorString = ""; //this is the placeholder for minor's birthday if applicable
			if($this->clientId == 'Y3373'){
				//build the age check for minors and put it here
				//echo "date check ".$this->exportData[$recordNumber][01]['debtorDob']." for minor line 901 clientFunctions<br />";
				if(strstr($this->exportData[$recordNumber][01]['debtorDob'], "/")){
					$age = $this->checkAge($this->exportData[$recordNumber][01]['debtorDob'], $this->exportData[$recordNumber][01]['listDate']);
					if($age < 18){
						$minorString .= " Minor age: ".$this->exportData[$recordNumber][01]['debtorDob']." | ";
						$this->exportData[$recordNumber][01]['debtorDob'] = "";
					}
				}
			}
			########################################################################################
			////////////////////////////////////////////////////
			//line 3
			////////////////////////////////////////////////////
				$this->exportData[$recordNumber][03]['Note1'] .= "Type:".substr($this->fileData[$counter],82,8);
				if(strlen($addOfficeNumberToNotes)>0) $this->exportData[$recordNumber][03]['Note1'] .= $addOfficeNumberToNotes;
				//$this->exportData[$recordNumber][03]['Note1'] .= $poeNotes;

				if($this->exportData[$recordNumber][01]['rpPoe'] != $this->exportData[$recordNumber][01]['debtorPoe'] && $this->exportData[$recordNumber][01]['rpPoeAddress'] != $this->exportData[$recordNumber][01]['debtorPoeAddress']){
					$addRpPoeNotesFlag = 1;
				}

				$noteString = $minorString; //holder for the notes we'll split
				if($medicareFlag == 1) $noteString .= " MEDICARE | ";
				$noteString .= "Type:".substr($this->fileData[$counter],82,8);
				#$noteString .= " | Orig Charge:".substr($this->fileData[$counter],106,13);
				if(strlen($addOfficeNumberToNotes)>0) $noteString .= $addOfficeNumberToNotes;
				$this->exportData = $this->splitNotes($noteString, $recordNumber); //now split the notes to fit on the 4 note lines in the FACS file
			////////////////////////////////////////////////////
			//end line 3
			////////////////////////////////////////////////////
			//90 window
			if($medicareFlag == 1){ //we need to populate the 90 window
				//90 line for user defined data for the control number - this inserts data to the FACS window 103
				$this->exportData = $this->populateWindow($recordNumber, 104, "04", "MEDICARE");
			}
			////////////////////////////////////////////////////

		return $this->exportData;
	}
/////////////////////////////////////////////////////////////////////////////
//this tests to see if someone is a minor or not
/////////////////////////////////////////////////////////////////////////////
function checkAge($thisAge, $listDate){
	$date = new DateTime($thisAge);
	$now = new DateTime($listDate);
	$interval = $now->diff($date);
	$age = $interval->y;
	return $age;
}
/////////////////////////////////////////////////////////////////////////////
////checks for the presence of the import file in the FILES array
////updates the interest FACS field if found
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function testForInterestFile(){
		if($this->accessType == "api"){
			$interestFile = $this->interestFileName;
		}else{
			$iName = basename($_FILES["uploadFile"]["name"][1]);
			$interestFile = "/home/nobody/".$_POST['clientId']."/".$iName;
		}
		//$interestFile = "C:\\xampp\\htdocs\\efs\\EBS15\\".$iName;
		if(file_exists($interestFile)){
			$thisInterestData = file($interestFile);
			$this->processInterestData($thisInterestData);
		}
		return $this->exportData;
	}
/////////////////////////////////////////////////////////////////////////////
////processes the interest data
////updates the interest FACS field if found
////returns populated object
/////////////////////////////////////////////////////////////////////////////
	function processInterestData($thisInterestData){
		$counter = 1;
		foreach($thisInterestData as $k=>$v){
			$interest = 0;
			$thisLine = explode(";", $v);
			$firstTwoChars = substr($v, 1, 2);
			$lastSevenChars = substr($v, 3, 7);

			if( $firstTwoChars == "CB" && is_numeric($lastSevenChars) ){
				$accountTest = $firstTwoChars.$lastSevenChars;
				$accountNumber = str_replace('"', "", $thisLine[0]);
				$balanceRaw = $thisLine[7];
				$interestRaw = $thisLine[8];
				$balanceToCheck = preg_replace('/\D/', "", $balanceRaw);
				$interest = preg_replace('/\D/', "", $interestRaw);

				if(array_key_exists($accountTest, $this->exportData) && ($balanceToCheck == $this->exportData[$accountTest][1]["balance"]) ){
					$this->exportData[$accountTest][1]["interest"] = $interest; //we've matched the account and balance so add the interest
				}

			}//end if
			$counter++;
		} //end for
	}
/////////////////////////////////////////////////////////////////////////////
////checks for valid line
////returns true or false
/////////////////////////////////////////////////////////////////////////////
	function checkValidLine_Y9155a($line){
		$firstTwoChars = substr($line, 0, 2);
		$lastSevenChars = substr($line, 2, 7);

		if($firstTwoChars == "CB"){ //we're looking for "CB" followed by 7 digits to start populating a record
			if(is_numeric($lastSevenChars)){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		} //end if
		//return $valid;
	}
	//////////////////////////
//////////////////////////////////////////////////////////////////////////////
function checkValidLine_VirginiaMason($record, $clientid){
	$firstFour = substr($record, 0, 4);
	if(strstr("EFS-", $firstFour)){ return 1; }else{ return 0;}
}
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
function populateRecord_VirginiaMason($key, $recordNumber, $clientId, $exportData, $record, $debtorDos){
	$testBalance = $record[70];
	if(!is_array($exportData[$recordNumber][3]['Note1'])) $exportData[$recordNumber][3]['Note1'] = array();
	$exportData[$recordNumber][1]['dateOfService'] = $debtorDos[1].$debtorDos[2].substr($debtorDos[0],2,2);
	if($testBalance > 0){
			$exportData[$recordNumber][1]['recordId'] = $recordNumber;
			$exportData[$recordNumber][1]['clientId'] = $clientId;
			$exportData[$recordNumber][1]['debtorName'] = strtoupper(str_replace(".", "", $record[55]));
			$exportData[$recordNumber][1]['debtorAddress1'] = strtoupper($record[59]);
			$exportData[$recordNumber][1]['debtorCity'] = strtoupper($record[45]);
			$exportData[$recordNumber][1]['debtorState'] = strtoupper($record[58]);
			$exportData[$recordNumber][1]['debtorZip'] = $record[60];
			$exportData[$recordNumber][1]['debtorSs'] = str_replace("-","", $record[57]);
			$exportData[$recordNumber][1]['debtorPhone'] = str_replace("1 (", "", $record[56]);
			$exportData[$recordNumber][1]['debtorPhone'] = preg_replace("/[^0-9,.]/", "", $exportData[$recordNumber][1]['debtorPhone']);
			if( !strstr($record[72], "NULL") && !strstr($record[74], "NULL") ){
				$exportData[$recordNumber][2]["spouseName"] = $record[74].",".$record[72];
				if(!strstr($record[73], "NULL")) $exportData[$recordNumber][2]["spouseName"] .= " ".$record[73];
				if(!strstr($record[75], "NULL")) $exportData[$recordNumber][2]["spousePhone"] = str_replace("-", "", $record[75]);
			}

			if($record[72] != "NULL" | $record[73] != "NULL" | $record[74] != "NULL"){
				$exportData[$recordNumber][2]['spouseName'] = strtoupper($record[74]);
				$exportData[$recordNumber][2]['spouseName'] .= ",".strtoupper($record[72]);
				if($record[73] !="NULL")$exportData[$recordNumber][2]['spouseName'] .= " ".strtoupper($record[73]);
			}

			$dobSplit = explode(" ", $record[46]);
			$debtorDob = explode("-", $dobSplit[0]);
			$exportData[$recordNumber][1]['debtorDob'] = $debtorDob[1].$debtorDob[2].substr($debtorDob[0],2,2);
			
			$rpSplit = explode(" ", $record[46]);
			$rpSplit = explode(" ", $record[29]);
			$rpDob = explode("-", $rpSplit[0]);
			$rpDob = $rpDob[1].$rpDob[2].substr($rpDob[0],2,2);

			///////////////////////////////////////////////////////
			$exportData[$recordNumber][1]['rpName'] = strtoupper(str_replace(".", "", $record[37]));
			$exportData[$recordNumber][1]['rpSs'] = str_replace("-","", $record[39]);
			$exportData[$recordNumber][1]['rpPhone'] = str_replace("1 (", "", $record[38]);
			$exportData[$recordNumber][1]['rpPhone'] = preg_replace("/[^0-9,.]/", "", $exportData[$recordNumber][1]['rpPhone']);
			////////////////////////////////////////////////////debtor POE info
			if(!strstr($record[48], "NULL")) $exportData[$recordNumber][2]['debtorPoe'] = strtoupper($record[48]);
			if(!strstr($record[51], "NULL"))$exportData[$recordNumber][2]['debtorPoeAddress'] = strtoupper($record[51]);
			if(!strstr($record[47], "NULL"))$exportData[$recordNumber][2]['debtorPoeCity'] = strtoupper($record[47]);
			if(!strstr($record[50], "NULL"))$exportData[$recordNumber][2]['debtorPoeState'] = strtoupper($record[50]);
			if(!strstr($record[52], "NULL"))$exportData[$recordNumber][2]['debtorPoeZip'] = $record[52];
			if($record[49] != "NULL"){ //this is the first call for the poe info from the debtor
				$exportData[$recordNumber][2]['debtorPoePhone'] = str_replace("1 (", "", $record[49]);
				$exportData[$recordNumber][2]['debtorPoePhone'] = preg_replace("/[^0-9,.]/", "", $exportData[$recordNumber][2]['debtorPoePhone']);
				if(strlen($exportData[$recordNumber][2]['debtorPoePhone'])>10) { $exportData[$recordNumber][2]['debtorPoePhone'] = substr($exportData[$recordNumber][2]['debtorPoePhone'], 1); }

				$exportData[$recordNumber][3]['Note1']['debtorPoe'] = $exportData[$recordNumber][1]['debtorDob'];
			}
			////////////////////////////////////////////////////
			////////////////////////////////////////////////////
			$debtorPoeInfo = ""; //flag for rp poe phone
			////////////////////////////////////////////////////
			////////////////////////////////////////////////////
			$rpFlag = 0; //this is a flag for later reference to know if we had to switch rp and patient info
			if($exportData[$recordNumber][1]['rpSs'] == $exportData[$recordNumber][1]['debtorSs']){
				$exportData[$recordNumber][1]['rpName'] = "";
				$exportData[$recordNumber][1]['rpSs'] = "";
				$exportData[$recordNumber][1]['rpPhone'] = "";
			}else{ //we need to switch the debtor and rp dob
				$rpFlag = 1;
				if(trim($record[48]) != "NULL")$debtorPoeInfo = "patient POE:".trim($record[48]);
				$exportData[$recordNumber][3]['Note1']['debtorDob'] = $exportData[$recordNumber][1]['debtorDob'];
				$exportData[$recordNumber][1]['debtorDob'] = $rpDob; //switch the date of birth

				////////////////////////////////////////////////////switch the POE info
				if(!strstr($record[32], "NULL")){ $exportData[$recordNumber][2]['debtorPoe'] = strtoupper($record[32]); }else{ $exportData[$recordNumber][2]['debtorPoe'] = ""; }
				if(!strstr($record[35], "NULL")){ $exportData[$recordNumber][2]['debtorPoeAddress'] = strtoupper($record[35]); }else{ $exportData[$recordNumber][2]['debtorPoeAddress']=""; }
				if(!strstr($record[31], "NULL")){ $exportData[$recordNumber][2]['debtorPoeCity'] = strtoupper($record[31]); }else{ $exportData[$recordNumber][2]['debtorPoeCity']=""; }
				if(!strstr($record[34], "NULL")){ $exportData[$recordNumber][2]['debtorPoeState'] = strtoupper($record[34]); }else{ $exportData[$recordNumber][2]['debtorPoeState']=""; }
				if(!strstr($record[36], "NULL")){ $exportData[$recordNumber][2]['debtorPoeZip'] = $record[36]; }else{ $exportData[$recordNumber][2]['debtorPoeZip']=""; }

				if($record[33] != "NULL"){ //we're switching the poe number with the rp info - put the debtor poe info in the notes
					$debtorPoeInfo .= " ".trim($record[49]);
					$exportData[$recordNumber][2]['debtorPoePhone'] = str_replace("1 (", "", $record[33]);
					$exportData[$recordNumber][2]['debtorPoePhone'] = preg_replace("/[^0-9,.]/", "", $exportData[$recordNumber][2]['debtorPoePhone']);
					if(strlen($exportData[$recordNumber][2]['debtorPoePhone'])>10) { $exportData[$recordNumber][2]['debtorPoePhone'] = substr($exportData[$recordNumber][2]['debtorPoePhone'], 1); }
				}
				////////////////////////////////////////////////////
			}
			///////////////////////////////////////////////////////
			if($record[9] != "NULL") $exportData[$recordNumber][1]['listDate'] = $record[9];
			$exportData[$recordNumber][1]['balance'] = str_replace(".", "", $record[70]); //remove the decimal
			$dateToTestAgainst = strtotime("2013-06-04"); //date to test against
			$dateServiceToTest = strtotime("20".substr($exportData[$recordNumber][1]['dateOfService'], 4,2)."-".substr($exportData[$recordNumber][1]['dateOfService'], 0,2)."-".substr($exportData[$recordNumber][1]['dateOfService'], 2,2));
			
			if($dateServiceToTest <= $dateToTestAgainst){ //this occurance happened before 6/13/2013 so it needs to be set to 9655
				$exportData[$recordNumber][1]['clientId'] = "Y9655";
			}

			//we need to add a couple of variables to store the charge, adjustments, and total values to assemble the notes later so we don't repeat ourselves in the notes
			$exportData[$recordNumber][3]['Note1']['guarantorId'] = $record[30];
			$exportData[$recordNumber][3]['Note1']['treatmentType'] = $record[27];

				if(!strstr($record[14], "NULL")){ //this is the insurance company payer - see if we already have it on file - if we don't then concatenate it
					if(strlen($exportData[$recordNumber][3]['Note1']['insCompany'])>0){
						if( !strstr($exportData[$recordNumber][3]['Note1']['insCompany'], trim($record[14])) ) $exportData[$recordNumber][3]['Note1']['insCompany'] .= " ".trim($record[14]);
					}else{
						$exportData[$recordNumber][3]['Note1']['insCompany'] = trim($record[14]);
					}
				}

			if($record[44] == 1) $exportData[$recordNumber][3]['Note1']['deceased'] = 1;
			$exportData[$recordNumber][3]['Note1']['gender'] = $record[53];
			$exportData[$recordNumber][3]['Note1']['charges'] = $record[18];
			$exportData[$recordNumber][3]['Note1']['adjs'] = $exportData[$recordNumber][3]['Note1']['adjs'] + $record[69];
			$exportData[$recordNumber][3]['Note1']['pmts'] = $exportData[$recordNumber][3]['Note1']['pmts'] + $record[71];
			$exportData[$recordNumber][3]['Note1']['debtor_poe'] = $exportData[$recordNumber][3]['Note1']['debtor_poe'] + $record[71];
			$exportData[$recordNumber][3]['Note1']['text'] = $exportData[$recordNumber][3]['Note1']['text']."|".$record[68]." ".$exportData[$recordNumber][1]['dateOfService']." org chg ".$record[18]." ttl bal ".$record[17]." ttl adj ".$record[69];
	}else{ //this for accounts that do not have the balance - this is where we populate the insurance info for a given debtor
			$exportData[$recordNumber][3]['Note1']['text'] =  $exportData[$recordNumber][3]['Note1']['text']."|".$record[68]." ".$dateOfService." org chg ".$record[18]." ttl bal ".$record[17]." ttl adj ".$record[69];

			if(!strstr($record[14], "NULL")){ //this is the insurance company payer - see if we already have it on file - if we don't then concatenate it
				if(strlen($exportData[$recordNumber][3]['Note1']['insCompany'])>0){
					if( !strstr($exportData[$recordNumber][3]['Note1']['insCompany'], trim($record[14])) ){
						$exportData[$recordNumber][3]['Note1']['insCompany'] = $exportData[$recordNumber][3]['Note1']['insCompany']." ".trim($record[14]);
					}
				}else{
					$exportData[$recordNumber][3]['Note1']['insCompany'] = trim($record[14]);

				}
			}

			if($record[44] == 1) $exportData[$recordNumber][3]['Note1']['deceased'] = 1;
			$exportData[$recordNumber][3]['Note1']['adjs'] = $exportData[$recordNumber][3]['Note1']['adjs'] + $record[69];
			$exportData[$recordNumber][3]['Note1']['pmts'] = $exportData[$recordNumber][3]['Note1']['pmts'] + $record[71];
	}
	$exportData[$recordNumber][3]['Note1']['encounterName'] = $record[26];
	$exportData[$recordNumber][3]['Note1']['encounterLocation'] = $record[25];
		if(strlen($debtorPoeInfo)>0){
					$exportData[$recordNumber][3]['Note1']['debtorPoeInfo'] = $debtorPoeInfo;
		}
	if( strstr($record[14], "Medicare") ) $exportData[$recordNumber][3]["Medicare"] = 1;
	$exportData = $this->splitAddress($recordNumber, $exportData);
	return $exportData;
}
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
function processXml($clientId, $xmlFileToOpen){
	$xml=simplexml_load_file($xmlFileToOpen) or die("Error: Cannot create object");
	$c = count($xml->Batch->Account);
	if($clientId == "Y3432"){
		for($i=0;$i<$c; $i++){
			$recordNumber = (integer) $xml->Batch->Account[$i]->AccountNum[0];
			$exportData[$recordNumber][01]['recordId'] = (integer) $xml->Batch->Account[$i]->AccountNum[0];
			$exportData[$recordNumber][02]['recordId'] = $exportData[$recordNumber][1]['recordId'];
			$exportData[$recordNumber][03]['recordId'] = $exportData[$recordNumber][1]['recordId'];
			$exportData[$recordNumber][04]['recordId'] = $exportData[$recordNumber][1]['recordId'];
			$exportData[$recordNumber][05]['recordId'] = $exportData[$recordNumber][1]['recordId'];
			$exportData[$recordNumber][06]['recordId'] = $exportData[$recordNumber][1]['recordId'];
			$exportData[$recordNumber][01]['clientId'] = $clientId;
			$exportData[$recordNumber][01]['debtorNumber'] = $exportData[$recordNumber][1]['recordId'];
			$exportData[$recordNumber][01]['debtorName'] = (string) $xml->Batch->Account[$i]->Guarantor[0];
			$exportData[$recordNumber][01]['debtorAddress1'] = (string) $xml->Batch->Account[$i]->GuarantorAddress1[0];
			$exportData[$recordNumber][01]['debtorCity'] = (string) $xml->Batch->Account[$i]->GuarantorCityStateZip[0];
			$exportData[$recordNumber][01]['debtorState'] = "";
			$exportData[$recordNumber][01]['debtorZip'] = "";
			$exportData[$recordNumber][01]['debtorSs'] = (integer) $xml->Batch->Account[$i]->GuarantorSSN[0];
			$exportData[$recordNumber][01]['debtorPhone'] = (string) $xml->Batch->Account[$i]->GuarantorPhone[0];
			$exportData[$recordNumber][01]['debtorPhone'] = preg_replace("/[^0-9,.]/", "", $exportData[$recordNumber][1]['debtorPhone']);
			$exportData[$recordNumber][01]['rpSs'] = $exportData[$recordNumber][1]['debtorSs'];
			$exportData[$recordNumber][01]['rpPhone'] = $exportData[$recordNumber][1]['debtorPhone'];
			$exportData[$recordNumber][01]['debtorDob'] = (string) $xml->Batch->Account[$i]->GuarantorDOB[0];
			$exportData[$recordNumber] = $this->calculateBalance3420($xml->Batch->Account[$i]->ServiceLine, $exportData[$recordNumber], $recordNumber);
			$exportData[$recordNumber][01]['balance'] = number_format($exportData[$recordNumber][1]['balance'], 2);
			$exportData[$recordNumber][01]['balance'] = (string) str_replace(".","",$exportData[$recordNumber][1]['balance']);
			$exportData[$recordNumber] = $this->checkY3432Poe($exportData[$recordNumber], $xml->Batch->Account[$i]);
		}
	}
	return $exportData;
}
///////////////////////////////////////////////////////////
//this is to combine the balances in the event of multiple service $linesToDrop
//returns updated array with balance field populated
///////////////////////////////////////////////////////////
function calculateBalance3420($serviceArray, $exportData, $recordNumber){
	$minorFound = 0; //flag to tell if we've found a minor on this account - if so, put them in the notes
	$mostRecentDate = "0"; //this is the placeholder for the date evaluation to see which is the most recent
	$minorstring = ""; //this is a string of text to be prepended to the notes if a minor is found
	$notestring = ""; //this is the overall note string we will ultimately split

	foreach($serviceArray as $k=>$v){ //we could have multiple service lines for each visit so we need to loop through it
		$exportData[01]['balance'] += (float) $v->Balance;
		$dateToCheck = (string) $v->DOS; //this is the date that we'll check to see if it is more recent
		$serviceDate = strtotime($dateToCheck); //make a timestamp

			if($serviceDate > $mostRecentDate){ //do a date comparison to get the most recent
				$exportData[01]['dateOfService'] = $dateToCheck; //set thus as the date since it's more recent
				$mostRecentDate = $serviceDate; //update the flag for next comparison
			}

		$notesBalance = number_format((float) $v->Balance, 2); //placeholder
		$notestring .= $v->DOS." Provider: ".$v->Provider." ".$notesBalance." | ";

			if($exportData[01]['debtorSs'] != $v->PatientSSN){ //we have a minor so we need to adjust the debtor/RP info

				$exportData[01]['rpName'] = $exportData[01]['debtorName'];
				$exportData[01]['rpPhone'] = $exportData[01]['debtorPhone'];
				$exportData[01]['rpSs'] = $exportData[01]['debtorSs'];
				$exportData[01]['debtorSs'] = $v->PatientSSN;
				$exportData[01]['debtorPhone'] = ""; //reset this because we don't get phone info on the service lines
				$exportData[01]['debtorName'] = $v->Patient;
				if(strlen($minorstring)==0)$minorstring .= "MINOR: ".$v->Patient." DOB:".$v->PatientDOB." | "; //do this once
			}else{ //this is an adult so we don't need to have duplicate info - reset the RP fields
				$exportData[01]['rpPhone'] = '';
				$exportData[01]['rpName'] = '';
				$exportData[01]['rpSs'] = '';
			}
	}
	$exportData[03]['Note1'] = $minorstring.$notestring;
	return $exportData;
}
///////////////////////////////////////////////////////////
//loops through 3432 data to check for the presence of employer info
//returns updated array with POE info if present
///////////////////////////////////////////////////////////
function checkY3432Poe($exportData, $accountInfo){
	if(strlen($accountInfo->Employer)>0){ //we have an employer name - update the export array
		$exportData[02]["debtorPoe"] = (string) $accountInfo->Employer;
		if(strlen($accountInfo->EmployerAddress)>0){ //now check for address
			$exportData[02]["debtorPoeAddress"] = (string) $accountInfo->EmployerAddress;
		}
		if(strlen($accountInfo->EmployerCityStateZip)>0 && strstr($accountInfo->EmployerCityStateZip, ", ,")){ //now check for city state zip
			$exportData[02]["debtorPoeCity"] = (string) $accountInfo->EmployerCityStateZip;
		}
		if(strlen($accountInfo->EmployerPhone)>0){ //now check for phone
			$exportData[02]["debtorPoePhone"] = (string) $accountInfo->EmployerPhone;
		}
	}
	return $exportData;
}
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
function massageY3432Data(){
	foreach($this->exportData as $k=>$v){
		//strip chars from balance
		$this->exportData[$k][01]['balance'] = str_replace(array('.', ','), '' , $this->exportData[$k][01]['balance']);
		//break notes into separate lines
		$this->exportData = $this->splitAddress($k, $this->exportData);
		if($this->exportData[$k][01]["debtorSs"] == $this->exportData[$k][01]["rpSs"]) $this->exportData[$k][01]["rpSs"] = ""; //strip the RP ss if it is the same as the debtor's
		//strip whatever non-numeric phone chars
		$this->exportData[$k][01]["debtorPhone"] = preg_replace("/[^0-9,.]/", "", $this->exportData[$k][01]["debtorPhone"]);
		$this->exportData[$k][02]["debtorPoePhone"] = preg_replace("/[^0-9,.]/", "", $this->exportData[$k][02]["debtorPoePhone"]);
		$this->exportData[$k][01]["rpPhone"] = preg_replace("/[^0-9,.]/","", $this->exportData[$k][01]["rpPhone"]);

		//break out address city, state, zip
		$addyData = explode(",", $this->exportData[$k][1]["debtorCity"]);
		$this->exportData[$k][01]["debtorCity"] = $addyData[0];
		$this->exportData[$k][01]["debtorState"] = $addyData[1];
		$this->exportData[$k][01]["debtorZip"] = $addyData[2];
		//format the dates
		$tempDob = explode("/", $this->exportData[$k][01]["debtorDob"]);
		$this->exportData[$k][01]["debtorDob"] = $tempDob[0].$tempDob[1].substr($tempDob[2], 2, 2);

		$tempDateOfService = explode("/", $this->exportData[$k][1]["dateOfService"]);
		$this->exportData[$k][01]["dateOfService"] = $tempDateOfService[0].$tempDateOfService[1].substr($tempDateOfService[2], 2, 2);
			//check for poe info to break out
			if(strlen($this->exportData[$k][01]["debtorPoeCity"])>0){ //we have poe info so we need to split the city state zip
				$addyData = explode(",", $this->exportData[$k][01]["debtorPoeCity"]);
				$this->exportData[$k][01]["debtorPoeCity"] = $addyData[0];
				$this->exportData[$k][01]["debtorPoeState"] = $addyData[1];
				$this->exportData[$k][01]["debtorPoeZip"] = $addyData[2];
			}
			$this->exportData = $this->splitNotes($this->exportData[$k][03]["Note1"], $k); //now split the notes to fit on the 4 note lines in the FACS file

	}
	return $this->exportData;
}
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
//end of class
}
///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////
?>
