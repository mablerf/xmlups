<?
////////////////////////////////////////////////////////////////////////////////
//
// AaronUPS Version 0.4: (PHP/cURL-XML)
// aaronups@shadowguarddev.com
//
// UPS Rate and Rate Shopping Script.
//
// This code is released without any warranty, even the implied warranty of 
// fitness for a particular purpose.  So, USE AT YOUR OWN RISK.
// 
// To use it, you MUST compile PHP with cURL (http://curl.haxx.se) support,
// and cURL must be compiled with SSL support.  
//
// I will try to answer any questions you have when using it, addressed to 
// aaronups@shadowguarddev.com.
//
//
// To use these tools, you have to register at UPS.  When I did it, it was here:
//
//   https://www.ups.com/servlet/registration?loc=en_US_EC&returnto=http://www.ec.ups.com/ecommerce/gettools/gtools_intro.html
//
// If this doesn't seem to work, just go to UPS.com and start looking for online tools, 
// and how to register for your developers key and get an XML key, etc..
//
//
// Based on the SurePay script by rachael@kreisler.org, version .07
// Find it at http://www.kreisler.org/surepay/index.php
//
////////////////////////////////////////////////////////////////////////////////
 
###########################################################################################
## Sample Code (simple) using this script
###########################################################################################
#
# This script should be pretty self explainitory.  Copy this section of code
# into another file in the same directory as aaronups.php (this file) to test
# it out.  be sure you fill in your $AccessLicenseNumber on line 204 of this
# file, as well as your UserID and Password on the following lines (205,206)
#
 
$SAMPLE=<<<__HTML__
 
<?
    // include the UPS Script.
    require_once('aaronups.php');
 
 
    // Create an opject of type ups
    $MyUPS=new ups();
 
    // set shipper info
    $MyUPS->SetShipper('Springfield','MO','65807','US');
     
    // uncomment this if you ship from somewhere other than the address you 
    // registered your key to.
    //$MyUPS->SetShipFrom('Springfield','MO','65807','US');  
 
    // Set the Ship To address.  This will probably be gleaned from the user
    $MyUPS->SetShipTo('Cassville','MO','65625','US',1);
     
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
 
 
// used my values I got back.
echo <<<__FOO__
 
<select name="foo">
    $selopt
</select>
 
 
<br><br>
Cost is $MyRate.
__FOO__;
 
?>
 
__HTML__;
#
#
###########################################################################################
 
#######################################################################################################################
## Function List
#######################################################################################################################
#
# Constructors - Look here for some default arrays, also, if UPS adds new service options you need to change one of them
#   ups()
#
# These functions probably won't be used by most people.  You will want to set the variables staticly for most cases
#   SetPostURL($url)
#   SetAccountInfo($ALN,$UID,$Pass)
#   SetPickupType($Code)
#   SetCustomerContext($context)
#   SetShipper($city,$state,$zip,$country)
#   SetShipFrom($city,$state,$zip,$country)
#   SetDefaultService($default)
#
# Set the ship to address (isres is to set wether or not you are shipping to a residential area)
#   SetShipTo($city,$state,$zip,$country,$isres=0)
#
# Modes of operation 
#   ModeRateShop()          // Process the current shipping options and get a list of rates (use GetRateListShort($handling); to get them)
#   ModeGetRate($ServiceCode)       // returns the price for the selected rate.  Usually you want to use the above function to find the method
#
# Get Data Functions
#   GetServiceName($service)        // Takes a 2 character service identifier and returns a string name for that service.
#
# Rate List Functions
#   GetRateListShort($handling=0,$sort='',$type='',$display='')
#                   // returns information for a selection of the service to ship via
#                   // $handling is an dollar amount to add to all shipping costs to adjust for packing, etc    
#                   // $sort is one of the following:
#                       PRICE {default} - sorts the service options by the price, ascending
#                       SERVICE - sorts the service options by the name of the service asc
#                   // $type is one of the following:
#                       OPTION {default} - returns option rows for use in a select box (you provide the select statement)
#                       RADIO - returns a group of radio buttons.  They are named UPSShipService, and are encapsulated in div's of class "UPSRadio"
#                   // $display is one of the following:
#                       TCOST_SERVICE {default} - The services will show up as "PRICE - SERVICE NAME"
#                       BASEDIFF - The first service will show up as above, the rest will show "upgrade to SERVICE NAME for PRICEDIFF"
#
#   SetRateListLimit(/*...*/)       // sets the rates that will be returned if they are available.  empty sets all avaliable (default)
#
# Package Functions
#   AddPackage($PackageType,$Description,$Weight,$Value=0.0,$WeightUnit='LBS',$CurrencyUnit='USD')
#   SetPackageSize($PackageNum,$Length,$Width,$Height,$Unit='IN') // use for odd shaped packages.  $PackageNum is returned by AddPackage
#   SetPackageValue($PackageNum,$Value,$CurrencyUnit='USD') // use for insured packages (or the options in AddPackage
#
# Error Functions
#   GetErrorSeverity() // Hard for a real problem, Transient(?) if you need to retry, Warning if there is just something weird you should know
#   GetErrorCode()    // this is the error code as defined by UPS.  Ranges [01xxxx - XML Error],[02xxxx - Architecture error],[11xxxx -Rate & Service specific]
#   GetErrorDescription() // Description of the error
#   GetErrorRetry()    // seconds to wait before you retry.  only set if it's a 'Transient'(?) error
#
# YOU DON'T NEED TO RUN THESE AND WILL PROBABLY CAUSE YOURSELF HEADACHES IF YOU DO
#   CreateRequest()   // Makes the XML request
#   XMLParser($simple) // parses the XML response into a useful format (array)
#   Process()               // sends the request to UPS, parses the response into a useuful format (condensed array)
#
#
# These functions show you a bunch of stuff you probably don't want to see, but feel free to look.  There is alot of data passed around that might be handy,
# espcially if you want to write your own Rate List Function.
#   Debug()
#   Debug2()
#
# This function is not really in the class, it's at the end of the file.  I wrote it to display arrays, like $HTTP_POST_VARS while debugging. 
# It recursively calls itself for nested arrays, and staticly numbers the arrays.  It is only used in the two Debug functions of UPS, and can safely
# be deleted to clear namespace, if you don't plan on running the Debug functions.
#   buildarray($array,$arraylabel)
#
#######################################################################################################################
 
 
class ups
{
 
 
 
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
///// VARIABLE DATA     //////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
 
##########################################################################
###### YOU MAY WISH TO SET THESE STATICLY ################################
##########################################################################
     
