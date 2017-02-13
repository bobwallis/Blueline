<?php
// Standard calls and rule offs for various methods which aren't in line with what would get generated by default
$method_extras_calls = array(
  array(
    'title' => 'Antelope Place Doubles',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '145.3.1',   'every' => 10, 'from' => -2, 'cover' => 3),
      'Single' => array('symbol' => 's', 'notation' => '145.3.123', 'every' => 10, 'from' => -2, 'cover' => 3),
    ),
    'ruleoffs' => array('every' => 10, 'from' => 1),
  ),
  array(
    'title' => 'Banana Doubles',
    'calls' => array(
      'Lead-End Bob'  => array('symbol' => '-', 'notation' => '125', 'every' => 8, 'from' => 0, 'cover' => 1),
      'Half-Lead Bob' => array('symbol' => 'h', 'notation' => '145', 'every' => 8, 'from' => -4, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 8, 'from' => 0),
  ),
  array(
    'title' => 'Double Norwich Court Bob Caters',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '3',   'every' => 18, 'from' => 4, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '123', 'every' => 18, 'from' => 4, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 18, 'from' => 0),
  ),
  array(
    'title' => 'Erin Caters',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '7',   'every' => 6, 'from' => -5, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '789', 'every' => 6, 'from' => -5, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -5),
  ),
  array(
    'title' => 'Erin Cinques',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '9',   'every' => 6, 'from' => -5, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '90E', 'every' => 6, 'from' => -5, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -5),
  ),
  array(
    'title' => 'Erin Doubles',
    'calls' => array(
      'Single' => array('symbol' => 's', 'notation' => '345', 'every' => 6, 'from' => -2, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -5),
  ),
  array(
    'title' => 'Erin Octuples',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => 'C',   'every' => 6, 'from' => -5, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => 'CDF', 'every' => 6, 'from' => -5, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -5),
  ),
  array(
    'title' => 'Erin Septuples',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => 'A',   'every' => 6, 'from' => -5, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => 'ABC', 'every' => 6, 'from' => -5, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -5),
  ),
  array(
    'title' => 'Erin Sextuples',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => 'E',   'every' => 6, 'from' => -5, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => 'ETA', 'every' => 6, 'from' => -5, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -5),
  ),
  array(
    'title' => 'Erin Triples',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '5',   'every' => 6, 'from' => -5, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '567', 'every' => 6, 'from' => -5, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -5),
  ),
  array(
    'title' => 'Original Caters',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '7',   'every' => 2, 'from' => -1, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '789', 'every' => 2, 'from' => -1, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 2, 'from' => 0),
  ),
  array(
    'title' => 'Original Cinques',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '9',   'every' => 2, 'from' => -1, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '90E', 'every' => 2, 'from' => -1, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 2, 'from' => 0),
  ),
  array(
    'title' => 'Original Doubles',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '3',   'every' => 2, 'from' => -1, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '345', 'every' => 2, 'from' => -1, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 2, 'from' => 0),
  ),
  array(
    'title' => 'Original Triples',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '5',   'every' => 2, 'from' => -1, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '567', 'every' => 2, 'from' => -1, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 2, 'from' => 0),
  ),
  array(
    'title' => 'Scientific Triples',
    'calls' => array(
      'Red'   => array('symbol' => '-', 'notation' => '3.7.3', 'every' => 30, 'from' => 6,  'cover' => 3),
      'Blue'  => array('symbol' => '-', 'notation' => '3.7.3', 'every' => 30, 'from' => 15, 'cover' => 3),
      'Green' => array('symbol' => '-', 'notation' => '3.7.3', 'every' => 30, 'from' => 23, 'cover' => 3),
    ),
    'ruleoffs' => array('every' => 30, 'from' => 0),
  ),
  array(
    'title' => 'Shipway Minor',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '34',   'every' => 8, 'from' => 2, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '3456', 'every' => 8, 'from' => 2, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 8, 'from' => 2),
  ),
  array(
    'title' => 'Shipway Major',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '36',   'every' => 8, 'from' => 2, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '3678', 'every' => 8, 'from' => 2, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 8, 'from' => 2),
  ),
  array(
    'title' => 'Shipway Royal',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '38',   'every' => 8, 'from' => 2, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '3890', 'every' => 8, 'from' => 2, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 8, 'from' => 2),
  ),
  array(
    'title' => 'Stedman Caters',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '7',   'every' => 6, 'from' => -3, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '789', 'every' => 6, 'from' => -3, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -3),
  ),
  array(
    'title' => 'Stedman Cinques',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '9',   'every' => 6, 'from' => -3, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '90E', 'every' => 6, 'from' => -3, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -3),
  ),
  array(
    'title' => 'Stedman Doubles',
    'calls' => array(
      'Single' => array('symbol' => 's', 'notation' => '45', 'every' => 6, 'from' => 0, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -3),
  ),
  array(
    'title' => 'Stedman Septuples',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => 'A',   'every' => 6, 'from' => -3, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => 'ABC', 'every' => 6, 'from' => -3, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -3),
  ),
  array(
    'title' => 'Stedman Sextuples',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => 'E',   'every' => 6, 'from' => -3, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => 'ETA', 'every' => 6, 'from' => -3, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -3),
  ),
  array(
    'title' => 'Stedman Triples',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '5',   'every' => 6, 'from' => -3, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '567', 'every' => 6, 'from' => -3, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 6, 'from' => -3),
  ),
  array(
    'title' => 'Titanic Cinques',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '9',   'every' => 2, 'from' => -1, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '90E', 'every' => 2, 'from' => -1, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 2, 'from' => -1),
  ),
  array(
    'title' => 'Titanic Doubles',
    'calls' => array(),
    'ruleoffs' => array('every' => 2, 'from' => -1),
  ),
  array(
    'title' => 'Titanic Triples',
    'calls' => array(
      'Bob'    => array('symbol' => '-', 'notation' => '5',   'every' => 2, 'from' => -1, 'cover' => 1),
      'Single' => array('symbol' => 's', 'notation' => '567', 'every' => 2, 'from' => -1, 'cover' => 1),
    ),
    'ruleoffs' => array('every' => 2, 'from' => -1),
  ),
);