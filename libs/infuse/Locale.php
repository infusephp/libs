<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

class Locale
{
	private static $localeInstance;

	private $locale = 'en';
	private $localeDir = false;
	private $localeData;

	/**
	 * Gets an instance of the locale
	 *
	 * @return Locale
	 */
	static function locale()
	{
		if( !self::$localeInstance )
			self::$localeInstance = new Locale();
		
		return self::$localeInstance;
	}

	function __construct( $locale = false )
	{
		if( $locale )
			$this->locale = $locale;
	}

	/**
	 * Sets the locale
	 *
	 * @param string $locale
	 */
	function setLocale( $locale )
	{
		$this->locale = $locale;
	}

	/**
	 * Gets the locale
	 *
	 * @return string
	 */
	function getLocale()
	{
		return $this->locale;
	}

	/**
	 * Sets the directory where locale data files can be loaded from.
	 * Locale data files are expected to be have the same name as the 
	 * locale with a .php extension. The locale data file should return
	 * an array with locale information.
	 *
	 * @param string $dir
	 */
	function setLocaleDataDir( $dir )
	{
		$this->localeDir = $dir;
		$this->localeData = [];
	}

	/**
	 * Translates a phrase
	 *
	 * @param string $phrase
	 * @param array $params parameters to inject into phrase
	 * @param string $locale
	 *
	 * @return string
	 */
	function translate( $phrase, array $params = [], $locale = false )
	{
		if( !$locale )
			$locale = $this->locale;

		// lazy load locale data
		$this->loadLocaleData( $locale );

		// look up the phrase
		$translatedPhrase = Util::array_value( $this->localeData, "$locale.phrases.$phrase" );

		if( $translatedPhrase != null )
		{
			// inject parameters into phrase
			if( count( $params ) > 0 )
			{
				foreach( $params as $param => $paramValue )
					$translatedPhrase = str_replace( '{{' . $param . '}}', $paramValue, $translatedPhrase );
			}

			return $translatedPhrase;
		}

		// if the phrase does not exist for this locale
		// just return the phrase key
		return $phrase;
	}

	/**
	 * Alias for translate()
	 */
	function t( $phrase, array $params = [], $locale = false )
	{
		return $this->translate( $phrase, $params, $locale );
	}

	/**
	 * Pluralizes a string
	 *
	 * @param int $n number in question
	 * @param string $singular singular string
	 * @param string $plural plural string
	 *
	 * @return string
	 */
	function pluralize( $n, $singular, $plural )
	{
		return ($n == 1) ? $singular : $plural;
	}

	/**
	 * Alias for pluaralize()
	 */
	function p( $n, $singular, $plural )
	{
		return $this->pluralize( $n, $singular, $plural );
	}

	/**
	 * Generates a select box for the currencies
	 *
	 * @param string $selectedCurrency
	 *
	 * @return string html
	 */
	function currencyOptions( $selectedCurrency = '' )
	{
		$selectedCurrency = strtolower( $selectedCurrency );
		
		$return ='';

		foreach (self::$currencies as $code => $currency) {
			$codeLower = strtolower($code);
			$selected = ($selectedCurrency == $codeLower) ? 'selected="selected"' : '';
			$return .= '<option value="' . $codeLower . '" ' . $selected . '>' . $code . ' - ' . $currency['name'] . '</option>' . "\n";
		}

		return $return;
	}
	
