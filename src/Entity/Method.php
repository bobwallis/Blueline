<?php

namespace Blueline\Entity;

use Blueline\Helpers\LeadHeadCodes;
use Blueline\Helpers\PlaceNotation;
use Blueline\Helpers\Stages;
use Blueline\Helpers\Text;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

require_once __DIR__.'/../Helpers/arrays_equal_in_some_rotation.php';
use function Blueline\Helpers\arrays_equal_in_some_rotation;

/**
 * Method entity.
 */
#[UniqueEntity('url')]
#[ORM\Entity(repositoryClass: \Blueline\Repository\MethodRepository::class)]
#[ORM\Table(name: 'methods')]
#[ORM\Index(name: 'idx_methods_stage', columns: ['stage'])]
#[ORM\Index(name: 'idx_methods_stage_lengthoflead', columns: ['stage', 'lengthoflead'])]
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
    public function __construct($firstSet = [])
    {
        $this->collections = new \Doctrine\Common\Collections\ArrayCollection();
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
     *
     * @return array<string, mixed>
     */
    public function __toArray($fields = null)
    {
        if (is_string($fields)) {
            $fields = array_filter(array_map('trim', explode(',', $fields)));
        }

        $fieldSerialisers = [
            'title' => fn () => $this->getTitle(),
            'abbreviation' => fn () => $this->getAbbreviation(),
            'provisional' => fn () => $this->getProvisional(),
            'stage' => fn () => $this->getStage(),
            'classification' => fn () => $this->getClassification(),
            'nameMetaphone' => fn () => $this->getNameMetaphone(),
            'notation' => fn () => $this->getNotation(),
            'notationExpanded' => fn () => $this->getNotationExpanded(),
            'leadHeadCode' => fn () => $this->getLeadHeadCode(),
            'leadHead' => fn () => $this->getLeadHead(),
            'fchGroups' => fn () => $this->getFchGroups(),
            'lengthOfLead' => fn () => $this->getLengthOfLead(),
            'lengthOfCourse' => fn () => $this->getLengthOfCourse(),
            'numberOfLeads' => fn () => $this->getNumberOfLeads(),
            'numberOfHunts' => fn () => $this->getNumberOfHunts(),
            'jump' => fn () => $this->getJump(),
            'little' => fn () => $this->getLittle(),
            'differential' => fn () => $this->getDifferential(),
            'plain' => fn () => $this->getPlain(),
            'trebleDodging' => fn () => $this->getTrebleDodging(),
            'palindromic' => fn () => $this->getPalindromic(),
            'doubleSym' => fn () => $this->getDoubleSym(),
            'rotational' => fn () => $this->getRotational(),
            'calls' => fn () => $this->getCalls(),
            'ruleOffs' => fn () => $this->getRuleOffs(),
            'callingPositions' => fn () => $this->getCallingPositions(),
            'magic' => fn () => $this->getMagic(),
            'cccbrId' => fn () => $this->getCccbrId(),
            'methodReferences' => fn () => $this->getMethodReferences(),
            'extensionConstruction' => fn () => $this->getExtensionConstruction(),
            'url' => fn () => $this->getUrl(),
        ];

        if (is_array($fields) && !empty($fields)) {
            $requestedFields = array_values(array_intersect($fields, array_keys($fieldSerialisers)));

            $objectVars = [];
            foreach ($requestedFields as $field) {
                $objectVars[$field] = $fieldSerialisers[$field]();
            }

            return $objectVars;
        }

        $objectVars = [];
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
     *
     * @return Method
     */
    public function setAll($map)
    {
        foreach ($map as $key => $value) {
            $method = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (is_callable([$this, $method])) {
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
            $this->setAbbreviation(substr(trim(str_replace([$this->getStageText(), $this->getClassification(), 'Differential', 'Little', 'Jump'], '', $this->getTitle())), 0, 2));
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
     * Get classification.
     *
     * @return string
     */
    public function getClassification()
    {
        if (!isset($this->classification)) {
            if ($this->getJump()) {
                $this->setClassification('Jump');
            } elseif ($this->getNumberOfHunts() > 0) {
                $principalHunts = array_values(array_filter($this->getHuntDetails(), function ($h) {
                    return $h['principal'];
                }));
                $principalHuntType = $principalHunts[0]['type'];

                // For Plain if all bells only make places and hunt then it's 'Place', otherwise 'Bob'
                if ('Plain' == $principalHuntType) {
                    $lead = $this->getLead();
                    array_unshift($lead, PlaceNotation::rounds($this->getStage()));
                    $leadPlusOneChangeFromNextLead = $lead;
                    // Include one extra change from the next lead to detect direction changes over the lead end.
                    array_push(
                        $leadPlusOneChangeFromNextLead,
                        PlaceNotation::apply(
                            PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded()))[0],
                            end($leadPlusOneChangeFromNextLead)
                        )
                    );

                    $allBellPathsArePlaceNotationOnly = array_reduce(array_map(function ($bell) use ($leadPlusOneChangeFromNextLead) {
                        $positions = array_map(function ($row) use ($bell) {
                            return array_search($bell, $row);
                        }, $leadPlusOneChangeFromNextLead);

                        $bellPlacesOnly = true;
                        for ($i = 2; $bellPlacesOnly && $i < count($positions); ++$i) {
                            // Allow only hunts and place-making: acceleration in place index must remain in {-1,0,1}.
                            $bellPlacesOnly = abs(($positions[$i] - $positions[$i - 1]) - ($positions[$i - 1] - $positions[$i - 2])) <= 1;
                        }

                        return $bellPlacesOnly;
                    }, PlaceNotation::rounds($this->getStage())), function ($carry, $val) {
                        return $carry && $val;
                    }, true);

                    $this->setClassification($allBellPathsArePlaceNotationOnly ? 'Place' : 'Bob');

                // For Treble Dodging see if internal places are made at each cross section. All => 'Surprise', None => 'Treble Bob', Some => 'Delight'
                } elseif ('Treble Dodging' == $principalHuntType) {
                    // If the hunt bell dodges in only one position it's Treble Bob (since there are no cross sections, and that edge case is defined Treble Bob)
                    if (array_reduce($principalHunts, function ($carry, $val) {
                        return $carry && 1 == max($val['path']) - min($val['path']);
                    }, true)) {
                        $this->setClassification('Treble Bob');
                    // Otherwise inspect cross sections
                    } else {
                        $lead = $this->getLead();
                        // Prefix rounds to the lead to detect cross sections at the start of the lead as well as the end.
                        array_unshift($lead, PlaceNotation::rounds($this->getStage()));

                        $crossSectionChangesByHunt = array_map(function ($hunt) use ($lead) {
                            $positions = array_map(function ($row) use ($hunt) {
                                return array_search($hunt['bell'], $row);
                            }, $lead);

                            $bellCrossSections = [];
                            for ($i = 1; $i + 2 < count($positions); ++$i) {
                                $lastChange = $positions[$i] - $positions[$i - 1];
                                $thisChange = $positions[$i + 1] - $positions[$i];
                                $nextChange = $positions[$i + 2] - $positions[$i + 1];
                                // A cross section is a three-change hunt in one direction between dodging positions.
                                if (1 == abs($thisChange) && $lastChange == $thisChange && $thisChange == $nextChange) {
                                    $bellCrossSections[] = $i;
                                }
                            }

                            return $bellCrossSections;
                        }, $principalHunts);
                        $crossSectionChanges = array_values(array_unique(array_reduce($crossSectionChangesByHunt, 'array_merge', [])));

                        $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
                        $internalPlacesAtCrossSectionChanges = array_map(function ($change) use ($notationExploded) {
                            return PlaceNotation::changeHasInternalPlaces($notationExploded[$change], $this->getStage());
                        }, $crossSectionChanges);
                        // If an internal place is made at every cross section it's 'Surprise'
                        if (array_reduce($internalPlacesAtCrossSectionChanges, function ($c, $v) {
                            return $c && $v;
                        }, true)) {
                            $this->setClassification('Surprise');
                        // If none then it's 'Treble Bob'
                        } elseif (array_reduce($internalPlacesAtCrossSectionChanges, function ($c, $v) {
                            return $c && !$v;
                        }, true)) {
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
            $postLeadEndNotation = reset($notationExploded);

            // Grandsire lead head codes (p/q families) are only valid when all hunt bells share the same path.
            // Check this before applying the code mapping.
            $huntDetails = $this->getHuntDetails();
            $allHuntPathsSame = true;
            if (count($huntDetails) > 1) {
                // Compare all hunt paths to the first hunt's path up to rotation,
                // since equivalent hunt loops can start at different positions.
                $firstHuntPath = $huntDetails[0]['path'];
                for ($i = 1; $i < count($huntDetails); ++$i) {
                    if (!arrays_equal_in_some_rotation($huntDetails[$i]['path'], $firstHuntPath)) {
                        $allHuntPathsSame = false;
                        break;
                    }
                }
            }
            if (!$allHuntPathsSame) {
                $this->setLeadHeadCode(PlaceNotation::trimExternalPlaces($leadHeadNotation, $this->getStage()).'z');
            } else {
                $this->setLeadHeadCode(LeadHeadCodes::toCode($this->getLeadHead(), $this->getStage(), $leadHeadNotation, $postLeadEndNotation));
            }
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
            $lead = $this->getLead();
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
            $permutation = array_map(function ($b) {
                return PlaceNotation::bellToInt($b) - 1;
            }, str_split($this->getLeadHead()));
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

    private $lead;

    /**
     * Get the rows of a plain lead.
     *
     * @return array<int, array<int, string>>
     */
    private function getLead()
    {
        if (!isset($this->lead)) {
            $this->lead = PlaceNotation::apply(
                PlaceNotation::explodedToPermutations($this->getStage(), PlaceNotation::explode($this->getNotationExpanded())),
                PlaceNotation::rounds($this->getStage())
            );
        }

        return $this->lead;
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
            $hunts = [];
            $leadHead = array_map(function ($n) {
                return PlaceNotation::bellToInt($n);
            }, str_split($this->getLeadHead()));
            for ($i = 0, $iLim = count($leadHead); $i < $iLim; ++$i) {
                if (($i + 1) == $leadHead[$i]) {
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
            $huntDetails = [];
            if ($this->getNumberOfHunts() > 0) {
                // Classify each of the hunt bells
                foreach ($this->getHunts() as $i => $hunt) {
                    $huntDetails[$i]['bell'] = $hunt;

                    // Build path metrics for this hunt bell over one lead.
                    $bellText = PlaceNotation::intToBell($hunt);
                    // Convert each row into the 1-based place occupied by this bell.
                    $path = array_map(function ($row) use ($bellText) {
                        return array_search($bellText, $row) + 1;
                    }, $this->getLead());
                    // Rotate into a closed loop representation for easier backwards-path comparisons.
                    array_unshift($path, array_pop($path));
                    $huntDetails[$i]['path'] = $path;

                    // Stationary hunts are treated as Treble Place under Framework v2.
                    $huntDetails[$i]['stationary'] = 1 === count(array_unique($huntDetails[$i]['path']));

                    // Framework v2 uses loop equality when rung backwards.
                    $huntDetails[$i]['wellFormedPath'] = arrays_equal_in_some_rotation($huntDetails[$i]['path'], array_reverse($huntDetails[$i]['path']));

                    // A path is little if it doesn't reach the front or back
                    $huntDetails[$i]['little'] = min($huntDetails[$i]['path']) > 1 || max($huntDetails[$i]['path']) < $this->getStage();

                    // Count the number of places in the hunt bell's path
                    $numberOfPlacesInPath = array_reduce($huntDetails[$i]['path'], function ($carry, $pos) {
                        $carry['places'] = ($carry['lastPos'] == $pos) ? $carry['places'] + 1 : $carry['places'];
                        $carry['lastPos'] = $pos;

                        return $carry;
                    }, ['lastPos' => end($huntDetails[$i]['path']), 'places' => 0])['places'];

                    // Count the number of blows the hunt bell makes in each position
                    $blowsInEachPosition = array_reduce($huntDetails[$i]['path'], function ($carry, $pos) {
                        if (isset($carry[$pos])) {
                            ++$carry[$pos];
                        } else {
                            $carry[$pos] = 1;
                        }
                        ksort($carry);

                        return $carry;
                    }, []);

                    // Check if the hunt bell makes the same number of blows in each position
                    $sameNumberOfBlowsInEachPosition = 1 == count(array_unique($blowsInEachPosition));
                    // When uniform, this is the repeated blow count per position (e.g. 2 for Plain).
                    $blowsPerPosition = $sameNumberOfBlowsInEachPosition ? reset($blowsInEachPosition) : null;

                    // Framework v2 classifies hunt paths by loop symmetry, place counts, and stationarity.
                    if ($huntDetails[$i]['wellFormedPath']) {
                        if (!$huntDetails[$i]['stationary'] && $sameNumberOfBlowsInEachPosition && 2 === $blowsPerPosition) {
                            $huntDetails[$i]['type'] = 'Plain';
                        } elseif (!$huntDetails[$i]['stationary'] && $sameNumberOfBlowsInEachPosition && $blowsPerPosition > 2 && 2 == $numberOfPlacesInPath) {
                            $huntDetails[$i]['type'] = 'Treble Dodging';
                        } elseif (($sameNumberOfBlowsInEachPosition && $numberOfPlacesInPath > 2) || $huntDetails[$i]['stationary']) {
                            $huntDetails[$i]['type'] = 'Treble Place';
                        } elseif (!$huntDetails[$i]['stationary'] && !$sameNumberOfBlowsInEachPosition) {
                            $huntDetails[$i]['type'] = 'Alliance';
                        } else {
                            $huntDetails[$i]['type'] = 'Hybrid';
                        }
                    // Non-well-formed paths are Hybrid
                    } else {
                        $huntDetails[$i]['type'] = 'Hybrid';
                    }
                }

                // Determine which are the principal hunts. If there's only one it is the principal hunt
                if (1 == count($huntDetails)) {
                    $huntDetails[0]['principal'] = true;
                // Otherwise there is a hierachy of hunt types defined in the rules (and just the non-little hunts of that type if there are both little and non-little examples)
                } else {
                    foreach (['Plain', 'Treble Dodging', 'Treble Place', 'Alliance', 'Hybrid'] as $type) {
                        $numberOfHuntsOfType = count(array_filter($huntDetails, function ($h) use ($type) {
                            return $h['type'] == $type;
                        }));
                        $numberOfHuntsOfTypeWhichAreLittle = count(array_filter($huntDetails, function ($h) use ($type) {
                            return $h['type'] == $type && $h['little'];
                        }));
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
            $this->setJump(false !== strpos($this->getNotationExpanded(), '(') || false !== strpos($this->getNotationExpanded(), '['));
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
            $this->setLittle(count(array_filter($this->getHuntDetails(), function ($h) {
                return $h['principal'] && $h['little'];
            })) > 0);
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
            $leadHead = array_map(function ($n) {
                return PlaceNotation::bellToInt($n);
            }, str_split($this->getLeadHead()));
            $visited = [];
            $workingBellCycleLengths = [];

            for ($bell = 1; $bell <= $this->getStage(); ++$bell) {
                if (isset($visited[$bell])) {
                    continue;
                }
                $current = $bell;
                $cycleLength = 0;
                // Walk the lead-head permutation to find this bell's cycle length in leads.
                do {
                    $visited[$current] = true;
                    $current = $leadHead[$current - 1];
                    ++$cycleLength;
                } while (!isset($visited[$current]));
                // Single-bell cycles are hunt bells; only working-bell cycle lengths matter for this test.
                if ($cycleLength > 1) {
                    $workingBellCycleLengths[] = $cycleLength;
                }
            }
            // If there are working bells with different cycle lengths, the method is differential.
            $this->setDifferential(!$this->getJump() && count(array_unique($workingBellCycleLengths)) > 1);
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
            $this->setPlain(!$this->getJump() && count(array_filter($this->getHuntDetails(), function ($h) {
                return $h['principal'] && 'Plain' == $h['type'];
            })) > 0);
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
            $this->setTrebleDodging(!$this->getJump() && count(array_filter($this->getHuntDetails(), function ($h) {
                return $h['principal'] && 'Treble Dodging' == $h['type'];
            })) > 0);
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
                if ('x' == $e) {
                    return $e;
                }
                $eExplode = str_split($e);
                for ($i = 0; $i < count($eExplode); ++$i) {
                    $eExplode[$i] = PlaceNotation::intToBell($stage + 1 - PlaceNotation::bellToInt($eExplode[$i]));
                }

                return implode('', array_reverse($eExplode));
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
        return ucfirst(Text::toList(array_filter([$this->getPalindromic() ? 'palindromic' : '', $this->getDoubleSym() ? 'double' : '', $this->getRotational() ? 'rotational' : ''])));
    }

    public function setCalls($calls)
    {
        $this->calls = $calls;

        return $this;
    }

    /**
     * Get calls.
     *
     * @return array
     */
    public function getCalls()
    {
        // Set default calls
        if (!isset($this->calls)) {
            $stage = $this->getStage();
            $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
            $calls = [];
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
                        if (0 == $stage % 2) {
                            if ($leadEndChange == '1'.$n) {
                                $calls = [
                                    'Bob' => ['symbol' => '-', 'notation' => '1'.$n_2, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                    'Single' => ['symbol' => 's', 'notation' => '1'.$n_2.$n_1.$n, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                ];
                            }
                        }
                        break;
                    case 1:
                        if (0 == $stage % 2) {
                            if ('12' == $leadEndChange) {
                                $calls = [
                                    'Bob' => ['symbol' => '-', 'notation' => '14', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                    'Single' => ['symbol' => 's', 'notation' => '1234', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                ];
                            } elseif ($leadEndChange == '1'.$n) {
                                if ('m' == $this->getLeadHeadCode() && $stage > 6) {
                                    $calls = [
                                        'Bob' => ['symbol' => '-', 'notation' => '14', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                        'Single' => ['symbol' => 's', 'notation' => '1234', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                    ];
                                } else {
                                    $calls = [
                                        'Bob' => ['symbol' => '-', 'notation' => '1'.$n_2, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                        'Single' => ['symbol' => 's', 'notation' => '1'.$n_2.$n_1.$n, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                    ];
                                }
                            } elseif ('14' == $leadEndChange && 6 == $stage) {
                                $calls = [
                                    'Bob' => ['symbol' => '-', 'notation' => '16', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                    'Single' => ['symbol' => 's', 'notation' => '156', 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                ];
                            }
                        } else {
                            if ($leadEndChange == '12'.$n || '1' == $leadEndChange) {
                                $calls = [
                                    'Bob' => ['symbol' => '-', 'notation' => '14'.$n, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                    'Single' => ['symbol' => 's', 'notation' => (($stage < 6) ? '123' : '1234'.$n), 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                ];
                            } elseif ('123' == $leadEndChange) {
                                $calls = [
                                    'Bob' => ['symbol' => '-', 'notation' => '12'.$n, 'from' => 0, 'every' => $lengthOfLead, 'cover' => 1],
                                ];
                            }
                        }
                        break;
                    case 2:
                        // Bobs and singles for Grandsire and Single Court like lead ends
                        if (0 == $stage % 2) {
                            if ($leadEndChange == '1'.$n && $postLeadEndChange == '3'.$n) {
                                $calls = [
                                    'Bob' => ['symbol' => '-', 'notation' => '3'.$n.'.1'.$n, 'from' => -1, 'every' => $lengthOfLead, 'cover' => 2],
                                    'Single' => ['symbol' => 's', 'notation' => '3'.$n.'.123'.$n, 'from' => -1, 'every' => $lengthOfLead, 'cover' => 2],
                                ];
                            }
                        } else {
                            if ('1' == $leadEndChange && ('3' == $postLeadEndChange || $postLeadEndChange == $n)) {
                                $calls = [
                                    'Bob' => ['symbol' => '-', 'notation' => '3.1', 'from' => -1, 'every' => $lengthOfLead, 'cover' => 2],
                                    'Single' => ['symbol' => 's', 'notation' => '3.123', 'from' => -1, 'every' => $lengthOfLead, 'cover' => 2],
                                ];
                            }
                        }
                        break;
                    default:
                        $calls = [];
                }
            }
            $this->calls = $calls;
        }

        return $this->calls ?: [];
    }

    public function setRuleOffs($ruleOffs)
    {
        $this->ruleOffs = $ruleOffs;

        return $this;
    }

    /**
     * Get ruleOffs.
     *
     * @return array
     */
    public function getRuleOffs()
    {
        if (empty($this->ruleOffs)) {
            // Check for methods similar to Grandsire and offset the rule off by one. TODO: Check that the hunt bells are actually hunting as well as leading one after the other near the lead end. (Hereford D G Bob Doubles is an example false positive)
            if (2 == $this->getNumberOfHunts()) {
                $hunts = $this->getHunts();
                $notationExploded = PlaceNotation::explode($this->getNotationExpanded());
                $leadEndChange = array_pop($notationExploded);
                array_shift($notationExploded);
                $postLeadEndChange2 = array_shift($notationExploded);
                if (1 == $hunts[0] && 2 == $hunts[1] && '1' == $leadEndChange[0] && (1 == strlen($leadEndChange) || '2' != $leadEndChange[1]) && '1' == $postLeadEndChange2[0]) {
                    $this->ruleOffs = ['every' => $this->getLengthOfLead(), 'from' => 1];
                }
            }
            // Otherwise assume this...
            $this->ruleOffs = ['every' => $this->getLengthOfLead(), 'from' => 0];
        }

        return $this->ruleOffs;
    }

    public function setCallingPositions($callingPositions)
    {
        $this->callingPositions = $callingPositions;

        return $this;
    }

    /**
     * Get callingPositions.
     *
     * @return array
     */
    public function getCallingPositions()
    {
        if (empty($this->callingPositions) && !empty($this->getCalls())) {
            $stage = $this->getStage();
            $calls = $this->getCalls();
            $lengthOfLead = $this->getLengthOfLead();
            if ($stage > 4 && !empty($calls) && isset($calls['Bob']) && $calls['Bob']['every'] == $lengthOfLead && 0 == $calls['Bob']['from'] && 1 == $calls['Bob']['cover']) {
                // Calling positions for calls at lead ends (Home, Wrong and so forth)
                $bobNotation = PlaceNotation::explodedToPermutations($stage, PlaceNotation::explode($calls['Bob']['notation']));
                $leadHeads = $this->getLeadHeads();
                // Work out what the lead end of a bobbed lead looks like
                $notation = PlaceNotation::explodedToPermutations($stage, PlaceNotation::explode($this->getNotationExpanded()));
                $notation[$lengthOfLead - 1] = $bobNotation[0];
                $bobbedLead = PlaceNotation::apply($notation, PlaceNotation::rounds($stage));
                $bobbedLeadHeadPermutation = array_map(function ($b) {
                    return PlaceNotation::bellToInt($b) - 1;
                }, array_pop($bobbedLead));
                // Collect an array of what happens at each lead if a bob is called
                $bobbedLeadHeads = [PlaceNotation::permute(PlaceNotation::rounds($stage), $bobbedLeadHeadPermutation)];
                for ($i = 1; $i < count($leadHeads); ++$i) {
                    array_push($bobbedLeadHeads, PlaceNotation::permute($leadHeads[$i - 1], $bobbedLeadHeadPermutation));
                }
                // Convert the array of lead heads into calling position names
                $this->callingPositions = ['from' => 0, 'every' => $lengthOfLead, 'titles' => array_map(function ($leadEnd) use ($stage) {
                    $position = array_search(PlaceNotation::intToBell($stage), $leadEnd);
                    switch ($position + 1) {
                        case $stage:
                            return 'H';
                        case $stage - 1:
                            if (0 == $stage % 2) {
                                return 'W';
                            }

                            return 'M';
                        case 2:
                            return 'I';
                        case 3:
                            return 'B';
                        case 4:
                            return 'F';
                        case $stage - 2:
                            if (0 == $stage % 2) {
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
                }, $bobbedLeadHeads)];
            } else {
                $this->callingPositions = [];
            }
        }

        return $this->callingPositions ?: [];
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
            $this->setUrl(str_replace([' ', '$', '&', '+', ',', '/', ':', ';', '=', '?', '@', '"', "'", '<', '>', '#', '%', '{', '}', '|', '\\', '^', '~', '[', ']', '.'], ['_'], iconv('UTF-8', 'ASCII//TRANSLIT', $this->getTitle())));
        }

        return $this->url;
    }

    public function addCollection(MethodInCollection $collection)
    {
        $this->collections[] = $collection;

        return $this;
    }

    public function removeCollection(MethodInCollection $collection)
    {
        $this->collections->removeElement($collection);
    }

    public function getCollections()
    {
        return $this->collections;
    }

    public function addPerformance(Performance $performance)
    {
        $this->performances[] = $performance;

        return $this;
    }

    public function removePerformance(Performance $performance)
    {
        $this->performances->removeElement($performance);
    }

    public function getPerformances()
    {
        return $this->performances;
    }

    public function getRenamed()
    {
        return $this->getPerformances()->filter(function ($p) {
            return 'renamedMethod' == $p->getType();
        });
    }

    public function getDuplicates()
    {
        return $this->getPerformances()->filter(function ($p) {
            return 'duplicateMethod' == $p->getType();
        });
    }

    public function getFirstTowerbellPeal()
    {
        return $this->getPerformances()->filter(function ($p) {
            return 'firstTowerbellPeal' == $p->getType();
        })->get(0);
    }

    public function getFirstHandbellPeal()
    {
        return $this->getPerformances()->filter(function ($p) {
            return 'firstHandbellPeal' == $p->getType();
        })->get(0);
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
            $leadHeadPermutation = array_map(function ($b) {
                return PlaceNotation::bellToInt($b) - 1;
            }, $tmp);
            $leadHeads = [$tmp];
            while (!PlaceNotation::rowsEqual($rounds, $tmp)) {
                $tmp = PlaceNotation::permute($tmp, $leadHeadPermutation);
                array_push($leadHeads, $tmp);
            }
            $this->leadHeads = $leadHeads;
        }

        return $this->leadHeads;
    }

    public function addMethodSimilarity1(MethodSimilarity $methodSimilarity1)
    {
        $this->methodSimilarity1[] = $methodSimilarity1;

        return $this;
    }

    public function removeMethodSimilarity1(MethodSimilarity $methodSimilarity1)
    {
        $this->methodSimilarity1->removeElement($methodSimilarity1);
    }

    public function getMethodSimilarity1()
    {
        return $this->methodSimilarity1;
    }

    public function addMethodSimilarity2(MethodSimilarity $methodSimilarity2)
    {
        $this->methodSimilarity2[] = $methodSimilarity2;

        return $this;
    }

    public function removeMethodSimilarity2(MethodSimilarity $methodSimilarity2)
    {
        $this->methodSimilarity2->removeElement($methodSimilarity2);
    }

    public function getMethodSimilarity2()
    {
        return $this->methodSimilarity2;
    }
}