    // URL to send request to
    var $postURL='https://www.ups.com/ups.app/xml/Rate';
 
    // Access Passwords
    var $AccessLicenseNumber='';
    var $UserId='';
    var $Password='';
 
    // Pickup Service You have
    var $UPSPickupTypeCode='01';
 
    // CustomerContext can contain XML you want Posted Back
    var $CustomerContext='AaronUPS Version 0.2: (PHP/cURL-XML)';
 
 
    // Address of Shipper.  should match info givin to ups
    var $ShipperCity='Rogersville';
    var $ShipperState='MO';
    var $ShipperPostalCode='65742';
    var $ShipperCountry='US';
 
    // Address package is shipped from, use if different than above
    var $ShipFromCity='Springfield';
    var $ShipFromState='MO';
    var $ShipFromPostalCode='65804';
    var $ShipFromCountry='US';
 
    // Default Service
    var $DefaultService=3; // 3=Ground
 
###############################################################################################
## These variables are usually set with functions, so you probably don't want to edit them.
###############################################################################################
 
         
    // UPS Service Data Options
    var $UPSRequestAction;
    var $UPSRequestOption;
    var $UPSServiceCode;
 
    // Address that is recieving the package 
    var $ShipToCity;
    var $ShipToState;
    var $ShipToPostalCode;
    var $ShipToCountry;
    var $ShipToResidential='';
 
    // used if you don't use packages (NOT IMPLEMENTED - Use Packages)
    var $PackageWeight;
    var $UPSPackageType;
 
    // Arrays to hold various default data
    var $ARRAY_PickupTypes;
    var $ARRAY_ServiceCodes;
    var $ARRAY_PackageTypes;
    var $ARRAY_Packages;
 
    // cURL return info.  Interesting for debug.
    var $curl_array;
 
    // Variables to hold the communication with UPS.  Saved for debug.
    var $request;
    var $response;
 
###############################################################################################
 
 
 
 
 
 
#################################################################################################
## Constructor Function
#################################################################################################
 
