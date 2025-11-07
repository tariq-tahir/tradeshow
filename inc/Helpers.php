<?php
/**
 * Helpers methods
 * List all your static functions you wish to use globally on your theme
 *
 * @package awps
 */


// Common Key Benefits (for autocomplete)
function get_key_benefits_options(){
    return [
        "Extra Long Grain (≥7.5mm)",
        "Aroma Retention Guaranteed",
        "FDA Compliant",
        "OEM/ODM Packaging Available",
        "Custom Milling Available",
        "Custom Polishing Available",
        "Bulk Discounts Available",
        "Flexible MOQ",
        "Fast Lead Time",
        "Traceable Batch Number",
        "Lab Tested for Purity",
        "Non-GMO",
        "Organic Certified",
        "Gluten Free",
        "Vegan Friendly",
        "Eco-Friendly Packaging"
    ];
}

// Common Quality Control Phrases (for autocomplete)
function get_quality_control_options(){
    return [
        "Lab Tested for Purity",
        "Lab Tested for Moisture",
        "Lab Tested for Adulteration",
        "Batch Traceability",
        "Third-Party Inspection Available",
        "SGS Certified",
        "BV Certified",
        "Pre-Shipment Inspection",
        "Certificate of Analysis Provided",
        "MSDS Available",
        "Shelf Life: 12 Months",
        "Shelf Life: 24 Months",
        "Storage: Cool & Dry Place",
        "Storage: Refrigerated",
        "Storage: Frozen"
    ];
}

// Sample Policies
function get_sample_policies(){
	return [
    "Free Sample",
    "Free Sample, Buyer Pays Shipping",
    "Paid Sample (Refundable on Bulk Order)",
    "Paid Sample (Non-Refundable)",
    "Sample Available Upon Request",
    "Sample Not Available",
	"Sample Provided with Customization",
    "Sample Lead Time: Immediate Dispatch",
    "Sample Lead Time: 3-7 Days",
    "Sample Lead Time: More than 7 Days",
    "Sample with OEM/ODM Available"
	];
} 

// Packaging Options
function get_packaging_options(){
	return [
    "Standard Pallet",
    "Euro Pallet",
    "Plastic Pallet",
    "Wooden Pallet",
    "Slip Sheet",
    "20ft Dry Container",
    "40ft Dry Container",
    "40ft High Cube Container",
    "Reefer Container (Refrigerated)",
    "Open Top Container",
    "Flat Rack Container",
    "Tank Container",
    "Corrugated Carton",
    "Wooden Crate",
    "Plastic Box",
    "Metal Box",
    "Fiberboard Box",
    "Jute Sack",
    "Polypropylene Bag",
    "Paper Bag",
    "Bulk Bag (FIBC)",
    "Mesh Bag",
    "Steel Drum",
    "Plastic Drum",
    "Fiber Drum",
    "Wooden Barrel",
    "Shrink Wrap",
    "Bubble Wrap",
    "Custom Packaging",
    "Loose/Bulk Cargo"
];
}

// Shipping Methods
function get_shipping_methods(){
	return [
    "FCL (Full Container Load)",
    "LCL (Less than Container Load)",
    "RORO (Roll-on Roll-off)",
    "Bulk Cargo",
    "Breakbulk",
    "Express Air Cargo",
    "Consolidated Air Freight",
    "Charter Air Freight",
    "Truck (FTL - Full Truck Load)",
    "Truck (LTL - Less than Truck Load)",
    "Rail Freight",
    "Express Courier (DHL, FedEx, UPS, TNT)",
    "Postal Service",
    "Door-to-Door Courier",
    "Sea-Air Combined",
    "Rail-Sea Combined",
    "Truck-Sea Combined"
];
}

// Payment Terms
function get_payment_terms(){
	return [
    "Cash in Advance (CIA)",
    "Telegraphic Transfer (TT)",
    "Wire Transfer",
    "Open Account 30 Days",
    "Open Account 60 Days",
    "Open Account 90 Days",
    "Irrevocable Letter of Credit",
    "Revocable Letter of Credit",
    "Confirmed Letter of Credit",
    "Unconfirmed Letter of Credit",
    "Sight Letter of Credit",
    "Usance Letter of Credit (Deferred Payment)",
    "Documents against Payment (D/P)",
    "Documents against Acceptance (D/A)",
    "Cash on Delivery (COD)",
    "Escrow Payment",
    "Consignment",
    "Bank Draft",
    "Standby Letter of Credit (SBLC)"
];
} 



