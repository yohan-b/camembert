/* ========================================================

Camembert Project
Alban FERON, 2007

SNMPObject class.
Repris (et simplifié) de Kindmana, par Aurélien Méré

======================================================== */

#include "snmpobject.h"
#include "pgdb.h"

unsigned int versions[NB_VERSIONS] = {2, 1};

SNMPObject::SNMPObject(SNMP *snmp, const char* community, IPList* lip) {
	this->lip = lip;
	this->snmp = snmp;
	this->community = new char[strlen(community)+1];
	strcpy(this->community, community);
	snmpid = 0;
	status = STATUS_UNTESTED;
	version = -1;
	by_vlan = SNMP_VLANUNKNOWN;
}

SNMPObject::SNMPObject(SNMP *snmp, const char* community, IP* ip) {
	this->lip = new IPList();
	this->lip->addIP(ip, DBSTATUS_NEW);
	this->snmp = snmp;
	this->community = new char[strlen(community)+1];
	strcpy(this->community, community);
	snmpid = 0;
	status = STATUS_UNTESTED;
	version = -1;
	by_vlan = SNMP_VLANUNKNOWN;
}

SNMPObject::~SNMPObject() {
	if(lip)
		delete lip;
	delete[] community;
}

const unsigned int SNMPObject::snmpopen() {
	if(status == STATUS_MANAGEABLE && lip->getIP())
		snmpid = snmp->snmpopen(lip->getIP());
	return (snmpid != 0);
}

const unsigned int SNMPObject::snmpclose() {
	if(snmpid)
		snmp->snmpclose(snmpid);
	snmpid = 0;
	return 1;
}

const SNMPResult *SNMPObject::snmpwalk(const Oid *oid, const unsigned int vlan, const SNMPResult *sres)
{
	int ix_vers=0;
	const SNMPResult *res = sres;
	char commu[128];

	if ((status == STATUS_MANAGEABLE) && (snmpid>0))
	{
		ix_vers = version;
		if (vlan != 0)
		{
			if (by_vlan != SNMP_VLANUNSUPPORTED)
			{
				sprintf(commu, "%s@%d", community, vlan);
				res = snmp->snmpwalk(snmpid, oid, ix_vers, commu, TIMEOUT, RETRY, sres);

				if (res!=NULL && (by_vlan != SNMP_VLANSUPPORTED))
					by_vlan = SNMP_VLANSUPPORTED;
				else if (by_vlan == SNMP_VLANUNKNOWN)
					by_vlan = SNMP_VLANUNSUPPORTED;
			}
		}
		else
			res = snmp->snmpwalk(snmpid, oid, ix_vers, community, TIMEOUT, RETRY, sres);
	}

	return(res);
}

const SNMPResult *SNMPObject::snmpwalk(const Oid *oid) { return this->snmpwalk(oid, 0, NULL); }
const SNMPResult *SNMPObject::snmpwalk(const Oid *oid, const unsigned int vlan) { return this->snmpwalk(oid, vlan, NULL); }

const unsigned int SNMPObject::snmpcheck() {

	int ix_vers=0;
	unsigned int id;
	const SNMPResult *res;
	Oid oid("1.3.6.1.2.1.1.5.0"); /* RFC 1213 -> sysName (hostname) */

	if (status == STATUS_UNTESTED && lip->getIP()) {

		status = STATUS_TESTING;

		// Check default IP with default community

		for (ix_vers=0; ix_vers<NB_VERSIONS; ix_vers++) {
			id = snmp->snmpopen(lip->getIP());
			res = snmp->snmpget(id, &oid, versions[ix_vers], community, TIMEOUT, RETRY);
			snmp->snmpclose(id);

			if (res != NULL) {
				delete res;
				version = ix_vers;
				status = STATUS_MANAGEABLE;
				lip->setDBstatus(DBSTATUS_UPDATED);
				return 1;
			}
		}

		// Check other IPs

		for(IPList *l=lip->getNext(); l!=NULL; l=l->getNext()) {
			for (ix_vers=0; ix_vers<NB_VERSIONS; ix_vers++) {
				id = snmp->snmpopen(l->getIP());
				res = snmp->snmpget(id, &oid, versions[ix_vers], community, TIMEOUT, RETRY);
				snmp->snmpclose(id);

				if (res != NULL) {
					delete res;
					version = ix_vers;
					status = STATUS_MANAGEABLE;
					l->setDBstatus(DBSTATUS_UPDATED);
					lip->setFirst(l);
					return 1;
				}
			}
		}

		status = STATUS_NOTMANAGEABLE;
		return 0;
	}

	return (status == STATUS_MANAGEABLE);
}

const SNMPResult *SNMPObject::multiplesnmpwalk(const unsigned int nboid, const unsigned int vlan, ...) {

	va_list varptr;
	const SNMPResult *r = NULL;
	const Oid *oid;
	unsigned int i;

	va_start(varptr, vlan);
	for (i=0; i<nboid; ++i) {
		oid = va_arg(varptr, const Oid *);
		r = this->snmpwalk(oid, vlan, r);
	}

	va_end(varptr);
	return(r);
}

const SNMPResult *SNMPObject::snmpget(const Oid *oid)
{
	if ((status == STATUS_MANAGEABLE) && (snmpid>0))
		return snmp->snmpget(snmpid, oid, versions[version], community, TIMEOUT, RETRY);

	return NULL;
}

const SNMPResult *SNMPObject::snmpset(const Oid *oid, const char *str)
{
	Vb vb;

	if ((status == STATUS_MANAGEABLE) && (snmpid>0)) {
		vb.set_value(str);
		vb.set_oid(*oid);
		return snmp->snmpset(snmpid, &vb, versions[version], community, TIMEOUT, RETRY);
	}

	return NULL;
}

const SNMPResult *SNMPObject::snmpset(const Oid *oid, const SnmpSyntax &stx) {

	Vb vb(*oid);

	if ((status == STATUS_MANAGEABLE) && (snmpid>0)) {
		vb.set_value(stx);
		return snmp->snmpset(snmpid, &vb, versions[version], community, TIMEOUT, RETRY);
	}

	return NULL;
}


const SNMPResult *SNMPObject::snmpset(const Oid *oid, const int value) {

	Vb vb;

	if ((status == STATUS_MANAGEABLE) && (snmpid>0)) {
		vb.set_value(value);
		vb.set_oid(*oid);
		return snmp->snmpset(snmpid, &vb, versions[version], community, TIMEOUT, RETRY);
	}

	return NULL;
}

void SNMPObject::addIP(IP *const ip, unsigned int indb) const {
	lip->addIP(ip, indb);
}

const int SNMPObject::getVersion() const {
	return version;
}

const int SNMPObject::getManageableStatus() const {
	return status;
}

IPList* SNMPObject::getIPList() const {
	return lip;
}

const char* SNMPObject::getCommunity() const {
	return community;
}
