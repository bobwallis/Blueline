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
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Stedman Cinques', 'a:2:{s:3:"Bob";s:6:"9:6:-3";s:6:"Single";s:8:"90E:6:-3";}', '6:-3');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Doubles', 'a:1:{s:6:"Single";s:8:"345:6:-2";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Triples', 'a:2:{s:3:"Bob";s:6:"5:6:-5";s:6:"Single";s:8:"567:6:-5";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Caters', 'a:2:{s:3:"Bob";s:6:"7:6:-5";s:6:"Single";s:8:"789:6:-5";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Cinques', 'a:2:{s:3:"Bob";s:6:"9:6:-5";s:6:"Single";s:8:"90E:6:-5";}', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Sextuples', '', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Septuples', '', '6:-5');
INSERT INTO methods_extras (method_title, calls, ruleOffs) VALUES('Erin Octuples', '', '6:-5');
