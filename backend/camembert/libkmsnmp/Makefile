libkmsnmp.a: address.o asn1.o counter.o ctr64.o gauge.o integer.o octet.o oid.o pdu.o snmpmsg.o timetick.o vb.o
	ar -r libkmsnmp.a address.o asn1.o counter.o ctr64.o gauge.o integer.o octet.o oid.o pdu.o snmpmsg.o timetick.o vb.o

CC=g++
CCOPT= -D_THREAD_SAFE -c -O2 -Wall

address.o: address.cpp address.h
	$(CC) $(CCOPT) address.cpp

asn1.o: asn1.cpp
	$(CC) $(CCOPT) asn1.cpp

counter.o: counter.cpp counter.h
	$(CC) $(CCOPT) counter.cpp	

ctr64.o: ctr64.cpp ctr64.h
	$(CC) $(CCOPT) ctr64.cpp	

gauge.o: gauge.cpp gauge.h
	$(CC) $(CCOPT) gauge.cpp

integer.o: integer.cpp integer.h
	$(CC) $(CCOPT) integer.cpp

octet.o: octet.cpp octet.h
	$(CC) $(CCOPT) octet.cpp

oid.o: oid.cpp oid.h
	$(CC) $(CCOPT) oid.cpp

pdu.o: pdu.cpp pdu.h
	$(CC) $(CCOPT) pdu.cpp

snmpmsg.o: snmpmsg.cpp snmpmsg.h
	$(CC) $(CCOPT) snmpmsg.cpp

timetick.o: timetick.cpp timetick.h
	$(CC) $(CCOPT) timetick.cpp

vb.o: vb.cpp vb.h
	 $(CC) $(CCOPT) vb.cpp

clean:
	rm -f *.o libkmsnmp.a 
