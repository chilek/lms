#!venv/bin/python
# -*- coding: utf-8 -*-

import ConfigParser
from collections import deque
import datetime
import hashlib
import json
import logging
import os.path
from signal import SIGTERM
import socket
import sys
import threading
import time

import daemon.pidfile
import memcache
import mysql.connector
import psycopg2
import ssh2
from ssh2.session import Session
#
# pylibmc
class ssh:
    client = None
    status = None
    
    def __init__(self, address, username, password, port, timeout):
        logging.info("ssh: connecting to server: %s " % (address))
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(timeout)
        try:
            sock.connect((address, port))
        except:
            self.status = False
            logging.warn("ssh: can't connect to server: %s " % (address))
        else:
            s = Session()
            s.handshake(sock)
            try:
                s.userauth_password(username, password)
            except:
                self.status = False
                logging.warn("ssh: bad login or password: %s " % (address))
            else:
                self.client = s.open_session()
                self.status = True
                
    def sendCommand(self, command):
        self.client.execute(command)
        size, data = self.client.read()
        while size > 0:
            logging.debug("ssh: client %s" % (data))
            size, data = self.client.read()
        logging.info("ssh: commands has been sent, SUCCESS")

        
class conSQL:
    def __init__(self):
        self.basePath = basePath      
        self.loadConf()

    def loadConf(self):
        config = ConfigParser.ConfigParser()
        config.readfp(open(self.basePath + '/conf/ldrm.conf'))
        self.type = config.get('db', 'type')
        self.ip = config.get('db', 'ip')
        self.port = int(config.get('db', 'port'))
        self.dbname = config.get('db', 'dbname')
        self.login = config.get('db', 'login')
        self.passwd = config.get('db', 'passwd')

    def getDataFromDB(self, mac):
        if self.type == 'mysql':
            results = self.selectMySQL("""SELECT n.id as id, inet_ntoa(n.ipaddr) as ip, n.name as nodename, access, warning
                            FROM nodes as n, macs as m
                            WHERE upper(m.mac) = '%s'
                            AND n.id = m.nodeid
                            ORDER by n.id""" % (mac))
            if results is False:
                return False
            dataMac = None
            if len(results) > 0:
                dataMac = {"id":results[0][0], "ip":results[0][1], "nodename":results[0][2], "access":results[0][3], "warning":results[0][4]}
                results = self.selectMySQL("""SELECT CONCAT(ROUND(COALESCE(x.upceil, y.upceil, z.upceil)),'k','/', ROUND(COALESCE(x.downceil, y.downceil, z.downceil)),'k') AS mrt
                            FROM (
                                SELECT n.id, MIN(n.name) AS name, SUM(t.downceil/o.cnt) AS downceil, SUM(t.upceil/o.cnt) AS upceil
                                FROM nodeassignments na
                                JOIN assignments a ON (na.assignmentid = a.id)
                                 JOIN tariffs t ON (a.tariffid = t.id)
                                JOIN nodes n ON (na.nodeid = n.id)
                                JOIN macs m ON (m.nodeid = n.id)
                                JOIN (
                                SELECT assignmentid, COUNT(*) AS cnt
                                    FROM nodeassignments
                                    GROUP BY assignmentid
                                ) o ON (o.assignmentid = na.assignmentid)
                                WHERE (a.datefrom <= unix_timestamp() OR a.datefrom = 0)
                                AND (a.dateto > unix_timestamp() OR a.dateto = 0)
                                AND upper(m.mac) = '%s'
                                GROUP BY n.id
                            ) x
                            LEFT JOIN (
                                SELECT SUM(t.downceil)/o.cnt AS downceil,
                                SUM(t.upceil)/o.cnt AS upceil
                                FROM assignments a
                                JOIN tariffs t ON (a.tariffid = t.id)
                                JOIN nodes n ON (a.customerid = n.ownerid)
                                JOIN macs m ON (m.nodeid = n.id)
                                JOIN (
                                SELECT COUNT(*) AS cnt, ownerid FROM vnodes
                                WHERE NOT EXISTS (
                                    SELECT 1 FROM nodeassignments, assignments a
                                    WHERE assignmentid = a.id AND nodeid = vnodes.id
                                    AND  (a.dateto > unix_timestamp() OR a.dateto = 0))
                                    GROUP BY ownerid
                                ) o ON (o.ownerid = n.ownerid)
                                WHERE (a.datefrom <= unix_timestamp() OR a.datefrom = 0)
                                AND (a.dateto > unix_timestamp() OR a.dateto = 0)
                                    AND NOT EXISTS (SELECT 1 FROM nodeassignments WHERE assignmentid = a.id)
                                    AND upper(m.mac) = '%s'
                                GROUP BY n.id
                            ) y ON (1=1)
                            RIGHT JOIN (
                                SELECT n.id, n.name, 64 AS downceil, 64 AS upceil
                                    FROM nodes as n,macs as m
                                    WHERE upper(m.mac) = '%s'
                                    AND m.nodeid = n.id
                            ) z ON (1=1)""" % (mac, mac, mac))
                if results is False:
                    return False
                if len(results) > 0:
                    dataMac.update({"mrt":results[0][0]})
                else:
                    logging.warn("conSQL: can't find tariff for %s" % (mac))
            else:
                logging.warn("conSQL: wrong mac address: %s, don't exist in DB" % (mac))
            return dataMac
        elif self.type == 'pgsql':
            results = self.selectPG("""SELECT n.id as id, inet_ntoa(n.ipaddr) as ip, n.name as nodename, access, warning
                            FROM nodes as n, macs as m
                            WHERE upper(m.mac) = '%s'
                            AND n.id = m.nodeid
                            ORDER by n.id""" % (mac))
            if results is False:
                return False
            dataMac = None
            if len(results) > 0:
                dataMac = {"id":results[0][0], "ip":results[0][1], "nodename":results[0][2], "access":results[0][3], "warning":results[0][4]}
                results = self.selectPG("""SELECT CONCAT(ROUND(COALESCE(x.upceil, y.upceil, z.upceil)),'k','/', ROUND(COALESCE(x.downceil, y.downceil, z.downceil)),'k') AS mrt
                            FROM (
                                SELECT n.id, MIN(n.name) AS name, SUM(t.downceil/o.cnt) AS downceil, SUM(t.upceil/o.cnt) AS upceil
                                FROM nodeassignments na
                                JOIN assignments a ON (na.assignmentid = a.id)
                                 JOIN tariffs t ON (a.tariffid = t.id)
                                JOIN nodes n ON (na.nodeid = n.id)
                                JOIN macs m ON (m.nodeid = n.id)
                                JOIN (
                                SELECT assignmentid, COUNT(*) AS cnt
                                    FROM nodeassignments
                                    GROUP BY assignmentid
                                ) o ON (o.assignmentid = na.assignmentid)
                                WHERE (a.datefrom <= extract(epoch from now()) OR a.datefrom = 0)
                                AND (a.dateto > extract(epoch from now()) OR a.dateto = 0)
                                AND upper(m.mac) = '%s'
                                GROUP BY n.id
                            ) x
                            LEFT JOIN (
                                SELECT SUM(t.downceil)/o.cnt AS downceil,
                                SUM(t.upceil)/o.cnt AS upceil
                                FROM assignments a
                                JOIN tariffs t ON (a.tariffid = t.id)
                                JOIN nodes n ON (a.customerid = n.ownerid)
                                JOIN macs m ON (m.nodeid = n.id)
                                JOIN (
                                SELECT COUNT(*) AS cnt, ownerid FROM vnodes
                                WHERE NOT EXISTS (
                                    SELECT 1 FROM nodeassignments, assignments a
                                    WHERE assignmentid = a.id AND nodeid = vnodes.id
                                    AND  (a.dateto > extract(epoch from now()) OR a.dateto = 0))
                                    GROUP BY ownerid
                                ) o ON (o.ownerid = n.ownerid)
                                WHERE (a.datefrom <= extract(epoch from now()) OR a.datefrom = 0)
                                AND (a.dateto > extract(epoch from now()) OR a.dateto = 0)
                                    AND NOT EXISTS (SELECT 1 FROM nodeassignments WHERE assignmentid = a.id)
                                    AND upper(m.mac) = '%s'
                                GROUP BY n.id, o.cnt
                            ) y ON (1=1)
                            RIGHT JOIN (
                                SELECT n.id, n.name, 64 AS downceil, 64 AS upceil
                                    FROM nodes as n,macs as m
                                    WHERE upper(m.mac) = '%s'
                                    AND m.nodeid = n.id
                            ) z ON (1=1)""" % (mac, mac, mac))

                if results is False:
                    return False
                if len(results) > 0:
                    dataMac.update({"mrt":results[0][0]})
                else:
                    logging.warn("conSQL: can't find tariff for %s" % (mac))
            else:
                logging.warn("conSQL: wrong mac address: %s, don't exist in DB" % (mac))
            return dataMac
        else:
            logging.error("conSQL: wrong type of db: %s, supported only mysql or pgslq" % (self.type))

    def selectMySQL(self, query):
        try:
            cnx = mysql.connector.connect(host=self.ip, database=self.dbname, user=self.login, password=self.passwd)
            try:
                cursor = cnx.cursor()
                cursor.execute(query)
            except:
                raise
            else:
                results = cursor.fetchall()
                return results
            finally:
                cnx.close()
        except Exception as e:
            logging.error('conSQL: ' + str(e))
            return False

    def selectPG(self, query):
        try:
            con = psycopg2.connect(host=self.ip, dbname=self.dbname, user=self.login, password=self.passwd)
        except Exception as e:
            logging.error('conSQL: ' + str(e))
            return False
        else:
            cur = con.cursor()
            try:
                cur.execute(query)
            except Exception as e:
                logging.error('conSQL: ' + str(e))
                return False
            else:
                results = cur.fetchall()
                return results
            finally:
                con.close()