function get_intoterms(){
	return [
    "EXW" => "Ex Works",
    "FCA" => "Free Carrier",
    "FAS" => "Free Alongside Ship",
    "FOB" => "Free On Board",
    "CFR" => "Cost and Freight",
    "CIF" => "Cost, Insurance and Freight",
    "CPT" => "Carriage Paid To",
    "CIP" => "Carriage and Insurance Paid To",
    "DAP" => "Delivered At Place",
    "DPU" => "Delivered At Place Unloaded", // introduced in Incoterms 2020 (replaced DAT)
    "DDP" => "Delivered Duty Paid"
	];
}

function get_languages() {
	return [
		    'English', 'Mandarin Chinese', 'Hindi', 'Spanish', 'French', 'Arabic', 
    'Bengali', 'Portuguese', 'Russian', 'Urdu', 'Indonesian', 'German', 
    'Japanese', 'Swahili', 'Italian', 'Dutch', 'Turkish', 'Korean', 
    'Vietnamese', 'Polish', 'Ukrainian', 'Romanian', 'Persian', 'Thai', 
    'Greek', 'Czech', 'Swedish', 'Danish', 'Finnish', 'Norwegian', 
    'Hebrew', 'Hungarian', 'Slovak', 'Catalan', 'Serbian', 'Croatian', 
    'Bulgarian', 'Lithuanian', 'Slovenian', 'Latvian', 'Estonian', 
    'Maltese', 'Afrikaans', 'Icelandic', 'Irish', 'Welsh', 'Basque', 
    'Galician', 'Breton', 'Scottish Gaelic', 'Albanian', 'Armenian', 
    'Georgian', 'Azerbaijani', 'Kazakh', 'Uzbek', 'Turkmen', 'Kyrgyz', 
    'Tajik', 'Mongolian', 'Nepali', 'Sinhala', 'Malayalam', 'Tamil', 
    'Telugu', 'Kannada', 'Marathi', 'Gujarati', 'Punjabi', 'Odia', 
    'Sindhi', 'Kashmiri', 'Sanskrit', 'Burmese', 'Khmer', 'Lao', 
    'Tibetan', 'Uyghur', 'Dzongkha', 'Malay', 'Tagalog', 'Malagasy', 
    'Yoruba', 'Igbo', 'Hausa', 'Somali', 'Oromo', 'Amharic', 
    'Zulu', 'Xhosa', 'Shona', 'Kinyarwanda', 'Kirundi', 'Sesotho', 
    'Tswana', 'Swati', 'Venda', 'Tsonga', 'Sango', 'Fulah', 
    'Wolof', 'Mandinka', 'Diola', 'Ewe', 'Twi', 'Fon', 
    'Bambara', 'Chewa', 'Kikongo', 'Lingala', 'Luganda', 'Runyankole', 
    'Kinyarwanda', 'Chichewa', 'Maasai', 'Khoekhoe', 'Sango', 'Tigrinya', 
    'Dinka', 'Nuer', 'Bari', 'Zarma', 'Kanuri', 'Tamasheq', 
    'Berber', 'Afar', 'Beja', 'Sidamo', 'Hadiyya', 'Kambaata'
	];
}

function get_countries() {
	return [
		'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina',
    'Armenia', 'Australia', 'Austria', 'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados',
    'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana',
    'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia', 'Cameroon',
    'Canada', 'Central African Republic', 'Chad', 'Chile', 'China', 'Colombia', 'Comoros',
    'Congo (Congo-Brazzaville)', 'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czechia (Czech Republic)',
    'Democratic Republic of the Congo', 'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador',
    'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini (fmr. "Swaziland")',
    'Ethiopia', 'Fiji', 'Finland', 'France', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece',
    'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana', 'Haiti', 'Honduras', 'Hungary', 'Iceland',
    'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy', 'Jamaica', 'Japan', 'Jordan',
    'Kazakhstan', 'Kenya', 'Kiribati', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho',
    'Liberia', 'Libya', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia',
    'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico', 'Micronesia',
    'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar (formerly Burma)',
    'Namibia', 'Nauru', 'Nepal', 'Netherlands', 'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Korea',
    'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palau', 'Palestine State', 'Panama', 'Papua New Guinea',
    'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda',
    'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines', 'Samoa', 'San Marino',
    'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone', 'Singapore',
    'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Korea', 'South Sudan', 'Spain',
    'Sri Lanka', 'Sudan', 'Suriname', 'Sweden', 'Switzerland', 'Syria', 'Tajikistan', 'Tanzania', 'Thailand',
    'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu',
    'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States of America', 'Uruguay',
    'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe'
	];
}

