SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

DROP TABLE IF EXISTS `methods_extras`;
CREATE TABLE IF NOT EXISTS `methods_extras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `method_title` varchar(255) NOT NULL,
  `calls` text NOT NULL,
  `ruleOffs` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `method_title` (`method_title`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(1, 'Double Norwich Court Bob Caters', 'a:2:{s:3:"Bob";s:4:"3::4";s:6:"Single";s:6:"123::4";}', '');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(2, 'Erin Caters', 'a:2:{s:3:"Bob";s:6:"7:6:-5";s:6:"Single";s:8:"789:6:-5";}', '6:-5');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(3, 'Erin Cinques', 'a:2:{s:3:"Bob";s:6:"9:6:-5";s:6:"Single";s:8:"90E:6:-5";}', '6:-5');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(4, 'Erin Doubles', 'a:1:{s:6:"Single";s:8:"345:6:-2";}', '6:-5');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(5, 'Erin Octuples', 'a:2:{s:3:"Bob";s:6:"C:6:-5";s:6:"Single";s:8:"CDF:6:-5";}', '6:-5');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(6, 'Erin Septuples', 'a:2:{s:3:"Bob";s:6:"A:6:-5";s:6:"Single";s:8:"ABC:6:-5";}', '6:-5');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(7, 'Erin Sextuples', 'a:2:{s:3:"Bob";s:6:"E:6:-5";s:6:"Single";s:8:"ETA:6:-5";}', '6:-5');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(8, 'Erin Triples', 'a:2:{s:3:"Bob";s:6:"5:6:-5";s:6:"Single";s:8:"567:6:-5";}', '6:-5');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(9, 'Original Caters', 'a:2:{s:3:"Bob";s:5:"7::-1";s:6:"Single";s:7:"789::-1";}', '');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(10, 'Original Cinques', 'a:2:{s:3:"Bob";s:5:"9::-1";s:6:"Single";s:7:"90E::-1";}', '');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(11, 'Original Doubles', 'a:2:{s:3:"Bob";s:5:"3::-1";s:6:"Single";s:7:"345::-1";}', '');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(12, 'Original Triples', 'a:2:{s:3:"Bob";s:5:"5::-1";s:6:"Single";s:7:"567::-1";}', '');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(13, 'Stedman Caters', 'a:2:{s:3:"Bob";s:6:"7:6:-3";s:6:"Single";s:8:"789:6:-3";}', '6:-3');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(14, 'Stedman Cinques', 'a:2:{s:3:"Bob";s:6:"A:6:-3";s:6:"Single";s:8:"ABC:6:-3";}', '6:-3');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(15, 'Stedman Doubles', 'a:1:{s:6:"Single";s:6:"45:6:0";}', '6:-3');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(16, 'Stedman Septuples', 'a:2:{s:3:"Bob";s:6:"A:6:-3";s:6:"Single";s:8:"ABC:6:-3";}', '6:-3');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(17, 'Stedman Sextuples', 'a:2:{s:3:"Bob";s:6:"E:6:-3";s:6:"Single";s:8:"ETA:6:-3";}', '6:-3');
INSERT INTO `methods_extras` (`id`, `method_title`, `calls`, `ruleOffs`) VALUES(18, 'Stedman Triples', 'a:2:{s:3:"Bob";s:6:"5:6:-3";s:6:"Single";s:8:"567:6:-3";}', '6:-3');


ALTER TABLE `methods_extras`
  ADD CONSTRAINT `methods_extras_ibfk_1` FOREIGN KEY (`method_title`) REFERENCES `methods` (`title`);
