/* ========================================================

Camembert Project
Alban FERON, 2007

SNMP Base class.
Totalement repris de Kindmana, par Aurélien Méré
(juste réindenté :p)

======================================================== */

#ifndef __CAMEMBERT_SNMP_H
#define __CAMEMBERT_SNMP_H

#include "ip.h"
#include "oid.h"
#include "pdu.h"
#include "vb.h"
#include "octet.h"
#include "snmpmsg.h"
#include "ip.h"
#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>
#include <sys/socket.h>
#include <sys/time.h>
#include <netinet/in.h>
#include <netdb.h>
#include <pthread.h>

class SNMPResult:public Vb {
	const SNMPResult *next;

	public: 
		inline const SNMPResult *getNext() const { return next; }

		SNMPResult(const Vb &vb);
		SNMPResult(const Vb *vb);
		SNMPResult(const Vb &vb, const SNMPResult *next);
		SNMPResult(const Vb *vb, const SNMPResult *next);
		inline ~SNMPResult() {}
};


class SNMP {

	int current_rid;
	pthread_mutex_t mtx_rid;

	const unsigned int snmpengine(const unsigned int idconn,
		Pdu *pdu,
		const unsigned int version,
		const char *community,
		const unsigned int timeout,
		const unsigned int retry);

	public:

		SNMP();

		const unsigned int snmpopen(const IP *ip);
		void snmpclose(const unsigned int idconn);

		const SNMPResult *snmpget(const unsigned int idconn, 
			const Oid *oid, 
			const unsigned int version, 
			const char *community, 
			const unsigned int timeout, 
			const unsigned int retry) ;

		const SNMPResult *snmpwalk(const unsigned int idconn, 
			const Oid *oid, 
			const unsigned int version, 
			const char *community,
			const unsigned int timeout, 
			const unsigned int retry, 
			const SNMPResult *prev);

		const SNMPResult *snmpset(const unsigned int idconn, 
			const Vb *vb, 
			const unsigned int version, 
			const char * community, 
			const unsigned int timeout, 
			const unsigned int retry);
};

class SNMPResults {

	SNMPResult const * ptr;
	SNMPResult const * current;

	public:

		SNMPResults &operator=(SNMPResult const *res) {
			if (ptr != NULL)
				delete ptr; 
			ptr=res; 
			current = NULL; 
			return *this;
		}

		SNMPResults(SNMPResult const *res):ptr(res),current(NULL) {}

		inline ~SNMPResults() {
			SNMPResult const *p = ptr, *next;
			while (p != NULL) {
				next=p->getNext();
				delete p;
				p=next;
			}
		}

		inline SNMPResult const *getNext() { 
			if (current == NULL)
				current=ptr; 
			else
				current=current->getNext(); 
			return current; 
		}
};


#endif /* __CAMEMBERT_H */
