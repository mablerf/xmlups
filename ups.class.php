<?php
////////////////////////////////////////////////////////////////////////////////
//
// AaronUPS Version 0.5: (PHP/cURL-XML)
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

 
#######################################################################################################################
## Function List
#######################################################################################################################
#
# Constructors - Look here for some default arrays, also, if UPS adds new service options you need to change one of them
#   ups($debug = FALSE, $DEV = false, $AccessLicenseNumber = null, $UserId = null, $Password = null, $ShipperNumber = null)
#		// if parameters aren't passed in here, they're populated from static variables
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
#						ARRAY - returns an array of results to be formatted/handled outside of this class
#                       OPTION {default} - returns option rows for use in a select box (you provide the select statement)
#                       RADIO - returns a group of radio buttons.  They are named UPSShipService, and are encapsulated in div's of class "UPSRadio"
#                   // $display is one of the following:
#                       TCOST_SERVICE {default} - The services will show up as "PRICE - SERVICE NAME"
#                       BASEDIFF - The first service will show up as above, the rest will show "upgrade to SERVICE NAME for PRICEDIFF"
#	GetRateListWithArrival($handling=0,$sort='',$type='',$display='')
#					// returns information for a selection of the service to ship via
#					// $sort is one of the following:
#                       PRICE {default} - sorts the service options by the price, ascending
#                       SERVICE - sorts the service options by the name of the service asc
#                   // $type is one of the following:
#						ARRAY - Currently the only option available. Returns an array of results to be formatted/handled outside of this class 
#                   // $display is one of the following:
#						{default} - "SERVICE NAME"
#                       TCOST_SERVICE - The services will show up as "PRICE - SERVICE NAME"
#                       BASEDIFF - The first service will show up as above, the rest will show "upgrade to SERVICE NAME for PRICEDIFF"
#
#
#   SetRateListLimit(/*...*/)       
#					// sets the rates that will be returned if they are available.  empty sets all avaliable (default)
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

	// Access Credentials 
	// get these from https://www.ups.com/upsdeveloperkit
	private $AccessLicenseNumber = ''; 
	private $ShipperNumber = ''; 
	private $UserId='';
	private $Password='';
	
	// Valid values are:
	//  00- Rates Associated with Shipper Number;
	//  01- Daily Rates;
	//  04- Retail Rates;
	//  53- Standard List Rates;
	var $CustomerClassificationCode = '00'; 
	
	private $DEV = false;
	private $RequestTypes = array( // Key must be a valid 'RequestAction'
			'Rate' => array(
					'URL' => 'https://onlinetools.ups.com/ups.app/xml/Rate',
					'DEVURL' =>  'https://wwwcie.ups.com/ups.app/xml/Rate'), 
			'TimeInTransit' => array(
					'URL' => 'https://onlinetools.ups.com/ups.app/xml/TimeInTransit',
					'DEVURL' =>  'https://wwwcie.ups.com/ups.app/xml/TimeInTransit'
			));
	private $postURL; // use SetPostURL to set this
	
    // Pickup Service You have
    var $UPSPickupTypeCode='01';
 
    // CustomerContext can contain XML you want Posted Back
    var $CustomerContext = 'AaronUPS Version 0.5: (PHP/cURL-XML)';
  
    // Address of Shipper.  should match info givin to ups
    protected $ShipFromCity='Rogersville';
    protected $ShipFromState='MO';
    protected $ShipFromPostalCode='65742';
    protected $ShipFromCountry='US';
 
    // Default Service
    var $DefaultService = '03'; // 3=Ground
 