// ✅ Get full state → city map (same as ProfileFields.php)
function get_state_cities() {
    return [
        'Punjab' => ['Lahore', 'Faisalabad', 'Rawalpindi', 'Multan', 'Gujranwala', 'Sargodha', 'Bahawalpur', 'Sialkot', 'Sheikhupura', 'Jhang', 'Rajanpur', 'Chiniot', 'Mian Channu', 'Dera Ghazi Khan', 'Kamalia', 'Bhakkar', 'Wazirabad', 'Mandi Bahauddin'],
        'Sindh' => ['Karachi', 'Hyderabad', 'Sukkur', 'Larkana', 'Nawabshah', 'Thatta', 'Badin', 'Dadu', 'Jamshoro', 'Tando Allahyar', 'Tando Muhammad Khan', 'Sanghar', 'Umerkot', 'Mirpur Khas', 'Shikarpur', 'Kandhkot', 'Dera Murad Jamali'],
        'Khyber Pakhtunkhwa' => ['Peshawar', 'Mardan', 'Abbottabad', 'Swat', 'Kohat', 'Dera Ismail Khan', 'Bannu', 'Charsadda', 'Nowshera', 'Chitral', 'Malakand', 'Dir', 'Bajaur', 'Karak', 'Hangu', 'Shangla'],
        'Balochistan' => ['Quetta', 'Turbat', 'Gwadar', 'Khuzdar', 'Sibi', 'Ziarat', 'Lasbela', 'Hub', 'Awaran', 'Dera Bugti', 'Kachhi', 'Kharan', 'Mastung', 'Washuk', 'Lehri', 'Chaman', 'Panjgur'],
        'Islamabad Capital Territory' => ['Islamabad'],
        'Gilgit-Baltistan' => ['Gilgit', 'Skardu', 'Hunza', 'Ghanche', 'Gojal', 'Shigar', 'Yasin'],
        'Azad Kashmir' => ['Muzaffarabad', 'Mirpur', 'Kotli', 'Rawalakot', 'Poonch', 'Neelum', 'Bagh']
    ];
}



if ( ! function_exists( 'dd' ) ) {
	/**
	 * Var_dump and die method
	 *
	 * @return void
	 */
	function dd() {
		echo '<pre>';
		array_map( function( $x ) {
			var_dump( $x );
		}, func_get_args() );
		echo '</pre>';
		die;
	}
}

if ( ! function_exists( 'starts_with' ) ) {
	/**
	 * Determine if a given string starts with a given substring.
	 *
	 * @param  string  $haystack
	 * @param  string|array  $needles
	 * @return bool
	 */
	function starts_with($haystack, $needles)
	{
		foreach ((array) $needles as $needle) {
			if ($needle != '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
				return true;
			}
		}
		return false;
	}
}

if (! function_exists('mix')) {
	/**
	 * Get the path to a versioned Mix file.
	 *
	 * @param  string  $path
	 * @param  string  $manifestDirectory
	 * @return \Illuminate\Support\HtmlString
	 *
	 * @throws \Exception
	 */
	function mix($path, $manifestDirectory = '')
	{
		if (! $manifestDirectory) {
			//Setup path for standard AWPS-Folder-Structure
			$manifestDirectory = "assets/dist/";
		}
		static $manifest;
		if (! starts_with($path, '/')) {
			$path = "/{$path}";
		}
		if ($manifestDirectory && ! starts_with($manifestDirectory, '/')) {
			$manifestDirectory = "/{$manifestDirectory}";
		}
		$rootDir = dirname(__FILE__, 2);
		if (file_exists($rootDir . '/' . $manifestDirectory.'/hot')) {
			return getenv('WP_SITEURL') . ":8080" . $path;
		}
		if (! $manifest) {
			$manifestPath =  $rootDir . $manifestDirectory . 'mix-manifest.json';
			if (! file_exists($manifestPath)) {
				throw new Exception('The Mix manifest does not exist.');
			}
			$manifest = json_decode(file_get_contents($manifestPath), true);
		}

		if (starts_with($manifest[$path], '/')) {
			$manifest[$path] = ltrim($manifest[$path], '/');
		}

		$path = $manifestDirectory . $manifest[$path];

		return get_template_directory_uri() . $path;
	}
}

if ( ! function_exists('assets') ) {
	/**
	 * Easily point to the assets dist folder.
	 *
	 * @param  string  $path
	 */
	function assets($path)
	{
		if (! $path) {
			return;
		}

		echo get_template_directory_uri() . '/assets/dist/' . $path;
	}
}

if ( ! function_exists('svg') ) {
	/**
	 * Easily point to the assets dist folder.
	 *
	 * @param  string  $path
	 */
	function svg($path)
	{
		if (! $path) {
			return;
		}

		echo get_template_part('assets/dist/svg/inline', $path . '.svg');
	}
}
