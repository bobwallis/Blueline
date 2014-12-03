<?php
/*
 * This file is part of Blueline.
 * It parses the file newpks.txt from http://dove.cccbr.org.uk into data ready for import into OldPK
 * objects, and implements the Iterator interface.
 * Refer to ../Command/ImportOldPKsCommand for usage.
 *
 * (c) Bob Wallis <bob.wallis@gmail.com>
 *
 */

namespace Blueline\TowersBundle\Helpers;

class OldPKTxtIterator implements \Iterator, \Countable
{
    private $oldPKArray;

    public function __construct($file)
    {
        $this->oldPKArray = array();

        // Open data file, extract column headings
        if (($handle = fopen($file, 'r')) == false) {
            return false;
        }
        if (($columns = fgetcsv($handle, 25, "\\")) === false) {
            return false;
        }

        // Set up data objects and read data into them from newpks.txt
        $data = array();
        foreach ($columns as $column) {
            $data[$column] = array();
        }
        while (($dataCollect = fgetcsv($handle, 25, "\\")) !== false) {
            foreach ($columns as $i => $column) {
                $data[$column][] = trim($dataCollect[$i]);
            }
        }
        fclose($handle);

        // Prevent entries appearing as a newPK when they appear as an oldPK themselves by following
        // chains through to the actual current Dove ID
        $foundWrongEntry = true;
        while ($foundWrongEntry) {
            $foundWrongEntry = false;
            for ($i = 0, $iLim = count($data['OldID']); $i < $iLim; ++$i) {
                $newOldPK = array_search($data['NewID'][$i], $data['OldID']);
                if ($newOldPK !== false) {
                    $data['NewID'][$i] = $data['NewID'][$newOldPK];
                    $foundWrongEntry   = true;
                }
            }
        }

        // Iterate over the data and push them onto $this->oldPKArray in a meaningful format
        for ($i = 0, $iLim = count($data['OldID']); $i < $iLim; ++$i) {
            array_push($this->oldPKArray, array(
                'oldpk'    => str_replace(' ', '_', $data['OldID'][$i]),
                'tower_id' => str_replace(' ', '_', $data['NewID'][$i]),
            ));
        }
    }

    // Implement the Iterator interface by borrowing it from the underlying array
    public function rewind()
    {
        return reset($this->oldPKArray);
    }
    public function current()
    {
        return current($this->oldPKArray);
    }
    public function key()
    {
        return key($this->oldPKArray);
    }
    public function next()
    {
        return next($this->oldPKArray);
    }
    public function valid()
    {
        return key($this->oldPKArray) !== null;
    }
    public function count()
    {
        return count($this->oldPKArray);
    }
}
