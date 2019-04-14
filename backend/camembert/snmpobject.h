/* ========================================================

Camembert Project
Alban FERON, 2007

SNMPObject class.
Repris (et simplifié) de Kindmana, par Aurélien Méré

======================================================== */

#ifndef __CAMEMBERT_SNMPOBJECT_H
#define __CAMEMBERT_SNMPOBJECT_H

#include "snmp.h"
#include "ip.h"
#include <stdarg.h>

#define STATUS_UNTESTED			-1
#define STATUS_NOTMANAGEABLE	0
#define STATUS_MANAGEABLE		1
#define STATUS_TESTING			2

#define SNMP_VLANUNKNOWN		-1
#define SNMP_VLANSUPPORTED		1
#define SNMP_VLANUNSUPPORTED	0

#define RETRY 3
#define NB_VERSIONS 2
#define TIMEOUT 2500

class SNMPObject {
	protected:
		SNMP *snmp;
		unsigned int snmpid;

		int status;
		int version;
		int by_vlan;

		IPList* lip;
		char* community;

		const SNMPResult *snmpwalk(const Oid *oid, const unsigned int vlan, const SNMPResult *sres);

	public:

		SNMPObject(SNMP *snmp, const char* community, IPList* lip);
		SNMPObject(SNMP *snmp, const char* community, IP* ip);
		~SNMPObject();

		const unsigned int snmpopen();
		const unsigned int snmpclose();
		const unsigned int snmpcheck();

		const SNMPResult *snmpwalk(const Oid *oid);
		const SNMPResult *snmpwalk(const Oid *oid, const unsigned int vlan);

		const SNMPResult *multiplesnmpwalk(const unsigned int nboid, ...);
		const SNMPResult *multiplesnmpwalk(const unsigned int nboid, const unsigned int vlan, ...);

		const SNMPResult *snmpget(const Oid *oid);
		const SNMPResult *snmpset(const Oid *oid, const char *str);
		const SNMPResult *snmpset(const Oid *oid, const int value);
		const SNMPResult *snmpset(const Oid *oid, const SnmpSyntax &stx);

		const int getVersion() const;
		const int getManageableStatus() const;

		void addIP(IP *const ip, unsigned int indb) const;
		IPList* getIPList() const;
		const char* getCommunity() const;
};

#endif /* __CAMEMBERT_H */
