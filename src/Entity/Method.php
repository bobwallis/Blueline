<?php

namespace Blueline\Entity;

use Blueline\Helpers\Text;
use Blueline\Helpers\PlaceNotation;
use Blueline\Helpers\Stages;
use Blueline\Helpers\LeadHeadCodes;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
require_once(__DIR__.'/../Helpers/arrays_equal_in_some_rotation.php');
use function Blueline\Helpers\arrays_equal_in_some_rotation;

/**
 * Method entity
 */
#[UniqueEntity('url')]
#[ORM\Entity(repositoryClass: \Blueline\Repository\MethodRepository::class)]
#[ORM\Table(name: 'methods')]
#[ORM\Index(name: 'idx_methods_stage', columns: array('stage'))]
#[ORM\Index(name: 'idx_methods_stage_lengthoflead', columns: array('stage', 'lengthoflead'))]
class Method
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $url;

    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    private ?string $abbreviation = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $provisional = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\GreaterThanOrEqual(3)]
    private int $stage;

    #[ORM\Column(type: 'string', length: 31, nullable: true)]
    private ?string $classification = null;

    #[ORM\Column(name: 'nameMetaphone', type: 'string', length: 255, nullable: true)]
    private ?string $nameMetaphone = null;

    #[ORM\Column(type: 'string', length: 2047)]
    private string $notation;

    #[ORM\Column(name: 'notationExpanded', type: 'string', length: 4096)]
    private string $notationExpanded;

    #[ORM\Column(name: 'leadHeadCode', type: 'string', length: 31, nullable: true)]
    private ?string $leadHeadCode = null;

    #[ORM\Column(name: 'leadHead', type: 'string', length: 31)]
    private string $leadHead;

    #[ORM\Column(name: 'fchGroups', type: 'string', length: 31, nullable: true)]
    private ?string $fchGroups = null;

    #[ORM\Column(name: 'lengthOfLead', type: 'integer')]
    #[Assert\GreaterThanOrEqual(1)]
    private int $lengthOfLead;

    #[ORM\Column(name: 'lengthOfCourse', type: 'integer')]
    private int $lengthOfCourse;

    #[ORM\Column(name: 'numberOfLeads', type: 'integer', nullable: true)]
    private ?int $numberOfLeads = null;

    #[ORM\Column(name: 'numberOfHunts', type: 'integer', nullable: true)]
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $numberOfHunts = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $jump = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $little = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $differential = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $plain = null;

    #[ORM\Column(name: 'trebleDodging', type: 'boolean', nullable: true)]
    private ?bool $trebleDodging = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $palindromic = null;

    #[ORM\Column(name: 'doubleSym', type: 'boolean', nullable: true)]
    private ?bool $doubleSym = null;

    #[ORM\Column(name: 'rotational', type: 'boolean', nullable: true)]
    private ?bool $rotational = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $calls = null;

    #[ORM\Column(name: 'ruleOffs', type: 'json', nullable: true)]
    private ?array $ruleOffs = null;

    #[ORM\Column(name: 'callingPositions', type: 'json', nullable: true)]
    private ?array $callingPositions = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $magic = null;

    #[ORM\Column(name: 'cccbrId', type: 'string', length: 255, nullable: true)]
    private ?string $cccbrId = null;

    #[ORM\Column(name: 'methodReferences', type: 'text', nullable: true)]
    private ?string $methodReferences = null;

    #[ORM\Column(name: 'extensionConstruction', type: 'text', nullable: true)]
    private ?string $extensionConstruction = null;

    #[ORM\OneToMany(targetEntity: MethodInCollection::class, mappedBy: 'method', cascade: ['persist', 'remove'])]
    private \Doctrine\Common\Collections\Collection $collections;

    #[ORM\OneToMany(targetEntity: Performance::class, mappedBy: 'method', cascade: ['persist', 'remove'])]
    private \Doctrine\Common\Collections\Collection $performances;

    #[ORM\OneToMany(targetEntity: MethodSimilarity::class, mappedBy: 'method1')]
    private \Doctrine\Common\Collections\Collection $methodSimilarity1;

    #[ORM\OneToMany(targetEntity: MethodSimilarity::class, mappedBy: 'method2')]
    private \Doctrine\Common\Collections\Collection $methodSimilarity2;

    /**
     * Create a method entity and optionally hydrate it from an associative array.
     *
     * @param array<string, mixed> $firstSet Initial property values keyed by setter-compatible names
     */
    public function __construct($firstSet = array())
    {
        $this->collections  = new \Doctrine\Common\Collections\ArrayCollection();
        $this->performances = new \Doctrine\Common\Collections\ArrayCollection();
        $this->setAll($firstSet);
    }

    /**
     * Convert the entity to a short debug string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'Method:'.$this->getTitle();
    }

    /**
     * Convert the entity to an array for template/API serialisation.
     *
        * Optionally filters to a subset of fields.
     *
     * @param string|array<int, string>|null $fields
     * @return array<string, mixed>
     */
    public function __toArray($fields = null)
    {
        if (is_string($fields)) {
            $fields = array_filter(array_map('trim', explode(',', $fields)));
        }

        $fieldSerialisers = array(
            'title' => fn() => $this->getTitle(),
            'abbreviation' => fn() => $this->getAbbreviation(),
            'provisional' => fn() => $this->getProvisional(),
            'stage' => fn() => $this->getStage(),
            'classification' => fn() => $this->getClassification(),
            'nameMetaphone' => fn() => $this->getNameMetaphone(),
            'notation' => fn() => $this->getNotation(),
            'notationExpanded' => fn() => $this->getNotationExpanded(),
            'leadHeadCode' => fn() => $this->getLeadHeadCode(),
            'leadHead' => fn() => $this->getLeadHead(),
            'fchGroups' => fn() => $this->getFchGroups(),
            'lengthOfLead' => fn() => $this->getLengthOfLead(),
            'lengthOfCourse' => fn() => $this->getLengthOfCourse(),
            'numberOfLeads' => fn() => $this->getNumberOfLeads(),
            'numberOfHunts' => fn() => $this->getNumberOfHunts(),
            'jump' => fn() => $this->getJump(),
            'little' => fn() => $this->getLittle(),
            'differential' => fn() => $this->getDifferential(),
            'plain' => fn() => $this->getPlain(),
            'trebleDodging' => fn() => $this->getTrebleDodging(),
            'palindromic' => fn() => $this->getPalindromic(),
            'doubleSym' => fn() => $this->getDoubleSym(),
            'rotational' => fn() => $this->getRotational(),
            'calls' => fn() => $this->getCalls(),
            'ruleOffs' => fn() => $this->getRuleOffs(),
            'callingPositions' => fn() => $this->getCallingPositions(),
            'magic' => fn() => $this->getMagic(),
            'cccbrId' => fn() => $this->getCccbrId(),
            'methodReferences' => fn() => $this->getMethodReferences(),
            'extensionConstruction' => fn() => $this->getExtensionConstruction(),
            'url' => fn() => $this->getUrl(),
        );

        if (is_array($fields) && !empty($fields)) {
            $requestedFields = array_values(array_intersect($fields, array_keys($fieldSerialisers)));

            $objectVars = array();
            foreach ($requestedFields as $field) {
                $objectVars[$field] = $fieldSerialisers[$field]();
            }

            return $objectVars;
        }

        $objectVars = array();
        foreach ($fieldSerialisers as $field => $serialiser) {
            $objectVars[$field] = $serialiser();
        }

        return array_filter($objectVars);
    }

    /**
     * Bulk-set properties from an associative array.
     *
     * Keys are mapped to setter names using snake_case to StudlyCase conversion.
     * Unknown keys are ignored.
     *
     * @param array<string, mixed> $map
     * @return Method
     */
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

    // Getters and setters
    public function getTitle()
    {
        if (!isset($this->title)) {
            $descriptorText = $this->getClassDescriptor();
            $this->setTitle(implode(' ', array_filter(['Unnamed', $descriptorText, $this->getStageText()])));
        }
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getAbbreviation()
    {
        if (!isset($this->abbreviation)) {
            $this->setAbbreviation(substr(trim(str_replace(array($this->getStageText(), $this->getClassification(), 'Differential', 'Little', 'Jump'), '', $this->getTitle())), 0, 2));
        }
        return $this->abbreviation;
    }

    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;
        return $this;
    }

    public function setProvisional($provisional)
    {
        $this->provisional = $provisional;
        return $this;
    }

    public function getProvisional()
    {
        return $this->provisional;
    }

    public function setStage($stage)
    {
        $this->stage = intval($stage);
        return $this;
    }

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
            if ($this->getJump()) {
                $this->setClassification('Jump');
            } elseif ($this->getNumberOfHunts() > 0) {
                $principalHunts = array_values(array_filter($this->getHuntDetails(), function ($h) { return $h['principal']; } ));
                $principalHuntType = $principalHunts[0]['type'];

                // For Plain if all bells only make places and hunt then it's 'Place', otherwise 'Bob'
                if ($principalHuntType == 'Plain') {
                    // Generate a lead, and a "lead+1 change" (we'll need the latter to check if all bells only hunt and make places since we'll need to check for dodges/points over the lead end)
                    $lead = PlaceNotation::apply(PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded())), PlaceNotation::rounds($this->getStage()));
                    array_unshift($lead, PlaceNotation::rounds($this->getStage()));
                    $leadPlusOneChangeFromNextLead = $lead;
                    array_push($leadPlusOneChangeFromNextLead, PlaceNotation::apply(PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded()))[0], end($leadPlusOneChangeFromNextLead)));

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

                // For Treble Dodging see if internal places are made at each cross section. All => 'Surprise', None => 'Treble Bob', Some => 'Delight'
                } elseif ($principalHuntType == 'Treble Dodging') {
                    // Generate a lead
                    $lead = PlaceNotation::apply(PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded())), PlaceNotation::rounds($this->getStage()));
                    array_unshift($lead, PlaceNotation::rounds($this->getStage()));
                    // If the hunt bell dodges in only one position it's Treble Bob (since there are no cross sections, and that edge case is defined Treble Bob)
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

    public function setNameMetaphone($nameMetaphone)
    {
        $this->nameMetaphone = $nameMetaphone;
        return $this;
    }

    public function getNameMetaphone()
    {
        if (!isset($this->nameMetaphone)) {
            $this->setNameMetaphone(metaphone(preg_replace('/(Differential)?\s*(Little)?\s*(Alliance|Bob|Delight|Hybrid|Place|Surprise|Slow Course|Treble Bob|Treble Place)?\s*(Jump)?\s*(Two|Singles|Minimus|Doubles|Minor|Triples|Major|Caters|Royal|Cinques|Maximus|Sextuples|Fourteen|Septuples|Sixteen|Octuples|Eighteen|Nineteen|Twenty|Twenty-one|Twenty-two)$/', '', $this->getTitle())));
        }
        return $this->nameMetaphone;
    }

    public function setNotation($notation)
    {
        $this->notation = $notation;
        return $this;
    }

    public function getNotation()
    {
        return $this->notation;
    }

    public function setNotationExpanded($notationExpanded)
    {
        $this->notationExpanded = $notationExpanded;
        return $this;
    }

    public function getNotationExpanded()
    {
        if (!isset($this->notationExpanded)) {
            $this->setNotationExpanded(PlaceNotation::expand($this->getNotation(), $this->getStage()));
        }
        return $this->notationExpanded;
    }

    public function getNotationSiril()
    {
        return PlaceNotation::siril($this->getNotationExpanded(), $this->getStage());
    }

    public function setLeadHeadCode($leadHeadCode)
    {
        $this->leadHeadCode = $leadHeadCode;
        return $this;
    }

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

    public function setLeadHead($leadHead)
    {
        $this->leadHead = $leadHead;
        return $this;
    }

    public function getLeadHead()
    {
        if (!isset($this->leadHead)) {
            $lead = PlaceNotation::apply(PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded())), PlaceNotation::rounds($this->getStage()));
            $this->setLeadHead(implode('', end($lead)));
        }
        return $this->leadHead;
    }

    public function setFchGroups($fchGroups)
    {
        $this->fchGroups = $fchGroups;
        return $this;
    }

    public function getFchGroups()
    {
        return $this->fchGroups;
    }

    public function setLengthOfLead($lengthOfLead)
    {
        $this->lengthOfLead = $lengthOfLead;
        return $this;
    }

    public function getLengthOfLead()
    {
        if (!isset($this->lengthOfLead)) {
            $this->setLengthOfLead(count(PlaceNotation::explode($this->getNotationExpanded())));
        }
        return $this->lengthOfLead;
    }

    public function setLengthOfCourse($lengthOfCourse)
    {
        $this->lengthOfCourse = $lengthOfCourse;
        return $this;
    }

    public function getLengthOfCourse()
    {
        if (!isset($this->lengthOfCourse)) {
            $this->setLengthOfCourse($this->getLengthOfLead() * $this->getNumberOfLeads());
        }
        return $this->lengthOfCourse;
    }

    public function setNumberOfLeads($numberOfLeads)
    {
        $this->numberOfLeads = $numberOfLeads;
        return $this;
    }

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

    public function setNumberOfHunts($numberOfHunts)
    {
        $this->numberOfHunts = $numberOfHunts;
        return $this;
    }

    public function getNumberOfHunts()
    {
        if (!isset($this->numberOfHunts)) {
            $this->numberOfHunts = count($this->getHunts());
        }
        return $this->numberOfHunts;
    }

    private $hunts;
    /**
     * Get hunt bells (bells fixed in lead-head permutation).
     *
     * @return array<int, int> Bell numbers that are hunt bells
     */
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

    /**
     * Get detailed hunt-bell path analysis for classification logic.
     *
     * Each entry contains the bell number, path, and derived properties such as
     * wellFormedPath, little, and hunt type.
     *
     * @return array<int, array<string, mixed>>
     */
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

                    // Generate the hunt bell's path
                    $huntDetails[$i]['path'] = array_map(function ($row) use ($huntText) {
                        return array_search($huntText, $row) + 1;
                    }, $lead);
                    array_unshift($huntDetails[$i]['path'], array_pop($huntDetails[$i]['path']));

                    // Well-formed paths are the same when rung backwards
                    $huntDetails[$i]['wellFormedPath'] = count($huntDetails[$i]['path'])%2 == 0 && arrays_equal_in_some_rotation($huntDetails[$i]['path'], array_reverse($huntDetails[$i]['path']));

                    // A path is little if it doesn't reach the front or back
                    $huntDetails[$i]['little'] = min($huntDetails[$i]['path']) > 1 || max($huntDetails[$i]['path']) < $this->getStage();

                    // Count the number of places in the hunt bell's path
                    $numberOfPlacesInPath = array_reduce($huntDetails[$i]['path'], function ($carry, $pos) {
                        $carry['places'] = ($carry['lastPos'] == $pos)? $carry['places'] + 1 : $carry['places'];
                        $carry['lastPos'] = $pos;
                        return $carry;
                    }, array('lastPos' => end($huntDetails[$i]['path']), 'places' => 0))['places'];

                    // Count the number of blows the hunt bell makes in each position
                    $blowsInEachPosition = array_reduce($huntDetails[$i]['path'], function ($carry, $pos) {
                        if (isset($carry[$pos])) {
                            $carry[$pos]++;
                        } else {
                            $carry[$pos] = 1;
                        }
                        ksort($carry);
                        return $carry;
                    }, array());

                    // Check if the hunt bell makes the same number of blows in each position
                    $sameNumberOfBlowsInEachPosition = count(array_unique($blowsInEachPosition)) == 1;

                    // Well-formed paths are then classified into a few different types using the 3 things we just calculated
                    if ($huntDetails[$i]['wellFormedPath']) {
                        // If the hunt makes two blows in every place - it's Plain
                        if (array_sum($blowsInEachPosition)/2 == count($blowsInEachPosition)) {
                            $huntDetails[$i]['type'] = 'Plain';
                        // If the same number of blows in each position and no more than two places in the lead then 'Treble Dodging'
                        } elseif ($sameNumberOfBlowsInEachPosition && $numberOfPlacesInPath == 2) {
                            $huntDetails[$i]['type'] = 'Treble Dodging';
                        // If the same number of blows in each position and more than 2 places then 'Treble Place'
                        } elseif ($sameNumberOfBlowsInEachPosition && $numberOfPlacesInPath > 2 ) {
                            $huntDetails[$i]['type'] = 'Treble Place';
                        // If a different number of blows in each position then 'Alliance'
                        } else {
                            $huntDetails[$i]['type'] = 'Alliance';
                        }

                    // Non-well-formed paths are Hybrid
                    } else {
                        $huntDetails[$i]['type'] = 'Hybrid';
                    }
                }

                // Determine which are the principal hunts
                // If there's only one it is the principal hunt
                if (count($huntDetails) == 1) {
                    $huntDetails[0]['principal'] = true;
                // Otherwise there is a hierachy of hunt types defined in the rules (and just the non-little hunts of that type if there are both little and non-little examples)
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

    public function setJump($jump)
    {
        $this->jump = $jump;
        return $this;
    }

    public function getJump()
    {
        if (!isset($this->jump)) {
            $this->setJump(strpos($this->getNotationExpanded(),'(') !== false || strpos($this->getNotationExpanded(),'[') !== false);
        }
        return $this->jump;
    }

    public function setLittle($little)
    {
        $this->little = $little;
        return $this;
    }

    public function getLittle()
    {
        if (!isset($this->little)) {
            $this->setLittle(count(array_filter($this->getHuntDetails(), function ($h) { return $h['principal'] && $h['little']; })) > 0);
        }
        return $this->little;
    }

    public function setDifferential($differential)
    {
        $this->differential = $differential;
        return $this;
    }

    public function getDifferential()
    {
        if (!isset($this->differential)) {
            $this->setDifferential($this->getNumberOfLeads() != ($this->getStage() - $this->getNumberOfHunts()));
        }
        return $this->differential;
    }

    public function setPlain($plain)
    {
        $this->plain = $plain;
        return $this;
    }

    public function getPlain()
    {
        if (!isset($this->plain)) {
            $this->setPlain(count(array_filter($this->getHuntDetails(), function ($h) { return $h['principal'] && $h['type'] == 'Plain'; })) > 0);
        }
        return $this->plain;
    }

    public function setTrebleDodging($trebleDodging)
    {
        $this->trebleDodging = $trebleDodging;
        return $this;
    }

    public function getTrebleDodging()
    {
        if (!isset($this->trebleDodging)) {
            $this->setTrebleDodging(count(array_filter($this->getHuntDetails(), function ($h) { return $h['principal'] && $h['type'] == 'Treble Dodging'; })) > 0);
        }
        return $this->trebleDodging;
    }

    public function setPalindromic($palindromic)
    {
        $this->palindromic = $palindromic;
        return $this;
    }

    public function getPalindromic()
    {
        if (!isset($this->palindromic)) {
            $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
            $this->setPalindromic(arrays_equal_in_some_rotation($notationExploded, array_reverse($notationExploded)));
        }
        return $this->palindromic;
    }

    public function setDoubleSym($doubleSym)
    {
        $this->doubleSym = $doubleSym;
        return $this;
    }

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
                    return implode('', array_reverse($eExplode));
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

    public function setRotational($rotational)
    {
        $this->rotational = $rotational;
        return $this;
    }

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
        if (!isset($this->calls)) {
            $stage = $this->getStage();
            $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
            $calls = array();
            if (!$this->getDifferential() && $stage > 4) {
                $leadEndChange = array_pop($notationExploded);
                $postLeadEndChange = array_shift($notationExploded);
                unset($notationExploded);
                $lengthOfLead = $this->getLengthOfLead();
                $n = PlaceNotation::intToBell($stage);
                $n_1 = PlaceNotation::intToBell($stage - 1);
                $n_2 = PlaceNotation::intToBell($stage - 2);
                switch ($this->getNumberOfHunts()) {
                    case 0:
                        if ($stage % 2 == 0) {
                            if ($leadEndChange == '1'.$n) {
                                $calls = array(
                                    'Bob' => array('symbol' => '-', 'notation' => '1'.$n_2, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1),
                                    'Single' => array('symbol' => 's', 'notation' => '1'.$n_2.$n_1.$n, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1)
                                );
                            }
                        }
                        break;
                    case 1:
                        if ($stage % 2 == 0) {
                            if ($leadEndChange == '12') {
                                $calls = array(
                                    'Bob' => array('symbol' => '-', 'notation' => '14', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1),
                                    'Single' => array('symbol' => 's', 'notation' => '1234', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1)
                                );
                            } elseif ($leadEndChange == '1'.$n) {
                                if ($this->getLeadHeadCode() == 'm' && $stage > 6) {
                                    $calls = array(
                                        'Bob' => array('symbol' => '-', 'notation' => '14', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1),
                                        'Single' => array('symbol' => 's', 'notation' => '1234', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1)
                                    );
                                } else {
                                    $calls = array(
                                        'Bob' => array('symbol' => '-', 'notation' => '1'.$n_2, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1),
                                        'Single' => array('symbol' => 's', 'notation' => '1'.$n_2.$n_1.$n, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1)
                                    );
                                }
                            } elseif ($leadEndChange == '14' && $stage == 6) {
                                $calls = array(
                                    'Bob' => array('symbol' => '-', 'notation' => '16', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1),
                                    'Single' => array('symbol' => 's', 'notation' => '156', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1)
                                );
                            }
                        } else {
                            if ($leadEndChange == '12'.$n || $leadEndChange == '1') {
                                $calls = array(
                                    'Bob' => array('symbol' => '-', 'notation' => '14'.$n, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1),
                                    'Single' => array('symbol' => 's', 'notation' => (($stage<6) ? '123' : '1234'.$n), 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1)
                                );
                            } elseif ($leadEndChange == '123') {
                                $calls = array(
                                    'Bob' => array('symbol' => '-', 'notation' => '12'.$n, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1)
                                );
                            }
                        }
                        break;
                    case 2:
                        // Bobs and singles for Grandsire and Single Court like lead ends
                        if ($stage % 2 == 0) {
                            if ($leadEndChange == '1'.$n && $postLeadEndChange == '3'.$n) {
                                $calls = array(
                                    'Bob' => array('symbol' => '-', 'notation' => '3'.$n.'.1'.$n, 'from' => -1, 'every' => $lengthOfLead, 'cover' => 2),
                                    'Single' => array( 'symbol' => 's', 'notation' => '3'.$n.'.123'.$n, 'from' => -1, 'every' => $lengthOfLead, 'cover' => 2)
                                );
                            }
                        } else {
                            if ($leadEndChange == '1' && ($postLeadEndChange == '3' || $postLeadEndChange == $n)) {
                                $calls = array(
                                    'Bob' => array('symbol' => '-', 'notation' => '3.1', 'from' => -1, 'every' => $lengthOfLead, 'cover' => 2),
                                    'Single' => array( 'symbol' => 's', 'notation' => '3.123', 'from' => -1, 'every' => $lengthOfLead, 'cover' => 2)
                                );
                            }
                        }
                        break;
                    default:
                        $calls = array();
                }
            }
            $this->calls = $calls;
        }

        return $this->calls ?: array();
    }

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
        if (empty($this->ruleOffs)) {
        // Check for methods similar to Grandsire and offset the rule off by one. TODO: Check that the hunt bells are actually hunting as well as leading one after the other near the lead end. (Hereford D G Bob Doubles is an example false positive)
            if ($this->getNumberOfHunts() == 2) {
                $hunts = $this->getHunts();
                $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
                $leadEndChange = array_pop($notationExploded);
                array_shift($notationExploded);
                $postLeadEndChange2 = array_shift($notationExploded);
                if ($hunts[0] == 1 && $hunts[1] == 2 && $leadEndChange[0] == '1' && (strlen($leadEndChange) == 1 || $leadEndChange[1] != '2') && $postLeadEndChange2[0] == '1') {
                    $this->ruleOffs = array( 'every' => $this->getLengthOfLead(), 'from' => 1 );
                }
            }
            // Otherwise assume this...
            $this->ruleOffs =  array( 'every' => $this->getLengthOfLead(), 'from' => 0 );
        }
        return $this->ruleOffs;
    }

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
        if (empty($this->callingPositions) && !empty($this->getCalls())) {
            $stage = $this->getStage();
            $calls = $this->getCalls();
            $lengthOfLead = $this->getLengthOfLead();
            if ($stage > 4 && !empty($calls) && isset($calls['Bob']) && $calls['Bob']['every'] == $lengthOfLead && $calls['Bob']['from'] == 0 && $calls['Bob']['cover'] == 1) {
                // Calling positions for calls at lead ends (Home, Wrong and so forth)
                $bobNotation = PlaceNotation::explodedToPermutations($stage, PlaceNotation::explode($calls['Bob']['notation']));
                $leadHeads = $this->getLeadHeads();
                // Work out what the lead end of a bobbed lead looks like
                $notation = PlaceNotation::explodedToPermutations($stage, PlaceNotation::explode($this->getNotationExpanded()));
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
            } else {
                $this->callingPositions = array();
            }
        }
        return $this->callingPositions ?: array();
    }

    public function setMagic($magic)
    {
        $this->magic = $magic;
        return $this;
    }

    public function getMagic()
    {
        return $this->magic;
    }

    public function setCccbrId($cccbrId)
    {
        $this->cccbrId = $cccbrId;
        return $this;
    }

    public function getCccbrId()
    {
        return $this->cccbrId;
    }

    public function setMethodReferences($methodReferences)
    {
        $this->methodReferences = $methodReferences;
        return $this;
    }

    public function getMethodReferences()
    {
        return $this->methodReferences;
    }

    public function setExtensionConstruction($extensionConstruction)
    {
        $this->extensionConstruction = $extensionConstruction;
        return $this;
    }

    public function getExtensionConstruction()
    {
        return $this->extensionConstruction;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl()
    {
        if (!isset($this->url)) {
            $this->setUrl(str_replace([' ', '$', '&', '+', ',', '/', ':', ';', '=', '?', '@', '"', "'", '<', '>', '#', '%', '{', '}', '|', "\\", '^', '~', '[', ']', '.'], ['_'], iconv('UTF-8', 'ASCII//TRANSLIT', $this->getTitle())));
        }
        return $this->url;
    }

    public function addCollection(\Blueline\Entity\MethodInCollection $collection)
    {
        $this->collections[] = $collection;

        return $this;
    }

    public function removeCollection(\Blueline\Entity\MethodInCollection $collection)
    {
        $this->collections->removeElement($collection);
    }

    public function getCollections()
    {
        return $this->collections;
    }

    public function addPerformance(\Blueline\Entity\Performance $performance)
    {
        $this->performances[] = $performance;
        return $this;
    }

    public function removePerformance(\Blueline\Entity\Performance $performance)
    {
        $this->performances->removeElement($performance);
    }

    public function getPerformances()
    {
        return $this->performances;
    }

    public function getRenamed()
    {
        return $this->getPerformances()->filter(function ($p) { return $p->getType() == 'renamedMethod'; });
    }

    public function getDuplicates()
    {
        return $this->getPerformances()->filter(function ($p) { return $p->getType() == 'duplicateMethod'; });
    }

    public function getFirstTowerbellPeal()
    {
        return $this->getPerformances()->filter(function ($p) { return $p->getType() == 'firstTowerbellPeal'; })->get(0);
    }

    public function getFirstHandbellPeal()
    {
        return $this->getPerformances()->filter(function ($p) { return $p->getType() == 'firstHandbellPeal'; })->get(0);
    }

    // Non-database methods

    /**
     * Build the method Class Descriptor as per the CCCBR Method Framework.
     *
     * @return string
     */
    public function getClassDescriptor()
    {
        $classification = isset($this->classification) ? $this->classification : $this->getClassification();
        $classificationTerms = ['Place', 'Bob', 'Treble Bob', 'Surprise', 'Delight', 'Treble Place', 'Alliance'];
        $hasClassificationTerm = in_array($classification, $classificationTerms, true);
        $canInferFlags = isset($this->notation) || isset($this->notationExpanded);
        $jump = isset($this->jump) ? $this->jump : ($canInferFlags ? $this->getJump() : false);
        $differential = isset($this->differential) ? $this->differential : ($canInferFlags ? $this->getDifferential() : false);
        $little = isset($this->little) ? $this->little : ($canInferFlags ? $this->getLittle() : false);

        $descriptor = [];
        if ($jump) {
            $descriptor[] = 'Jump';
        }
        if ($differential) {
            $descriptor[] = 'Differential';
        }
        if ($little && $hasClassificationTerm) {
            $descriptor[] = 'Little';
        }
        if ($hasClassificationTerm) {
            $descriptor[] = $classification;
        }

        return trim(implode(' ', $descriptor));
    }

    private $leadHeads;

    /**
     * Get all lead heads in the plain course.
     *
     * @return array<int, array<int, string>> Sequence of lead-head rows
     */
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

    public function addMethodSimilarity1(\Blueline\Entity\MethodSimilarity $methodSimilarity1)
    {
        $this->methodSimilarity1[] = $methodSimilarity1;
        return $this;
    }

    public function removeMethodSimilarity1(\Blueline\Entity\MethodSimilarity $methodSimilarity1)
    {
        $this->methodSimilarity1->removeElement($methodSimilarity1);
    }

    public function getMethodSimilarity1()
    {
        return $this->methodSimilarity1;
    }

    public function addMethodSimilarity2(\Blueline\Entity\MethodSimilarity $methodSimilarity2)
    {
        $this->methodSimilarity2[] = $methodSimilarity2;
        return $this;
    }

    public function removeMethodSimilarity2(\Blueline\Entity\MethodSimilarity $methodSimilarity2)
    {
        $this->methodSimilarity2->removeElement($methodSimilarity2);
    }

    public function getMethodSimilarity2()
    {
        return $this->methodSimilarity2;
    }
}
