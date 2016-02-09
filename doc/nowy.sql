

CREATE TABLE netnodes (
  id int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL,
  type tinyint DEFAULT 0,
  invprojectid int(11),
  status tinyint DEFAULT 0,
  location varchar(255) DEFAULT '',
  location_city int(11) DEFAULT NULL,
  location_street int(11) DEFAULT NULL,
  location_house varchar(32) DEFAULT NULL,
  location_flat varchar(32) DEFAULT NULL,
  longitude decimal(10,6) DEFAULT NULL,
  latitude decimal(10,6) DEFAULT NULL,
  ownership tinyint(1) DEFAULT 0,
  coowner varchar(255) DEFAULT '',
  ownerid int(11) NOT NULL DEFAULT '0'
  uip tinyint(1) DEFAULT 0,
  miar tinyint(1) DEFAULT 0,
  divisionid int(11) DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (invprojectid) REFERENCES invprojects (id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (location_city) REFERENCES location_cities (id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (location_street) REFERENCES location_streets (id) ON DELETE SET NULL ON UPDATE CASCADE
  FOREIGN KEY (divisionid) REFERENCES divisions (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=INNODB;


CREATE TABLE netelements (
  id            int(11)         NOT NULL auto_increment,
  name          varchar(32)     NOT NULL DEFAULT '',
  type		tinyint(1)	NOT NULL DEFAULT '0',
  description   text            NOT NULL DEFAULT '',
  producer      varchar(64)     NOT NULL DEFAULT '',
  model         varchar(32)     NOT NULL DEFAULT '',
  serialnumber  varchar(32)     NOT NULL DEFAULT '',
  purchasetime  int(11)         NOT NULL DEFAULT '0',
  guaranteeperiod tinyint unsigned DEFAULT '0',
  netnodeid     int(11)         DEFAULT NULL,
  invprojectid  int(11)         DEFAULT NULL,
  status        tinyint         DEFAULT '0',
  netdevicemodelid int(11) DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (netnodeid) REFERENCES netnodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (invprojectid) REFERENCES invprojects (id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (netdevicemodelid) REFERENCES netdevicemodels (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE netdevparams (
  id                int(11)         NOT NULL auto_increment,
  netelemid         int(11)         NOT NULL DEFAULT '0',
  shortname     varchar(32)     NOT NULL DEFAULT '',
  nastype       int(11)         NOT NULL DEFAULT '0',
  clients       int(11)         NOT NULL DEFAULT '0',
  secret        varchar(60)     NOT NULL DEFAULT '',
  community     varchar(50)     NOT NULL DEFAULT '',
  channelid     int(11)         DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX channelid (channelid),
  FOREIGN KEY (channelid) REFERENCES ewx_channels (id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (netelemid) REFERENCES netelements (id) ON DELETE NULL ON UPDATE CASCADE,
) ENGINE=InnoDB;


CREATE TABLE netcables (
  id		int(11)		NOT NULL auto_increment,
  netelemid	int(11)		NOT NULL DEFAULT '0',
  type		tinyint(2)	NOT NULL DEFAULT '0',
  label		varchar(100)	NOT NULL DEFAULT '',
  capacity	smallint(4)	NOT NULL DEFAULT '0',
  distance	int(4)		UNSIGNED NOT NULL DEFAULT '0',
  srcelemid	int(11)		DEFAULT NULL,	
  dstelemid	int(11)		DEFAULT NULL,	
  PRIMARY KEY (id),
  INDEX netelemid(netelemid),
  FOREIGN KEY (netelemid) REFERENCES netelements (id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (srcelemid) REFERENCES netnodes (id) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (dstelemid) REFERENCES netnodes (id) ON DELETE SET NULL ON UPDATE CASCADE
  UNIQUE KEY type (netelemid,type)
) ENGINE=InnoDB;

CREATE TABLE netports (
  id            int(11)         NOT NULL auto_increment,
  netelemid     int(11)         NOT NULL DEFAULT '0',
  label		varchar(32)	NOT NULL DEFAULT '',
  type		tinyint(1)	UNSIGNED NOT NULL DEFAULT '0',
  connector	tinyint(1)      UNSIGNED NOT NULL DEFAULT '0',
  technology	tinyint(1)      UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  INDEX netelemid(netelemid),
  FOREIGN KEY (netelemid) REFERENCES netelements (id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY label (label, netelemid)
) ENGINE=InnoDB;

CREATE TABLE netradiosectors (
  id		int(11)		NOT NULL auto_increment,
  netelemid     int(11)         NOT NULL DEFAULT '0',
  name		varchar(64)	NOT NULL,
  azimuth	decimal(9,2)	DEFAULT 0 NOT NULL,
  width		decimal(9,2)	DEFAULT 0 NOT NULL,
  altitude	smallint	DEFAULT 0 NOT NULL,
  rsrange	int(11)		DEFAULT 0 NOT NULL,
  license	varchar(64)	DEFAULT NULL,
  technology	int(11)		DEFAULT 0 NOT NULL,
  frequency	numeric(9,5)	DEFAULT NULL,
  frequency2	numeric(9,5)	DEFAULT NULL,
  bandwidth	numeric(9,5)	DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX netelemid (netelemid),
  FOREIGN KEY (netelemid) REFERENCES netelements (id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY name (name, netelemid)
) ENGINE=INNODB;

CREATE TABLE netparams (
  id            int(11)         NOT NULL auto_increment,
  netelemid     int(11)         NOT NULL DEFAULT '0',
  label		varchar(64)     NOT NULL,
  type          tinyint(2)      NOT NULL DEFAULT '0', 
  capacity	int(11)         NOT NULL DEFAULT '1',
  PRIMARY KEY (id),
  INDEX netelemid (netdelemid),
  FOREIGN KEY (netelemid) REFERENCES netelements (id) ON DELETE CASCADE ON UPDATE CASCADE,
) ENGINE=INNODB;


CREATE TABLE netsplitters (
  id            int(11)         NOT NULL auto_increment,
  netelemid     int(11)         NOT NULL DEFAULT '0',
  side          tinyint(2)      NOT NULL DEFAULT '0',
  capacity      int(11)         NOT NULL DEFAULT '1',
  PRIMARY KEY (id),
  INDEX netelemid (netdelemid),
  FOREIGN KEY (netelemid) REFERENCES netelements (id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY side (netelemid,side)
) ENGINE=INNODB;

CREATE TABLE netwires (
  id            int(11)         NOT NULL auto_increment,
  netcableid    int(11)         NOT NULL DEFAULT '0',
  bundle	tinyint(2)	NOT NULL DEFAULT '1',
  wire		tinyint(2)	NOT NULL DEFAULT '1',
  PRIMARY KEY (id),
  INDEX netcableid (netcableid),
  FOREIGN KEY (netcableid) REFERENCES netelements (id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY wire (netcableid,bundle,wire)
) ENGINE=INNODB;

CREATE TABLE netconnections (
  id            int(11)         NOT NULL auto_increment,
  srcwireid	int(11)         DEFAULT NULL,
  dstwireid	int(11)		DEFAULT NULL,
  srcconnector	tinyint(2)	DEFAULT NULL,
  dstconnector	tinyint(2)	DEFAULT NULL,
  distance      float(4,1)	DEFAULT NULL,
  description	varchar(50)	NOT NULL DEFAULT '',	
  PRIMARY KEY (id),
  FOREIGN KEY (srcwireid) REFERENCES netwires (id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (dstwireid) REFERENCES netwires (id) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY wires (srcwireid,dstwireid),
) ENGINE=INNODB;