	/**
	 * Generates a select box for the time zones
	 * - lifted from php.net comments
	 *
	 * @param string $selectedTimezone
	 *
	 * @return string html
	 */
	function timezoneOptions( $selectedTimezone = '' )
	{
		$all = timezone_identifiers_list();
		
		$i = 0;
		foreach($all AS $zone)
		{
			$zone = explode('/',$zone);
			$zonen[$i]['continent'] = isset($zone[0]) ? $zone[0] : '';
			$zonen[$i]['city'] = isset($zone[1]) ? $zone[1] : '';
			$zonen[$i]['subcity'] = isset($zone[2]) ? $zone[2] : '';
			$i++;
		}
		
		asort($zonen);
		$return = '';
		foreach($zonen AS $zone)
		{
			extract($zone);
			if($continent == 'Africa' || $continent == 'America' || $continent == 'Antarctica' || $continent == 'Arctic' || $continent == 'Asia' || $continent == 'Atlantic' || $continent == 'Australia' || $continent == 'Europe' || $continent == 'Indian' || $continent == 'Pacific')
			{
				if(!isset($selectcontinent))
					$return .= '<optgroup label="'.$continent.'">'; // continent
				elseif($selectcontinent != $continent)
					$return .= '</optgroup><optgroup label="'.$continent.'">'; // continent
			
				if(isset($city) != '')
				{
					if (!empty($subcity) != '')
						$city = $city . '/'. $subcity;
						
					$return .= "<option ".((($continent.'/'.$city)==$selectedTimezone)?'selected="selected "':'')." value=\"".($continent.'/'.$city)."\">".str_replace('_',' ',$city)."</option>"; //Timezone
				}
				else
				{
					if (!empty($subcity) != '')
					$city = $city . '/'. $subcity;
					
					$return .= "<option ".(($continent==$selectedTimezone)?'selected="selected "':'')." value=\"".$continent."\">".$continent."</option>"; //Timezone
				}
				
				$selectcontinent = $continent;
			}
		}
		
		$return .= '</optgroup>';

		return $return;
	}

	/**
	 * Loads locale data for a supplied locale
	 *
	 * @param string $locale
	 */
	private function loadLocaleData( $locale )
	{
		if( isset( $this->localeData[ $locale ] ) )
			return;
		
		$filename = str_replace( '//', '/', $this->localeDir . '/' ) . $locale . '.php';

		if( $this->localeDir && file_exists( $filename ) )
			$this->localeData[ $locale ] = include $filename;
		else
			$this->localeData[ $locale ] = [];
	}