###############################################################################################
## These variables are usually set with functions, so you probably don't want to edit them.
###############################################################################################
         
    // UPS Service Data Options
    private $UPSRequestAction;
    private $UPSRequestOption;
    private $UPSServiceCode;
 
    // Address that is recieving the package
    protected $ShipToCity;
    protected $ShipToState;
    protected $ShipToPostalCode;
    protected $ShipToCountry;
    protected $ShipToResidential='';
    
    
    // used if you don't use packages (NOT IMPLEMENTED - Use Packages)
    protected $PackageWeight;
    protected $UPSPackageType;
 
    // Arrays to hold various default data
    private $ARRAY_PickupTypes;
    private $ARRAY_ServiceCodes;
    private $ARRAY_PackageTypes;
    private $ARRAY_Packages;
 
    // cURL return info.  Interesting for debug.
    protected $curl_array;
    protected $PackageContentValue;
    
    // Variables to hold the communication with UPS.  Saved for debug.
    protected $Request;
    protected $Response;
 
    private $ResponseDistilled;
    
###############################################################################################
 
 
 
 
 
 
#################################################################################################
## Constructor Function
#################################################################################################
 
    function ups($debug = FALSE, $DEV = false, $AccessLicenseNumber = null, $UserId = null, $Password = null, $ShipperNumber = null)
    {
    	$this->debug_enabled = $debug;
    	if($DEV)
    	{
    		$this->DEV = $DEV;
    	}
    	
        if(isset($AccessLicenseNumber))
        {
        	$this->AccessLicenseNumber = $AccessLicenseNumber;
        }
        
        if(empty($this->AccessLicenseNumber))
        {
        	$this->AddError("<h1>AccessLicenseNumber is empty.</h1>  You need to put your access license number from UPS in this variable.</h1>");
        }
        
        if(isset($UserId))
        {
        	$this->UserId = $UserId;
        }
        
        if(empty($this->UserId))
        {
        	$this->AddError("<h1>UserId is empty.</h1>  You need to put your user id from UPS in this variable.</h1>");
        }
        

        if(isset($Password))
        {
        	$this->Password = $Password;
        }
        
        if(empty($this->Password))
        {
        	$this->AddError("<h1>Password is empty.</h1>  You need to put your password from UPS in this variable.</h1>");
        }
        
 		if(isset($ShipperNumber))
 		{
 			$this->ShipperNumber = $ShipperNumber;
 		}
 		
 		if(empty($this->ShipperNumber) && $debug)
 		{
 			$this->AddError("<h1>Shipper Number is empty.</h1> Shipper number is only required if you want to get negotiated rates.");
 		}
        
 		if(!empty($this->ERRORS))
 		{
 			return $this->ERRORS;
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
        // the true value in 'Display' tells the Rate List Functions to return this service if they
        // get it in a rate shop list.  these values can be adjusted by using the function
        // The key is the Service Code from the Rates API, I don't know why the Service Codes don't 
        // match in the Time In Transit API, but they don't.
        // SetRateListLimit(/*...*/)
 		// Valid Service Codes from Rates API Doc. 
        $this->ARRAY_ServiceCodes = array(
        		//Valid domestic values: 
        		'14' => array(
        				'Display' => true,
        				'RateCode' => '14',
        				'TimeInTransitCode' => '1DM',
        				'Description' => 'Next Day Air Early AM'), 
        		'01' => array(
        				'Display' => true,
        				'RateCode' => '01',
        				'TimeInTransitCode' => '1DA',
        				'Description' => 'Next Day Air'), 
        		'13' => array(
        				'Display' => true,
        				'RateCode' => '13',
        				'TimeInTransitCode' => '1DP',
        				'Description' => 'Next Day Air Saver'), 
        		'59' => array(
        				'Display' => false,
        				'RateCode' => '59',
        				'TimeInTransitCode' => '2DM',
        				'Description' => '2nd Day Air AM'),
        		'02' => array(
        				'Display' => true,
        				'RateCode' => '02',
        				'TimeInTransitCode' => '2DA',
        				'Description' => '2nd Day Air'), 
        		'12' => array( 
        				'Display' => true,
        				'RateCode' => '12',
        				'TimeInTransitCode' => '3DS',
        				'Description' => '3 Day Select'), 
        		'02300' => array( // This code isn't straightforward, Saturday Delivery is Option Code 300
        				'Display' => false,
        				'RateCode' => '02',
        				'TimeInTransitCode' => '2DAS',
        				'Description' => '2 Day Air Saturday Delivery'),
        		'03' => array(
        				'Display' => true,
        				'RateCode' => '03',
        				'TimeInTransitCode' => 'GND',
        				'Description' => 'Ground'),
        		
        		//Valid international values: 
        		'11' => array(
        				'Display' => true,
        				'RateCode' => '11',
        				'TimeInTransitCode' => '03',
        				'Description' => 'Standard'),
        		'07' => array(
        				'Display' => true,
        				'RateCode' => '07',
        				'TimeInTransitCode' => '01',
        				'Description' => 'Worldwide Express'), 
        		'54' => array(
        				'Display' => true,
        				'RateCode' => '54',
        				'TimeInTransitCode' => '21',
        				'Description' => 'Worldwide Express Plus'),
        		'08' => array(
        				'Display' => true,
        				'RateCode' => '08',
        				'TimeInTransitCode' => '05',
        				'Description' => 'Worldwide Expedited'), 
        		'65' => array(
        				'Display' => true,
        				'RateCode' => '65',
        				'TimeInTransitCode' => '28',
        				'Description' => 'Saver'), // Required for Rating and Ignored for Shopping'),
        		
        		//Valid Poland to Poland Same Day values:
        		'82' => array(
        				'Display' => true,
        				'RateCode' => '82',
        				'TimeInTransitCode' => '34',
        				'Description' => 'UPS Today Standard'), 
        		'83' => array(
        				'Display' => true,
        				'RateCode' => '83',
        				'TimeInTransitCode' => '35',
        				'Description' => 'UPS Today Dedicated Courier'),
        		'84' => array(
        				'Display' => true,
        				'RateCode' => '84',
        				'TimeInTransitCode' => '36',
        				'Description' => 'UPS Today Intercity'), 
        		'85' => array(
        				'Display' => true,
        				'RateCode' => '85',
        				'TimeInTransitCode' => '37',
        				'Description' => 'UPS Today Express'), 
        		'86' => array(
        				'Display' => true,
        				'RateCode' => '86',
        				'TimeInTransitCode' => '38',
        				'Description' => 'UPS Today Express Saver'),
        		'96' => array(
        				'Display' => true,
        				'RateCode' => '96',
        				'TimeInTransitCode' => '09',
        				'Description' => 'UPS World Wide Express Freight'),
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
        
        $this->SetPostURL('Rate');  // set a default. Woo. 
        $this->SetShipper(); // set default from 
    } // end ups()
     
##############################################################################################################
 
 
 
 
 
##############################################################################################################
## Set Functions you probably won't need.  Usually these things are set staticly
##############################################################################################################
 
    // URL to send request to
    private function SetPostURL($requestType)
    {
        $this->postURL = $this->RequestTypes[$requestType][$this->DEV?'DEVURL':'URL'];
    }
    
    function SetAccountInfo($ALN,$UID,$Pass)
    {
    	$this->AccessLicenseNumber=$ALN;
    	$this->UserId=$UID;
    	$this->Password=$Pass;
    }
    
    function SetPickupType($Code)
    {
        $this->UPSPickupTypeCode = $Code;  
    }
 
    // CustomerContext can contain XML you want Posted Back
    function SetCustomerContext($context)
    {
        $this->CustomerContext=$context;
    }
    
    function SetPackageContentValue($amt=0)
    {
    	$this->PackageContentValue = $amt;
    }
    
    // Because Service Code in the Rate API doesn't match up with the Service Code in the Time In Transit API
    function GetRateCodeFromTITCode($titcode)
    {
    	foreach($this->ARRAY_ServiceCodes as $ratecode => $info)
    	{
    		if($info['TimeInTransitCode'] == $titcode)
    		{
    			return $ratecode;
    		}
    	}
    	
    	$this->AddError('Unknown Time In Transit Code requested: '.$titcode);
    	return false;
    }
    
     
##############################################################################################################
 

 
 
##############################################################################################################
## Address Functions
##############################################################################################################
 
    // This gets run at the end of the constructor to populate defaults from the static vars. 
    // Don't need to use it again unless you've not set those or you want to run for a different address
	function SetShipper($city = null, $state = null, $zip = null, $country = null)
	{
		$this->ShipperCity = isset($city)?$city:$this->ShipFromCity;
	    $this->ShipperState = isset($state)?$state:$this->ShipFromState;
        $this->ShipperPostalCode = isset($zip)?$zip:$this->ShipFromPostalCode;
        $this->ShipperCountry = isset($country)?$country:$this->ShipFromCountry;
    }
    
    function SetShipTo($city,$state,$zip,$country,$isres=0)
    {
    	$this->ShipToCity=$city;
    	$this->ShipToState=$state;
    	$this->ShipToPostalCode=$zip;
    	$this->ShipToCountry=empty($country)?'USA':$country;
    	if($isres)
        {
            $this->ShipToResidential = '<ResidentialAddress/>';
        }
    }
    
 
##############################################################################################################
 
 
 
 
##############################################################################################################
## Mode Functions
##############################################################################################################
 
    // Submits a request for ALL the Rates for the set packages
    function ModeRateShop($rslts = null)
    {
    	$this->UPSRequestAction='Rate';
        $this->UPSRequestOption='shop';
        
 	    // Create the request and then send and process it.
    	$this->CreateRequest();
        $this->ResponseDistilled = $this->Process($rslts);
        
        return $this->ERRORS;
    }
 
    // Submits a request to get Time In Transit for all services 
    // According to the API this takes into account weekends and holidays. 
    // By default it assumes the package will be shipped today. 
    // Functionality to change the ship date is not implemented here yet.
    function ModeGetTimeInTransit($rslts = null)
    {
    	$this->UPSRequestAction = 'TimeInTransit';
    	$this->SetPostURL($this->UPSRequestAction);
    	$this->CreateTITRequest();
    	$this->ResponseDistilled = $this->Process($rslts);
    	
    	return $this->ERRORS;
    }
 
    // Want both the Rates AND the Time In Transit? Use this. 
    // It fetches the rates, then the transit time. Why UPS doesn't
    // offer web services to get both sets of data with one request is a mystery
    function ModeGetRatesAndTransit()
    {
    	$this->ModeRateShop();
    	$this->ModeGetTimeInTransit($this->ResponseDistilled);
    	
    	return $this->ERRORS;
    }
    
    
    // get the cost of the selected service
    function ModeGetRate($ServiceCode)
    {
            // set the action from ups to Rate and rate.
            // this will get us one rate, the one we chose
            $this->UPSRequestAction='Rate';  
            $this->UPSRequestOption='rate';  
            $this->UPSServiceCode=$ServiceCode;
             
            // Create the request and then send and process it.
            $this->CreateRequest();
            $this->ResponseDistilled = $this->Process();
 
            // turn the details into a total cost for shipping and return it (float)
            // if the request was not successful, return 0;  (shipping is never free, hopefully)
            if($this->ResponseDistilled['Success'])
            {
            	$tmp = array_shift($this->ResponseDistilled['RateOptions']);
                $retval = $tmp['TotalCost'];
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
        return $this->ARRAY_ServiceCodes[$service]['Description'];
    }
 
##############################################################################################################
 
 
##############################################################################################################
## Rate List Functions 
##############################################################################################################
 
    /**
     * Turn the details into <option> blocks and return them.
     * if the request was not successful, return 0;
     * @param number $handling - Additional dollar amount to add on top of the UPS rates.
     * @param string $sort - 'PRICE', 'SERVICE' 
     * @param string $type - 'ARRAY', 'OPTION', 'RADIO'
     * @param string $display - Format of description. 'TCOST_SERVICE', 'BASEDIFF' // TODO: add actual functionality
     * @return boolean
     */
    function GetRateListShort($handling=0,$sort='PRICE',$type='OPTION',$display='TCOST_SERVICE')
    {
            if($this->ResponseDistilled['Success'])
            {
                foreach($this->ResponseDistilled['RateOptions'] as $service => $RateOption)
                {
                    $service=$RateOption['Service'];
                    $serviceType=$RateOption['ServiceType'];
                    $totalCost=$RateOption['TotalCost']+$handling;
                         
                    if($this->ARRAY_ServiceCodes[$service]['Display'])
                    {
                        switch($display)
                        {
                            default:
                            case 'TCOST_SERVICE':
                                $tc='$'.number_format($totalCost,2);
                                $disp="{$tc} - {$serviceType}";
                                break;
 
                            case 'BASEDIFF':
                                if(!empty($base)) // TODO: Actually set/create $base somewhere so this does something
                                {
                                    $cost=($totalCost=$RateOption['TotalCost']+$handling)-$base;
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
                            case 'ARRAY': 	
                         		switch($sort)
                         		{
	                         		default:
	                         		case 'PRICE':
	                         			$retval[($totalCost*100)] = array($service => $disp);
	                         			break;
	                         			
	                         		case 'SERVICE':
	                         			$retval["$service"] = array($service => $disp);
	                         			break;
                         		}
                         		break;
                         		
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
                if($type !== 'ARRAY')
                {
                	$retval=@join(' ',$retval);
                }
            }
            else
            {
                $retval=FALSE;
            }
 
            return $retval;
    }
 
    /**
     * Format the data, including Time In Transit
     * @param number $handling - Additional dollar amount to add on top of the UPS rates.
     * @param string $sort - 'PRICE', 'DAYS', 'SERVICE' 
     * @param string $type - 'ARRAY', // TODO: implement 'OPTION', 'RADIO'
     * @param string $display - Format of description. 'TCOST_SERVICE', 'BASEDIFF' // TODO: add actual functionality
     * @return boolean FALSE on failure, string or array depending on $type selection
     */
    function GetRateListWithArrival($handling=0,$sort='PRICE',$type='',$display='')
    {
    	$retval = array();
    	
    	if($this->ResponseDistilled['Success'])
    	{
    		foreach($this->ResponseDistilled['RateOptions'] as $service => $RateOption)
    		{
    			if(is_array($RateOption['Service']))
    			{
    				$RateOption['Service'] = $service;
    			}
    			
    			if($this->ARRAY_ServiceCodes[$service]['Display'])
    			{
	    			$serviceType = $this->ARRAY_ServiceCodes[$service]['Description'];
	    			$totalCost = isset($RateOption['TotalCost'])?($RateOption['TotalCost']+$handling):'Unknown';
	    		
	    			switch($display)
	    			{	    					
	    				case 'TCOST_SERVICE':
	    					$tc = is_numeric($totalCost)?'$'.number_format($totalCost,2):$totalCost;
	    					$disp="{$tc} - {$serviceType}";
	    					break;
	    			
	    				case 'BASEDIFF':		 
	    					if(!empty($base)) // TODO: Actually set/create $base somewhere so this does something
	    					{
	    						$cost=($totalCost=$RateOption['TotalCost']+$handling)-$base;
	    						$tc = is_numeric($totalCost)?'$'.number_format($totalCost,2):$totalCost;
	    						$disp="upgrade to {$serviceType} for {$tc}";
	    					}
	    					else
	    					{
	    			
	    						$base=(empty($base))?($totalCost):($base);
	    						$tc = is_numeric($totalCost)?'$'.number_format($totalCost,2):$totalCost;
	    						$disp="{$tc} - {$serviceType}";
	    					}
	    					break;
	    					
	    				default:
	    					$tc = is_numeric($totalCost)?'$'.number_format($totalCost,2):$totalCost;
	    					$disp = "{$serviceType}";
	    			}
	    			
	    			$list = array();
	    			$list['service']=$disp;
	    			$list['cost'] = $tc;
	    			if(!isset($RateOption['EstimatedArrival']))
	    			{
	    				if(isset($RateOption['GuaranteedDaysToDelivery']))
	    				{
	    					// TODO: update this to get an estimate based on the service's rated guarantee
	    				}
	    			}
	    			else
	    			{
	    				$list['days'] = isset($RateOption['EstimatedArrival'])?(@ceil((strtotime($RateOption['EstimatedArrival']['Date']) - time())/(86400))):'unknown';
	    				$list['arrival'] = isset($RateOption['EstimatedArrival'])?($RateOption['EstimatedArrival']['DayOfWeek']):'unknown';
	    			}
	    			
	    			switch($type)
                    {
						default:
						case 'ARRAY': 
							switch(strtoupper($sort))
							{
								default:
								case 'PRICE':
									$retval[($totalCost*100)] = $list;
									break;
								case 'DAYS':
									$di = isset($RateOption['EstimatedArrival']['Date'])?strtotime($RateOption['EstimatedArrival']['Date']):time();
									while(array_key_exists(intval($di), $retval))
									{
										$di += 1;
									}	
															
									$retval[$di] = $list;
									break;
								case 'SERVICE':
									$retval[$serviceType] = $list;
									break;
							}
						break;
	    			}
	    		}
    		}    
    		ksort($retval);
    	}
    	else
    	{
    		$retval=FALSE;
    	}
    	return $retval;
    }
 
    /**
     * sets the rates that will be returned if they are available.  empty sets all avaliable (default)
     * Any arguments passed should be string(s) matching a Service Code (zero fill) from the Rating API 
     * (aka. the key in $this->ARRAY_ServiceCodes)
     * 
     * If no arguments are passed, this will enable display of all service codes. If you're not changing them, 
     * I recommend not using this function. 
     * @return number
     */
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
                $this->ARRAY_ServiceCodes[$key]['Display']=false;    // turn each service off
            }
             
            reset($this->ARRAY_ServiceCodes);    // reset the array (just to be sure)
            // turn on select servcies
            while(list($key,$val)=each($args))
            {
                $this->ARRAY_ServiceCodes[$val]['Display']=true; // turn select services on
            }
        }
        else    // otherwise, make all services available
        {
            reset($this->ARRAY_ServiceCodes);    // reset the array (just to be sure)
            while(list($key,$val) = each($this->ARRAY_ServiceCodes))
            {
            	if($key !== '02300') // this one's a pain in the butt. Disable by default until I figure out what else to do with it.
            	{
            		$this->ARRAY_ServiceCodes[$key]['Display']=true; // turn each service on
            	}
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
 
    private function AddError($err)
    {
    	if(!is_array($err))
    	{
    		$err = array('Code' => 999, 'Description' => $err);
    	}
    	
    	$this->ResponseDistilled['Error'] = $err;
    }
    
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
 	
 	function CreateTITRequest()
 	{
 		if(empty($this->AccessLicenseNumber) || empty($this->UserId) || empty($this->Password))
 		{
 			$this->AddError('Can\'t create a Time In Transit Request without Access Credentials.');
 			return false;
 		}
 		
 		// Package Content Value can make a difference in the quote, in case you care.
 		$this->PackageContentValue = !empty($this->PackageContentValue)?$this->PackageContentValue:1; 
 		$PickupDate = date('Ymd'); // TODO: Update this to allow customized Pick Up Date
 		
 		$this->Request = <<<__REQUEST__
 		
<?xml version="1.0"?>
<AccessRequest xml:lang="en-US">
  <AccessLicenseNumber>$this->AccessLicenseNumber</AccessLicenseNumber>
  <UserId>$this->UserId</UserId>
  <Password>$this->Password</Password>
</AccessRequest>
<?xml version="1.0"?>
<TimeInTransitRequest xml:lang="en-US">
  <Request>
    <TransactionReference>
	<CustomerContext>$this->CustomerContext</CustomerContext>
      <XpciVersion>1.001</XpciVersion>
    </TransactionReference>
    <RequestAction>$this->UPSRequestAction</RequestAction>
  </Request>
  <TransitFrom>
    <AddressArtifactFormat>
      <PoliticalDivision2>$this->ShipFromCity</PoliticalDivision2>
      <PoliticalDivision1>$this->ShipFromState</PoliticalDivision1>
      <PostcodePrimaryLow>$this->ShipFromPostalCode</PostcodePrimaryLow>
      <CountryCode>$this->ShipFromCountry</CountryCode>
    </AddressArtifactFormat>
  </TransitFrom>
  <TransitTo>
    <AddressArtifactFormat>
      <PoliticalDivision2>$this->ShipToCity</PoliticalDivision2>
      <PoliticalDivision1>$this->ShipToState</PoliticalDivision1>
      <PostcodePrimaryLow>$this->ShipToPostalCode</PostcodePrimaryLow>
      <CountryCode>$this->ShipToCountry</CountryCode>
      $this->ShipToResidential
    </AddressArtifactFormat>
  </TransitTo>
  <PickupDate>$PickupDate</PickupDate>
</TimeInTransitRequest>
__REQUEST__;
 		 		
 	}
 	
    function CreateRequest()
    {
        $this->Request='';
 
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
 
 
 
        // CREATE REQUEST BLOCK
        $this->Request=<<<__REQUEST__
 
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
 <CustomerClassific>
 	<Code>$this->CustomerClassificationCode</Code>
 </CustomerClassific>
  <Shipment>
   <Shipper>
   	<ShipperNumber>$this->ShipperNumber</ShipperNumber>
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
   
   <ShipFrom>
      <Address>
        <City>$this->ShipFromCity</City>
        <StateProvinceCode>$this->ShipFromState</StateProvinceCode>
        <PostalCode>$this->ShipFromPostalCode</PostalCode>
        <CountryCode>$this->ShipFromCountry</CountryCode>
      </Address>
    </ShipFrom>
              
   <Service>
    <Code>$this->UPSServiceCode</Code>
   </Service>

  $PackageList
 
  </Shipment>
</RatingServiceSelectionRequest>
 
 
__REQUEST__;
 
 
    } // END CreateRequest()
 

###############################################################################################
## Process the current settings
###############################################################################################
 
 
    /**
     * Using the current settings, make the cURL request, then on to the distil
     * @param string $out - Previously processed results (like from Rate) to be appended by new results (like from Time In Transit)
     * @return 
     */
    function Process($OUT = null)
    {
        $this->Response='';

        //******************************************************************************
        // INITIALIZE cURL SESSION AND SET OPTIONS.  SEE PHP MANUAL FOR MORE DETAILS.
        // http://www.php.net/manual/en/ref.curl.php
        //******************************************************************************
 
        // INITIALIZE 
        $ch = curl_init ();
 
        // TELL cURL WHERE TO POST THE REQUEST.  UNCOMMENT THE SECOND URL TO SEND A LIVE POST.
        curl_setopt ($ch, CURLOPT_URL, $this->postURL);
 
        // TELL cURL TO DO A REGULAR HTTP POST.
        curl_setopt ($ch, CURLOPT_POST, 1);
 
        // PASS THE REQUEST STRING THAT WE BUILD ABOVE
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $this->Request);
 
        // TELL cURL TO USE strlen()  TO GET THE DATA SIZE.
        //curl_setopt ($ch, CURLOPT_POSTFIELDSIZE, 0);
 
        // TELL cURL WHEN TO TIME OUT 
        //IF YOU'RE TIMING OUT BEFORE GETTING A RESPONSE FROM SUREPAY, INCREASE THIS NUMBER 
         curl_setopt ($ch, CURLOPT_TIMEOUT, 360); 
                           
        // TELL cURL TO INCLUDE THE HEADER IN THE OUTPUT 
        curl_setopt ($ch, CURLOPT_HEADER, 0);
 
        // TELL cURL TO USE SSL VERSION 3.
        curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'SSLv3');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
          
        // TRANSFER THE SUREPAY RESPONSE INTO A VARIABLE.
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
 
 
        //******************************************************************************
        // EXECUTE THE REQUEST
        //******************************************************************************
 
		$this->Response = curl_exec ($ch);
        $this->curlerr = curl_error($ch);
 
        // get stats for later use in debug, if nessesary.
        $this->curl_array = curl_getinfo($ch);
 
        // close curl connection
        curl_close ($ch);
        //******************************************************************************
        // PARSE RESPONSE
        //******************************************************************************
        if(!empty($this->curlerr))
        {
        	return false;
        }
        $reader = new SimpleXMLElement($this->Response);

		if(!isset($OUT) || !is_array($OUT))
		{
			$OUT = array(
				'Success' => 0,
				'Error' => array('Code' => 0),
				'RateOptions' => array()
			);
		}
		
		// Pick and choose the info we want. 
		// TODO: Update to include ALL of the returned data instead of just the little bits we want for the moment. 
		$OUT['Success'] = $reader->Response->ResponseStatusCode;
		
		$errors = $reader->xpath('//Error');
		if(!empty($errors))
		{
			foreach($errors as $e)
			{
				$OUT['Error'] = (array) $e;
			}
		}
		
		if(isset($reader->RatedShipment))
		{
			// RatedShipment won't exist in a TimeInTransit response
			foreach($reader->RatedShipment as $ri => $RS)
			{
				$svc_code = $RS->Service->Code;
				
				if($this->IncludeService($svc_code))
				{
					$svc_info = array(
						'Service' => "$svc_code",
						'ServiceType' => $this->ARRAY_ServiceCodes["$svc_code"],
						'TotalCost' => $RS->TotalCharges->MonetaryValue,
						'GuaranteedDaysToDelivery' => !empty($RS->GuaranteedDaysToDelivery)?$RS->GuaranteedDaysToDelivery:''
					);
					$OUT['RateOptions']["$svc_code"] = $svc_info;
				}
			}
		}
		else if(isset($reader->TransitResponse))
		{
			// TransitResponse won't exist in a Rate response
			foreach($reader->TransitResponse->ServiceSummary as $SS)
			{	
				$svc_code = $this->GetRateCodeFromTITCode($SS->Service->Code);
				
				if($svc_code !== false && $this->IncludeService($svc_code))
				{
					$svc_info = (isset($OUT['RateOptions']["$svc_code"]))?$OUT['RateOptions']["$svc_code"]:array();
					$svc_info['Service'] = "$svc_code";
					$svc_info['Guaranteed'] = $SS->Guaranteed->Code;
					$svc_info['EstimatedArrival'] = (array) $SS->EstimatedArrival;
					$svc_info['SaturdayDelivery'] = isset($SS->SaturdayDelivery)?$SS->SaturdayDelivery:'';
					$OUT['RateOptions']["$svc_code"] = $svc_info;
				}			
			}
		}
		
		return $OUT;
	}  
 
	/**
	 * Check to see if the specified Rate Service Code is enabled or not (enable or disable using SetRateListLimit())
	 * @param string $RateCode
	 * @return boolean
	 */
	private function IncludeService($RateCode)
	{
		if(!isset($this->ARRAY_ServiceCodes["$RateCode"]))
		{
			if($this->debug_enabled)
			{
				// This isn't a critical error, but good to know if you're not getting the results you're expecting
				$this->ERROR[] = "Unknown Rate Code Requested: $RateCode.";
			}
			return false;
		}
		
		return $this->ARRAY_ServiceCodes["$RateCode"]['Display'];
	}
 
###################################################################################################
## Debuging output of interest
###################################################################################################
    function Debug()
    {
        // format Request for display
        $treq=htmlspecialchars($this->Request);
 
        // format Response for display
        $tres=htmlspecialchars($this->Result);
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
## Helper Function that changes an array into a series of nested tables for easy display
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

