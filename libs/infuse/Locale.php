<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.18.1
 * @copyright 2013 Jared King
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
		$this->localeData = array();
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
	function translate( $phrase, array $params = array(), $locale = false )
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
	function t( $phrase, array $params = array(), $locale = false )
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
			$this->localeData[ $locale ] = array();
	}

	/**
	 * @staticvar $locales
	 *
	 * List of locale codes
	 */
	static $locales = array(
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
		'zu-ZA' );
	
	/**
	 * @staticvar $currencies
	 *
	 * List of currency codes, names, and symbols
	 **/
	static $currencies = array(
		'AED' => array(
			'name' => 'United Arab Emirates Dirham',
			'symbol' => 'د.إ'
		),
		'AFN' => array(
			'name' => 'Afghanistan Afghani',
			'symbol' => '؋'
		),
		'ALL' => array(
			'name' => 'Albania Lek',
			'symbol' => 'Lek'
		),
		'AMD' => array(
			'name' => 'Armenia Dram'
		),
		'ANG' => array(
			'name' => 'Netherlands Antilles Guilder',
			'symbol' => 'ƒ'
		),
		'AOA' => array(
			'name' => 'Angola Kwanza'
		),
		'ARS' => array(
			'name' => 'Argentina Peso',
			'symbol' => '$'
		),
		'AUD' => array(
			'name' => 'Australia Dollar',
			'symbol' => '$'
		),
		'AWG' => array(
			'name' => 'Aruba Guilder',
			'symbol' => 'ƒ'
		),
		'AZN' => array(
			'name' => 'Azerbaijan New Manat',
			'symbol' => 'ман'
		),
		'BAM' => array(
			'name' => 'Bosnia and Herzegovina Convertible Marka',
			'symbol' => 'KM'
		),
		'BBD' => array(
			'name' => 'Barbados Dollar',
			'symbol' => '$'
		),
		'BDT' => array(
			'name' => 'Bangladesh Taka'
		),
		'BGN' => array(
			'name' => 'Bulgaria Lev',
			'symbol' => 'лв'
		),
		'BHD' => array(
			'name' => 'Bahrain Dinar'
		),
		'BIF' => array(
			'name' => 'Burundi Franc'
		),
		'BMD' => array(
			'name' => 'Bermuda Dollar',
			'symbol' => '$'
		),
		'BND' => array(
			'name' => 'Brunei Darussalam Dollar',
			'symbol' => '$'
		),
		'BOB' => array(
			'name' => 'Bolivia Boliviano',
			'symbol' => '$b'
		),
		'BRL' => array(
			'name' => 'Brazil Real',
			'symbol' => 'R$'
		),
		'BSD' => array(
			'name' => 'Bahamas Dollar',
			'symbol' => '$'
		),
		'BTN' => array(
			'name' => 'Bhutan Ngultrum'
		),
		'BWP' => array(
			'name' => 'Botswana Pula',
			'symbol' => 'P'
		),
		'BYR' => array(
			'name' => 'Belarus Ruble',
			'symbol' => 'p.'
		),
		'BZD' => array(
			'name' => 'Belize Dollar',
			'symbol' => 'BZ$'
		),
		'CAD' => array(
			'name' => 'Canada Dollar',
			'symbol' => '$'
		),
		'CDF' => array(
			'name' => 'Congo/Kinshasa Franc'
		),
		'CHF' => array(
			'name' => 'Switzerland Franc',
			'symbol' => 'CHF'
		),
		'CLP' => array(
			'name' => 'Chile Peso',
			'symbol' => '$'
		),
		'CNY' => array(
			'name' => 'China Yuan Renminbi',
			'symbol' => '¥'
		),
		'COP' => array(
			'name' => 'Colombia Peso',
			'symbol' => 'p.'
		),
		'CRC' => array(
			'name' => 'Costa Rica Colon',
			'symbol' => '₡'
		),
		'CUC' => array(
			'name' => 'Cuba Convertible Peso'
		),
		'CUP' => array(
			'name' => 'Cuba Peso',
			'symbol' => '₱'
		),
		'CVE' => array(
			'name' => 'Cape Verde Escudo'
		),
		'CZK' => array(
			'name' => 'Czech ReKoruna',
			'symbol' => 'Kč'
		),
		'DJF' => array(
			'name' => 'Djibouti Franc',
			'symbol' => 'CHF'
		),
		'DKK' => array(
			'name' => 'Denmark Krone',
			'symbol' => 'kr'
		),
		'DOP' => array(
			'name' => 'Dominican RePeso',
			'symbol' => 'RD$'
		),
		'DZD' => array(
			'name' => 'Algeria Dinar'
		),
		'EGP' => array(
			'name' => 'Egypt Pound',
			'symbol' => '£'
		),
		'ERN' => array(
			'name' => 'Eritrea Nakfa'
		),
		'ETB' => array(
			'name' => 'Ethiopia Birr'
		),
		'EUR' => array(
			'name' => 'Euro Member Countries',
			'symbol' => '€'
		),
		'FJD' => array(
			'name' => 'Fiji Dollar',
			'symbol' => '$'
		),
		'FKP' => array(
			'name' => 'Falkland Islands (Malvinas) Pound',
			'symbol' => '£'
		),
		'GBP' => array(
			'name' => 'United Kingdom Pound',
			'symbol' => '£'
		),
		'GEL' => array(
			'name' => 'Georgia Lari'
		),
		'GGP' => array(
			'name' => 'Guernsey Pound',
			'symbol' => '£'
		),
		'GHS' => array(
			'name' => 'Ghana Cedi'
		),
		'GIP' => array(
			'name' => 'Gibraltar Pound',
			'symbol' => '£'
		),
		'GMD' => array(
			'name' => 'Gambia Dalasi'
		),
		'GNF' => array(
			'name' => 'Guinea Franc'
		),
		'GTQ' => array(
			'name' => 'Guatemala Quetzal',
			'symbol' => 'Q'
		),
		'GYD' => array(
			'name' => 'Guyana Dollar',
			'symbol' => '$'
		),
		'HKD' => array(
			'name' => 'Hong Kong Dollar',
			'symbol' => 'HK$'
		),
		'HNL' => array(
			'name' => 'Honduras Lempira',
			'symbol' => 'L'
		),
		'HRK' => array(
			'name' => 'Croatia Kuna',
			'symbol' => 'kn'
		),
		'HTG' => array(
			'name' => 'Haiti Gourde'
		),
		'HUF' => array(
			'name' => 'Hungary Forint',
			'symbol' => 'Ft'
		),
		'IDR' => array(
			'name' => 'Indonesia Rupiah',
			'symbol' => 'Rp'
		),
		'ILS' => array(
			'name' => 'Israel Shekel',
			'symbol' => '₪'
		),
		'IMP' => array(
			'name' => 'Isle of Man Pound',
			'symbol' => '£'
		),
		'INR' => array(
			'name' => 'India Rupee',
			'symbol' => '₹'
		),
		'IQD' => array(
			'name' => 'Iraq Dinar'
		),
		'IRR' => array(
			'name' => 'Iran Rial',
			'symbol' => '﷼'
		),
		'ISK' => array(
			'name' => 'Iceland Krona',
			'symbol' => 'kr'
		),
		'JEP' => array(
			'name' => 'Jersey Pound',
			'symbol' => '£'
		),
		'JMD' => array(
			'name' => 'Jamaica Dollar',
			'symbol' => 'J$'
		),
		'JOD' => array(
			'name' => 'Jordan Dinar'
		),
		'JPY' => array(
			'name' => 'Japan Yen',
			'symbol' => '¥'
		),
		'KES' => array(
			'name' => 'Kenya Shilling'
		),
		'KGS' => array(
			'name' => 'Kyrgyzstan Som',
			'symbol' => 'лв'
		),
		'KHR' => array(
			'name' => 'Cambodia Riel',
			'symbol' => '៛'
		),
		'KMF' => array(
			'name' => 'Comoros Franc'
		),
		'KPW' => array(
			'name' => 'Korea (North) Won',
			'symbol' => '₩'
		),
		'KRW' => array(
			'name' => 'Korea (South) Won',
			'symbol' => '₩'
		),
		'KWD' => array(
			'name' => 'Kuwait Dinar'
		),
		'KYD' => array(
			'name' => 'Cayman Islands Dollar',
			'symbol' => '$'
		),
		'KZT' => array(
			'name' => 'Kazakhstan Tenge',
			'symbol' => 'лв'
		),
		'LAK' => array(
			'name' => 'Laos Kip',
			'symbol' => '₭'
		),
		'LBP' => array(
			'name' => 'Lebanon Pound',
			'symbol' => '£'
		),
		'LKR' => array(
			'name' => 'Sri Lanka Rupee',
			'symbol' => '₨'
		),
		'LRD' => array(
			'name' => 'Liberia Dollar',
			'symbol' => '$'
		),
		'LSL' => array(
			'name' => 'Lesotho Loti'
		),
		'LTL' => array(
			'name' => 'Lithuania Litas',
			'symbol' => 'Lt'
		),
		'LVL' => array(
			'name' => 'Latvia Lat',
			'symbol' => 'Ls'
		),
		'LYD' => array(
			'name' => 'Libya Dinar'
		),
		'MAD' => array(
			'name' => 'Morocco Dirham'
		),
		'MDL' => array(
			'name' => 'Moldova Leu'
		),
		'MGA' => array(
			'name' => 'Madagascar Ariary'
		),
		'MKD' => array(
			'name' => 'Macedonia Denar',
			'symbol' => 'ден'
		),
		'MMK' => array(
			'name' => 'Myanmar (Burma) Kyat'
		),
		'MNT' => array(
			'name' => 'Mongolia Tughrik',
			'symbol' => '₮'
		),
		'MOP' => array(
			'name' => 'Macau Pataca'
		),
		'MRO' => array(
			'name' => 'Mauritania Ouguiya'
		),
		'MUR' => array(
			'name' => 'Mauritius Rupee',
			'symbol' => '₨'
		),
		'MVR' => array(
			'name' => 'Maldives (Maldive Islands) Rufiyaa'
		),
		'MWK' => array(
			'name' => 'Malawi Kwacha'
		),
		'MXN' => array(
			'name' => 'Mexico Peso',
			'symbol' => '$'
		),
		'MYR' => array(
			'name' => 'Malaysia Ringgit',
			'symbol' => 'RM'
		),
		'MZN' => array(
			'name' => 'Mozambique Metical',
			'symbol' => 'MT'
		),
		'NAD' => array(
			'name' => 'Namibia Dollar',
			'symbol' => '$'
		),
		'NGN' => array(
			'name' => 'Nigeria Naira',
			'symbol' => '₦'
		),
		'NIO' => array(
			'name' => 'Nicaragua Cordoba',
			'symbol' => 'C$'
		),
		'NOK' => array(
			'name' => 'Norway Krone',
			'symbol' => 'kr'
		),
		'NPR' => array(
			'name' => 'Nepal Rupee',
			'symbol' => '₨'
		),
		'NZD' => array(
			'name' => 'New Zealand Dollar',
			'symbol' => '$'
		),
		'OMR' => array(
			'name' => 'Oman Rial',
			'symbol' => '﷼'
		),
		'PAB' => array(
			'name' => 'Panama Balboa',
			'symbol' => 'B/.'
		),
		'PEN' => array(
			'name' => 'Peru Nuevo Sol',
			'symbol' => 'S/.'
		),
		'PGK' => array(
			'name' => 'Papua New Guinea Kina'
		),
		'PHP' => array(
			'name' => 'Philippines Peso',
			'symbol' => '₱'
		),
		'PKR' => array(
			'name' => 'Pakistan Rupee',
			'symbol' => '₨'
		),
		'PLN' => array(
			'name' => 'Poland Zloty',
			'symbol' => 'zł'
		),
		'PYG' => array(
			'name' => 'Paraguay Guarani',
			'symbol' => 'Gs'
		),
		'QAR' => array(
			'name' => 'Qatar Riyal',
			'symbol' => '﷼'
		),
		'RON' => array(
			'name' => 'Romania New Leu',
			'symbol' => 'lei'
		),
		'RSD' => array(
			'name' => 'Serbia Dinar',
			'symbol' => 'Дин.'
		),
		'RUB' => array(
			'name' => 'Russia Ruble',
			'symbol' => 'руб'
		),
		'RWF' => array(
			'name' => 'Rwanda Franc'
		),
		'SAR' => array(
			'name' => 'Saudi Arabia Riyal',
			'symbol' => '﷼'
		),
		'SBD' => array(
			'name' => 'Solomon Islands Dollar',
			'symbol' => '$'
		),
		'SCR' => array(
			'name' => 'Seychelles Rupee',
			'symbol' => '₨'
		),
		'SDG' => array(
			'name' => 'Sudan Pound'
		),
		'SEK' => array(
			'name' => 'Sweden Krona',
			'symbol' => 'kr'
		),
		'SGD' => array(
			'name' => 'Singapore Dollar',
			'symbol' => '$'
		),
		'SHP' => array(
			'name' => 'Saint Helena Pound',
			'symbol' => '£'
		),
		'SLL' => array(
			'name' => 'Sierra Leone Leone'
		),
		'SOS' => array(
			'name' => 'Somalia Shilling',
			'symbol' => 'S'
		),
		'SPL*' => array(
			'name' => 'Seborga Luigino'
		),
		'SRD' => array(
			'name' => 'Suriname Dollar',
			'symbol' => '$'
		),
		'STD' => array(
			'name' => '	São Tomé and Príncipe Dobra'
		),
		'SVC' => array(
			'name' => 'El Salvador Colon',
			'symbol' => '$'
		),
		'SYP' => array(
			'name' => 'Syria Pound',
			'symbol' => '£'
		),
		'SZL' => array(
			'name' => 'Swaziland Lilangeni'
		),
		'THB' => array(
			'name' => 'Thailand Baht',
			'symbol' => '฿'
		),
		'TJS' => array(
			'name' => 'Tajikistan Somoni'
		),
		'TMT' => array(
			'name' => 'Turkmenistan Manat'
		),
		'TND' => array(
			'name' => 'Tunisia Dinar',
			'symbol' => 'DT'
		),
		'TOP' => array(
			'name' => "Tonga Paanga"
		),
		'TRY' => array(
			'name' => 'Turkey Lira',
			'symbol' => 'TRY'
		),
		'TTD' => array(
			'name' => 'Trinidad and Tobago Dollar',
			'symbol' => 'TT$'
		),
		'TVD' => array(
			'name' => 'Tuvalu Dollar',
			'symbol' => '$'
		),
		'TWD' => array(
			'name' => 'Taiwan New Dollar',
			'symbol' => 'NT$'
		),
		'TZS' => array(
			'name' => 'Tanzania Shilling'
		),
		'UAH' => array(
			'name' => 'Ukraine Hryvna',
			'symbol' => '₴'
		),
		'UGX' => array(
			'name' => 'Uganda Shilling'
		),
		'USD' => array(
			'name' => 'United States Dollar',
			'symbol' => '$'
		),
		'UYU' => array(
			'name' => 'Uruguay Peso',
			'symbol' => '$U'
		),
		'UZS' => array(
			'name' => 'Uzbekistan Som',
			'symbol' => 'лв'
		),
		'VEF' => array(
			'name' => 'Venezuela Bolivar',
			'symbol' => 'Bs'
		),
		'VND' => array(
			'name' => 'Viet Nam Dong',
			'symbol' => '₫'
		),
		'VUV' => array(
			'name' => 'Vanuatu Vatu'
		),
		'WST' => array(
			'name' => 'Samoa Tala'
		),
		'XCD' => array(
			'name' => 'East Caribbean Dollar',
			'symbol' => '$'
		),
		'XDR' => array(
			'name' => 'International Monetary Fund (IMF) Special Drawing Rights'
		),
		'XOF' => array(
			'name' => 'Communauté Financière Africaine (BCEAO) Franc'
		),
		'XPF' => array(
			'name' => 'Comptoirs Français du Pacifique (CFP) Franc'
		),
		'YER' => array(
			'name' => 'Yemen Rial',
			'symbol' => '﷼'
		),
		'ZAR' => array(
			'name' => 'South Africa Rand',
			'symbol' => 'R'
		),
		'ZWD' => array(
			'name' => 'Zimbabwe Dollar',
			'symbol' => 'Z$'
		)
	);

	static $usaStates = array(
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
        'WY' => "Wyoming" );
}