<?php

namespace Blueline\MethodsBundle\Entity;

use \Blueline\BluelineBundle\Helpers\Text;
use \Blueline\MethodsBundle\Helpers\PlaceNotation;
use \Blueline\MethodsBundle\Helpers\Stages;

/**
 * Blueline\MethodsBundle\Entity\Method
 */
class Method
{
    /**
     * @var string $title
     */
    private $title;

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
     * @var string $ruleOffs
     */
    private $ruleOffs;

    /**
     * Constructor
     */
    public function __construct( $firstSet = array() )
    {
        $this->collections  = new \Doctrine\Common\Collections\ArrayCollection();
        $this->performances = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setAll( $firstSet );
    }

    public function __toString() {
        return $this->getTitle();
    }

    /**
     * Sets multiple variables using an array of them
     *
     * @param array $map
     */
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.str_replace( ' ', '', ucwords( str_replace( '_', ' ', $key ) ) );
            if ( is_callable( array( $this, $method ) ) ) {
                $this->$method( $value );
            }
        }

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
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
     * Set stage
     *
     * @param  integer $stage
     * @return Method
     */
    public function setStage($stage)
    {
        $this->stage = $stage;

        return $this;
    }

    /**
     * Get stage
     *
     * @return integer
     */
    public function getStage()
    {
        return $this->stage;
    }
    public function getStageText()
    {
        return Stages::toString( $this->getStage() );
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
        return $this->notationExpanded;
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
        return $this->lengthOfLead;
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
        return $this->numberOfHunts;
    }

    private $hunts;
    public function getHunts()
    {
        if (!$this->hunts) {
            $hunts = array();
            $leadHead = array_map( function ($n) { return PlaceNotation::bellToInt( $n ); }, str_split( $this->getLeadHead() ) );
            for ( $i = 0, $iLim = count( $leadHead ); $i < $iLim; ++$i ) {
                if ( ($i+1) == $leadHead[$i] ) { array_push( $hunts, $leadHead[$i] ); }
            }
            $this->hunts = $hunts;
        }

        return $this->hunts;
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
        return $this->rotational;
    }

    public function getSymmetryText()
    {
        return ucfirst( Text::toList( array_filter( array( ($this->getPalindromic()?'palindromic':''), ($this->getDoublesym()?'double':''), ($this->getRotational()?'rotational':'') ) ) ) );
    }

    /**
     * Set calls
     *
     * @param  array   $calls
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
        if ( empty( $this->calls ) ) {
            $stage = $this->getStage();
            $notationExploded = PlaceNotation::explode( $this->getNotationExpanded() );
            if ( !$this->getDifferential() && $stage > 4 ) {
                $leadEndChange = array_pop( $notationExploded );
                $postLeadEndChange = array_shift( $notationExploded );
                unset( $notationExploded ); // Prevent naive reuse
                $n = PlaceNotation::intToBell( $stage );
                $n_1 = PlaceNotation::intToBell( $stage - 1 );
                $n_2 = PlaceNotation::intToBell( $stage - 2 );
                switch ( $this->getNumberOfHunts() ) {
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
                            if ( $this->getLeadHeadCode() == 'm' ) {
                                $this->calls = array( 'Bob' => '14::', 'Single' => '1234::' );
                            } else {
                                $this->calls = array( 'Bob' => '1'.$n_2.'::', 'Single' => '1'.$n_2.$n_1.$n.'::' );
                            }
                        }
                    } else {
                        if ($leadEndChange == '12'.$n || $leadEndChange == '1') {
                            $this->calls = array( 'Bob' => '14'.$n.'::', 'Single' => (($stage<6)?'123':'1234'.$n).'::' );
                        } elseif ($leadEndChange == '123') {
                            $this->calls = array( 'Bob' => '12'.$n.'::' );
                        }
                    }
                    break;
                case 2:
                    // Bobs and singles for Grandsire and Single Court like lead ends
                    if ( $leadEndChange == '1' && ( $postLeadEndChange == '3' || $postLeadEndChange == $n ) ) {
                        $this->calls = array( 'Bob' => '3.1::-1', 'Single' => '3.23::-1' );
                    }
                    break;
                default:
                    $this->calls = array();
                }
            }
        }

        // Parse the format
        $calls = $this->calls;
        if ( is_array( $calls ) && count( $calls ) > 0 ) {
            foreach ($calls as $title => &$call) {
                if ( is_string( $call ) ) {
                    if ( preg_match( '/^([^:]*):([^:]*):([^:]*)$/', $call, $matches ) && isset( $matches[1] ) ) {
                        $call = array( 'notation' => $matches[1], 'every' => intval( $matches[2]?:$this->getLengthOfLead() ), 'from' => intval( $matches[3] )?:0 );
                    } else {
                        unset( $calls[$title] );
                    }
                }
            }
            $this->calls = $calls;
        }

        return $this->calls? : array();
    }

    /**
     * Set ruleOffs
     *
     * @param  string $ruleOffs
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
        // Generate rule off from the string if it's there
        if ( is_string( $this->ruleOffs ) && preg_match( '/^([^:]*):([^:]*)$/', $this->ruleOffs, $matches ) && isset( $matches[1], $matches[2] ) ) {
            return array( 'every' => intval( $matches[1] ), 'from' => intval( $matches[2] ) );
        } else {
            return $this->ruleOffs? : array( 'from' => 0, 'every' => $this->getLengthOfLead() );
        }
    }

    // Non-database methods

    private $callingPositions;
    public function getCallingPositions()
    {
        if (!$this->callingPositions) {
            $stage = $this->getStage();
            $calls = $this->getCalls();
            $lengthOfLead = $this->getLengthOfLead();
            if ( $stage < 6 || empty( $calls ) || !isset( $calls['Bob'] ) ) {
                $this->callingPositions = array();
            } else {
                // Calling positions for calls at lead ends (Home, Wrong and so forth)
                $bobNotation = PlaceNotation::explodedToPermutations( $stage, PlaceNotation::explode( $calls['Bob']['notation'] ), $stage );
                if ( $calls['Bob']['every'] == $lengthOfLead && $calls['Bob']['from'] == 0 && count( $bobNotation ) == 1 ) {
                    $leadHeads = $this->getLeadHeads();
                    // Work out what the lead end of a bobbed lead looks like
                    $notation = PlaceNotation::explodedToPermutations( $this->getStage(), PlaceNotation::explode( $this->getNotationExpanded() ) );
                    $notation[$lengthOfLead-1] = $bobNotation[0];
                    $bobbedLead = PlaceNotation::apply( $notation, PlaceNotation::rounds( $stage ) );
                    $bobbedLeadHeadPermutation = array_map( function ($b) { return PlaceNotation::bellToInt( $b ) - 1; }, array_pop( $bobbedLead ) );
                    // Collect an array of what happens at each lead if a bob is called
                    $bobbedLeadHeads = array( PlaceNotation::permute( PlaceNotation::rounds( $stage ), $bobbedLeadHeadPermutation ) );
                    for ( $i = 1; $i < count( $leadHeads ); $i++ ) {
                        array_push( $bobbedLeadHeads, PlaceNotation::permute( $leadHeads[$i-1], $bobbedLeadHeadPermutation ) );
                    }
                    // Convert the array of lead heads into calling position names
                    $this->callingPositions = array( 'from' => 0, 'every' => $lengthOfLead, 'titles' => array_map( function ($leadEnd) use ($stage) {
                        $position = array_search( PlaceNotation::intToBell( $stage ), $leadEnd );
                        switch ($position+1) {
                            case 2:
                                return 'I';
                            case 3:
                                return 'B';
                            case 4:
                                return 'F';
                            case $stage-2:
                                return 'M';
                            case $stage-1:
                                return 'W';
                            case $stage:
                                return 'H';
                            case 5:
                                return 'V';
                        }

                        return null;
                    }, $bobbedLeadHeads ) );
                }
            }
        }

        return $this->callingPositions? : array();
    }

    private $leadHeads;
    public function getLeadHeads()
    {
        if (!$this->leadHeads) {
            $rounds = PlaceNotation::rounds( $this->getStage() );
            $tmp = str_split( $this->getLeadHead() );
            $leadHeadPermutation = array_map( function ($b) { return PlaceNotation::bellToInt( $b ) - 1; }, $tmp );
            $leadHeads = array( $tmp );
            while ( !PlaceNotation::rowsEqual( $rounds, $tmp ) ) {
                $tmp = PlaceNotation::permute( $tmp, $leadHeadPermutation );
                array_push( $leadHeads, $tmp );
            }
            $this->leadHeads = $leadHeads;
        }

        return $this->leadHeads;
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
        return $this->url;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $collections;

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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $performances;

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
        return $this->getPerformances()->filter( function($p) { return $p->getType() == 'renamedMethod'; } );
    }

    /**
     * Get performances where the method was duplicate named
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDuplicates()
    {
        return $this->getPerformances()->filter( function($p) { return $p->getType() == 'duplicateMethod'; } );
    }

    /**
     * Get the first towerbell peal
     *
     * @return \Blueline\MethodsBundle\Entity\Performance
     */
    public function getFirstTowerbellPeal()
    {
        return $this->getPerformances()->filter( function($p) { return $p->getType() == 'firstTowerbellPeal'; } )->get(0);
    }

    /**
     * Get the first handbell peal
     *
     * @return \Blueline\MethodsBundle\Entity\Performance
     */
    public function getFirstHandbellPeal()
    {
        return $this->getPerformances()->filter( function($p) { return $p->getType() == 'firstHandbellPeal'; } )->get(0);
    }
}