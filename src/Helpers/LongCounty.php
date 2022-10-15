<?php
/*
 * This file is part of Blueline.
 * It contains long versions of coutry abbreviations used by Dove
 *
 * (c) Bob Wallis <bob.wallis@gmail.com>
 *
 */

namespace Blueline\Helpers;

class LongCounty
{
    private static $countries = array(
        'England',
        'Wales',
        'Scotland',
        'Ireland',
        'USA',
        'Canada',
        'Australia',
        'New Zealand',
        'South Africa',
        'Netherlands',
    );

    private static $counties = array(
        'Beds'      => 'Bedfordshire',
        'Berks'     => 'Berkshire',
        'Bucks'     => 'Buckinghamshire',
        'C Bris'    => 'City of Bristol',
        'C London'  => 'City of London',
        'Cambs'     => 'Cambridgeshire',
        'Ches'      => 'Cheshire',
        'Corn'      => 'Cornwall',
        'Cumbr'     => 'Cumbria',
        'Derbys'    => 'Derbyshire',
        'Devon'     => 'Devon',
        'Dorset'    => 'Dorset',
        'Durham'    => 'County Durham',
        'Essex'     => 'Essex',
        'Glos'      => 'Gloucestershire',
        'Gr Lon'    => 'Greater London',
        'Gr London' => 'Greater London',
        'Gr Man'    => 'Greater Manchester',
        'Hants'     => 'Hampshire',
        'Herefs'    => 'Herefordshire',
        'Herts'     => 'Hertfordshire',
        'Hunts'     => 'Huntingdonshire',
        'IOW'       => 'Isle of Wight',
        'IoW'       => 'Isle of Wight',
        'Kent'      => 'Kent',
        'Lancs'     => 'Lancashire',
        'Leics'     => 'Leicestershire',
        'Lincs'     => 'Lincolnshire',
        'Mers'      => 'Merseyside',
        'Middx'     => 'Middlesex',
        'Norf'      => 'Norfolk',
        'Northants' => 'Northamptonshire',
        'Nthumbld'  => 'Northumberland',
        'Northumb'  => 'Northumberland',
        'Notts'     => 'Nottinghamshire',
        'Oxon'      => 'Oxfordshire',
        'Rutland'   => 'Rutland',
        'Shrops'    => 'Shropshire',
        'Som'       => 'Somerset',
        'Staffs'    => 'Staffordshire',
        'Suff'      => 'Suffolk',
        'Surrey'    => 'Surrey',
        'Sussex'    => 'Sussex',
        'E Sussex'  => 'East Sussex',
        'W Sussex'  => 'West Sussex',
        'TyneWear'  => 'Tyne and Wear',
        'Tyne&Wear' => 'Tyne and Wear',
        'Tyne+Wear' => 'Tyne and Wear',
        'W Mids'    => 'West Midlands',
        'Warks'     => 'Warwickshire',
        'Wmld'      => 'Westmorland',
        'Wilts'     => 'Wiltshire',
        'Worcs'     => 'Worcestershire',
        'Yorks'     => 'Yorkshire',
        'N Yks'     => 'North Yorkshire',
        'N Yorks'   => 'North Yorkshire',
        'W Yorks'   => 'West Yorkshire',
        'ER Yks'    => 'East Riding of Yorkshire',
        'ER Yorks'  => 'East Riding of Yorkshire',
        'W Yks'     => 'West Yorkshire',
        'S Yks'     => 'South Yorkshire',
        'S Yorks'   => 'South Yorkshire',
        'Scilly'    => 'Isles of Scilly',
    );

    private static $scottishAreas = array(
        'Arg+Bute'   => 'Argyll and Bute',
        'ArgyllBute' => 'Argyll and Bute',
        'C Aberdn'   => 'City of Aberdeen',
        'C Aberdeen' => 'City of Aberdeen',
        'C Dundee'   => 'City of Dundee',
        'C Edin'     => 'City of Edinburgh',
        'C Glas'     => 'City of Glasgow',
        'Clackman'   => 'Clackmannanshire',
        'E Loth'     => 'East Lothian',
        'Fife'       => 'Fife',
        'Highland'   => 'Highland',
        'PthKross'   => 'Perth and Kinross',
        'PerthKross' => 'Perth and Kinross',
        'Renfrews'   => 'Renfrewshire',
        'Stirling'   => 'Stirling',
    );

