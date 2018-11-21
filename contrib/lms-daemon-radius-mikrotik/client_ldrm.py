#!venv/bin/python
# -*- coding: utf-8 -*-

import json
from socket import *
import sys

class clienttcp:
    
    def __init__(self,host,port):
        self.s=socket(AF_INET,SOCK_STREAM)
        self.host=host
        self.port=port
    
    def sendDATA(self,keyData,clientData):
        try:
            self.s.connect((self.host,self.port))
            self.s.settimeout(1)
            try:
                data=json.dumps(['DATA',keyData,clientData])
                self.s.send(data)
                try:
                    msg=json.loads(self.s.recv(1024))
                    self.s.close
                    if msg[0]=='OK':
                        return True
                    else:
                        return False
                except ValueError:
                    print 'Recived data is not json'
            except ValueError:
                print 'Send data is not json'
            except socket.timeout:
                print 'Connection Timeout'
            except socket.error:
                print 'Cant connect to server'
            self.s.close
            return False
        except:
            return False


if __name__ == "__main__":
    data={
        'NAS_Identifier':sys.argv[1],
        'NAS_IP_Address':sys.argv[2],
        'NAS_Port':sys.argv[3],
        'NAS_Port_Type':sys.argv[4],
        'Calling_Station_Id':sys.argv[5],
        'Framed_IP_Address':sys.argv[6],
        'Called_Station_Id':sys.argv[7],
        'User_Name':sys.argv[8],       
        'Password':sys.argv[9]
        }
        
    clienttcp('127.0.0.1',8888).sendDATA(sys.argv[8]+sys.argv[6],data)