class queueDrd:
    queueDrd = deque([])
    
    dataClient = {}
    
    size = 1000
    
    def getAllItemInQueue(self):
        return list(self.queueDrd)
    
    def fetch(self):
        if len(self.queueDrd) > 0:            
            machash = self.queueDrd.popleft()
            data = self.dataClient.pop(machash)
            return [machash, data]
        else:
            return None
    
    def add(self, machash, data):
        
        if len(self.queueDrd) < self.size:
            if machash not in self.queueDrd:
                self.queueDrd.append(machash)
                self.dataClient.update({machash:data})
     
    def remove(self, machash):
        if machash in self.queueDrd:
            self.queueDrd.remove(machash)
        if machash in self.dataClient:
            del self.dataClient[machash]
            
     
class deamonMT(threading.Thread):
        
    def __init__(self, QH):  
        threading.Thread.__init__(self)
        self.setDaemon(True)
        
        self.basePath = basePath      
        self.loadConf()
                
        self.cache = memcache.Client([self.ip + ':' + self.port], debug=0) 
    
        self.QH = QH
        
        self.SQL = conSQL()
    
    
    def run(self):
        logging.info("deamonMT: ready and waiting..")
        
        while True:
            if len(self.QH.queueDrd) > 0:
                data = self.QH.fetch()
                if data is not None:
                    if self.api == 'ssh':
                        MTCommands=self.createMTCommands(data)
                        if MTCommands is not False:
                            self.executeMT(MTCommands[1],MTCommands[0])
                            logging.info("deamonMT: sending commands to execute on Mikrotik :\n" + str(MTCommands))
                    else:
                        logging.error('deamonMT: incorrect api: %s' % (self.api))
            ql = len(self.QH.queueDrd)
            if ql == 0:
                time.sleep(60)
            elif ql == 1:
                time.sleep(30)
            elif ql < 10:
                time.sleep(15)
            elif ql > 0 and ql < 50:
                time.sleep(5)
            elif ql > 0 and ql < 100:
                time.sleep(1)
            elif ql > 0 and ql < 300:
                time.sleep(0.5)
            else:
                time.sleep(0.1)
                    
    
    def createMTCommands(self, data):
        if self.is_valid_ipv4_address(data[1]['Framed_IP_Address']):
            self.macData = self.cache.get(hashlib.sha1(data[0] + data[1]['Framed_IP_Address']).hexdigest())
            if self.macData is None:
                logging.info('deamonMT: miss cache for mac: %s ip: %s, extracting data from DB' % (data[1]['User_Name'], data[1]['Framed_IP_Address']))
                while True:
                    dataSQL = self.SQL.getDataFromDB(data[1]['User_Name'])
                    if dataSQL is False:
                        time.sleep(5)
                    else:
                        if dataSQL is not None:
                            data[1].update({"nodeId":dataSQL["id"]})
                            data[1].update({"access":dataSQL["access"]})
                            data[1].update({"warning":dataSQL["warning"]})
                            data[1].update({"nodename":dataSQL["nodename"]})
                            data[1].update({"mrt":dataSQL["mrt"]})
                            self.cache.set(hashlib.sha1(data[0] + data[1]['Framed_IP_Address']).hexdigest(), data[1], self.time)
                            self.macData = data[1]
                        else:
                            # cant find mac in db, send info?
                            data[1].update({"nodeId":None})
                            self.cache.set(hashlib.sha1(data[0] + data[1]['Framed_IP_Address']).hexdigest(), data[1], self.time)
                            self.macData = data[1]
                        break
            else:
                logging.info('deamonMT: hit cache for mac: %s ip: %s, extracting data from memcached' % (data[1]['User_Name'], data[1]['Framed_IP_Address']))
            execOnMT = None
            logging.debug(self.macData)                                    
            if self.macData['nodeId'] is not None and self.macData['nodename'] is not None and  self.macData['mrt'] is not None and self.is_valid_ipv4_address(self.macData['Framed_IP_Address']) and self.is_valid_ipv4_address(self.macData['NAS_IP_Address']):
                datenow = datetime.datetime.fromtimestamp(time.time()).strftime('%Y-%m-%d')
                execOnMT = '/queue simple remove [find target=' + str(self.macData['Framed_IP_Address']) + '/32]; '                   
                execOnMT += '/ip firewall nat remove [find src-address="' + str(self.macData['Framed_IP_Address']) + '" and chain="'+self.warnchainName+'"]; '
                execOnMT += '/ip firewall address-list remove [find address="' + str(self.macData['Framed_IP_Address']) + '" and list="'+self.blockListName+'"]; '
                if str(self.macData['mrt']) =='0k/0k': 
                    execOnMT += """/queue simple add name=""" + str(self.macData['nodename']) + """ target=""" + str(self.macData['Framed_IP_Address']) + """/32 parent=none packet-marks="" priority=8/8 queue=s100/s100 limit-at=64k/64k max-limit=64k/64k burst-limit=0/0 burst-threshold=0/0 burst-time=0s/0s comment=""" + str(datenow) + """; """    
                else:
                    execOnMT += """/queue simple add name=""" + str(self.macData['nodename']) + """ target=""" + str(self.macData['Framed_IP_Address']) + """/32 parent=none packet-marks="" priority=8/8 queue=s100/s100 limit-at=64k/64k max-limit=""" + str(self.macData['mrt']) + """ burst-limit=0/0 burst-threshold=0/0 burst-time=0s/0s comment=""" + str(datenow) + """; """ 
                if self.macData['access'] == 0:
                    execOnMT += """/ip firewall address-list add list="""+self.blockListName+""" address=""" + str(self.macData['Framed_IP_Address']) + """ comment=""" + str(self.macData['nodeId']) + """; """
                    if self.macData['warning'] == 1:
                        execOnMT += """/ip firewall nat add chain="""+self.warnchainName+""" action=dst-nat to-addresses=""" + self.lmswarn + """ to-ports=8001 protocol=tcp src-address=""" + str(self.macData['Framed_IP_Address']) + """ limit=10/1h,1:packet log=no log-prefix="" comment=""" + str(datenow) + """; """
                if self.macData['access'] == 1:  
                    if self.macData['warning'] == 1:
                        execOnMT += """/ip firewall nat add chain="""+self.warnchainName+""" action=dst-nat to-addresses=""" + self.lmswarn + """ to-ports=8001 protocol=tcp src-address=""" + str(self.macData['Framed_IP_Address']) + """ limit=10/1h,1:packet log=no log-prefix="" comment=""" + str(datenow) + """; """
                
                logging.debug("deamonMT: Mikrotik commands for ip %s:\n %s" % (self.macData['NAS_IP_Address'], execOnMT))
                
                return (self.macData['NAS_IP_Address'], execOnMT)
            else:
                logging.info('deamonMT: incorrect data: nodeId, access, warning, nodename, mtr, NAS_IP_Address or Framed_IP_Address')
                return False
        else:
            logging.info('deamonMT: incorrect Framed_IP_Address or null')
            return False
        
    def is_valid_ipv4_address(self, address):
        try:
            socket.inet_pton(socket.AF_INET, address)
        except AttributeError:  
            try:
                socket.inet_aton(address)
            except socket.error:
                return False
            return address.count('.') == 3
        except socket.error: 
            return False
        return True
                    
    def loadConf(self):
        
        config = ConfigParser.ConfigParser()
        config.readfp(open(self.basePath + '/conf/ldrm.conf'))
        
        self.ip = config.get('memcached', 'ip')
        self.port = config.get('memcached', 'port')
        self.time = int(config.get('memcached', 'time'))
        
        self.lmswarn = config.get('lms', 'warnserver')
        
        self.api = config.get('mt', 'api')
        self.loginSsh = config.get('mt', 'login')
        self.passwdSsh = config.get('mt', 'pass')
        self.portSsh = int(config.get('mt', 'port'))
        self.timeoutSsh = int(config.get('mt', 'timeout'))
        
        self.warnchainName = config.get('mt', 'warnchainName')
        self.blockListName = config.get('mt', 'blockListName')
        
    def executeMT(self, execOnMT, ipToCon):        
        i = 0
        while (True):
            if i < 3:
                S = ssh(ipToCon, self.loginSsh, self.passwdSsh, int(self.portSsh), int(self.timeoutSsh))
                if S.status is True:
                    S.sendCommand(execOnMT)
                    break
                else:
                    time.sleep(5)
                i += 1
            else:
                break


