#!/usr/bin/python
# -*- coding: utf-8 -*-

# Ce script fait un write memory sur chacun des switchs passés en paramètres.

from pysnmp.entity.rfc3413.oneliner import cmdgen
from pysnmp.proto                   import rfc1902
import random
import re
import os,sys

CiscoPortSecurityMacAddresses = 1,3,6,1,4,1,9,9,315,1,2,2,1,4
CiscoPortSecurityLastAddress = 1,3,6,1,4,1,9,9,315,1,2,1,1,10
CiscoifAdminStatus = 1,3,6,1,2,1,2,2,1,7   # 1 = up, 2 = down, 3 = testing
CiscoifOperStatus = 1,3,6,1,2,1,2,2,1,8   # 1 = up, 2 = down, 3 = testing
Cisco_ccCopySourceFileType = 1,3,6,1,4,1,9,9,96,1,1,1,1,3   # needs a random row and a file type: 3: startupConfig, 4: runningConfig
Cisco_ccCopyDestFileType = 1,3,6,1,4,1,9,9,96,1,1,1,1,4    # needs a random row and a file type: 3: startupConfig, 4: runningConfig
Cisco_ccCopyEntryRowStatus = 1,3,6,1,4,1,9,9,96,1,1,1,1,14 # needs a random row and a status: 1: starts copying
Cisco_ccCopyState = 1,3,6,1,4,1,9,9,96,1,1,1,1,10   # needs a random row; 1: waiting, 2: running, 3: done ok, 4: failed

CommunityData = cmdgen.CommunityData('cerbere', 'pacadmins')

def getCMD( host, oid, value=None ):
    targetAddr = cmdgen.UdpTransportTarget((host, 161))

    if value != None:
        errorIndication, errorStatus, errorIndex, varBinds = cmdgen.CommandGenerator().getCmd(
                    CommunityData, targetAddr, oid + ( value, )
                )
    else:
        errorIndication, errorStatus, errorIndex, varBinds = cmdgen.CommandGenerator().getCmd(
                    CommunityData, targetAddr, oid
                )

    return (errorIndication, errorStatus, errorIndex, varBinds)

def setCMD( host, oid, value):
    targetAddr = cmdgen.UdpTransportTarget((host, 161))
    errorIndication, errorStatus, errorIndex, varBinds = cmdgen.CommandGenerator().setCmd(
            CommunityData, targetAddr, ( oid , value )
        )
    return (errorIndication, errorStatus, errorIndex, varBinds)

def writeMemory( host ):
    row = random.randrange(1, 10)

    errorIndication, errorStatus, errorIndex, varBinds = setCMD( host, Cisco_ccCopySourceFileType + (row,), rfc1902.Integer(4) )
    source_result = { 'errorIndication': errorIndication, 'errorStatus': errorStatus, 'errorIndex': errorIndex, 'value': varBinds}

    errorIndication, errorStatus, errorIndex, varBinds = setCMD( host, Cisco_ccCopyDestFileType + (row,), rfc1902.Integer(3) )
    dest_result = { 'errorIndication': errorIndication, 'errorStatus': errorStatus, 'errorIndex': errorIndex, 'value': varBinds}

    errorIndication, errorStatus, errorIndex, varBinds = setCMD( host, Cisco_ccCopyEntryRowStatus + (row,), rfc1902.Integer(1) )
    dest_result = { 'errorIndication': errorIndication, 'errorStatus': errorStatus, 'errorIndex': errorIndex, 'value': varBinds}

    ok = False
    while not ok:
        errorIndication, errorStatus, errorIndex, varBinds = getCMD( host, Cisco_ccCopyState + (row,) )
        state_result = { 'errorIndication': errorIndication, 'errorStatus': errorStatus, 'errorIndex': errorIndex, 'value': varBinds}
        if not state_result['value']:
                state_result['value']=0
                return state_result
        else: 
                tmp=state_result['value']
                state_result['value']=tmp[0][1]
        if state_result['value'] == 3 or state_result['value'] == 4:
            ok = True
    return state_result


def wr_mem(switch):
    wr_mem_result = writeMemory( switch )
    if wr_mem_result['value'] == 3:
        print "La sauvegarde du switch "+switch+" a réussi."
    else:
        print "La sauvegarde du switch "+switch+" a échoué."
        print "Pour information: "
        print wr_mem_result
    return wr_mem_result

i = 1
while i < len(sys.argv):
    wr_mem(sys.argv[i])
    i = i+1