	/**
	 * @staticvar $locales
	 *
	 * List of locale codes
	 */
	static $locales = [
		'af-ZA',
		'am-ET',
		'ar-AE',
		'ar-BH',
		'ar-DZ',
		'ar-EG',
		'ar-IQ',
		'ar-JO',
		'ar-KW',
		'ar-LB',
		'ar-LY',
		'ar-MA',
		'arn-CL',
		'ar-OM',
		'ar-QA',
		'ar-SA',
		'ar-SY',
		'ar-TN',
		'ar-YE',
		'as-IN',
		'az-Cyrl-AZ',
		'az-Latn-AZ',
		'ba-RU',
		'be-BY',
		'bg-BG',
		'bn-BD',
		'bn-IN',
		'bo-CN',
		'br-FR',
		'bs-Cyrl-BA',
		'bs-Latn-BA',
		'ca-ES',
		'co-FR',
		'cs-CZ',
		'cy-GB',
		'da-DK',
		'de-AT',
		'de-CH',
		'de-DE',
		'de-LI',
		'de-LU',
		'dsb-DE',
		'dv-MV',
		'el-GR',
		'en-029',
		'en-AU',
		'en-BZ',
		'en-CA',
		'en-GB',
		'en-IE',
		'en-IN',
		'en-JM',
		'en-MY',
		'en-NZ',
		'en-PH',
		'en-SG',
		'en-TT',
		'en-US',
		'en-ZA',
		'en-ZW',
		'es-AR',
		'es-BO',
		'es-CL',
		'es-CO',
		'es-CR',
		'es-DO',
		'es-EC',
		'es-ES',
		'es-GT',
		'es-HN',
		'es-MX',
		'es-NI',
		'es-PA',
		'es-PE',
		'es-PR',
		'es-PY',
		'es-SV',
		'es-US',
		'es-UY',
		'es-VE',
		'et-EE',
		'eu-ES',
		'fa-IR',
		'fi-FI',
		'fil-PH',
		'fo-FO',
		'fr-BE',
		'fr-CA',
		'fr-CH',
		'fr-FR',
		'fr-LU',
		'fr-MC',
		'fy-NL',
		'ga-IE',
		'gd-GB',
		'gl-ES',
		'gsw-FR',
		'gu-IN',
		'ha-Latn-NG',
		'he-IL',
		'hi-IN',
		'hr-BA',
		'hr-HR',
		'hsb-DE',
		'hu-HU',
		'hy-AM',
		'id-ID',
		'ig-NG',
		'ii-CN',
		'is-IS',
		'it-CH',
		'it-IT',
		'iu-Cans-CA',
		'iu-Latn-CA',
		'ja-JP',
		'ka-GE',
		'kk-KZ',
		'kl-GL',
		'km-KH',
		'kn-IN',
		'kok-IN',
		'ko-KR',
		'ky-KG',
		'lb-LU',
		'lo-LA',
		'lt-LT',
		'lv-LV',
		'mi-NZ',
		'mk-MK',
		'ml-IN',
		'mn-MN',
		'mn-Mong-CN',
		'moh-CA',
		'mr-IN',
		'ms-BN',
		'ms-MY',
		'mt-MT',
		'nb-NO',
		'ne-NP',
		'nl-BE',
		'nl-NL',
		'nn-NO',
		'nso-ZA',
		'oc-FR',
		'or-IN',
		'pa-IN',
		'pl-PL',
		'prs-AF',
		'ps-AF',
		'pt-BR',
		'pt-PT',
		'qut-GT',
		'quz-BO',
		'quz-EC',
		'quz-PE',
		'rm-CH',
		'ro-RO',
		'ru-RU',
		'rw-RW',
		'sah-RU',
		'sa-IN',
		'se-FI',
		'se-NO',
		'se-SE',
		'si-LK',
		'sk-SK',
		'sl-SI',
		'sma-NO',
		'sma-SE',
		'smj-NO',
		'smj-SE',
		'smn-FI',
		'sms-FI',
		'sq-AL',
		'sr-Cyrl-BA',
		'sr-Cyrl-CS',
		'sr-Cyrl-ME',
		'sr-Cyrl-RS',
		'sr-Latn-BA',
		'sr-Latn-CS',
		'sr-Latn-ME',
		'sr-Latn-RS',
		'sv-FI',
		'sv-SE',
		'sw-KE',
		'syr-SY',
		'ta-IN',
		'te-IN',
		'tg-Cyrl-TJ',
		'th-TH',
		'tk-TM',
		'tn-ZA',
		'tr-TR',
		'tt-RU',
		'tzm-Latn-DZ',
		'ug-CN',
		'uk-UA',
		'ur-PK',
		'uz-Cyrl-UZ',
		'uz-Latn-UZ',
		'vi-VN',
		'wo-SN',
		'xh-ZA',
		'yo-NG',
		'zh-CN',
		'zh-HK',
		'zh-MO',
		'zh-SG',
		'zh-TW',
		'zu-ZA' ];
	
