TI-Planet API
===

TI-Planet API					     Version 1.1 

See PDF documentation for a better output ;)

 
## Introduction
First, we'd like to thank you for your interest in the TI-Planet API ; we can't wait to see what you'll create!
Here are a few things you should know before getting to the heart of the matter.
*	API Key : Like many other APIs, ours too needs you to provide your unique API Key in order to function, in each request. You can obtain one by emailing us (info -at- tiplanet.org) and tell us in a few words what you're going to do with the API.
*	Protocol & URL : The API is located at  tiplanet.org/api.php , accessible in HTTP or HTTPS.
*	HTTP methods : The API accepts both GET and POST HTTP requests. For the sake of clarity, the documentation will be using GET methods.
*	"Fair use" : We'd like you to use the API responsibly and reasonably - don't be evil ;-)
*	Legal : The TI-Planet API is "as is" with no express or implied warranty for accuracy or accessibility.


## Request types
The API provides 2 types of requests : searching for archives and getting information on an archive.
To chose between those two, specify the type in a "req" (request) parameter:
* …&req=search    	   for an archive search request
*	…&req=arc    	      for an archive information request 

### Archive search
In order to search within the TI-Planet archives, you can use one or several of the following filters : 
*	…&req=search&name=XXXX        for filtering by the title of the archives
*	…&req=search&author=XXXX      for filtering by name of the archives' author(s)
*	…&req=search&platform=XXXX    for filtering by the platform of the archives


### Archive information
In order to retreive information about a specific archive , you must provide the "arcID" parameter: 
…&req=info&arcID=XXXX      	  "arcID" being the TI-Planet archive ID

*Note : the ‘arcID' value is a positive integer.*


## Output format
The API provides 3 output formats: JSON (default and recommanded), XML, or as a PHP serialized-array.
To chose between those three, specify the type in an "output" parameter :
*	…&output=xml     for a XML (1.0 valid) response (application/xml)
*	…&output=php     for a PHP serialized-array response (application/vnd.php.serialized)
*	…&output=json    (default – not needed) (application/json)

*Note :  ‘phpdebug' is also available for debugging purposes, which outputs the response with print_r().*

*Note : gz-compression can be enabled by passing  …&gz=1  (disabled by default).*


## Response
A response is given when both the API key and the request are valid. It contains the following elements:

### Request-related fields
Ever response will contain the 3 following fields :
*	Status         either 0 (OK) or the error code	(integer)
*	Message        details about the Status		(string)
*	Results        the number of results		(integer)

*Note : An additional "Alert" field will be added in case of an unrecognized output format variable, with a "Unrecognized output type 'xxxxx' ; defaulting to json." message.*

Possible error codes with their corresponding messages, by order of importance :
*	 1 : "No API key given !"
*	 2 : "Invalid API key !"
*	10 : "No request type given !"
*	11 : "Unrecognized request type : 'xxxxx' !"
*	20 : "Neither 'name' nor 'author' nor 'platform' parameter given !"
*	30 : "No archive id ('arcID') given !"
*	31 : "The archive does not exist !"
*	32 : "The archive is private !"

### Result(s) of the request
The results are outputted as indexed arrays (json/php) or within a "ResultX" tag (XML), containing all or a subset of the following keys/values (json/php) or tags (XML), which depend on the request type:

#### Archive search request
"arcID", "name", "platform".

#### Archive information request
"arcID", "name", "upload_date", "author", "category", "screenshot", "url", "dlcount", "nspire_os", "license", "platform", "page".

*Note : The 'author', 'platform' and 'category' fields are arrays of string(s).*


## Examples
Searching for all archives by "Adriweb" on the z80 platform, with XML output :
http://tiplanet.org/api.php?key=XXXXXXX&req=search&author=Adriweb&platform=z80&output=xml

Getting information about the archive with ID 6034 :
http://tiplanet.org/api.php?key=XXXXXXX&req=arc&arcID=6034


## Feedback
A question ? A bug report ? A feature request ? We do welcome any feedback, of course :-) 
You can post on our forum or drop us an email (info -at- tiplanet.org).

