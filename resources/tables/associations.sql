SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

DROP TABLE IF EXISTS `associations`;
CREATE TABLE IF NOT EXISTS `associations` (
  `abbreviation` varchar(8) NOT NULL COMMENT 'The abbreviation used by Dove if possible',
  `name` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL COMMENT 'Web address of the Association''s site',
  PRIMARY KEY (`abbreviation`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('ANZAB', 'Australian and New Zealand Association', 'http://www.anzab.org.au/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('ASCY', 'Ancient Society of College Youths', 'http://www.ascy.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('B&W', 'Bath and Wells Diocesan Association', 'http://www.bath-wells.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Beds', 'Bedfordshire Association', 'http://www.bacr.co.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('CarDG', 'Carlisle Diocesan Guild', 'http://www.carlisle-dgcbr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('CheDG', 'Chester Diocesan Guild', 'http://www.chesterdg.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('CovDG', 'Coventry Diocesan Guild', 'http://www.coventrydg.co.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('D&N', 'Durham and Newcastle Diocesan Association', 'http://dandn.freehostia.com/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('DDA', 'Derby Diocesan Association', 'http://derbyda.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('DevAs', 'Devon Association', 'http://www.devonbells.co.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('EDWNA', 'East Derbyshire and West Nottinghamshire Association', '');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('EGDG', 'East Grinstead and District Guild', '');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Ely', 'Ely Diocesan Association', 'http://www.ely.anglican.org/bells/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Essex', 'Essex Association', 'http://www.eacr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('G&B', 'Gloucester and Bristol Diocesan Association', 'http://www.bellsgandb.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('GDG', 'Guildford Diocesan Guild', 'http://www.guildfordguild.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('GDR', 'Guild of Devonshire Ringers', 'http://groups.exeter.ac.uk/gdr/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('HCA', 'Hertford County Association', 'http://www.hcacr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('HDG', 'Hereford Diocesan Guild', 'http://www.hdgb.org/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Irish', 'Irish Association', 'http://www.bellringingireland.org/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('KCA', 'Kent County Association', 'http://www.kcacr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('L&M', 'Llandaff and Monmouth Diocesan Association', 'http://www.llanmon.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Lancs', 'Lancashire Association', 'http://www.lacr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('LeiDG', 'Leicester Diocesan Guild', 'http://www.leicesterdg.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('LinDG', 'Lincoln Diocesan Guild', 'http://ldgcb.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('LivUS', 'Liverpool Universities Society', 'http://luscr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Lundy', 'Lundy Island Society', 'http://www.btinternet.com/~bob.caton/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('LWAS', 'Lichfield and Walsall Archdeaconries Society', 'http://www.lwascr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Middx', 'Middlesex County Association', 'http://www.mcaldg.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('MUG', 'Manchester University Guild', 'http://www.mugcr.org/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('NAG', 'North American Guild', 'http://www.nagcr.org/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('NDA', 'Norwich Diocesan Association', 'http://www.norwich-diocese-ringers.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('NSA', 'North Staffordshire Association', 'http://www.nsacr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('NWA', 'North Wales Asssociation', 'http://www.northwalesbellringers.org/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('ODG', 'Oxford Diocesan Guild', 'http://www.odg.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('OS', 'Oxford Society', 'http://www.oxfordsociety.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('OUS', 'Oxford University Society', 'http://www.ouscr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('PDG', 'Peterborough Diocesan Guild', 'http://www.pdg.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('S&B', 'Swansea and Brecon Diocesan Guild', 'http://myweb.tiscali.co.uk/pckmj/sbdg/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('SAG', 'South African Guild', 'http://www.scifac.ru.ac.za/cathedral/bellguild.htm');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Salis', 'Salisbury Diocesan Guild', 'http://www.sdgr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Salop', 'Shropshire Association', 'http://www.sacbr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Scot', 'Scottish Association', 'http://www.sacr.org/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('SDDG', 'St David''s Diocesan Guild', 'http://www.rdasouthwales.org.uk/stdavidsringing/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('SMB', 'St Martin''s Guild', 'http://smgcbr.heralded.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('SRCY', 'Society of Royal	Cumberland Youths', 'http://www.srcy.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Suff', 'Suffolk Guild', 'http://www.suffolkbells.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Surr', 'Surrey Association', 'http://www.surreybellringers.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('SuxCA', 'Sussex County Association', 'http://www.scacr.org/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Swell', 'Southwell and Nottingham Diocesan Guild', 'http://www.southwelldg.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Trans', 'Transvaal Society', '');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('TruDG', 'Truro Diocesan Guild', 'http://www.tdgr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('UBSCR', 'University of Bristol Society', 'http://www.bris.ac.uk/Depts/Union/UBSCR/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('UL', 'University of London Society', 'http://www.ulscr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('W&P', 'Winchester and Portsmouth Diocesan Guild', 'http://www.wp-ringers.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('WDA', 'Worcestershire and Districts Change Ringing Association', 'http://www.wdcra.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('YACR', 'Yorkshire Association', 'http://www.yacr.org.uk/');
INSERT INTO `associations` (`abbreviation`, `name`, `link`) VALUES('Zimb', 'Zimbabwe Guild', '');
