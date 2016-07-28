<?php

namespace Blueline\MethodsBundle\Entity;

use Blueline\BluelineBundle\Helpers\Text;
use Blueline\MethodsBundle\Helpers\PlaceNotation;
use Blueline\MethodsBundle\Helpers\Stages;
use Blueline\MethodsBundle\Helpers\LeadHeadCodes;
require_once(__DIR__.'/../../BluelineBundle/Helpers/arrays_equal_in_some_rotation.php');
use function Blueline\BluelineBundle\Helpers\arrays_equal_in_some_rotation;

/**
 * Blueline\MethodsBundle\Entity\Method
 */
class Method
{
    // Constructor
    public function __construct($firstSet = array())
    {
        $this->collections  = new \Doctrine\Common\Collections\ArrayCollection();
        $this->performances = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setAll($firstSet);
    }

    // Casting helpers
    public function __toString()
    {
        return 'Method:'.$this->getTitle();
    }

    public function __toArray()
    {
        $objectVars = get_object_vars($this);
        foreach ($objectVars as $k => $v) {
            switch ($k) {
                // Don't try to drill down into sub-entities or show stuff only used internally
                case 'leadHeads':
                case 'collections':
                case 'performances':
                case 'methodsimilarity1':
                case 'methodsimilarity2':
                    $v = null;
                    break;
                default:
                    $objectVars[$k] = $this->{'get'.ucwords($k)}();
            }
        }

        return array_filter($objectVars);
    }