    function ups()
    {
         
        if(empty($this->AccessLicenseNumber))
        {
            echo "<h1>AccessLicenseNumber is empty.  You need to put your access license number from UPS in this variable.</h1>";
        }
        if(empty($this->UserId))
        {
            echo "<h1>UserId is empty.  You need to put your user id from UPS in this variable.</h1>";
        }
        if(empty($this->Password))
        {
            echo "<h1>Password is empty.  You need to put your password from UPS in this variable.</h1>";
        }
 
        // These are the pickup types from UPS at the time of this writing.  Most businesses will
        // be '01', most individuals will be '03'.  
        $this->ARRAY_PickupTypes=array(
                         1 => array( '01', 'Daily Pickup'    ),
                         3 => array( '03', 'Customer Counter'    ),
                         6 => array( '06', 'One Time Pickup' ),
                         7 => array( '07', 'On Call Air' ),
                        19 => array( '19', 'Letter Center'   ),
                        20 => array( '20', 'Air Service Center'  )
                    );
 
 
        // This is the service Type that you are shipping with.  
        // If UPS starts offering other services (and you get empty drop boxes) fill them in here
        // Make sure the array index matches the service code
        // the true value at the end tells the Rate List Functions to return this service if they
        // get it in a rate shop list.  these values can be adjusted by using the function
        // SetRateListLimit(/*...*/)
        $this->ARRAY_ServiceCodes=array(
                         1 => array( '01', 'Next Day Air'        ,true),
                         2 => array( '02', '2nd Day Air'     ,true),
                         3 => array( '03', 'Ground'          ,true),
                         7 => array( '07', 'Worldwide Express'       ,true),
                         8 => array( '08', 'Worldwide Expendited'    ,true),
                        11 => array( '11', 'Standard'            ,true),
                        12 => array( '12', '3-Day Select'        ,true),
                        13 => array( '13', 'Next Day Air Saver'      ,true),
                        14 => array( '14', 'Next Day Air Early AM'   ,true),
                        54 => array( '54', 'Worldwide Express Plus'  ,true),
                        59 => array( '59', '2nd Day Air AM'      ,true),
                        65 => array( '65', 'Express Saver'       ,true)
                    );
 
 
                     
 
 
        // Array of Package Types.  Usually '02' Package is used, but the rest are useful
        $this->ARRAY_PackageTypes=array(
                         0 => array( '00', 'Unknown'     ),
                         1 => array( '01', 'UPS letter'  ),
                         2 => array( '02', 'Package'     ),
                         3 => array( '03', 'UPS Tube'        ),
                         4 => array( '04', 'UPS Pak'     ),
                        21 => array( '21', 'UPS Express Box' ),
                        24 => array( '24', 'UPS 25KG Box'    ),
                        25 => array( '25', 'UPS 10KG Box'    )
                    );
 
    } // end ups()
     
##############################################################################################################
 
 
 
 
 
##############################################################################################################
## Set Functions you probably won't need.  Usually these things are set staticly
##############################################################################################################
 
    // URL to send request to
    function SetPostURL($url)
    {
        $this->postURL=$url;
    }
 
    function SetAccountInfo($ALN,$UID,$Pass)
    {
        $this->AccessLicenseNumber=$ALN;
        $this->UserId=$UID;
        $this->Password=$Pass;
    }
 
    function SetPickupType($Code)
    {
        $this->UPSPickupTypeCode=$Code;  
    }
 
    // CustomerContext can contain XML you want Posted Back
    function SetCustomerContext($context)
    {
        $this->CustomerContext=$context;
    }
     
##############################################################################################################
 
 
 
 
 
##############################################################################################################
## Address Functions
##############################################################################################################
 
    function SetShipper($city,$state,$zip,$country)
    {
 
        $this->ShipperCity=$city;
        $this->ShipperState=$state;
        $this->ShipperPostalCode=$zip;
        $this->ShipperCountry=$country;
    }
 
 
    function SetShipFrom($city,$state,$zip,$country)
    {
 
        $this->ShipFromCity=$city;
        $this->ShipFromState=$state;
        $this->ShipFromPostalCode=$zip;
        $this->ShipFromCountry=$country;
    }
 
 
    function SetShipTo($city,$state,$zip,$country,$isres=0)
    {
 
        $this->ShipToCity=$city;
        $this->ShipToState=$state;
        $this->ShipToPostalCode=$zip;
        $this->ShipToCountry=$country;
        if($isres)
        {
            $this->ShipToResidential='<ResidentialAddress/>';
        }
    }
 
    function SetDefaultService($default)
    {
        $this->DefaultService=$default;  // set the $DefaultSerice Variable
        return 0;           // return no errors
    }
 
 
##############################################################################################################
 
 
 
 
##############################################################################################################
## Mode Functions
##############################################################################################################
 
    // get an <option> block list of all the rates for the set packages.
    function ModeRateShop()
    {
            $this->UPSRequestAction='Rate';
            $this->UPSRequestOption='shop';
 
            // Create the request and then send and process it.
            $this->CreateRequest();
            $this->Process();
 
            return $this->GetErrorCode();
    }
 
 
 
    // get the cost of the selected rate
    function ModeGetRate($ServiceCode)
    {
            // set the action from ups to Rate and rate.
            // this will get us one rate, the one we chose
            $this->UPSRequestAction='Rate';  
            $this->UPSRequestOption='rate';  
            $this->UPSServiceCode=$ServiceCode;
             
            // Create the request and then send and process it.
            $this->CreateRequest();
            $this->Process();
 
            // turn the details into a total cost for shipping and return it (float)
            // if the request was not successful, return 0;  (shipping is never free, hopefully)
            if($this->ResponseDistilled['Success'])
            {
                        $retval=$this->ResponseDistilled["RateOption_0"]['TotalCost'];
            }
            else
            {
                $retval=0;
            }
 
            return $retval;
    }
     
##############################################################################################################
 
 
##############################################################################################################
## Get Data Functions
##############################################################################################################
 