	/**
	 * @staticvar $currencies
	 *
	 * List of currency codes, names, and symbols
	 **/
	static $currencies = [
		'AED' => [
			'name' => 'United Arab Emirates Dirham',
			'symbol' => 'د.إ'
		],
		'AFN' => [
			'name' => 'Afghanistan Afghani',
			'symbol' => '؋'
		],
		'ALL' => [
			'name' => 'Albania Lek',
			'symbol' => 'Lek'
		],
		'AMD' => [
			'name' => 'Armenia Dram'
		],
		'ANG' => [
			'name' => 'Netherlands Antilles Guilder',
			'symbol' => 'ƒ'
		],
		'AOA' => [
			'name' => 'Angola Kwanza'
		],
		'ARS' => [
			'name' => 'Argentina Peso',
			'symbol' => '$'
		],
		'AUD' => [
			'name' => 'Australia Dollar',
			'symbol' => '$'
		],
		'AWG' => [
			'name' => 'Aruba Guilder',
			'symbol' => 'ƒ'
		],
		'AZN' => [
			'name' => 'Azerbaijan New Manat',
			'symbol' => 'ман'
		],
		'BAM' => [
			'name' => 'Bosnia and Herzegovina Convertible Marka',
			'symbol' => 'KM'
		],
		'BBD' => [
			'name' => 'Barbados Dollar',
			'symbol' => '$'
		],
		'BDT' => [
			'name' => 'Bangladesh Taka'
		],
		'BGN' => [
			'name' => 'Bulgaria Lev',
			'symbol' => 'лв'
		],
		'BHD' => [
			'name' => 'Bahrain Dinar'
		],
		'BIF' => [
			'name' => 'Burundi Franc'
		],
		'BMD' => [
			'name' => 'Bermuda Dollar',
			'symbol' => '$'
		],
		'BND' => [
			'name' => 'Brunei Darussalam Dollar',
			'symbol' => '$'
		],
		'BOB' => [
			'name' => 'Bolivia Boliviano',
			'symbol' => '$b'
		],
		'BRL' => [
			'name' => 'Brazil Real',
			'symbol' => 'R$'
		],
		'BSD' => [
			'name' => 'Bahamas Dollar',
			'symbol' => '$'
		],
		'BTN' => [
			'name' => 'Bhutan Ngultrum'
		],
		'BWP' => [
			'name' => 'Botswana Pula',
			'symbol' => 'P'
		],
		'BYR' => [
			'name' => 'Belarus Ruble',
			'symbol' => 'p.'
		],
		'BZD' => [
			'name' => 'Belize Dollar',
			'symbol' => 'BZ$'
		],
		'CAD' => [
			'name' => 'Canada Dollar',
			'symbol' => '$'
		],
		'CDF' => [
			'name' => 'Congo/Kinshasa Franc'
		],
		'CHF' => [
			'name' => 'Switzerland Franc',
			'symbol' => 'CHF'
		],
		'CLP' => [
			'name' => 'Chile Peso',
			'symbol' => '$'
		],
		'CNY' => [
			'name' => 'China Yuan Renminbi',
			'symbol' => '¥'
		],
		'COP' => [
			'name' => 'Colombia Peso',
			'symbol' => 'p.'
		],
		'CRC' => [
			'name' => 'Costa Rica Colon',
			'symbol' => '₡'
		],
		'CUC' => [
			'name' => 'Cuba Convertible Peso'
		],
		'CUP' => [
			'name' => 'Cuba Peso',
			'symbol' => '₱'
		],
		'CVE' => [
			'name' => 'Cape Verde Escudo'
		],
		'CZK' => [
			'name' => 'Czech ReKoruna',
			'symbol' => 'Kč'
		],
		'DJF' => [
			'name' => 'Djibouti Franc',
			'symbol' => 'CHF'
		],
		'DKK' => [
			'name' => 'Denmark Krone',
			'symbol' => 'kr'
		],
		'DOP' => [
			'name' => 'Dominican RePeso',
			'symbol' => 'RD$'
		],
		'DZD' => [
			'name' => 'Algeria Dinar'
		],
		'EGP' => [
			'name' => 'Egypt Pound',
			'symbol' => '£'
		],
		'ERN' => [
			'name' => 'Eritrea Nakfa'
		],
		'ETB' => [
			'name' => 'Ethiopia Birr'
		],
		'EUR' => [
			'name' => 'Euro Member Countries',
			'symbol' => '€'
		],
		'FJD' => [
			'name' => 'Fiji Dollar',
			'symbol' => '$'
		],
		'FKP' => [
			'name' => 'Falkland Islands (Malvinas) Pound',
			'symbol' => '£'
		],
		'GBP' => [
			'name' => 'United Kingdom Pound',
			'symbol' => '£'
		],
		'GEL' => [
			'name' => 'Georgia Lari'
		],
		'GGP' => [
			'name' => 'Guernsey Pound',
			'symbol' => '£'
		],
		'GHS' => [
			'name' => 'Ghana Cedi'
		],
		'GIP' => [
			'name' => 'Gibraltar Pound',
			'symbol' => '£'
		],
		'GMD' => [
			'name' => 'Gambia Dalasi'
		],
		'GNF' => [
			'name' => 'Guinea Franc'
		],
		'GTQ' => [
			'name' => 'Guatemala Quetzal',
			'symbol' => 'Q'
		],
		'GYD' => [
			'name' => 'Guyana Dollar',
			'symbol' => '$'
		],
		'HKD' => [
			'name' => 'Hong Kong Dollar',
			'symbol' => 'HK$'
		],
		'HNL' => [
			'name' => 'Honduras Lempira',
			'symbol' => 'L'
		],
		'HRK' => [
			'name' => 'Croatia Kuna',
			'symbol' => 'kn'
		],
		'HTG' => [
			'name' => 'Haiti Gourde'
		],
		'HUF' => [
			'name' => 'Hungary Forint',
			'symbol' => 'Ft'
		],
		'IDR' => [
			'name' => 'Indonesia Rupiah',
			'symbol' => 'Rp'
		],
		'ILS' => [
			'name' => 'Israel Shekel',
			'symbol' => '₪'
		],
		'IMP' => [
			'name' => 'Isle of Man Pound',
			'symbol' => '£'
		],
		'INR' => [
			'name' => 'India Rupee',
			'symbol' => '₹'
		],
		'IQD' => [
			'name' => 'Iraq Dinar'
		],
		'IRR' => [
			'name' => 'Iran Rial',
			'symbol' => '﷼'
		],
		'ISK' => [
			'name' => 'Iceland Krona',
			'symbol' => 'kr'
		],
		'JEP' => [
			'name' => 'Jersey Pound',
			'symbol' => '£'
		],
		'JMD' => [
			'name' => 'Jamaica Dollar',
			'symbol' => 'J$'
		],
		'JOD' => [
			'name' => 'Jordan Dinar'
		],
		'JPY' => [
			'name' => 'Japan Yen',
			'symbol' => '¥'
		],
		'KES' => [
			'name' => 'Kenya Shilling',
			'symbol' => 'KSh'
		],
		'KGS' => [
			'name' => 'Kyrgyzstan Som',
			'symbol' => 'лв'
		],
		'KHR' => [
			'name' => 'Cambodia Riel',
			'symbol' => '៛'
		],
		'KMF' => [
			'name' => 'Comoros Franc'
		],
		'KPW' => [
			'name' => 'Korea (North) Won',
			'symbol' => '₩'
		],
		'KRW' => [
			'name' => 'Korea (South) Won',
			'symbol' => '₩'
		],
		'KWD' => [
			'name' => 'Kuwait Dinar'
		],
		'KYD' => [
			'name' => 'Cayman Islands Dollar',
			'symbol' => '$'
		],
		'KZT' => [
			'name' => 'Kazakhstan Tenge',
			'symbol' => 'лв'
		],
		'LAK' => [
			'name' => 'Laos Kip',
			'symbol' => '₭'
		],
		'LBP' => [
			'name' => 'Lebanon Pound',
			'symbol' => '£'
		],
		'LKR' => [
			'name' => 'Sri Lanka Rupee',
			'symbol' => '₨'
		],
		'LRD' => [
			'name' => 'Liberia Dollar',
			'symbol' => '$'
		],
		'LSL' => [
			'name' => 'Lesotho Loti'
		],
		'LTL' => [
			'name' => 'Lithuania Litas',
			'symbol' => 'Lt'
		],
		'LVL' => [
			'name' => 'Latvia Lat',
			'symbol' => 'Ls'
		],
		'LYD' => [
			'name' => 'Libya Dinar'
		],
		'MAD' => [
			'name' => 'Morocco Dirham'
		],
		'MDL' => [
			'name' => 'Moldova Leu'
		],
		'MGA' => [
			'name' => 'Madagascar Ariary'
		],
		'MKD' => [
			'name' => 'Macedonia Denar',
			'symbol' => 'ден'
		],
		'MMK' => [
			'name' => 'Myanmar (Burma) Kyat'
		],
		'MNT' => [
			'name' => 'Mongolia Tughrik',
			'symbol' => '₮'
		],
		'MOP' => [
			'name' => 'Macau Pataca'
		],
		'MRO' => [
			'name' => 'Mauritania Ouguiya'
		],
		'MUR' => [
			'name' => 'Mauritius Rupee',
			'symbol' => '₨'
		],
		'MVR' => [
			'name' => 'Maldives (Maldive Islands) Rufiyaa'
		],
		'MWK' => [
			'name' => 'Malawi Kwacha'
		],
		'MXN' => [
			'name' => 'Mexico Peso',
			'symbol' => '$'
		],
		'MYR' => [
			'name' => 'Malaysia Ringgit',
			'symbol' => 'RM'
		],
		'MZN' => [
			'name' => 'Mozambique Metical',
			'symbol' => 'MT'
		],
		'NAD' => [
			'name' => 'Namibia Dollar',
			'symbol' => '$'
		],
		'NGN' => [
			'name' => 'Nigeria Naira',
			'symbol' => '₦'
		],
		'NIO' => [
			'name' => 'Nicaragua Cordoba',
			'symbol' => 'C$'
		],
		'NOK' => [
			'name' => 'Norway Krone',
			'symbol' => 'kr'
		],
		'NPR' => [
			'name' => 'Nepal Rupee',
			'symbol' => '₨'
		],
		'NZD' => [
			'name' => 'New Zealand Dollar',
			'symbol' => '$'
		],
		'OMR' => [
			'name' => 'Oman Rial',
			'symbol' => '﷼'
		],
		'PAB' => [
			'name' => 'Panama Balboa',
			'symbol' => 'B/.'
		],
		'PEN' => [
			'name' => 'Peru Nuevo Sol',
			'symbol' => 'S/.'
		],
		'PGK' => [
			'name' => 'Papua New Guinea Kina'
		],
		'PHP' => [
			'name' => 'Philippines Peso',
			'symbol' => '₱'
		],
		'PKR' => [
			'name' => 'Pakistan Rupee',
			'symbol' => '₨'
		],
		'PLN' => [
			'name' => 'Poland Zloty',
			'symbol' => 'zł'
		],
		'PYG' => [
			'name' => 'Paraguay Guarani',
			'symbol' => 'Gs'
		],
		'QAR' => [
			'name' => 'Qatar Riyal',
			'symbol' => '﷼'
		],
		'RON' => [
			'name' => 'Romania New Leu',
			'symbol' => 'lei'
		],
		'RSD' => [
			'name' => 'Serbia Dinar',
			'symbol' => 'Дин.'
		],
		'RUB' => [
			'name' => 'Russia Ruble',
			'symbol' => 'руб'
		],
		'RWF' => [
			'name' => 'Rwanda Franc'
		],
		'SAR' => [
			'name' => 'Saudi Arabia Riyal',
			'symbol' => '﷼'
		],
		'SBD' => [
			'name' => 'Solomon Islands Dollar',
			'symbol' => '$'
		],
		'SCR' => [
			'name' => 'Seychelles Rupee',
			'symbol' => '₨'
		],
		'SDG' => [
			'name' => 'Sudan Pound'
		],
		'SEK' => [
			'name' => 'Sweden Krona',
			'symbol' => 'kr'
		],
		'SGD' => [
			'name' => 'Singapore Dollar',
			'symbol' => '$'
		],
		'SHP' => [
			'name' => 'Saint Helena Pound',
			'symbol' => '£'
		],
		'SLL' => [
			'name' => 'Sierra Leone Leone'
		],
		'SOS' => [
			'name' => 'Somalia Shilling',
			'symbol' => 'S'
		],
		'SPL*' => [
			'name' => 'Seborga Luigino'
		],
		'SRD' => [
			'name' => 'Suriname Dollar',
			'symbol' => '$'
		],
		'STD' => [
			'name' => '	São Tomé and Príncipe Dobra'
		],
		'SVC' => [
			'name' => 'El Salvador Colon',
			'symbol' => '$'
		],
		'SYP' => [
			'name' => 'Syria Pound',
			'symbol' => '£'
		],
		'SZL' => [
			'name' => 'Swaziland Lilangeni'
		],
		'THB' => [
			'name' => 'Thailand Baht',
			'symbol' => '฿'
		],
		'TJS' => [
			'name' => 'Tajikistan Somoni'
		],
		'TMT' => [
			'name' => 'Turkmenistan Manat'
		],
		'TND' => [
			'name' => 'Tunisia Dinar',
			'symbol' => 'DT'
		],
		'TOP' => [
			'name' => "Tonga Paanga"
		],
		'TRY' => [
			'name' => 'Turkey Lira',
			'symbol' => 'TRY'
		],
		'TTD' => [
			'name' => 'Trinidad and Tobago Dollar',
			'symbol' => 'TT$'
		],
		'TVD' => [
			'name' => 'Tuvalu Dollar',
			'symbol' => '$'
		],
		'TWD' => [
			'name' => 'Taiwan New Dollar',
			'symbol' => 'NT$'
		],
		'TZS' => [
			'name' => 'Tanzania Shilling',
			'symbol' => 'TSh'
		],
		'UAH' => [
			'name' => 'Ukraine Hryvna',
			'symbol' => '₴'
		],
		'UGX' => [
			'name' => 'Uganda Shilling',
			'symbol' => 'USh'
		],
		'USD' => [
			'name' => 'United States Dollar',
			'symbol' => '$'
		],
		'UYU' => [
			'name' => 'Uruguay Peso',
			'symbol' => '$U'
		],
		'UZS' => [
			'name' => 'Uzbekistan Som',
			'symbol' => 'лв'
		],
		'VEF' => [
			'name' => 'Venezuela Bolivar',
			'symbol' => 'Bs'
		],
		'VND' => [
			'name' => 'Viet Nam Dong',
			'symbol' => '₫'
		],
		'VUV' => [
			'name' => 'Vanuatu Vatu'
		],
		'WST' => [
			'name' => 'Samoa Tala'
		],
		'XCD' => [
			'name' => 'East Caribbean Dollar',
			'symbol' => '$'
		],
		'XDR' => [
			'name' => 'International Monetary Fund (IMF) Special Drawing Rights'
		],
		'XOF' => [
			'name' => 'Communauté Financière Africaine (BCEAO) Franc'
		],
		'XPF' => [
			'name' => 'Comptoirs Français du Pacifique (CFP) Franc'
		],
		'YER' => [
			'name' => 'Yemen Rial',
			'symbol' => '﷼'
		],
		'ZAR' => [
			'name' => 'South Africa Rand',
			'symbol' => 'R'
		],
		'ZWD' => [
			'name' => 'Zimbabwe Dollar',
			'symbol' => 'Z$'
		]
	];

