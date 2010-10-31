DROP TABLE IF EXISTS method_extras;
CREATE TABLE IF NOT EXISTS method_extras (
  method_title varchar(255) NOT NULL,
  calls text NOT NULL,
  ruleOffs text NOT NULL,
  PRIMARY KEY (method_title)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Stedman Triples', 'stedman calls', '6:-3');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Stedman Doubles', '', '6:-3');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Stedman Caters', '', '6:-3');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Stedman Cinques', '', '6:-3');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Erin Doubles', '', '6:-5');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Erin Triples', '', '6:-5');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Erin Caters', '', '6:-5');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Erin Cinques', '', '6:-5');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Erin Sextuples', '', '6:-5');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Erin Septuples', '', '6:-5');
INSERT INTO method_extras (method_title, calls, ruleOffs) VALUES('Erin Octuples', '', '6:-5');