    function GetServiceName($service)
    {
        return $this->ARRAY_ServiceCodes[intval($service)][1];
    }
 
##############################################################################################################
 
 
##############################################################################################################
## Rate List Functions 
##############################################################################################################
 
    function GetRateListShort($handling=0,$sort='',$type='',$display='')
    {
            // turn the details into <option> blocks and return them.
            // if the request was not successful, return 0;
            if($this->ResponseDistilled['Success'])
            {
                for($i=0;$i<$this->ResponseDistilled['RateOptions'];$i++)
                {
                    $service=$this->ResponseDistilled["RateOption_$i"]['Service'];
                    $serviceType=$this->ResponseDistilled["RateOption_$i"]['ServiceType'];
                    $totalCost=$this->ResponseDistilled["RateOption_$i"]['TotalCost']+$handling;
                         
                    if($this->ARRAY_ServiceCodes[intval($service)][2])
                    {
                        switch($display)
                        {
                            default:
                            case 'TCOST_SERVICE':
                                $tc='$'.number_format($totalCost,2);
                                $disp="{$tc} - {$serviceType}";
                                break;
 
                            case 'BASEDIFF':
                                 
                                if(!empty($base))
                                {
                                    $cost=($totalCost=$this->ResponseDistilled["RateOption_$i"]['TotalCost']+$handling)-$base;
                                    $tc='$'.number_format($cost,2);
                                    $disp="upgrade to {$serviceType} for {$tc}";
                                }
                                else
                                {
 
                                    $base=(empty($base))?($totalCost):($base);
                                    $tc='$'.number_format($totalCost,2);
                                    $disp="{$tc} - {$serviceType}";
                                }
                                break;
                        }
                        switch($type)
                        {
                            default:
                            case 'OPTION':
                                $sel=(intval($service)==$this->DefaultService)?('SELECTED'):('');
                                switch($sort)
                                {
                                    default:
                                    case 'PRICE':
                                        $retval[($totalCost*100)]=<<<__HTML__
<option value='$service' $sel>$disp</option>
 
__HTML__;
                                        break;
 
                                    case 'SERVICE':
                                        $retval["$service"]=<<<__HTML__
<option value='$service' $sel>$disp</option>
 
__HTML__;
                                        break;
                                }
                                break;
                                 
                            case 'RADIO':
                                $sel=(intval($service)==$this->DefaultService)?('CHECKED'):('');
                                switch($sort)
                                {
                                    default:
                                    case 'PRICE':
                                        $retval[($totalCost*100)]=<<<__HTML__
<div class="UPSRadio"><input type="radio" name="UPSShipService" value='$service' $sel>$disp</div>
 
__HTML__;
                                        break;
 
                                    case 'SERVICE':
                                        $retval["$service"]=<<<__HTML__
<div class="UPSRadio"><input type="radio" name="UPSShipService" value='$service' $sel>$disp</div>
 
__HTML__;
                                        break;
                                }
                                break;
                        }
                    }
                }
                        ksort($retval);
                        $retval=@join(' ',$retval);
            }
            else
            {
                $retval=0;
            }
 
            return $retval;
    }
 
 
    function SetRateListLimit(/*...*/)
    {
        // If arguments were passed, it means we are limiting the options
        if(func_num_args())
        {
            $args=func_get_args();          // get service codes to turn on
            $args=array_map("intval",$args);    // make sure they are all integers
            reset($args);               // reset the array (just to be sure)
            reset($this->ARRAY_ServiceCodes);    // reset the array (just to be sure)
             
            // Turn all services off
            while(list($key,$val) = each($this->ARRAY_ServiceCodes))
            {
                $this->ARRAY_ServiceCodes[$key][2]=false;    // turn each service off
            }
             
            reset($this->ARRAY_ServiceCodes);    // reset the array (just to be sure)
            // turn on select servcies
            while(list($key,$val)=each($args))
            {
                $this->ARRAY_ServiceCodes[$val][2]=true; // turn select services on
            }
        }
        else    // otherwise, make all services available
        {
            reset($this->ARRAY_ServiceCodes);    // reset the array (just to be sure)
            while(list($key,$val) = each($this->ARRAY_ServiceCodes))
            {
                $this->ARRAY_ServiceCodes[$key][2]=true; // turn each service on
            }
             
            reset($this->ARRAY_ServiceCodes);    // reset the array (just to be kind)
        }
        return func_num_args();  // just seems the right thing to do...
    }
     
##############################################################################################################
 
     
##############################################################################################################
## Package Handling Functions
##############################################################################################################
 
    function AddPackage($PackageType,$Description,$Weight,$Value=0.0,$WeightUnit='LBS',$CurrencyUnit='USD')
    {
        $this->ARRAY_Packages[]=array($PackageType,$Description,'',$Weight,$WeightUnit,$Value,$CurrencyUnit);
        return (count($this->ARRAY_Packages));
    }
 