class servertcp(threading.Thread):
        
    def __init__(self, QH):  
        threading.Thread.__init__(self)
        self.setDaemon(True)
        
        self.basePath = basePath      
        self.loadConf()        
        
        self.QH = QH
        
    def run(self):
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.bind((self.host, int(self.port)))
        s.listen(5)
        logging.info("servertcp(on port %s): ready and waiting.." % (self.port))
        while True:
            client, ipPort = s.accept()
            client.settimeout(int(self.connectionTimeout))
            if ipPort[0] == '127.0.0.1':
                try:
                    data = json.loads(client.recv(1024))
                    if data[0] == 'DATA':
                        self.QH.add(hashlib.sha1(data[1]).hexdigest(), data[2])
                        client.send(json.dumps(['OK']))
                        logging.info("servertcp: new data, mac: %s from %s" % (data[2]['User_Name'], ipPort[0]))
                    else:
                        client.send(json.dumps(['BADCOMMAND']))
                    client.close    
                except ValueError as e:
                    logging.warn('servertcp: recived data is not json, %s' % (e))
                    client.close
                except socket.timeout as e:
                    logging.warn('servertcp: connection Timeout, %s' % (e))
                    client.close
            else:
                logging.warn("servertcp: dont't accept conections form %s, only localhost is accepted" % (ipPort[0]))
                client.close
            
    def loadConf(self):
        config = ConfigParser.ConfigParser()
        config.readfp(open(self.basePath + '/conf/ldrm.conf'))
        self.host = config.get('tcpserver', 'host')
        self.port = config.get('tcpserver', 'port')
        self.connectionTimeout = config.get('tcpserver', 'connectionTimeout')

        
