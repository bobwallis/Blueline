<?php
/*
 * This file is part of Blueline.
 * It parses the file dove.txt from http://dove.cccbr.org.uk into Tower entities and implements
 * the Iterator interface. Refer to ../Command/ImportTowersCommand for usage.
 *
 * (c) Bob Wallis <bob.wallis@gmail.com>
 *
 */

namespace Blueline\TowersBundle\Helpers;


class DoveTxtIterator implements \Iterator, \Countable
{
    private $file;
    private $handle;
    private $columns;
    private $position;
    private $currentTower;
    private $count = -1;

    public function __construct($file)
    {
        // Open dove.txt, count the number of lines and extract column headings
        $this->file = $file;
        if (($this->handle = fopen($file, 'r')) == false) {
            return false;
        }
        if (($this->columns = fgetcsv($this->handle, 0, "\\")) == false) {
            return false;
        }
        // Read the first tower
        $this->position     = -1;
        $this->currentTower = $this->next();
    }

    public function rewind()
    {
        // Rewind the file handle, re-read the column line, and read the first tower
        rewind($this->handle);
        $this->position     = -1;
        $this->columns      = fgetcsv($this->handle, 0, "\\");
        $this->currentTower = $this->next();
    }

    public function current()
    {
        return $this->currentTower;
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        // Try and read the next line
        ++$this->position;
        $this->currentTower = null;
        if (($data = fgetcsv($this->handle, 0, "\\")) === false) {
            return;
        }
        // Convert number-indexed data to associative array indexed by column headings
        $towerData = array();
        foreach ($this->columns as $i => $column) {
            $towerData[$column] = $data[$i];
        }
        // Initialise $this->currentTower, and set the easy fields
        $this->currentTower = array(
            'id'              => str_replace(' ', '_', $towerData['DoveID']) ?: null,
            'gridreference'   => $towerData['NG'] ?: null,
            'latitude'        => floatval($towerData['Lat']) ?: null,
            'longitude'       => floatval($towerData['Long']) ?: null,
            'latitudesatnav'  => floatval($towerData['SNLat']) ?: null,
            'longitudesatnav' => floatval($towerData['SNLong']) ?: null,
            'postcode'        => $towerData['Postcode'] ?: null,
            'bells'           => intval($towerData['Bells']),
            'weight'          => intval($towerData['Wt']),
            'weightapprox'    => empty($towerData['App']) ? false : true,
            'note'            => $towerData['Note'] ?: null,
            'hz'              => floatval($towerData['Hz']) ?: null,
            'practicenight'   => intval($towerData['PDNo']) ?: null,
            'practicestart'   => $towerData['PSt'] ?: null,
            'practicenotes'   => $towerData['PrXF'] ?: null,
            'groundfloor'     => empty($towerData['GF']) ? false : true,
            'toilet'          => empty($towerData['Toilet']) ? false : true,
            'unringable'      => empty($towerData['UR']) ? false : true,
            'simulator'       => empty($towerData['Simulator']) ? false : true,
            'overhaulyear'    => intval($towerData['OvhaulYr']) ?: null,
            'tunedyear'       => intval($towerData['TuneYr']) ?: null,
            'affiliations'    => $towerData['Affiliations'] ?: null,
            'extrainfo'       => $towerData['ExtraInfo'] ?: null,
        );

        // We have to do some work to tidy up the other Dove data

        // A blank country name will mean 'England', but there are some other cases where the data
        // in 'country' is actually a continent, so fix that. Also expand abbreviations.
        if (empty($towerData['Country'])) {
            if ($towerData['County'] == 'Zimbabwe' || $towerData['County'] == 'Kenya') {
                $towerData['Country'] = $towerData['County'];
                $towerData['County'] = null;
            } else {
                $towerData['Country'] = 'England';
            }
        } else {
            switch ($towerData['Country']) {
                case 'Channel Is' :
                    $towerData['Country'] = 'Channel Islands'; break;
                case 'Windward Is' :
                    $towerData['Country'] = 'Windward Islands'; break;
            }
        }
        $this->currentTower['country'] = $towerData['Country'];

        // Fix 'county'. Replace '(none)' with a blank county, expand counties to full names
        if ($towerData['County'] == '(none)') {
            $towerData['County'] = null;
        }
        $towerData['County'] = LongCounty::get($towerData['County'], $this->currentTower['country']);
        // Also treat the Windward Islands as a special case
        if ($towerData['Country'] == 'Windward Islands') {
            $towerData['County'] = $towerData['AltName'];
            $towerData['AltName'] = null;
        }
        $this->currentTower['county'] = $towerData['County'];

        // Replace abbreviations in dedications
        if (!empty($towerData['Dedicn'])) {
            $towerData['Dedicn'] = str_replace(
                array( 'Eng',     'Univ',       'RC ',             'SS ', 'S ',  'Cath ',      'Cath,',      'P Church',      'Ch ',     ' ch ',     ' K&M',             ' Gt',        'John Bapt',        ' Magd',     'Senara V',          'Mary V',          'BVM',                 'BV',             'Nativity St',    ' of Blessed',     '& Blessed',     'SMV',                'John Ev',             'Mark Ev',             'James Ap',          'Andrew Ap',          'Thomas Ap',          ' A&M',                    ' V&M',                   ' B&M',                   'Margaret Q',         'Edward Conf & K',               'Edward Conf',          'Edward M',          'George M',          'Thomas M',          'Stephen M',          'Laurence M',          'Matthew AEM' ),
                array( 'English', 'University', 'Roman Catholic ', 'Ss ', 'St ', 'Cathedral ', 'Cathedral,', 'Parish Church', 'Church ', ' church ', ' King and Martyr', ' the Great', 'John the Baptist', ' Magdalen', 'Senara the Virgin', 'Mary the Virgin', 'Blessed Virgin Mary', 'Blessed Virgin', 'Nativity of St', ' of the Blessed', '& the Blessed', 'St Mary the Virgin', 'John the Evangelist', 'Mark the Evangelist', 'James the Apostle', 'Andrew the Apostle', 'Thomas the Apostle', ' the Apostle and Martyr', ' the Virgin and Martyr', ' the Bishop and Martyr', 'Margaret the Queen', 'Edward the Confessor and King', 'Edward the Confessor', 'Edward the Martyr', 'George the Martyr', 'Thomas the Martyr', 'Stephen the Martyr', 'Laurence the Martyr', 'Matthew the Apostle, Evangelist and Martyr' ),
                $towerData['Dedicn']);
            $towerData['Dedicn'] = preg_replace(
                array( '/Cath$/',   '/Ch$/' ),
                array( 'Cathedral', 'Church' ),
                $towerData['Dedicn']);
            if ($towerData['Dedicn'] == '(unknown)') {
                $towerData['Dedicn'] = null;
            }
        }
        $this->currentTower['dedication'] = $towerData['Dedicn'] ?: null;

        // Replace abbreviated dioceses
        switch ($towerData['Diocese']) {
            case 'PrivOwnership' :
                $towerData['Diocese'] = 'Private Ownership';
                break;
            case '(Not Anglican)' :
                $towerData['Diocese'] = 'Non-Anglican';
                break;
            case 'AnglicanNonUK' :
                $towerData['Diocese'] = 'Anglican (Non-UK)';
                break;
            case 'ExtraParochial' :
                $towerData['Diocese'] = 'Extra-Parochial';
                break;
            case '(RC)' :
                $towerData['Diocese'] = 'Roman Catholic';
                break;
            case '(Ireland)' :
                $towerData['Diocese'] = 'Ireland';
                break;
            case '(Scotland)' :
                $towerData['Diocese'] = 'Scotland';
                break;
            case 'ChConsvnTrust' :
                $towerData['Diocese'] = 'Churches Conservation Trust';
                break;
            case 'LocalAuthority' :
                $towerData['Diocese'] = 'Local Authority';
                break;
            case 'Trust (nonCCT)':
                $towerData['Diocese'] = 'Trust (non-CCT)';
                break;
            case 'St Eds and Ips':
                $towerData['Diocese'] = 'St Edmundsbury and Ipswich';
                break;
            case 'RiponAndLeeds':
                $towerData['Diocese'] = 'Ripon and Leeds';
                break;
            case 'SouthwellNottm':
                $towerData['Diocese'] = 'Southwell and Nottingham';
                break;
            case 'SwanseaBrecon':
                $towerData['Diocese'] = 'Swansea and Brecon';
                break;
        }
        $this->currentTower['diocese'] = $towerData['Diocese'] ?: null;

        // Replace abbreviated contractors
        switch ($towerData['Contractor']) {
            case 'Local labour/guild/assn' :
                $towerData['Contractor'] = 'Local labour';
                break;
            case '(The owner)':
                $towerData['Contractor'] = 'The Owner';
                break;
            case 'Merseyside Bell Restn Group':
                $towerData['Contractor'] = 'Merseyside Bell Restoration Group';
                break;
        }
        $this->currentTower['contractor'] = $towerData['Contractor'] ?: null;

        // Only include altName if necessary
        if (empty($towerData['AltName']) || $towerData['Place'] == $towerData['AltName']) {
            $this->currentTower['altname'] = null;
        } else {
            $this->currentTower['altname'] = $towerData['AltName'];
        }

        // Concat 'place2' and 'place'
        $this->currentTower['place'] = str_replace('The,', 'The', implode(', ', array_reverse(explode(', ', empty($towerData['Place2']) ? $towerData['Place'] : $towerData['Place'].', '.$towerData['Place2']))));

        // Prepend http:// to the web page if it doesn't have a protocol
        $towerData['WebPage'] = $towerData['WebPage'] ? (preg_match('/^[a-z]+:\/\//', $towerData['WebPage']) ? $towerData['WebPage'] : 'http://'.$towerData['WebPage']) : null;
        // Encode spaces to %20. This isn't an ideal technique.
        $towerData['WebPage'] = str_replace(' ', '%20', $towerData['WebPage']);
        $this->currentTower['webpage'] = $towerData['WebPage'];

        return $this->currentTower;
    }

    public function valid()
    {
        return ($this->currentTower && !!$this->currentTower['id']);
    }

    public function count()
    {
        if ($this->count === -1) {
            $count = 0;
            $handle = fopen($this->file, 'r');
            while (!feof($handle)) {
                if (fgets($handle) !== false) {
                    $count++;
                }
            }
            fclose($handle);
            $this->count = $count - 1;
        }

        return $this->count;
    }
}
