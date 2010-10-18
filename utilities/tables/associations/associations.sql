SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

DROP TABLE IF EXISTS `associations`;
CREATE TABLE IF NOT EXISTS `associations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `abbreviation` varchar(8) NOT NULL COMMENT 'The abbreviation used by Dove if possible',
  `name` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL COMMENT 'Web address of the Association''s site',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `abbreviation` (`abbreviation`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=60 ;

INSERT INTO `associations` VALUES(1, 'ASCY', 'Ancient Society of College Youths', 'http://www.ascy.org.uk/');
INSERT INTO `associations` VALUES(2, 'B&W', 'Bath and Wells Diocesan Association', 'http://www.bath-wells.org.uk/');
INSERT INTO `associations` VALUES(3, 'CarDG', 'Carlisle Diocesan Guild', 'http://www.carlisle-dgcbr.org.uk/');
INSERT INTO `associations` VALUES(4, 'CovDG', 'Coventry Diocesan Guild', 'http://www.coventrydg.co.uk/');
INSERT INTO `associations` VALUES(5, 'Ely', 'Ely Diocesan Association', 'http://www.ely.anglican.org/bells/');
INSERT INTO `associations` VALUES(6, 'G&B', 'Gloucester and Bristol Diocesan Association', 'http://www.bellsgandb.org.uk/');
INSERT INTO `associations` VALUES(7, 'HDG', 'Hereford Diocesan Guild', 'http://www.hdgb.org/');
INSERT INTO `associations` VALUES(8, 'KCA', 'Kent County Association', 'http://www.kcacr.org.uk/');
INSERT INTO `associations` VALUES(9, 'LinDG', 'Lincoln Diocesan Guild', 'http://ldgcb.org.uk/');
INSERT INTO `associations` VALUES(10, 'L&M', 'Llandaff and Monmouth Diocesan Association', 'http://www.llanmon.org.uk/');
INSERT INTO `associations` VALUES(11, 'NSA', 'North Staffordshire Association', 'http://www.nsacr.org.uk/');
INSERT INTO `associations` VALUES(12, 'ODG', 'Oxford Diocesan Guild', 'http://www.odg.org.uk/');
INSERT INTO `associations` VALUES(13, 'OS', 'Oxford Society', 'http://www.oxfordsociety.org.uk/');
INSERT INTO `associations` VALUES(14, 'OUS', 'Oxford University Society', 'http://www.ouscr.org.uk/');
INSERT INTO `associations` VALUES(15, 'PDG', 'Peterborough Diocesan Guild', 'http://www.pdg.org.uk/');
INSERT INTO `associations` VALUES(16, 'YACR', 'Yorkshire Association', 'http://www.yacr.org.uk/');
INSERT INTO `associations` VALUES(17, 'ANZAB', 'Australian and New Zealand Association', 'http://www.anzab.org.au/');
INSERT INTO `associations` VALUES(18, 'Beds', 'Bedfordshire Association', 'http://www.bacr.co.uk/');
INSERT INTO `associations` VALUES(19, 'CheDG', 'Chester Diocesan Guild', 'http://www.chesterdg.org.uk/');
INSERT INTO `associations` VALUES(20, 'D&N', 'Durham and Newcastle Diocesan Association', 'http://dandn.freehostia.com/');
INSERT INTO `associations` VALUES(21, 'DDA', 'Derby Diocesan Association', 'http://derbyda.org.uk/');
INSERT INTO `associations` VALUES(22, 'DevAs', 'Devon Association', 'http://www.devonbells.co.uk/');
INSERT INTO `associations` VALUES(23, 'EDWNA', 'East Derbyshire and West Nottinghamshire Association', '');
INSERT INTO `associations` VALUES(24, 'EGDG', 'East Grinstead and District Guild', '');
INSERT INTO `associations` VALUES(25, 'Essex', 'Essex Association', 'http://www.eacr.org.uk/');
INSERT INTO `associations` VALUES(26, 'GDG', 'Guildford Diocesan Guild', 'http://www.guildfordguild.org.uk/');
INSERT INTO `associations` VALUES(27, 'GDR', 'Guild of Devonshire Ringers', 'http://groups.exeter.ac.uk/gdr/');
INSERT INTO `associations` VALUES(28, 'HCA', 'Hertford County Association', 'http://www.hcacr.org.uk/');
INSERT INTO `associations` VALUES(29, 'Irish', 'Irish Association', 'http://www.bellringingireland.org/');
INSERT INTO `associations` VALUES(30, 'Lancs', 'Lancashire Association', 'http://www.lacr.org.uk/');
INSERT INTO `associations` VALUES(31, 'LeiDG', 'Leicester Diocesan Guild', 'http://www.leicesterdg.org.uk/');
INSERT INTO `associations` VALUES(32, 'LivUS', 'Liverpool Universities Society', 'http://www.luscr.org.uk/liv_index.html');
INSERT INTO `associations` VALUES(33, 'Lundy', 'Lundy Island Society', 'http://www.btinternet.com/~bob.caton/');
INSERT INTO `associations` VALUES(34, 'LWAS', 'Lichfield and Walsall Archdeaconries Society', 'http://www.lwascr.org.uk/');
INSERT INTO `associations` VALUES(35, 'Middx', 'Middlesex County Association', 'http://www.mcaldg.org.uk/');
INSERT INTO `associations` VALUES(36, 'MUG', 'Manchester University Guild', 'http://compsoc.man.ac.uk/~mowen/mugcr/mugcr.html');
INSERT INTO `associations` VALUES(37, 'NAG', 'North American Guild', 'http://www.nagcr.org/');
INSERT INTO `associations` VALUES(38, 'NDA', 'Norwich Diocesan Association', 'http://www.norwich-diocese-ringers.org.uk/');
INSERT INTO `associations` VALUES(39, 'NWA', 'North Wales Asssociation', 'http://www.northwalesbellringers.org/');
INSERT INTO `associations` VALUES(40, 'S&B', 'Swansea and Brecon Diocesan Guild', 'http://myweb.tiscali.co.uk/pckmj/sbdg/');
INSERT INTO `associations` VALUES(41, 'SAG', 'South African Guild', 'http://www.scifac.ru.ac.za/cathedral/bellguild.htm');
INSERT INTO `associations` VALUES(42, 'Salis', 'Salisbury Diocesan Guild', 'http://www.sdgr.org.uk/');
INSERT INTO `associations` VALUES(43, 'Salop', 'Shropshire Association', 'http://www.sacbr.org.uk/');
INSERT INTO `associations` VALUES(44, 'Scot', 'Scottish Association', 'http://www.sacr.org/');
INSERT INTO `associations` VALUES(45, 'SDDG', 'St David''s Diocesan Guild', 'http://www.rdasouthwales.org.uk/stdavidsringing/');
INSERT INTO `associations` VALUES(47, 'SRCY', 'Society of Royal	Cumberland Youths', 'http://www.srcy.org.uk/');
INSERT INTO `associations` VALUES(48, 'Suff', 'Suffolk Guild', 'http://www.suffolkbells.org.uk/');
INSERT INTO `associations` VALUES(49, 'Surr', 'Surrey Association', 'http://www.surreybellringers.org.uk/');
INSERT INTO `associations` VALUES(50, 'SuxCA', 'Sussex County Association', 'http://www.scacr.org/');
INSERT INTO `associations` VALUES(51, 'Swell', 'Southwell and Nottingham Diocesan Guild', 'http://www.southwelldg.org.uk/');
INSERT INTO `associations` VALUES(52, 'SMB', 'St Martin''s Guild', 'http://smgcbr.heralded.org.uk/');
INSERT INTO `associations` VALUES(53, 'Trans', 'Transvaal Society', '');
INSERT INTO `associations` VALUES(54, 'TruDG', 'Truro Diocesan Guild', 'http://www.tdgr.org.uk/');
INSERT INTO `associations` VALUES(55, 'UBSCR', 'University of Bristol Society', 'http://www.bris.ac.uk/Depts/Union/UBSCR/');
INSERT INTO `associations` VALUES(56, 'UL', 'University of London Society', 'http://www.ulscr.org.uk/');
INSERT INTO `associations` VALUES(57, 'W&P', 'Winchester and Portsmouth Diocesan Guild', 'http://www.wp-ringers.org.uk/');
INSERT INTO `associations` VALUES(58, 'WDA', 'Worcestershire and Districts Change Ringing Association', 'http://www.wdcra.org.uk/');
INSERT INTO `associations` VALUES(59, 'Zimb', 'Zimbabwe Guild', '');