    function SetPackageSize($PackageNum,$Length,$Width,$Height,$Unit='IN')
    {
        $this->ARRAY_Packages[$PackageNum-1][2]=array($Unit,$Length,$Width,$Height);
        return $PackageNum;
    }
     
    function SetPackageValue($PackageNum,$Value,$CurrencyUnit='USD')
    {
        $this->ARRAY_Packages[$PackageNum-1][5]=$Value;
        $this->ARRAY_Packages[$PackageNum-1][6]=$CurrencyUnit;
        return $PackageNum;
    }
 
##############################################################################################################
 
 
 
 
######################################################################################
## Error Functions
######################################################################################
 
    function GetErrorSeverity()
    {
        $retval=0;
        if(!($this->ResponseDistilled['Success']))
        {
            $retval=$this->ResponseDistilled['Error']['Description'];
        }
        return $retval;
    }
 
    function GetErrorCode()
    {
        $retval=0;
        if(!($this->ResponseDistilled['Success']))
        {
            $retval=$this->ResponseDistilled['Error']['Code'];
        }
        return $retval;
    }
     
    function GetErrorDescription()
    {
        $retval=0;
        if(!($this->ResponseDistilled['Success']))
        {
            $retval=$this->ResponseDistilled['Error']['Description'];
        }
        return $retval;
    }
 
    function GetErrorRetry()
    {
        $retval=0;
        if(!($this->ResponseDistilled['Success']))
        {
            $retval=$this->ResponseDistilled['Error']['MinimumRetrySeconds'];
        }
        return $retval;
    }
 
######################################################################################
 
 
 
#################################################################################################
## CreateRequest - assemples the XML request
#################################################################################################
 
    function CreateRequest()
    {
        $this->Request='';
 
        // CREATE SHIPFROM ADDRESS BLOCK
        $ShipFromAddress='';
        if(!empty($this->ShipFromCity) || !empty($this->ShipFromState) || !empty($this->ShipFromPostalCode) || !empty($this->ShipFromCountry))
        {
            $ShipFromAddress=<<<__SHIPFROMADDRESS__
 
    <ShipFrom>
      <Address>
        <City>$this->ShipFromCity</City>
        <StateProvinceCode>$this->ShipFromState</StateProvinceCode>
        <PostalCode>$this->ShipFromPostalCode</PostalCode>
        <CountryCode>$this->ShipFromCountry</CountryCode>
      </Address>
    </ShipFrom>
              
__SHIPFROMADDRESS__;
 
        }
 
        // CREATE PACKAGELIST BLOCK
        $PackageList='';
        reset($this->ARRAY_Packages);
        while(list($key,$val)=each($this->ARRAY_Packages))
        {
            $Dimensions='';
            $InSure='';
            $pcode=$val[0];
             
            if(!empty($val[2]))
            {
                list($c,$l,$w,$h)=$val[2];
 
                $Dimensions=<<<__DIM__
    <Dimensions>
      <UnitOfMeasurement>
        <Code>$c</Code>
      </UnitOfMeasurement>
      <Length>$l</Length>
      <Width>$w</Width>
      <Height>$h</Height>
    </Dimensions>
__DIM__;
            }
 
            if(!empty($val[5]))
            {
                $InSure=<<<__INS__
    <PackageServiceOptions>
      <InsuredValue>
        <CurrencyCode>$val[6]</CurrencyCode>
        <MonetaryValue>$val[5]</MonetaryValue>
      </InsuredValue>
    </PackageServiceOptions>
__INS__;
                 
            }
 
            $pdesc=$this->ARRAY_PackageTypes[intval($val[0])][1];
 
            $PackageList.=<<<__PACKAGE__
  <Package>
    <PackagingType>
      <Code>$val[0]</Code>
      <Description>$pdesc</Description>
    </PackagingType>
    <Description>$val[1]</Description>
$Dimensions
    <PackageWeight>
      <Weight>$val[3]</Weight>
      <UnitOfMeasurement>
        <Code>$val[4]</Code>
      </UnitOfMeasurement>
    </PackageWeight>
$InSure
  </Package>
 
__PACKAGE__;
 
        }
 
 
        // write code for this to be filled in..
        $ShipmentWeight='';
 
 
 
        // CREATE REQUEST BLOCK
        $this->request=<<<__REQUEST__
 
<?xml version="1.0"?>
<AccessRequest xml:lang="en-US">
  <AccessLicenseNumber>$this->AccessLicenseNumber</AccessLicenseNumber>
  <UserId>$this->UserId</UserId>
  <Password>$this->Password</Password>
</AccessRequest>
 
<?xml version="1.0"?>
<RatingServiceSelectionRequest xml:lang="en-US">
  <Request>
   <TransactionReference>
    <CustomerContext>$this->CustomerContext</CustomerContext>
    <XpciVersion>1.0001</XpciVersion>
   </TransactionReference>
   <RequestAction>$this->UPSRequestAction</RequestAction>
   <RequestOption>$this->UPSRequestOption</RequestOption>
  </Request>
 
  <PickupType>
   <Code>$this->UPSPickupTypeCode</Code>
  </PickupType>
 
  <Shipment>
   <Shipper>
    <Address>
      <City>$this->ShipperCity</City>
      <StateProvinceCode>$this->ShipperState</StateProvinceCode>
      <PostalCode>$this->ShipperPostalCode</PostalCode>
      <CountryCode>$this->ShipperCountry</CountryCode>
    </Address>
   </Shipper>
 
   <ShipTo>
    <Address>
      <City>$this->ShipToCity</City>
      <StateProvinceCode>$this->ShipToState</StateProvinceCode>
      <PostalCode>$this->ShipToPostalCode</PostalCode>
      <CountryCode>$this->ShipToCountry</CountryCode>
      $this->ShipToResidential
    </Address>
   </ShipTo>
   
   $ShipFromAddress
 
   <Service>
    <Code>$this->UPSServiceCode</Code>
   </Service>
 
  $ShipmentWeight
 
  $PackageList
 
  </Shipment>
</RatingServiceSelectionRequest>
 
 
__REQUEST__;
 
 
    } // END CreateRequest()
 
 
 
 
###############################################################################################
## XMLParser to put all the values from response into an array
###############################################################################################
 
