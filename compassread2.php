<?php 
include_once 'config/core.php';
include_once './jwt/BeforeValidException.php';
include_once './jwt/ExpiredException.php';
include_once './jwt/SignatureInvalidException.php';
include_once './jwt/JWT.php';
use \Firebase\JWT\JWT;





const DATE_FORMAT = '!d M Y';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");


$curl = curl_init();

$path = $_SERVER['REQUEST_URI'];
$parameters = $_POST;

//---------------- generic cURL settings start ----------------
$header     = array(
      "Referer: https://compass.scouts.org.uk/login/User/Login",
"Origin: https://mywordpress",
"Content-Type: application/x-www-form-urlencoded",
"Cache-Control: no-cache",
"Pragma: no-cache",
"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.5 Safari/605.1.15"
      );


curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.5 Safari/605.1.15');
curl_setopt($curl, CURLOPT_AUTOREFERER, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($curl, CURLOPT_COOKIESESSION, true);
curl_setopt($curl, CURLOPT_COOKIEFILE, 'cookies.txt');
curl_setopt($curl, CURLOPT_COOKIEJAR, 'cookies.txt');
//---------------- generic cURL settings end ----------------



$url = 'https://compass.scouts.org.uk/Login.ashx';
curl_setopt($curl, CURLOPT_URL, $url);
 
$error="Success";
$post = "EM=".$_POST['userid']."&PW=".$_POST['password']."&ON=10000001";
$userid = $_POST['userid'];
$password = $_POST['password'];
curl_setopt($curl, CURLOPT_POST, TRUE);
curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

$output = curl_exec($curl);
$info = curl_getinfo($curl);
//echo($output);
if(!strpos($output, "Compass - System Startup")){$error="Failure";};
if ($error!="Failure") {
$url = 'https://compass.scouts.org.uk/MemberProfile.aspx';
curl_setopt($curl, CURLOPT_URL, $url);
$output2 = curl_exec($curl);

$pos = strpos($output2, '~Page.UseCN#');
$ss = substr ( $output2 , $pos+11,20 );
$ss= substr ($ss, 1, strpos($ss,"~")-1);
$userid = $ss;
//echo($userid);
//$userid="122875";


$url = 'https://compass.scouts.org.uk/MemberProfile.aspx?CN='.$userid.'&Page=TRAINING&TAB';
curl_setopt($curl, CURLOPT_URL, $url);
$output = curl_exec($curl);

$body = new DOMDocument();
//		libxml_use_internal_errors(true);
		$body->loadHTML($output);
//		libxml_clear_errors();

	$xpath = new DOMXPath($body);
$obj = new stdClass();
$data = array();
$role = array();
$plp = array();
$plpline =array();
$plptable = array();
$mandatetable = array();
$mandateline = array();
$mandate = array();

	$rows = $xpath->query('//table[@id="tbl_p5_TrainModules"]/tr');
 
foreach ($rows as $rowPlp) {
    
			if ($rowPlp->getAttribute('class') == 'msTR') {
			    $role['id'] = $rowPlp->getAttribute('data-ng_mrn');
			    $role['title'] = $rowPlp->childNodes->item(0)->textContent;
			    $role['location'] =  $rowPlp->childNodes->item(3)->textContent;
			    $role['status'] =  $rowPlp->childNodes->item(2)->textContent;
			    $ta = explode(' ', $rowPlp->childNodes->item(4)->textContent);
				$role['ta_number'] = array_shift($ta);
				$role['ta_name'] = implode(' ', $ta);
			    $role['pta'] =  $rowPlp->childNodes->item(4)->textContent;
			    $role['completed'] =  $rowPlp->childNodes->item(5)->textContent;
			    $matches = [];
				if (preg_match('/^(.+) : (.+)$/', $rowPlp->childNodes->item(5)->textContent, $matches))
				{
					$role['completiontype'] = $matches[1];
		     		$role['completiondate'] = DateTimeImmutable::createFromFormat(DATE_FORMAT, $matches[2])->format('Y-m-d');
		     		$role['ct'] =  $matches[2];
				}
			    $role['woodbadge'] =  $rowPlp->childNodes->item(5)->getAttribute('id');
			    $role['status'] =  $rowPlp->childNodes->item(2)->textContent;
			    $role['datefrom'] = DateTimeImmutable::createFromFormat(DATE_FORMAT, $rowPlp->childNodes->item(1)->textContent)->format('Y-m-d');
			    
		//	    $data[$rowPlp->getAttribute('data-ng_mrn')] =  $role; //($rowPlp->childNodes->item(0)->textContent);
		$data[] =  $role; //($rowPlp->childNodes->item(0)->textContent);
			}
			elseif (strstr($rowPlp->getAttribute('class'), 'trPLP') !== false) {
			    $plpTable = $rowPlp->childNodes[0]->childNodes[0];
			    if ($plpTable->getAttribute('data-pk') != $plp->roleNumber)
				{
				    // Possible Error
				}
				$plptable = [];
				foreach ($plpTable->childNodes as $moduleRow)
				{
				    $plpline = [];
				    if (get_class($moduleRow) != DOMElement::class) continue;
				    if ($moduleRow->getAttribute('class') == 'msTR trMTMN')
					{
					    $plpline['pk']= $moduleRow->getAttribute('data-pk'); 
					    $plpline['m']= substr($moduleRow->childNodes[0]->getAttribute('id'), 4);
					
					$matches = [];
						if (preg_match('/^([A-Z0-9]+) - (.+)$/', $moduleRow->childNodes->item(0)->textContent, $matches))
						{
							$plpline['code'] = $matches[1];
							$plpline['name'] = $matches[2];
						}
						$plpline['learningrequired'] = ($moduleRow->childNodes->item(1)->textContent == 'Yes');
						$plpline['learningmethod'] = $moduleRow->childNodes->item(2)->textContent;
						$plpline['learningmethod'] = $moduleRow->childNodes->item(2)->textContent;
						if(DateTimeImmutable::createFromFormat(DATE_FORMAT, $moduleRow->childNodes->item(3)->textContent)){
						$plpline['learningdate'] = DateTimeImmutable::createFromFormat(DATE_FORMAT, $moduleRow->childNodes->item(3)->textContent)->format('Y-m-d');
						}
						$ta = explode(' ', $moduleRow->childNodes->item(4)->textContent);
						$plpline['validatedMembershipNumber'] = array_shift($ta);
						$plpline['validatedName'] = implode(' ', $ta);
						if(DateTimeImmutable::createFromFormat(DATE_FORMAT,$moduleRow->childNodes->item(5)->textContent)) {
						$plpline['validatedDate'] = DateTimeImmutable::createFromFormat(DATE_FORMAT, $moduleRow->childNodes->item(5)->textContent)->format('Y-m-d');
						}
						 
					$plptable[] = $plpline;
					}
				}    
				$plp[$plpTable->getAttribute('data-pk')] = $plptable;	
				
			}
			
			
             
}

$rows = $xpath->query('//table[@id="tbl_p5_TrainOGL"]/tr');
foreach ($rows as $rowMog) {
	$matches = [];
	$mandateline = [];
	if (preg_match('/SubFoldOGLdata_([A-Z0-9]+)/', $rowMog->getAttribute('class'), $matches)) {
				
				$mandateline['mandCode'] = $matches[1];
				foreach ($rowMog->childNodes as $cell)
				{
				
					if ($cell->getAttribute('class') == 'tdData' && $cell->firstChild->nodeName == 'label')
					{
						$matches = [];
						if (preg_match('/^([A-Z0-9]+) - (.*+)$/', $cell->firstChild->textContent, $matches))
						{
						$mandateline['linkedModuleCode'] = $matches[1];
						$mandateline['linkedModuleLabel'] = $matches[2];
						}
					}
					elseif (strstr($cell->getAttribute('class'), 'OGLComp_'.	$mandateline['mandCode']) !== false)
					{
						$mandateline['date'] = DateTimeImmutable::createFromFormat(DATE_FORMAT, $cell->textContent)->format('Y-m-d');;
					}
					elseif (strstr($cell->getAttribute('class'), 'OGLRenew_'.$mandateline['mandCode']) !== false)
					{
						$mandateline['expiry'] = DateTimeImmutable::createFromFormat(DATE_FORMAT, $cell->textContent)->format('Y-m-d');
					}
					elseif ($cell->firstChild->nodeName == 'input')
					{
						$mandateline['pk'] = $cell->firstChild->getAttribute('data-pk');
					}
				}

				$mandatetable[] = $mandateline;
			}
		}	    
	
$url = 'https://compass.scouts.org.uk/MemberProfile.aspx?CN='.$userid.'&Page=PERMITS&TAB';
curl_setopt($curl, CURLOPT_URL, $url);
$output = curl_exec($curl);	 


$body = new DOMDocument();
$body->loadHTML($output);
$xpath = new DOMXPath($body);
$rows =  $xpath->query('//table[@id="tbl_p4_permits"]//tr');
$permits = [];
$permit = [];

foreach ($rows as $rowper) {
   
    	if ($rowper->getAttribute('class') == 'msTR msTRPERM') {
    	    $permit['permittype'] = $rowper->childNodes->item(1)->textContent;
    	    $permit['category'] = $rowper->childNodes->item(2)->textContent;
    	     $permit['type'] = $rowper->childNodes->item(3)->textContent;
    	    $permit['restrictions'] = $rowper->childNodes->item(4)->textContent;
            //$permit['expires'] = $rowper->childNodes->item(5)->textContent;
    	    $permit['expires'] = DateTimeImmutable::createFromFormat(DATE_FORMAT, $rowper->childNodes->item(5)->textContent)->format('Y-m-d');
    	    $permit['status'] = $rowper->childNodes->item(5)->getAttribute('class');
    	    $permits[] = $permit;
    	}
    
}
$option=[];
$hierarchy=[];
$hierarchies=[];
foreach ($data as $role) {
$url = 'https://compass.scouts.org.uk/Popups/Profile/AssignNewRole.aspx?VIEW='.$role['id'];
 
curl_setopt($curl, CURLOPT_URL, $url);
$output = curl_exec($curl);	 
$body = new DOMDocument();
$body->loadHTML($output);
$xpath = new DOMXPath($body);
 
$rows =  $xpath->query("//select");
$hierarchy=[];
foreach ($rows as $rowper) {
    if (strpos($rowper->getAttribute('name'), 'ctl00$workarea$cbo_p1_location_')!==false){
    $hier=[];
       $option[]=$rowper->getAttribute('id');
       $option[]=$rowper->getAttribute('name');
       $option[]=$rowper->getAttribute('title');
       $hier['level'] = $rowper->getAttribute('title');
       $hier['tech'] = $rowper->getAttribute('name');
       $children = $xpath->query("//select[@name='".$rowper->getAttribute('name')."']/option/@value");
       $children2 = $xpath->query("//select[@name='".$rowper->getAttribute('name')."']/option");
      foreach ($children2 as $child  => $value ) {
         $option[]=$children[$child]->textContent;
         $option[]=$value->textContent;
          $hier['key'] = $children[$child]->textContent;
          $hier['description'] = $value->textContent;
       }
       $hierarchy[]=$hier;
    }
}
$hierarchies[$role['id']] = $hierarchy;
}
$obj->roles = $data;
$obj->plps = $plp;
$obj->mandate = $mandatetable;
$obj->permits = $permits;
$obj->hierarchies = $hierarchies;

}
 
echo json_encode(array(
                "message" => $error,
                "object" => $obj
               
            ));
 

curl_close ($curl);
 
?>