	static $usaStates = [
		'AL' => "Alabama",
        'AK' => "Alaska", 
        'AZ' => "Arizona", 
        'AR' => "Arkansas", 
        'CA' => "California", 
        'CO' => "Colorado", 
        'CT' => "Connecticut", 
        'DE' => "Delaware", 
        'DC' => "District Of Columbia", 
        'FL' => "Florida", 
        'GA' => "Georgia", 
        'HI' => "Hawaii", 
        'ID' => "Idaho", 
        'IL' => "Illinois", 
        'IN' => "Indiana", 
        'IA' => "Iowa", 
        'KS' => "Kansas", 
        'KY' => "Kentucky", 
        'LA' => "Louisiana", 
        'ME' => "Maine", 
        'MD' => "Maryland", 
        'MA' => "Massachusetts", 
        'MI' => "Michigan", 
        'MN' => "Minnesota", 
        'MS' => "Mississippi", 
        'MO' => "Missouri", 
        'MT' => "Montana",
        'NE' => "Nebraska",
        'NV' => "Nevada",
        'NH' => "New Hampshire",
        'NJ' => "New Jersey",
        'NM' => "New Mexico",
        'NY' => "New York",
        'NC' => "North Carolina",
        'ND' => "North Dakota",
        'OH' => "Ohio", 
        'OK' => "Oklahoma", 
        'OR' => "Oregon", 
        'PA' => "Pennsylvania", 
        'RI' => "Rhode Island", 
        'SC' => "South Carolina", 
        'SD' => "South Dakota",
        'TN' => "Tennessee",
        'TX' => "Texas",
        'UT' => "Utah",
        'VT' => "Vermont",
        'VA' => "Virginia",
        'WA' => "Washington",
        'WV' => "West Virginia",
        'WI' => "Wisconsin", 
        'WY' => "Wyoming" ];
}