    function XMLParser($simple) 
    {
        $p = xml_parser_create();
        xml_parser_set_option($p,XML_OPTION_CASE_FOLDING,0);
        xml_parser_set_option($p,XML_OPTION_SKIP_WHITE,1);
        xml_parse_into_struct($p,$simple,$vals,$index);
        xml_parser_free($p);
 
        return $vals;
    }
 
 
###############################################################################################
## Process the current settings
###############################################################################################
 
 
    function Process()
    {
        // clear out our variables
        unset($this->ResponseDistilled);
        $this->Response='';
 
        //******************************************************************************
        // INITIALIZE cURL SESSION AND SET OPTIONS.  SEE PHP MANUAL FOR MORE DETAILS.
        // http://www.php.net/manual/en/ref.curl.php
        //******************************************************************************
 
        // INITIALIZE 
 
        $ch = curl_init ();
 
        // TELL cURL WHERE TO POST THE REQUEST.  UNCOMMENT THE SECOND URL TO SEND A LIVE POST.
 
        curl_setopt ($ch, CURLOPT_URL, $this->postURL);
        //curl_setopt ($ch, CURLOPT_URL, "https://xml.surepay.com");
 
        // TELL cURL TO DO A REGULAR HTTP POST.
 
        curl_setopt ($ch, CURLOPT_POST, 1);
 
        // PASS THE REQUEST STRING THAT WE BUILD ABOVE
 
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $this->request);
 
        // TELL cURL TO USE strlen()  TO GET THE DATA SIZE.
 
        curl_setopt ($ch, CURLOPT_POSTFIELDSIZE, 0);
 
        // TELL cURL WHEN TO TIME OUT 
        //IF YOU'RE TIMING OUT BEFORE GETTING A RESPONSE FROM SUREPAY, INCREASE THIS NUMBER 
 
        curl_setopt ($ch, CURLOPT_TIMEOUT, 360); 
                           
        // TELL cURL TO INCLUDE THE HEADER IN THE OUTPUT 
 
        curl_setopt ($ch, CURLOPT_HEADER, 0);
 
        // TELL cURL TO USE SSL VERSION 3.
 
        curl_setopt ($ch, CURLOPT_SSLVERSION, 3);
          
        // TRANSFER THE SUREPAY RESPONSE INTO A VARIABLE.
 
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
 
 
        //******************************************************************************
        // EXECUTE THE REQUEST
        //******************************************************************************
 
        $this->result = curl_exec ($ch);
 
        // get stats for later use in debug, if nessesary.
        $this->curl_array = curl_getinfo($ch);
 
        // close curl connection
        curl_close ($ch);
        //******************************************************************************
        // PARSE RESPONSE
        //******************************************************************************
 
 
 
        // CALL THE XML PARSING FUNCTION TO CREATE ARRAY OF RESULT
 