class ldrm:
        
    def __init__(self, basePath):
        self.basePath = basePath
        self.loadConf()
    
    def run(self):
        if 'debug' == sys.argv[1]:
            if self.log == 'debug':
                logging.basicConfig(level=logging.DEBUG, format='%(relativeCreated)6d %(threadName)s %(message)s')
            elif self.log == 'info':
                logging.basicConfig(level=logging.INFO, format='%(relativeCreated)6d %(threadName)s %(message)s')
            elif self.log == 'warn':
                logging.basicConfig(level=logging.WARN, format='%(relativeCreated)6d %(threadName)s %(message)s')
            elif self.log == 'error':
                logging.basicConfig(level=logging.ERROR, format='%(relativeCreated)6d %(threadName)s %(message)s')
            elif self.log == 'critical':
                logging.basicConfig(level=logging.CRITICAL, format='%(relativeCreated)6d %(threadName)s %(message)s')
            else:
                logging.basicConfig(level=logging.INFO, format='%(relativeCreated)6d %(threadName)s %(message)s')
        else:
            if self.log == 'debug':
                logging.basicConfig(level=logging.DEBUG,
                            format='%(asctime)s %(name)-1s %(levelname)-1s %(message)s',
                            datefmt='%d-%m-%d %H:%M',
                            filename=self.logFile,
                            filemode='w')
            elif self.log == 'info':
                logging.basicConfig(level=logging.INFO,
                            format='%(asctime)s %(name)-1s %(levelname)-1s %(message)s',
                            datefmt='%d-%m-%d %H:%M',
                            filename=self.logFile,
                            filemode='w')
            elif self.log == 'warn':
                logging.basicConfig(level=logging.WARN,
                            format='%(asctime)s %(name)-1s %(levelname)-1s %(message)s',
                            datefmt='%d-%m-%d %H:%M',
                            filename=self.logFile,
                            filemode='w')
            elif self.log == 'error':
                logging.basicConfig(level=logging.ERROR,
                            format='%(asctime)s %(name)-1s %(levelname)-1s %(message)s',
                            datefmt='%d-%m-%d %H:%M',
                            filename=self.logFile,
                            filemode='w')
            elif self.log == 'critical':
                logging.basicConfig(level=logging.CRITICAL,
                            format='%(asctime)s %(name)-1s %(levelname)-1s %(message)s',
                            datefmt='%d-%m-%d %H:%M',
                            filename=self.logFile,
                            filemode='w')
            else:
                logging.basicConfig(level=logging.INFO,
                            format='%(asctime)s %(name)-1s %(levelname)-1s %(message)s',
                            datefmt='%d-%m-%d %H:%M',
                            filename=self.logFile,
                            filemode='w')

        logging.info("ldrm: start main thread")

        QH = queueDrd()
        
        ST = servertcp(QH)
        ST.start()
        
        DMT = deamonMT(QH)
        DMT.start()

        while True:
            try:
                time.sleep(1)
            except KeyboardInterrupt:
                if 'debug' == sys.argv[1]:
                    break
                    
        logging.info("ldrm: stop main thread")
                
    def loadConf(self):
        config = ConfigParser.ConfigParser()
        config.readfp(open(self.basePath + '/conf/ldrm.conf'))
        self.log = config.get('main', 'log')
        self.logFile = config.get('main', 'logFile')

    def start(self):
        """
        Start the daemon
        """
        if os.path.isfile(self.basePath + '/conf/ldrm.conf') is False:
            print 'ldrm: Where is conf, should be in main directory\nfile: ldrm.conf'
            sys.exit(1)
        
        pidfile = '/tmp/ldrm.pid'
        try:
            pf = file(pidfile, 'r')
            pid = int(pf.read().strip())
            pf.close()
        except IOError:
            pid = None
    
        if pid:
            message = "ldrm: pidfile %s already exist. Daemon already running?"
            sys.stderr.write(message % pidfile)
            sys.exit(1)
        
        working_directory = self.basePath
        pidfile = daemon.pidfile.PIDLockFile(pidfile)

        with daemon.DaemonContext(working_directory=working_directory, pidfile=pidfile):
                self.run()

    def stop(self):
        """
        Stop the daemon
        """        

        pidfile = '/tmp/ldrm.pid'
        
        try:
            pf = file(pidfile, 'r')
            pid = int(pf.read().strip())
            pf.close()
        except IOError:
            pid = None
    
        if not pid:
            message = "pidfile %s does not exist. Daemon not running?"
            sys.stderr.write(message % pidfile)
            return 
           
        try:
            while 1:
                os.kill(pid, SIGTERM)
                time.sleep(0.1)
        except OSError, err:
            err = str(err)
            if err.find("No such process") > 0:
                if os.path.exists(pidfile):
                    os.remove(pidfile)
            else:
                print str(err)
                sys.exit(1)

    
if __name__ == "__main__":
    basePath = os.path.dirname(os.path.abspath(__file__))
    if len(sys.argv) == 2:
        if 'debug' == sys.argv[1]:
            ldrm(basePath).run()
        elif 'start' == sys.argv[1]:            
            ldrm(basePath).start()
        elif 'stop' == sys.argv[1]:
            ldrm(basePath).stop()
        elif 'help' == sys.argv[1]:
            print "usage: %s \n\tstart \t\t-> start deamon \n\tstop \t\t-> stop deamon \n\tdebug \t-> non-daemon mode \n\thelp \t-> show this" % sys.argv[0]
            sys.exit(2)
        else:
            print "Unknown command"
            sys.exit(2)
        sys.exit(0)
    else:
        print "usage: %s \n\tstart \t-> start deamon \n\tstop \t-> stop deamon \n\tdebug \t-> non-daemon mode \n\thelp \t-> show this" % sys.argv[0]
        sys.exit(2)   
