<?php

namespace infuse;

class Currency
{
	/**
	 * Generates a select box for the currencies
	 *
	 * @return string html
	 */
	static function options( $selectedCurrency )
	{
		$return = '<select name="currency">' . "\n";
		foreach (self::$currencies as $code => $currency) {
			$codeLower = strtolower($code);
			$selected = ($selectedCurrency == $codeLower) ? 'selected="selected"' : '';
			$return .= '<option value="' . $codeLower . '" ' . $selected . '>' . $code . ' - ' . $currency['name'] . '</option>' . "\n";
		}
		$return .= '</select>';
		return $return;
	}

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
			'symbol' => '	$b'
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
			'name' => 'Czech Republic Koruna',
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
			'name' => 'Dominican Republic Peso',
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
			'name' => 'Tunisia Dinar'
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
		) );
}