        $attributes = $this->XMLParser($this->result);
 
 
        $ShipRate=0;
        $ShipPackage=0;
        $MaxPackage=0;
 
 
        // Setup Some defaults
        $this->ResponseDistilled['Success']=0;
        $this->ResponseDistilled['Error']['Code']=0;
 
 
        reset($attributes);
        while (list ($key, $val) = each ($attributes)) 
        {
 
            switch($val['tag'])
            {
                case 'ResponseStatusCode':
                    $this->ResponseDistilled['Success']=$val['value'];
                    break;
 
                case 'Error':
                    while((list($key,$val)=each($attributes)) && ($val['tag']!='Error'))
                    {
                        if($val['tag']=='ErrorSeverity')
                        {
                            $this->ResponseDistilled['Error']['Severity']=$val['value'];
                        }
 
                        if($val['tag']=='ErrorCode')
                        {
                            $this->ResponseDistilled['Error']['Code']=$val['value'];
                        }
                         
                        if($val['tag']=='ErrorDescription')
                        {
                            $this->ResponseDistilled['Error']['Description']=$val['value'];
                        }
                         
                        if($val['tag']=='MinimumRetrySeconds')
                        {
                            $this->ResponseDistilled['Error']['MinimumRetrySeconds']=$val['value'];
                        }
                     
                    }
                    break;
                     
 
                case 'RatedShipment':
                    while((list($key,$val)=each($attributes)) && ($val['tag']!='RatedShipment'))
                    {
                        switch($val['tag'])
                        {
                            case 'Service':
                                while((list($key,$val)=each($attributes)) && ($val['tag']!='Service'))
                                {
                                    if($val['tag']=='Code')
                                    {
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['Service']=$val['value'];
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['ServiceType']=$this->ARRAY_ServiceCodes[intval($val['value'])][1];
                                    }
                                }
                                break;
 
                            case 'BillingWeight':
                                while((list($key,$val)=each($attributes)) && ($val['tag']!='BillingWeight'))
                                {
                                    if($val['tag']=='Code')
                                    {
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['Unit']=$val['value'];
                                    }
                                    if($val['tag']=='Weight')
                                    {
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['Weight']=$val['value'];
                                    }
                                }
                                break;
 
                            case 'TransportationCharges':
                                while((list($key,$val)=each($attributes)) && ($val['tag']!='TransportationCharges'))
                                {
                                    if($val['tag']=='CurrencyCode')
                                    {
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['Currency']=$val['value'];
                                    }
                                    if($val['tag']=='MonetaryValue')
                                    {
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['TransportCost']=$val['value'];
                                    }
                                }
                                break;
 
                            case 'ServiceOptionsCharges':
                                while((list($key,$val)=each($attributes)) && ($val['tag']!='ServiceOptionsCharges'))
                                {
                                    if($val['tag']=='CurrencyCode')
                                    {
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['Currency']=$val['value'];
                                    }
                                    if($val['tag']=='MonetaryValue')
                                    {
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['ServiceCost']=$val['value'];
                                    }
                                }
                                break;
 
                            case 'TotalCharges':
                                while((list($key,$val)=each($attributes)) && ($val['tag']!='TotalCharges'))
                                {
                                    if($val['tag']=='CurrencyCode')
                                    {
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['Currency']=$val['value'];
                                    }
                                    if($val['tag']=='MonetaryValue')
                                    {
                                        $this->ResponseDistilled["RateOption_$ShipRate"]['TotalCost']=$val['value'];
                                    }
                                }
                                break;
                                 
                            case 'GuaranteedDaysToDelivery':
                                $this->ResponseDistilled["RateOption_$ShipRate"]['Days']=$val['value'];
                                break;
                                 
                            case 'ScheduledDeliveryTime':
                                $this->ResponseDistilled["RateOption_$ShipRate"]['DeliveryTime']=$val['value'];
                                break;
 
                            case 'RatedPackage':
                                while((list($key,$val)=each($attributes)) && ($val['tag']!='RatedPackage'))
                                {
                                    switch($val['tag'])
                                    {
 
                                        case 'BillingWeight':
                                            while((list($key,$val)=each($attributes)) && ($val['tag']!='BillingWeight'))
                                            {
                                                if($val['tag']=='Code')
                                                {
                                                    $this->ResponseDistilled["RateOption_$ShipRate"]["Package_$ShipPackage"]['Unit']=$val['value'];
                                                }
                                                if($val['tag']=='Weight')
                                                {
                                                    $this->ResponseDistilled["RateOption_$ShipRate"]["Package_$ShipPackage"]['Weight']=$val['value'];
                                                }
                                            }
                                            break;
 
                                        case 'TransportationCharges':
                                            while((list($key,$val)=each($attributes)) && ($val['tag']!='TransportationCharges'))
                                            {
                                                if($val['tag']=='CurrencyCode')
                                                {
                                                    $this->ResponseDistilled["RateOption_$ShipRate"]["Package_$ShipPackage"]['Currency']=$val['value'];
                                                }
                                                if($val['tag']=='MonetaryValue')
                                                {
                                                    $this->ResponseDistilled["RateOption_$ShipRate"]["Package_$ShipPackage"]['TransportCost']=$val['value'];
                                                }
                                            }
                                            break;
 
                                        case 'ServiceOptionsCharges':
                                            while((list($key,$val)=each($attributes)) && ($val['tag']!='ServiceOptionsCharges'))
                                            {
                                                if($val['tag']=='CurrencyCode')
                                                {
                                                    $this->ResponseDistilled["RateOption_$ShipRate"]["Package_$ShipPackage"]['Currency']=$val['value'];
                                                }
                                                if($val['tag']=='MonetaryValue')
                                                {
                                                    $this->ResponseDistilled["RateOption_$ShipRate"]["Package_$ShipPackage"]['ServiceCost']=$val['value'];
                                                }
                                            }
                                            break;
 
                                        case 'TotalCharges':
                                            while((list($key,$val)=each($attributes)) && ($val['tag']!='TotalCharges'))
                                            {
                                                if($val['tag']=='CurrencyCode')
                                                {
                                                    $this->ResponseDistilled["RateOption_$ShipRate"]["Package_$ShipPackage"]['Currency']=$val['value'];
                                                }
                                                if($val['tag']=='MonetaryValue')
                                                {
                                                    $this->ResponseDistilled["RateOption_$ShipRate"]["Package_$ShipPackage"]['TotalCost']=$val['value'];
                                                }
                                            }
                                            break;
                                    }
                                }
                                $ShipPackage++;
                                break;
                                 
                        }
                    }
                    $ShipRate++;
                    if($ShipPackage>$MaxPackage)
                    {
                        $MaxPackage=$ShipPackage;
                    }
                    $ShipPackage=0;
                    break;
 
 
            }
             
        }
 