    private static $welshAreas = array(
        'Bl Gwent'   => 'Blaenau Gwent',
        'Bridgend'   => 'Bridgend',
        'Caerph\'y'  => 'Caerphilly',
        'Caerphilly' => 'Caerphilly',
        'Cardiff'    => 'Cardiff',
        'Carms'      => 'Carmarthenshire',
        'Cered\'on'  => 'Ceredigion',
        'Ceredigion' => 'Ceredigion',
        'Conwy'      => 'Conwy',
        'Denbighs'   => 'Denbighshire',
        'Dyfed'      => 'Dyfed',
        'Flints'     => 'Flintshire',
        'Gwynedd'    => 'Gwynedd',
        'Ynys Mon'   => 'Isle of Anglesey',
        'Merthyr'    => 'Merthyr Tydfil',
        'Monmths'    => 'Monmouthshire',
        'Neath PT'   => 'Neath Port Talbot',
        'Newport'    => 'Newport',
        'Pembs'      => 'Pembrokeshire',
        'Powys'      => 'Powys',
        'RhonCyTa'   => 'Rhondda Cynon Taf',
        'RhonddaCT'  => 'Rhondda Cynon Taf',
        'Swansea'    => 'Swansea',
        'Torfaen'    => 'Torfaen',
        'ValeGlam'   => 'The Vale of Glamorgan',
        'Wrexham'    => 'Wrexham',
    );

    private static $irishAreas = array(
        'Wicklow' => 'Wicklow',
        'Down'    => 'Down',
        'Antrim'  => 'Antrim',
        'Cork'    => 'Cork',
        'Derry'   => 'Londonderry',
        'Louth'   => 'Louth',
        'Dublin'  => 'Dublin',
        'Ferman'  => 'Fermanagh',
        'Kilk'    => 'Kilkenny',
        'Lim'     => 'Limerick',
        'Armagh'  => 'Armagh',
        'Tip'     => 'Tipperary',
        'Waterfd' => 'Waterford',
        'Wexford' => 'Wexford',
    );

    private static $states = array(
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
        'WY' => "Wyoming",
    );

    private static $canadianStates = array(
        'ON' => 'Ontario',
        'QC' => 'Quebec',
        'NS' => 'Nova Scotia',
        'NB' => 'New Brunswick',
        'MB' => 'Manitoba',
        'BC' => 'British Columbia',
        'PE' => 'Prince Edward Island',
        'SK' => 'Saskatchewan',
        'AB' => 'Alberta',
        'NL' => 'Newfoundland and Labrador',
    );

    private static $australianAreas = array(
        'SA'   => 'South Australia',
        'NSW'  => 'New South Wales',
        'Vict' => 'Victoria',
        'Vic'  => 'Victoria',
        'VIC'  => 'Victoria',
        'Qld'  => 'Queensland',
        'QLD'  => 'Queensland',
        'ACT'  => 'Australian Capital Territory',
        'Tas'  => 'Tasmania',
        'WA'   => 'Western Australia',
    );

    private static $newZealandAreas = array(
        'NI' => 'North Island',
        'SI' => 'South Island',
    );

    private static $southAfricanAreas = array(
        'WC'         => 'Western Cape',
        'KZN'        => 'KwaZulu-Natal',
        'EC'         => 'Eastern Cape',
        'Gaut'       => 'Gauteng',
        'North West' => 'North West',
    );

    private static $netherlandsAreas = array(
        'S Holland' => 'South Holland',
    );

    public static function get($short, $country)
    {
        // Look up in a different county array depending on the country
        switch (strtoupper($country)) {
            case 'ENGLAND':
                return isset(self::$counties[$short]) ? self::$counties[$short] : $short;
            case 'SCOTLAND' :
                return isset(self::$scottishAreas[$short]) ? self::$scottishAreas[$short] : $short;
            case 'WALES' :
                return isset(self::$welshAreas[$short]) ? self::$welshAreas[$short] : $short;
            case 'IRELAND' :
                return isset(self::$irishAreas[$short]) ? self::$irishAreas[$short] : $short;
            case 'USA' :
                return isset(self::$states[$short]) ? self::$states[$short] : $short;
            case 'CANADA' :
                return isset(self::$canadianStates[$short]) ? self::$canadianStates[$short] : $short;
            case 'AUSTRALIA' :
                return isset(self::$australianAreas[$short]) ? self::$australianAreas[$short] : $short;
            case 'NEW ZEALAND' :
                return isset(self::$newZealandAreas[$short]) ? self::$newZealandAreas[$short] : $short;
            case 'SOUTH AFRICA' :
                return isset(self::$southAfricanAreas[$short]) ? self::$southAfricanAreas[$short] : $short;
            case 'NETHERLANDS' :
                return isset(self::$netherlandsAreas[$short]) ? self::$netherlandsAreas[$short] : $short;
            default :
                return $short;
        }
    }
}