    // setAll helper
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (is_callable(array( $this, $method ))) {
                $this->$method($value);
            }
        }

        return $this;
    }

    // Variables
    /**
     * @var string $title
     */
    private $title;

    /**
     * @var boolean $provisional
     */
    private $provisional;

    /**
     * @var integer $stage
     */
    private $stage;

    /**
     * @var string $classification
     */
    private $classification;

    /**
     * @var string $nameMetaphone
     */
    private $nameMetaphone;

    /**
     * @var string $notation
     */
    private $notation;

    /**
     * @var string $notationExpanded
     */
    private $notationExpanded;

    /**
     * @var string $leadHeadCode
     */
    private $leadHeadCode;

    /**
     * @var string $leadHead
     */
    private $leadHead;

    /**
     * @var string $fchGroups
     */
    private $fchGroups;

    /**
     * @var integer $lengthOfLead
     */
    private $lengthOfLead;

    /**
     * @var integer $lengthOfCourse
     */
    private $lengthOfCourse;

    /**
     * @var integer $numberOfLeads
     */
    private $numberOfLeads;

    /**
     * @var integer $numberOfHunts
     */
    private $numberOfHunts;

    /**
     * @var boolean $little
     */
    private $little;

    /**
     * @var boolean $differential
     */
    private $differential;

    /**
     * @var boolean $plain
     */
    private $plain;

    /**
     * @var boolean $trebleDodging
     */
    private $trebleDodging;

    /**
     * @var boolean $palindromic
     */
    private $palindromic;

    /**
     * @var boolean $doubleSym
     */
    private $doubleSym;

    /**
     * @var boolean $rotational
     */
    private $rotational;

    /**
     * @var array $calls
     */
    private $calls;

    /**
     * @var array $ruleOffs
     */
    private $ruleOffs;

    /**
     * @var array $callingPositions
     */
    private $callingPositions;

    /**
     * @var integer $magic
     */
    private $magic;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $collections;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $performances;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $methodsimilarity1;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $methodsimilarity2;
    
    // Getters and setters
    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        if (!isset($this->title)) {
            $classification = $this->getClassification();
            $this->setTitle('Unnamed '.($this->getDifferential()?'Differential ':'').($this->getLittle()?'Little ':'').($classification?$classification.' ':'').$this->getStageText());
        }
        return $this->title;
    }

    /**
     * Set title
     *
     * @param  string $title
     * @return Method
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set provisional
     *
     * @param  boolean $provisional
     * @return Method
     */
    public function setProvisional($provisional)
    {
        $this->provisional = $provisional;
        return $this;
    }

    /**
     * Get provisional
     *
     * @return boolean
     */
    public function getProvisional()
    {
        return $this->provisional;
    }

    /**
     * Set stage
     *
     * @param  integer $stage
     * @return Method
     */
    public function setStage($stage)
    {
        $this->stage = intval($stage);
        return $this;
    }

    /**
     * Get stage
     *
     * @return integer
     */
    public function getStage()
    {
        if (!isset($this->stage)) {
            $this->setStage(PlaceNotation::guessStage($this->getNotation()));
        }
        return $this->stage;
    }
    public function getStageText()
    {
        return Stages::toString($this->getStage());
    }

    /**
     * Set classification
     *
     * @param  string $classification
     * @return Method
     */
    public function setClassification($classification)
    {
        $this->classification = $classification;
        return $this;
    }

    /**
     * Get classification
     *
     * @return string
     */
    public function getClassification()
    {
        if (!isset($this->classification)) {
            if ($this->getNumberOfHunts() > 0) {
                $principalHunts = array_values(array_filter($this->getHuntDetails(), function ($h) { return $h['principal']; } ));
                $principalHuntType = $principalHunts[0]['type'];

                // For Plain there is a special case for 'Slow Course', and then if all bells only make places and hunt then it's 'Place', otherwise 'Bob'
                if ($principalHuntType == 'Plain') {
                    // Generate a lead, and a "lead+1 change" (we'll need the latter to  check if all bells only hunt and make places since we'll need to check for dodges/points over the lead end)
                    $lead = PlaceNotation::apply(PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded())), PlaceNotation::rounds($this->getStage()));
                    array_unshift($lead, PlaceNotation::rounds($this->getStage()));
                    $leadPlusOneChangeFromNextLead = $lead;
                    array_push($leadPlusOneChangeFromNextLead, PlaceNotation::apply(PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded()))[0], end($leadPlusOneChangeFromNextLead)));

                    // 'Slow Course' methods have one principal hunt, and another hunt bell that makes 2nds when the principal hunt is leading
                    $slowCourse = false;
                    if ($this->getNumberOfHunts() == 2 && count($principalHunts) == 1) {
                        $principalHunt = $principalHunts[0]['bell'];
                        // Filter the lead for all the rows where the principal hunt is leading
                        $rowsWithPrincipalHuntLeading = array_filter($lead, function ($row) use ($principalHunt) { return $row[0] == $principalHunt; });
                        // Then check that in all of them there is a different hunt bell in 2nds place and that the hunt bell has a well-formed path
                        $hunts = array_map(function ($h) { return PlaceNotation::intToBell($h['bell']); }, array_values(array_filter($this->getHuntDetails(), function ($h) { return $h['wellFormedPath']; } )));
                        $slowCourse = array_reduce($rowsWithPrincipalHuntLeading, function ($carry, $row) use($hunts) { return $carry && in_array($row[1], $hunts); }, true );
                    }
                    if ($slowCourse) {
                        $this->setClassification('Slow Course');

                    // If not Slow Course then look into the other options
                    } else {
                        // Work out if every bell only ever hunts or makes places
                        $placesOnly = array_reduce(array_map(function ($bell) use ($leadPlusOneChangeFromNextLead) {
                            // Extract the path of each bell
                            $positions = array_map(function ($row) use ($bell) { return array_search($bell, $row); }, $leadPlusOneChangeFromNextLead);
                            // Then iterate through and check that the line only ever contains hunts or places
                            $bellPlacesOnly = true;
                            for ($i = 2; $bellPlacesOnly && $i < count($positions); ++$i) {
                                $bellPlacesOnly = abs(($positions[$i] - $positions[$i-1]) - ($positions[$i-1] - $positions[$i-2])) <= 1;
                            }
                            return $bellPlacesOnly;
                        }, PlaceNotation::rounds($this->getStage())), function ($carry, $val) { return $carry && $val; }, true);
                        // If all bells only make places and hunt then it's 'Place', otherwise 'Bob'
                        $this->setClassification($placesOnly? 'Place' : 'Bob');
                    }
                // For Treble Dodging see if internal places are made at each cross section. All => 'Surprise', None => 'Treble Bob', Some => 'Delight'
                } elseif ($principalHuntType == 'Treble Dodging') {
                    // Generate a lead
                    $lead = PlaceNotation::apply(PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded())), PlaceNotation::rounds($this->getStage()));
                    array_unshift($lead, PlaceNotation::rounds($this->getStage()));
                    // If the hunt bell dodges in only one position it's Treble Bob
                    if (array_reduce($principalHunts, function ($carry, $val) { return $carry && max($val['path']) - min($val['path']) == 1; }, true)) {
                        $this->setClassification('Treble Bob');
                    // Otherwise inspect cross sections
                    } else {
                        // A cross section is a change at which a principal hunt bell passes from one dodging position to another i.e. hunts for three changes. Find all examples.
                        $crossSectionChanges = array_map(function ($hunt) use ($lead) {
                            // Extract the path of each bell
                            $positions = array_map(function ($row) use ($hunt) { return array_search($hunt, $row); }, $lead);
                            // Then build an array of all the changes which are cross sections (assuming changes are indexed from zero)
                            $bellCrossSections = array();
                            for ($i = 1; $i+2 < count($positions); ++$i) {
                                $lastChange = $positions[$i] - $positions[$i-1];
                                $thisChange = $positions[$i+1] - $positions[$i];
                                $nextChange = $positions[$i+2] - $positions[$i+1];
                                if (abs($thisChange) == 1 && $lastChange == $thisChange && $thisChange == $nextChange) {
                                    $bellCrossSections[] = $i;
                                }
                            }
                            return $bellCrossSections;
                        }, array_map(function ($h) { return $h['bell']; }, $principalHunts));
                        // Merge the by-hunt-bell cross sections into one list
                        $crossSectionChanges = array_unique(array_reduce($crossSectionChanges, 'array_merge', array()));
                        // Test if there are internal places made at each cross section
                        $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
                        $n = PlaceNotation::intToBell($this->getStage());
                        $internalPlacesAtCrossSectionChanges = array_map(function ($change) use ($notationExploded, $n) {
                            return !($notationExploded[$change] == 'x' || strlen(preg_replace('/[1'.$n.']?/', '', $notationExploded[$change])) == 0);
                        }, $crossSectionChanges);
                        // If an internal place is made at every cross section it's 'Surprise'
                        if (array_reduce($internalPlacesAtCrossSectionChanges, function($c, $v) { return $c && $v; }, true )) {
                            $this->setClassification('Surprise');
                        // If none then it's 'Treble Bob'
                        } elseif (array_reduce($internalPlacesAtCrossSectionChanges, function($c, $v) { return $c && !$v; }, true )) {
                            $this->setClassification('Treble Bob');
                        // Otherwise it's 'Delight'
                        } else {
                            $this->setClassification('Delight');
                        }
                    }
                // Only 'Plain' and 'Treble Dodging' hunt types have multiple options - so in any other cases we can just use the hunt type as the method classification
                } else {
                    $this->setClassification($principalHuntType);
                }
            }
        }
        return $this->classification;
    }

    /**
     * Set nameMetaphone
     *
     * @param  string $nameMetaphone
     * @return Method
     */
    public function setNameMetaphone($nameMetaphone)
    {
        $this->nameMetaphone = $nameMetaphone;
        return $this;
    }

    /**
     * Get nameMetaphone
     *
     * @return string
     */
    public function getNameMetaphone()
    {
        if (!isset($this->nameMetaphone)) {
            $this->setNameMetaphone(metaphone(preg_replace('/(Differential)?\s*(Little)?\s*(Alliance|Bob|Delight|Hybrid|Place|Surprise|Slow Course|Treble Bob|Treble Place)?\s*(Singles|Minimus|Doubles|Minor|Triples|Major|Caters|Royal|Cinques|Maximus|Sextuples|Fourteen|Septuples|Sixteen|Octuples|Eighteen|Nineteen|Twenty|Twenty-one|Twenty-two)$/', '', $this->getTitle())));
        }
        return $this->nameMetaphone;
    }

    /**
     * Set notation
     *
     * @param  string $notation
     * @return Method
     */
    public function setNotation($notation)
    {
        $this->notation = $notation;
        return $this;
    }

    /**
     * Get notation
     *
     * @return string
     */
    public function getNotation()
    {
        return $this->notation;
    }

    /**
     * Set notationExpanded
     *
     * @param  string $notationExpanded
     * @return Method
     */
    public function setNotationExpanded($notationExpanded)
    {
        $this->notationExpanded = $notationExpanded;
        return $this;
    }

    /**
     * Get notationExpanded
     *
     * @return string
     */
    public function getNotationExpanded()
    {
        if (!isset($this->notationExpanded)) {
            $this->setNotationExpanded(PlaceNotation::expand($this->getNotation(), $this->getStage()));
        }
        return $this->notationExpanded;
    }

    /**
     * Get notationSiril
     *
     * @return string
     */
    public function getNotationSiril()
    {
        return PlaceNotation::siril($this->getNotationExpanded(), $this->getStage());
    }

    /**
     * Set leadHeadCode
     *
     * @param  string $leadHeadCode
     * @return Method
     */
    public function setLeadHeadCode($leadHeadCode)
    {
        $this->leadHeadCode = $leadHeadCode;
        return $this;
    }

    /**
     * Get leadHeadCode
     *
     * @return string
     */
    public function getLeadHeadCode()
    {
        if (!isset($this->leadHeadCode)) {
            $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
            $leadHeadNotation = end($notationExploded);
            $postLeadEndNotation = ($this->getNumberOfHunts() == 2) ? $notationExploded[0] : '';
            $this->setLeadHeadCode(LeadHeadCodes::toCode($this->getLeadHead(), $this->getStage(), $this->getNumberOfHunts(), $leadHeadNotation, $postLeadEndNotation));
        }
        return $this->leadHeadCode;
    }

    /**
     * Set leadHead
     *
     * @param  string $leadHead
     * @return Method
     */
    public function setLeadHead($leadHead)
    {
        $this->leadHead = $leadHead;
        return $this;
    }

    /**
     * Get leadHead
     *
     * @return string
     */
    public function getLeadHead()
    {
        if (!isset($this->leadHead)) {
            $lead = PlaceNotation::apply(PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded())), PlaceNotation::rounds($this->getStage()));
            $this->setLeadHead(implode('', end($lead)));
        }
        return $this->leadHead;
    }

    /**
     * Set fchGroups
     *
     * @param  string $fchGroups
     * @return Method
     */
    public function setFchGroups($fchGroups)
    {
        $this->fchGroups = $fchGroups;
        return $this;
    }

    /**
     * Get fchGroups
     *
     * @return string
     */
    public function getFchGroups()
    {
        return $this->fchGroups;
    }

    /**
     * Set lengthOfLead
     *
     * @param  integer $lengthOfLead
     * @return Method
     */
    public function setLengthOfLead($lengthOfLead)
    {
        $this->lengthOfLead = $lengthOfLead;
        return $this;
    }

    /**
     * Get lengthOfLead
     *
     * @return integer
     */
    public function getLengthOfLead()
    {
        if (!isset($this->lengthOfLead)) {
            $this->setLengthOfLead(count(PlaceNotation::explode($this->getNotationExpanded())));
        }
        return $this->lengthOfLead;
    }

    /**
     * Set lengthOfCourse
     *
     * @param  integer $lengthOfCourse
     * @return Method
     */
    public function setLengthOfCourse($lengthOfCourse)
    {
        $this->lengthOfCourse = $lengthOfCourse;
        return $this;
    }

    /**
     * Get lengthOfCourse
     *
     * @return integer
     */
    public function getLengthOfCourse()
    {
        if (!isset($this->lengthOfCourse)) {
            $this->setLengthOfCourse($this->getLengthOfLead() * $this->getNumberOfLeads());
        }
        return $this->lengthOfCourse;
    }

    /**
     * Set numberOfLeads
     *
     * @param  integer $numberOfLeads
     * @return Method
     */
    public function setNumberOfLeads($numberOfLeads)
    {
        $this->numberOfLeads = $numberOfLeads;
        return $this;
    }

    /**
     * Get numberOfLeads
     *
     * @return integer
     */
    public function getNumberOfLeads()
    {
        if (!isset($this->numberOfLeads)) {
            $permutation = array_map(function ($b) { return PlaceNotation::bellToInt($b) - 1; }, str_split($this->getLeadHead()));
            $rounds = PlaceNotation::rounds($this->getStage());
            $test = PlaceNotation::permute($rounds, $permutation);
            for ($numberOfLeads = 1; !PlaceNotation::rowsEqual($test, $rounds); ++$numberOfLeads) {
                $test = PlaceNotation::permute($test, $permutation);
            }
            $this->setNumberOfLeads($numberOfLeads);
        }
        return $this->numberOfLeads;
    }

    /**
     * Set numberOfHunts
     *
     * @param  integer $numberOfHunts
     * @return Method
     */
    public function setNumberOfHunts($numberOfHunts)
    {
        $this->numberOfHunts = $numberOfHunts;
        return $this;
    }

    /**
     * Get numberOfHunts
     *
     * @return integer
     */
    public function getNumberOfHunts()
    {
        if (!isset($this->numberOfHunts)) {
            $this->numberOfHunts = count($this->getHunts());
        }
        return $this->numberOfHunts;
    }

    private $hunts;
    public function getHunts()
    {
        if (!isset($this->hunts)) {
            $hunts = array();
            $leadHead = array_map(function ($n) { return PlaceNotation::bellToInt($n); }, str_split($this->getLeadHead()));
            for ($i = 0, $iLim = count($leadHead); $i < $iLim; ++$i) {
                if (($i+1) == $leadHead[$i]) {
                    array_push($hunts, $leadHead[$i]);
                }
            }
            $this->hunts = $hunts;
        }
        return $this->hunts;
    }

    private $huntDetails;
    public function getHuntDetails()
    {
        if (!isset($this->huntDetails)) {
            $huntDetails = array();
            if ($this->getNumberOfHunts() > 0) {
                // Generate a lead of the method
                $lead = PlaceNotation::apply(PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded())), PlaceNotation::rounds($this->getStage()));
                // Classify each of the hunt bells
                foreach ($this->getHunts() as $i => $hunt) {
                    $huntText = PlaceNotation::intToBell($hunt);
                    $huntDetails[$i]['bell'] = $hunt;
                    $huntDetails[$i]['path'] = array_map(function ($row) use ($huntText) {
                        return array_search($huntText, $row) + 1;
                    }, $lead);
                    array_unshift($huntDetails[$i]['path'], array_pop($huntDetails[$i]['path']));
                    // Well-formed paths are the same when rung backwards
                    $huntDetails[$i]['wellFormedPath'] = count($huntDetails[$i]['path'])%2 == 0 && arrays_equal_in_some_rotation($huntDetails[$i]['path'], array_reverse($huntDetails[$i]['path']));
                    // A path is little if it doesn't reach the front or back
                    $huntDetails[$i]['little'] = min($huntDetails[$i]['path']) > 1 || max($huntDetails[$i]['path']) < $this->getStage();
                    $numberOfPlacesInPath = array_reduce($huntDetails[$i]['path'], function ($carry, $pos) {
                        $carry['places'] = ($carry['lastPos'] == $pos)? $carry['places'] + 1 : $carry['places'];
                        $carry['lastPos'] = $pos;
                        return $carry;
                    }, array('lastPos' => end($huntDetails[$i]['path']), 'places' => 0))['places'];
                    $blowsInEachPosition = array_reduce($huntDetails[$i]['path'], function ($carry, $pos) {
                        if (isset($carry[$pos])) {
                            $carry[$pos]++;
                        } else {
                            $carry[$pos] = 1;
                        }
                        ksort($carry);
                        return $carry;
                    }, array());
                    $sameNumberOfBlowsInEachPosition = count(array_unique($blowsInEachPosition)) == 1;
                    // Well-formed paths are classified into a few different types
                    if ($huntDetails[$i]['wellFormedPath']) {
                        // If the hunt makes two blows in every place - it's Plain
                        if (array_sum($blowsInEachPosition)/2 == count($blowsInEachPosition)) {
                            $huntDetails[$i]['type'] = 'Plain';
                        } elseif ($sameNumberOfBlowsInEachPosition && $numberOfPlacesInPath >= 2) { // Don't think we actually need the 2nd condition here?
                            // If the same number of blows in each position and no more than two places in the lead then 'Treble Dodging'
                            if ($numberOfPlacesInPath == 2) {
                                $huntDetails[$i]['type'] = 'Treble Dodging';
                            // If the same number of blows in each position and more than 2 places then 'Treble Place'
                            } else {
                                $huntDetails[$i]['type'] = 'Treble Place';
                            }
                        // If a different number of blows in each position then 'Alliance'
                        } else {
                            $huntDetails[$i]['type'] = 'Alliance';
                        }
                    // Non-well-formed paths are Hybrid
                    } else {
                        $huntDetails[$i]['type'] = 'Hybrid';
                    }
                }
                // Determine which are the principal hunts. Which is all the hunts of the first type of hunt which has an example (and just the non-little ones if there are both little and non-little hunts of that type)
                if (count($huntDetails) == 1) {
                    $huntDetails[0]['principal'] = true;
                } else {
                    foreach (array('Plain', 'Treble Dodging', 'Treble Place', 'Alliance', 'Hybrid') as $type) {
                        $numberOfHuntsOfType = count(array_filter($huntDetails, function ($h) use ($type) { return $h['type'] == $type; }));
                        $numberOfHuntsOfTypeWhichAreLittle = count(array_filter($huntDetails, function ($h) use ($type) { return $h['type'] == $type && $h['little']; }));
                        if ($numberOfHuntsOfType > 0) {
                            for ($i = 0; $i < count($huntDetails); ++$i) {
                                $huntDetails[$i]['principal'] = ($huntDetails[$i]['type'] == $type && ($numberOfHuntsOfType == $numberOfHuntsOfTypeWhichAreLittle || !$huntDetails[$i]['little']));
                            }
                            break;
                        }
                    }
                }
            }
            $this->huntDetails = $huntDetails;
        }
        return $this->huntDetails;
    }

    /**
     * Set little
     *
     * @param  boolean $little
     * @return Method
     */
    public function setLittle($little)
    {
        $this->little = $little;
        return $this;
    }

    /**
     * Get little
     *
     * @return boolean
     */
    public function getLittle()
    {
        if (!isset($this->little)) {
            $this->setLittle(count(array_filter($this->getHuntDetails(), function ($h) { return $h['principal'] && $h['little']; })) > 0);
        }
        return $this->little;
    }

    /**
     * Set differential
     *
     * @param  boolean $differential
     * @return Method
     */
    public function setDifferential($differential)
    {
        $this->differential = $differential;
        return $this;
    }

    /**
     * Get differential
     *
     * @return boolean
     */
    public function getDifferential()
    {
        if (!isset($this->differential)) {
            $this->setDifferential($this->getNumberOfLeads() != ($this->getStage() - $this->getNumberOfHunts()));
        }
        return $this->differential;
    }

    /**
     * Set plain
     *
     * @param  boolean $plain
     * @return Method
     */
    public function setPlain($plain)
    {
        $this->plain = $plain;
        return $this;
    }

    /**
     * Get plain
     *
     * @return boolean
     */
    public function getPlain()
    {
        if (!isset($this->plain)) {
            $this->setPlain(count(array_filter($this->getHuntDetails(), function ($h) { return $h['principal'] && $h['type'] == 'Plain'; })) > 0);
        }
        return $this->plain;
    }

    /**
     * Set trebleDodging
     *
     * @param  boolean $trebleDodging
     * @return Method
     */
    public function setTrebleDodging($trebleDodging)
    {
        $this->trebleDodging = $trebleDodging;
        return $this;
    }

    /**
     * Get trebleDodging
     *
     * @return boolean
     */
    public function getTrebleDodging()
    {
        if (!isset($this->trebleDodging)) {
            $this->setTrebleDodging(count(array_filter($this->getHuntDetails(), function ($h) { return $h['principal'] && $h['type'] == 'Treble Dodging'; })) > 0);
        }
        return $this->trebleDodging;
    }

    /**
     * Set palindromic
     *
     * @param  boolean $palindromic
     * @return Method
     */
    public function setPalindromic($palindromic)
    {
        $this->palindromic = $palindromic;
        return $this;
    }

    /**
     * Get palindromic
     *
     * @return boolean
     */
    public function getPalindromic()
    {
        if (!isset($this->palindromic)) {
            $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
            $this->setPalindromic(arrays_equal_in_some_rotation($notationExploded, array_reverse($notationExploded)));
        }
        return $this->palindromic;
    }

    /**
     * Set doubleSym
     *
     * @param  boolean $doubleSym
     * @return Method
     */
    public function setDoubleSym($doubleSym)
    {
        $this->doubleSym = $doubleSym;
        return $this;
    }

    /**
     * Get doubleSym
     *
     * @return boolean
     */
    public function getDoubleSym()
    {
        if (!isset($this->doubleSym)) {
            $stage = $this->getStage();
            $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
            $notationExplodedReversed = array_map(function ($e) use ($stage) {
                if ($e == 'x') {
                    return $e;
                } else {
                    $eExplode = str_split($e);
                    for ($i = 0; $i < count($eExplode); ++$i) {
                        $eExplode[$i] = PlaceNotation::intToBell($stage + 1 - PlaceNotation::bellToInt($eExplode[$i]));
                    }
                    return implode(array_reverse($eExplode));
                }
            }, $notationExploded);
            $same = $notationExploded == $notationExplodedReversed;
            for ($i = 0; !$same && $i < count($notationExploded) + 1; ++$i) {
                array_push($notationExplodedReversed, array_shift($notationExplodedReversed));
                $same = $notationExploded == $notationExplodedReversed;
            }
            $this->setDoubleSym($same);
        }
        return $this->doubleSym;
    }

    /**
     * Set rotational
     *
     * @param  boolean $rotational
     * @return Method
     */
    public function setRotational($rotational)
    {
        $this->rotational = $rotational;
        return $this;
    }

    /**
     * Get rotational
     *
     * @return boolean
     */
    public function getRotational()
    {
        if (!isset($this->rotational)) {
            $this->setRotational($this->getPalindromic() && $this->getDoubleSym());
        }
        return $this->rotational;
    }
    public function getSymmetryText()
    {
        return ucfirst(Text::toList(array_filter(array( ($this->getPalindromic() ? 'palindromic' : ''), ($this->getDoubleSym() ? 'double' : ''), ($this->getRotational() ? 'rotational' : '') ))));
    }

    /**
     * Set calls
     *
     * @param  array  $calls
     * @return Method
     */
    public function setCalls($calls)
    {
        $this->calls = $calls;
        return $this;
    }

    /**
     * Get calls
     *
     * @return array
     */
    public function getCalls()
    {
        // Set default calls
        if (empty($this->calls)) {
            $stage = $this->getStage();
            $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
            if (!$this->getDifferential() && $stage > 4) {
                $leadEndChange = array_pop($notationExploded);
                $postLeadEndChange = array_shift($notationExploded);
                unset($notationExploded);
                $n = PlaceNotation::intToBell($stage);
                $n_1 = PlaceNotation::intToBell($stage - 1);
                $n_2 = PlaceNotation::intToBell($stage - 2);
                switch ($this->getNumberOfHunts()) {
                    case 0:
                        if ($stage % 2 == 0) {
                            if ($leadEndChange == '1'.$n) {
                                $this->calls = array( 'Bob' => '1'.$n_2.'::', 'Single' => '1'.$n_2.$n_1.$n.'::' );
                            }
                        } else {
                        }
                        break;
                    case 1:
                        if ($stage % 2 == 0) {
                            if ($leadEndChange == '12') {
                                $this->calls = array( 'Bob' => '14::', 'Single' => '1234::' );
                            } elseif ($leadEndChange == '1'.$n) {
                                if ($this->getLeadHeadCode() == 'm' && $stage > 6) {
                                    $this->calls = array( 'Bob' => '14::', 'Single' => '1234::' );
                                } else {
                                    $this->calls = array( 'Bob' => '1'.$n_2.'::', 'Single' => '1'.$n_2.$n_1.$n.'::' );
                                }
                            }
                        } else {
                            if ($leadEndChange == '12'.$n || $leadEndChange == '1') {
                                $this->calls = array( 'Bob' => '14'.$n.'::', 'Single' => (($stage<6) ? '123' : '1234'.$n).'::' );
                            } elseif ($leadEndChange == '123') {
                                $this->calls = array( 'Bob' => '12'.$n.'::' );
                            }
                        }
                        break;
                    case 2:
                        // Bobs and singles for Grandsire and Single Court like lead ends
                        if ($stage % 2 == 0) {
                            if ($leadEndChange == '1'.$n && $postLeadEndChange == '3'.$n) {
                                $this->calls = array( 'Bob' => '3'.$n.'.1'.$n.'::-1', 'Single' => '3'.$n.'.123'.$n.'::-1' );
                            }
                        } else {
                            if ($leadEndChange == '1' && ($postLeadEndChange == '3' || $postLeadEndChange == $n)) {
                                $this->calls = array( 'Bob' => '3.1::-1', 'Single' => '3.123::-1' );
                            }
                        }
                        break;
                    default:
                        $this->calls = array();
                }
            }
        }

        // Parse the format
        $calls = $this->calls;
        if (is_array($calls) && count($calls) > 0) {
            foreach ($calls as $title => &$call) {
                if (is_string($call)) {
                    if (preg_match('/^([^:]*):([^:]*):([^:]*)$/', $call, $matches) && isset($matches[1])) {
                        $call = array( 'notation' => $matches[1], 'every' => intval($matches[2] ?: $this->getLengthOfLead()), 'from' => intval($matches[3]) ?: 0 );
                    } else {
                        unset($calls[$title]);
                    }
                }
            }
            $this->calls = $calls;
        }

        return $this->calls ?: array();
    }

    /**
     * Set ruleOffs
     *
     * @param  array $ruleOffs
     * @return Method
     */
    public function setRuleOffs($ruleOffs)
    {
        $this->ruleOffs = $ruleOffs;
        return $this;
    }

    /**
     * Get ruleOffs
     *
     * @return array
     */
    public function getRuleOffs()
    {
        if (is_string($this->ruleOffs) && preg_match('/^([^:]*):([^:]*)$/', $this->ruleOffs, $matches) && isset($matches[1], $matches[2])) {
        // Generate rule off from the string if it's there
            return array( 'every' => intval($matches[1]), 'from' => intval($matches[2]) );
        } else if ($this->ruleOffs) {
        // Use the preset value if it's there
            return $this->ruleOffs;
        } else {
        // Check for methods similar to Grandsire and offset the rule off by one. TODO: Check that the hunt bells are actually hunting as well as leading one after the other near the lead end. (Hereford D G Bob Doubles is an example false positive)
            if ($this->getNumberOfHunts() == 2) {
                $hunts = $this->getHunts();
                $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
                $leadEndChange = array_pop($notationExploded);
                array_shift($notationExploded);
                $postLeadEndChange2 = array_shift($notationExploded);
                if ($hunts[0] == 1 && $hunts[1] == 2 && $leadEndChange{0} == '1' && (strlen($leadEndChange) == 1 || $leadEndChange{1} != '2') && $postLeadEndChange2{0} == '1') {
                    return array( 'every' => $this->getLengthOfLead(), 'from' => 1 );
                }
            }
        }
        // Otherwise assume this...
        return array( 'every' => $this->getLengthOfLead(), 'from' => 0 );
    }

    /**
     * Set callingPositions
     *
     * @param  array $callingPositions
     * @return Method
     */
    public function setCallingPositions($callingPositions)
    {
        $this->callingPositions = $callingPositions;
        return $this;
    }

    /**
     * Get callingPositions
     *
     * @return array
     */
    public function getCallingPositions()
    {
        if (!$this->callingPositions) {
            $stage = $this->getStage();
            $calls = $this->getCalls();
            $lengthOfLead = $this->getLengthOfLead();
            if ($stage < 6 || empty($calls) || !isset($calls['Bob'])) {
                $this->callingPositions = array();
            } else {
                // Calling positions for calls at lead ends (Home, Wrong and so forth)
                $bobNotation = PlaceNotation::explodedToPermutations($stage, PlaceNotation::explode($calls['Bob']['notation']), $stage);
                if ($calls['Bob']['every'] == $lengthOfLead && $calls['Bob']['from'] == 0 && count($bobNotation) == 1) {
                    $leadHeads = $this->getLeadHeads();
                    // Work out what the lead end of a bobbed lead looks like
                    $notation = PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded()));
                    $notation[$lengthOfLead-1] = $bobNotation[0];
                    $bobbedLead = PlaceNotation::apply($notation, PlaceNotation::rounds($stage));
                    $bobbedLeadHeadPermutation = array_map(function ($b) { return PlaceNotation::bellToInt($b) - 1; }, array_pop($bobbedLead));
                    // Collect an array of what happens at each lead if a bob is called
                    $bobbedLeadHeads = array( PlaceNotation::permute(PlaceNotation::rounds($stage), $bobbedLeadHeadPermutation) );
                    for ($i = 1; $i < count($leadHeads); $i++) {
                        array_push($bobbedLeadHeads, PlaceNotation::permute($leadHeads[$i-1], $bobbedLeadHeadPermutation));
                    }
                    // Convert the array of lead heads into calling position names
                    $this->callingPositions = array( 'from' => 0, 'every' => $lengthOfLead, 'titles' => array_map(function ($leadEnd) use ($stage) {
                        $position = array_search(PlaceNotation::intToBell($stage), $leadEnd);
                        switch ($position+1) {
                            case $stage:
                                return 'H';
                            case $stage-1:
                                if ($stage%2 == 0) {
                                    return 'W';
                                }
                                return 'M';
                            case 2:
                                return 'I';
                            case 3:
                                return 'B';
                            case 4:
                                return 'F';
                            case $stage-2:
                                if ($stage%2 == 0) {
                                    return 'M';
                                }
                                return 'W';
                            case 5:
                                return 'V';
                            case 6:
                                return 'X';
                            case 7:
                                return 'S';
                            case 8:
                                return 'E';
                            case 9:
                                return 'N';
                        }
                        return;
                    }, $bobbedLeadHeads) );
                }
            }
        }
        return $this->callingPositions ?: array();
    }

    /**
     * Set magic
     *
     * @param  integer $magic
     * @return Method
     */
    public function setMagic($magic)
    {
        $this->magic = $magic;
        return $this;
    }

    /**
     * Get magic
     *
     * @return integer
     */
    public function getMagic()
    {
        return $this->magic;
    }

    /**
     * @var string
     */
    private $url;

    /**
     * Set url
     *
     * @param  string $url
     * @return Method
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        if (!isset($this->url)) {
            $this->setUrl(str_replace([' ', '$', '&', '+', ',', '/', ':', ';', '=', '?', '@', '"', "'", '<', '>', '#', '%', '{', '}', '|', "\\", '^', '~', '[', ']', '.'], ['_'], iconv('UTF-8', 'ASCII//TRANSLIT', $this->getTitle())));
        }
        return $this->url;
    }

    /**
     * Add collections
     *
     * @param  \Blueline\MethodsBundle\Entity\MethodInCollection $collection
     * @return Method
     */
    public function addCollection(\Blueline\MethodsBundle\Entity\MethodInCollection $collection)
    {
        $this->collections[] = $collection;

        return $this;
    }

    /**
     * Remove collections
     *
     * @param \Blueline\MethodsBundle\Entity\MethodInCollection $collection
     */
    public function removeCollection(\Blueline\MethodsBundle\Entity\MethodInCollection $collection)
    {
        $this->collections->removeElement($collection);
    }

    /**
     * Get collections
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * Add performances
     *
     * @param  \Blueline\MethodsBundle\Entity\Performance $performance
     * @return Method
     */
    public function addPerformance(\Blueline\MethodsBundle\Entity\Performance $performance)
    {
        $this->performances[] = $performance;
        return $this;
    }

    /**
     * Remove performances
     *
     * @param \Blueline\MethodsBundle\Entity\Performance $performance
     */
    public function removePerformance(\Blueline\MethodsBundle\Entity\Performance $performance)
    {
        $this->performances->removeElement($performance);
    }

    /**
     * Get performances
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPerformances()
    {
        return $this->performances;
    }

    /**
     * Get performances where the method was originally named something else
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRenamed()
    {
        return $this->getPerformances()->filter(function ($p) { return $p->getType() == 'renamedMethod'; });
    }

    /**
     * Get performances where the method was duplicate named
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDuplicates()
    {
        return $this->getPerformances()->filter(function ($p) { return $p->getType() == 'duplicateMethod'; });
    }

    /**
     * Get the first towerbell peal
     *
     * @return \Blueline\MethodsBundle\Entity\Performance
     */
    public function getFirstTowerbellPeal()
    {
        return $this->getPerformances()->filter(function ($p) { return $p->getType() == 'firstTowerbellPeal'; })->get(0);
    }

    /**
     * Get the first handbell peal
     *
     * @return \Blueline\MethodsBundle\Entity\Performance
     */
    public function getFirstHandbellPeal()
    {
        return $this->getPerformances()->filter(function ($p) { return $p->getType() == 'firstHandbellPeal'; })->get(0);
    }

    // Non-database methods

    private $leadHeads;
    public function getLeadHeads()
    {
        if (!$this->leadHeads) {
            $rounds = PlaceNotation::rounds($this->getStage());
            $tmp = str_split($this->getLeadHead());
            $leadHeadPermutation = array_map(function ($b) { return PlaceNotation::bellToInt($b) - 1; }, $tmp);
            $leadHeads = array( $tmp );
            while (!PlaceNotation::rowsEqual($rounds, $tmp)) {
                $tmp = PlaceNotation::permute($tmp, $leadHeadPermutation);
                array_push($leadHeads, $tmp);
            }
            $this->leadHeads = $leadHeads;
        }
        return $this->leadHeads;
    }

    /**
     * Add methodsimilarity1
     *
     * @param \Blueline\MethodsBundle\Entity\MethodSimilarity $methodsimilarity1
     *
     * @return Method
     */
    public function addMethodsimilarity1(\Blueline\MethodsBundle\Entity\MethodSimilarity $methodsimilarity1)
    {
        $this->methodsimilarity1[] = $methodsimilarity1;
        return $this;
    }

    /**
     * Remove methodsimilarity1
     *
     * @param \Blueline\MethodsBundle\Entity\MethodSimilarity $methodsimilarity1
     */
    public function removeMethodsimilarity1(\Blueline\MethodsBundle\Entity\MethodSimilarity $methodsimilarity1)
    {
        $this->methodsimilarity1->removeElement($methodsimilarity1);
    }

    /**
     * Get methodsimilarity1
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMethodsimilarity1()
    {
        return $this->methodsimilarity1;
    }

    /**
     * Add methodsimilarity2
     *
     * @param \Blueline\MethodsBundle\Entity\MethodSimilarity $methodsimilarity2
     *
     * @return Method
     */
    public function addMethodsimilarity2(\Blueline\MethodsBundle\Entity\MethodSimilarity $methodsimilarity2)
    {
        $this->methodsimilarity2[] = $methodsimilarity2;
        return $this;
    }

    /**
     * Remove methodsimilarity2
     *
     * @param \Blueline\MethodsBundle\Entity\MethodSimilarity $methodsimilarity2
     */
    public function removeMethodsimilarity2(\Blueline\MethodsBundle\Entity\MethodSimilarity $methodsimilarity2)
    {
        $this->methodsimilarity2->removeElement($methodsimilarity2);
    }

    /**
     * Get methodsimilarity2
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMethodsimilarity2()
    {
        return $this->methodsimilarity2;
    }
}