        $this->ResponseDistilled['RateOptions']=$ShipRate;
        $this->ResponseDistilled['Packages']=$MaxPackage;
 
 
 
    } // end process();
 
 
 
 
 
 
###################################################################################################
## Debuging output of interest
###################################################################################################
    function Debug()
    {
 
        // format Request for display
        $treq=htmlspecialchars($this->request);
 
        // format Response for display
        $tres=htmlspecialchars($this->result);
        $tres=str_replace("&lt;","\r&lt;",$tres);
 
        // format cURL info for display
        $tcURL=buildarray($this->curl_array,'cURL Info');
 
        // format distilled response for display
        $tDist=buildarray($this->ResponseDistilled,'Distilled Response');
 
        // build temp page
        $TESTPAGE=<<<__TESTPAGE__
        <html>
        <head>
        <title>Testing cURL with UPS</title>
        </head>
 
        <body bgcolor="#999999">
 
        <table border="1" width="100%" bgcolor="#ffffff">
            <tr>
                <td width="50%" bgcolor="#9999ff">
                    <b>Request to UPS:</b>
                </td>
                <td width="50%" bgcolor="#99ff99">
                    <b>Response from UPS:</b><br>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <font face="Verdana,Helvetica,Arial,sans-serif" size="2">
                        <pre>$treq</pre>
                    </font>
                </td>
                <td valign="top">
                    <font face="Verdana,Helvetica,Arial,sans-serif" size="2">
                        <pre>$tres</pre>
                    </font>
                </td>
            </tr>
            <tr>
                <td width="50%" bgcolor="#ff9999">
                    <b>cURL Info:</b>
                </td>
                <td width="50%" bgcolor="#99ffff">
                    <b>Parsed Response</b><br>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    $tcURL
                </td>
                <td valign="top">
                    $tDist
                </td>
            </tr>
 
        </table>
 
        </body>
        </html>
__TESTPAGE__;
 
 
        echo $TESTPAGE;
         
    } // end Debug();
 
    function Debug2()
    {
        $tDist=buildarray($this->ResponseDistilled,'Distilled Response');
        echo $tDist;
    } // end Debug2();
 
 
} // end class ups
 
 
############################################################################################
## Helper Function that changes an array into a series of nexted tables for easy display
############################################################################################
 
function buildarray($array,$arraylabel)
{
 
    // count table number
    static $array_count_preinc;
    $array_count_preinc++;
 
    // gather statistics
    $size=sizeof($array);
    $foo=<<<__TABLE__
    <table border="4" bgcolor="#ffffff">
      <tr>
    <td colspan="2" bgcolor="#ffaaaa">
      Array $array_count_preinc: $arraylabel<br>
      Entries: $size
    </td>
      </tr>
      <tr bgcolor="#aaaaff">
    <th>
      Key
    </th>
    <th>
      Value
    </th>
      </tr>
__TABLE__;
     
     
 
    if(is_array($array))
    {
        while (list ($key, $val) = each ($array))
        {
            $foo.= "<tr><td bgcolor=\"#aaffaa\" valign=\"top\"><b>$key</b></td><td>";
            if(is_array($val))
            {
                $foo.=buildarray($val,"");
            }
            else
            {
                $foo.= $val;
            }
            $foo.= "</td></tr>";
        }
    }
    $foo.= "</table>";
 
    return $foo;
}
 
 
 
?>
