<?php
###########################################################################################
## Sample Code (simple) using this script
###########################################################################################
#
# This script should be pretty self explainitory.  Copy this section of code
# into another file in the same directory as ups.class.php (this file) to test
# it out.  be sure you fill in your $AccessLicenseNumber, as well as your UserID and Password 
# 
#


    // include the UPS Script.
    require_once('ups.class.php');
 
 
    // Create an opject of type ups
    //$MyUPS = new ups();
    
    // Don't want to update the class to set your AccessLicenseNumber, UserID or Password? Pass them in
    $debug = FALSE;
    $AccessLicenseNumber = '';
    $UserId = '';
    $Password = '';
    $MyUPS = new ups($debug, FALSE, $AccessLicenseNumber, $UserId, $Password);
 
    // set shipper info
    $MyUPS->SetShipper('Springfield','MO','65807','US');
     
    // uncomment this if you ship from somewhere other than the address you 
    // registered your key to.
    //$MyUPS->SetShipFrom('Springfield','MO','65807','US');  
 
    // Set the Ship To address.  This will probably be gleaned from the user
    $MyUPS->SetShipTo('Sandy','UT','84094','US',1);
     
    // Add a package.  Note that I saved the package number for use in the following functions
    $pkg=$MyUPS->AddPackage('02','First Package',33);
    $MyUPS->SetPackageValue($pkg,87.53); // Adding an insured value
    $MyUPS->SetPackageSize($pkg,108,2,2);    // Adding a size to the box
 
    // adding another package (with an insured value)
    $pkg=$MyUPS->AddPackage('02','Second Package',113,25.50);
     
    // Request the rates this shipping setup
    $UPSError=$MyUPS->ModeRateShop();
     
    // limit the shipping services I want displayed 
    //    (these are the service codes from ups. NOTE: you can use either integers (12) or strings ('12') )
    $MyUPS->SetRateListLimit('03',12,'02');
 
    // get the list of rates I specified, adding 1.50 to each one for handling
    $selopt=$MyUPS->GetRateListShort(1.50);
 
    // set the services list back to all of them
    $MyUPS->SetRateListLimit();
 
    // I debuged here to see that everything was happy =)
    //  $MyUPS->Debug();
 
 
    // here I'm getting the cost of service '03' (ground).  usually this would be on the next page
    // after the user selected something from the options of ModeRateShop
    $MyRate=$MyUPS->ModeGetRate('03');
 
    // Want to display the Rates AND Transit time?
    $MyUPS->ModeGetRatesAndTransit();
    $price = $MyUPS->GetRateListWithArrival(1.50, 'PRICE', 'ARRAY');
    
    $list_by_price = "<table><tr><th>Service</th><th>Price</th><th>Days In Transit</th><th>Arrival Day</th></tr>";
	foreach($price as $v){
	    $list_by_price .= "<tr>";
	    foreach($v as $vv){
		$list_by_price.= "<td>{$vv}</td>";
	    }
	    $list_by_price.= "<tr>";
	}
	$list_by_price.="</table>";

    echo "<h3>Services sorted by price</h3>".$list_by_price;
    
    // Want to see that same data sorted by Days in Transit?
    $days = $MyUPS->GetRateListWithArrival(1.50, 'DAYS', 'ARRAY');
    
    $list_by_days = "<table><tr><th>Service</th><th>Price</th><th>Days In Transit</th><th>Arrival Day</th></tr>";
	foreach($days as $v){
	    $list_by_days .= "<tr>";
	    foreach($v as $vv){
		$list_by_days.= "<td>{$vv}</td>";
	    }
	    $list_by_days.= "<tr>";
	}
	$list_by_days.="</table>";
    
    echo "<h3>Services sorted by day</h3>".$list_by_days;
 
// used my values I got back.
echo <<<__FOO__
<!DOCTYPE HTML>
<head>
<title>
</head>
<body>
<select name="foo">
    $selopt
</select>
 
 
<br><br>
Cost is $MyRate.
</body>
</html>
__FOO__;
 
?>

