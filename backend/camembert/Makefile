
CC=g++
OPTIONS=-D_THREAD_SAFE -O3 -march=pentiumpro -c -Wall -I/usr/local/include -Ilibkmsnmp/
LINKOPTIONS=-D_THREAD_SAFE -Wall -I/usr/local/include -Ilibkmsnmp/ -L/usr/local/lib

main: main.o monitor.o neighbours.o functions.o vlan.o materiel.o interface.o action.o fdb.o arp.o pgdb.o snmpobject.o snmp.o ip.o libkmsnmp/libkmsnmp.a
	$(CC) $(LINKOPTIONS) -pthread -lpq main.o monitor.o neighbours.o functions.o vlan.o materiel.o interface.o action.o fdb.o arp.o pgdb.o snmpobject.o snmp.o ip.o libkmsnmp/libkmsnmp.a -o main 

libkmsnmp/libkmsnmp.a:
	cd libkmsnmp/ && make libkmsnmp.a

main.o: main.cpp camembert.h
	$(CC) $(OPTIONS) main.cpp

monitor.o: monitor.cpp monitor.h
	$(CC) $(OPTIONS) monitor.cpp

neighbours.o: neighbours.cpp neighbours.h
	$(CC) $(OPTIONS) neighbours.cpp

functions.o: functions.h functions.cpp
	$(CC) $(OPTIONS) functions.cpp

vlan.o: vlan.h vlan.cpp
	$(CC) $(OPTIONS) vlan.cpp

action.o: action.h action.cpp
	$(CC) $(OPTIONS) action.cpp

interface.o: interface.h interface.cpp
	$(CC) $(OPTIONS) interface.cpp

materiel.o: materiel.h materiel.cpp
	$(CC) $(OPTIONS) materiel.cpp

fdb.o: fdb.h fdb.cpp
	$(CC) $(OPTIONS) fdb.cpp

arp.o: arp.h arp.cpp
	$(CC) $(OPTIONS) arp.cpp

pgdb.o: pgdb.cpp pgdb.h
	$(CC) $(OPTIONS) pgdb.cpp

snmpobject.o: snmpobject.cpp snmpobject.h
	$(CC) $(OPTIONS) snmpobject.cpp

snmp.o: snmp.cpp snmp.h
	$(CC) $(OPTIONS) snmp.cpp

ip.o: ip.cpp ip.h
	$(CC) $(OPTIONS) ip.cpp

clean:
	rm -f *.o
	cd libkmsnmp/ && rm -f *.o
