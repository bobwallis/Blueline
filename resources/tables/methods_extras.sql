DROP TABLE IF EXISTS methods_extras;
CREATE TABLE IF NOT EXISTS methods_extras (
  method_title varchar(255) NOT NULL,
  calls text NOT NULL,
  ruleOffs text NOT NULL,
  PRIMARY KEY (method_title)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Stedman Triples', 'a:2:{s:3:"Bob";s:6:"5:6:-3";s:6:"Single";s:8:"567:6:-3";}', '6:-3');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Stedman Doubles', 'a:1:{s:6:"Single";s:6:"45:6:0";}', '6:-3');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Stedman Caters', 'a:2:{s:3:"Bob";s:6:"7:6:-3";s:6:"Single";s:8:"789:6:-3";}', '6:-3');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Stedman Cinques', 'a:2:{s:3:"Bob";s:6:"A:6:-3";s:6:"Single";s:8:"ABC:6:-3";}', '6:-3');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Stedman Sextuples', 'a:2:{s:3:"Bob";s:6:"E:6:-3";s:6:"Single";s:8:"ETA:6:-3";}', '6:-3');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Stedman Septuples', 'a:2:{s:3:"Bob";s:6:"A:6:-3";s:6:"Single";s:8:"ABC:6:-3";}', '6:-3');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Doubles', 'a:1:{s:6:"Single";s:8:"345:6:-2";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Triples', 'a:2:{s:3:"Bob";s:6:"5:6:-5";s:6:"Single";s:8:"567:6:-5";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Caters', 'a:2:{s:3:"Bob";s:6:"7:6:-5";s:6:"Single";s:8:"789:6:-5";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Cinques', 'a:2:{s:3:"Bob";s:6:"9:6:-5";s:6:"Single";s:8:"90E:6:-5";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Sextuples', 'a:2:{s:3:"Bob";s:6:"E:6:-5";s:6:"Single";s:8:"ETA:6:-5";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Septuples', 'a:2:{s:3:"Bob";s:6:"A:6:-5";s:6:"Single";s:8:"ABC:6:-5";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Octuples', 'a:2:{s:3:"Bob";s:6:"C:6:-5";s:6:"Single";s:8:"CDF:6:-5";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Double Norwich Court Bob Caters', 'a:2:{s:3:"Bob";s:4:"3::4";s:6:"Single";s:6:"123::4";}', '');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Original Doubles', 'a:2:{s:3:"Bob";s:5:"3::-1";s:6:"Single";s:7:"345::-1";}', '');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Original Triples', 'a:2:{s:3:"Bob";s:5:"5::-1";s:6:"Single";s:7:"567::-1";}', '');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Original Caters', 'a:2:{s:3:"Bob";s:5:"7::-1";s:6:"Single";s:7:"789::-1";}', '');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Original Cinques', 'a:2:{s:3:"Bob";s:5:"9::-1";s:6:"Single";s:7:"90E::-1";}', '');
