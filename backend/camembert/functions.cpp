#include <regex.h>
#include "functions.h"
#include "vlan.h"

#define ACTION_SHUTDOWN 0
#define ACTION_NO_SHUTDOWN 1
#define ACTION_DESCRIPTION 2
#define ACTION_MAX_MAC_COUNT 3
#define ACTION_NO_STICKY 4
#define ACTION_VLAN 7

#define OID_DESCRIPTION "1.3.6.1.2.1.31.1.1.1.18"
#define OID_ADMINSTATUS "1.3.6.1.2.1.2.2.1.7"
#define OID_MAXMACCOUNT "1.3.6.1.4.1.9.9.315.1.2.1.1.3"
#define OID_SECUREMACROWSTATUS "1.3.6.1.4.1.9.9.315.1.2.2.1.4"
#define OID_ACCESSVLAN	"1.3.6.1.4.1.9.9.68.1.2.2.1.2"

Oid OID_ifName		("1.3.6.1.2.1.2.2.1.2");
Oid OID_ifType		("1.3.6.1.2.1.2.2.1.3");
Oid OID_ifAddress	("1.3.6.1.2.1.2.2.1.6");
Oid OID_ifDescription	(OID_DESCRIPTION);
Oid OID_ifSpeed		("1.3.6.1.2.1.2.2.1.5");
Oid OID_ifAdminStatus	(OID_ADMINSTATUS);
Oid OID_ifOperStatus	("1.3.6.1.2.1.2.2.1.8");

Oid OID_ifAccessVlan	(OID_ACCESSVLAN);
Oid OID_ifVoiceVlan	("1.3.6.1.4.1.9.9.68.1.5.1.1.1");
Oid OID_ifNativeVlan	("1.3.6.1.4.1.9.9.46.1.6.1.1.5");

Oid OID_ifDot1dPort("1.3.6.1.2.1.17.1.4.1.2");

Oid OID_PortInterface		("1.3.6.1.4.1.9.9.87.1.4.1.1.25");
Oid OID_PortDot1D		("1.3.6.1.4.1.9.9.87.1.4.1.1.35");
Oid OID_SpanningTreePortFast	("1.3.6.1.4.1.9.9.87.1.4.1.1.36");

Oid OID_ARPCache("1.3.6.1.2.1.3.1.1.2");

Oid OID_ForwardingInterface	("1.3.6.1.2.1.17.4.3.1.2");
Oid OID_ForwardingType		("1.3.6.1.2.1.17.4.3.1.3");

Oid OID_PortSecurityEnabled	("1.3.6.1.4.1.9.9.315.1.2.1.1.1");
Oid OID_PortSecureStatus	("1.3.6.1.4.1.9.9.315.1.2.1.1.2");
Oid OID_MaximumSecureMacCount	(OID_MAXMACCOUNT);
Oid OID_CurrentSecureMacCount	("1.3.6.1.4.1.9.9.315.1.2.1.1.4");
Oid OID_ViolationCount		("1.3.6.1.4.1.9.9.315.1.2.1.1.9");
Oid OID_SecureLastMac		("1.3.6.1.4.1.9.9.315.1.2.1.1.10");
Oid OID_StickyEnabled		("1.3.6.1.4.1.9.9.315.1.2.1.1.15");

Oid OID_SecureMacType("1.3.6.1.4.1.9.9.315.1.2.2.1.2");

// ==================================
// Fonctions sur les interfaces
// ==================================

// set_admin_status
// Blabla commentaire
void set_admin_status(Materiel *m, unsigned int ifNum, bool newStatus) {
	//I = m->getInterface
	char oidC[64];
	oidC[0] = 0;
	sprintf(oidC, OID_ADMINSTATUS".%d", ifNum);
	Oid OID_AdminStatusSetting(oidC);
	m->snmpset(&OID_AdminStatusSetting, 2-newStatus);
}

void set_description(Materiel *m, unsigned int ifNum, const char *newDesc) {
	char oidC[64];
	oidC[0] = 0;
	sprintf(oidC, OID_DESCRIPTION".%d", ifNum);
	Oid OID_DescriptionSetting(oidC);
	m->snmpset(&OID_DescriptionSetting, newDesc);
}

void set_max_mac_count(Materiel *m, unsigned int ifNum, unsigned int newMax) {
	char oidC[64];
	oidC[0] = 0;
	sprintf(oidC, OID_MAXMACCOUNT".%d", ifNum);
	Oid OID_MaximumSecureMacCountSetting(oidC);
	m->snmpset(&OID_MaximumSecureMacCountSetting, newMax);
}

void delete_sticky(Materiel *m, unsigned int ifNum, const char *macToDelete) {
	char oidC[64];
	oidC[0] = 0;

	char *c;
	char mac[24];
	strcpy(mac, macToDelete);
	int intmac[6], i = 0;
	c = strtok(mac, ":");
	while(c) {
		intmac[i++] = strtol(c, NULL, 16);
		c = strtok(NULL, ":");
	}
	// ugly workaround for 2950 switch, to be removed after ios upgrade 
	if (ifNum < 100) {
		sprintf(oidC, OID_SECUREMACROWSTATUS".%d.%d.%d.%d.%d.%d.%d", ifNum, intmac[0], intmac[1], intmac[2], intmac[3], intmac[4], intmac[5]);
	} 
	else {
		sprintf(oidC, "1.3.6.1.4.1.9.9.315.1.2.3.1.5.%d.%d.%d.%d.%d.%d.%d.%d", ifNum, intmac[0], intmac[1], intmac[2], intmac[3], intmac[4], intmac[5], 964);
	}
	Oid OID_SecureMacAddrRowStatus(oidC);
	m->snmpset(&OID_SecureMacAddrRowStatus, 6);
}

void set_vlan(Materiel *m, unsigned int ifNum, unsigned int newVlan) {
	char oidC[64];
	oidC[0] = 0;
	sprintf(oidC, OID_ACCESSVLAN".%d", ifNum);
	Oid OID_ifAccessVlanSetting(oidC);
	m->snmpset(&OID_ifAccessVlanSetting, newVlan);
}

void apply_actions(Materiel *m) {
	InterfaceList *ifl;
	Interface *I;
	Action *a;
	unsigned short int n;

	for(ifl=m->getInterfaces(); ifl!=NULL; ifl=ifl->getNext()) {
		I = ifl->getInterface();
		for(a=I->getActions(); a!=NULL; a=a->getNext()) {
			switch(n = a->getNum()) {
				case ACTION_SHUTDOWN:
				case ACTION_NO_SHUTDOWN: set_admin_status(m, I->getIfNumber(), n); break;
				case ACTION_DESCRIPTION: set_description(m, I->getIfNumber(), a->getOption()); break;
				case ACTION_MAX_MAC_COUNT: set_max_mac_count(m, I->getIfNumber(), atoi(a->getOption())); break;
				case ACTION_NO_STICKY: delete_sticky(m, I->getIfNumber(), a->getOption()); break;
				case ACTION_VLAN: set_vlan(m, I->getIfNumber(), atoi(a->getOption())); break;
			}
		}
	}
}

// get_interfaces_infos_base
// Va chercher le nom, type, l'adresse MAC, la vitesse et le statut d'un équipement donné
// Crée les interfaces si elles n'existent pas
void get_interfaces_infos_base(Materiel *m) {
	const SNMPResult *r;
	unsigned short int ifNum;
	Interface *i;
	int val;
	unsigned long value;

	SNMPResults res(m->multiplesnmpwalk(6, 0,
		&OID_ifOperStatus,
		&OID_ifAdminStatus,
		&OID_ifSpeed,
		&OID_ifAddress,
		&OID_ifType,
		&OID_ifName));
	// Parcourt les résultats du SNMPWalk
	while(r = res.getNext()) {
		const Oid &o = r->get_oid();
		ifNum = o[10];
		i = m->getInterfaceById(ifNum);
		// Si l'interface n'existe pas sur l'équipement...
		if(!i) {
			pthread_mutex_lock(&mtx_materiels);
			// On la crée...
			i = new Interface(++maxIdInterface, ifNum);
			pthread_mutex_unlock(&mtx_materiels);
			// et on l'ajoute au matériel.
			m->addInterface(i, DBSTATUS_NEW);
		}
		switch(o[9]) {
			// Blabla mise à jour des infos sur l'interface.
			case 2: i->setName(r->get_printable_value()); break;
			case 3: r->get_value(val); i->ifType = val; break;
			case 6: i->setAddress(r->get_printable_value()); break;
			case 5: r->get_value(value); i->speed = value; break;
			case 7: r->get_value(val); i->adminStatus = 2-val; break;
			case 8: r->get_value(val); i->operStatus = 2-val; break;
		}

		// On signale qu'on a mis à jour les infos.
		m->getInterfaces()->foundInterface(ifNum);
	}
}

void get_interfaces_dot1d(Materiel *m) {
	const SNMPResult *r;
	int value;
	unsigned short int vlan;
	VlanList vlans;
	Interface *i;

	for(InterfaceList *ifl=m->getInterfaces(); ifl!=NULL; ifl=ifl->getNext()) {
		if((value = ifl->getInterface()->vlan) > 0)
			vlans.addVlan(value);
	}
	vlans.addVlan(0);

	while(vlans.getNext(vlan)) {
		SNMPResults res(m->snmpwalk(&OID_ifDot1dPort, vlan));
		while(r = res.getNext()) {
			const Oid &o = r->get_oid();
			r->get_value(value);
			i = m->getInterfaceById(value);
			if(i)
				i->ifDot1d = o[11];
		}
	}
}

// get_interfaces_description
// Récupère la description des interfaces d'un équipement donné
void get_interfaces_description(Materiel *m) {
	const SNMPResult *r;
	Interface *i;

	SNMPResults res(m->snmpwalk(&OID_ifDescription));
	while(r = res.getNext()) {
		const Oid &o = r->get_oid();
		if((i = m->getInterfaceById(o[11])) != NULL)
			i->setDescription(r->get_printable_value());
	}
}

// get_interfaces_vlan
// Récupère le VLAN et le VLAN voix des interfaces de l'équipement donné
void get_interfaces_vlan(Materiel *m) {
	const SNMPResult *r;
	Interface *i;
	int val;

	SNMPResults res(m->multiplesnmpwalk(2, 0, &OID_ifVoiceVlan, &OID_ifAccessVlan));
	while(r = res.getNext()) {
		const Oid &o = r->get_oid();
		if((i = m->getInterfaceById(o[14])) != NULL) {
			switch(o[10]) {
				case 2: r->get_value(val); i->vlan = val; break;
				case 5: r->get_value(val); i->voiceVlan = val; break;
			}
		}
	}
}

// get_interfaces_trunk
// Vérifie si les interfaces sont en Trunk et récupère le native VLAN
void get_interfaces_trunk(Materiel *m) {
	const SNMPResult *r;
	Interface *i;
	int val;

	SNMPResults res(m->snmpwalk(&OID_ifNativeVlan));
	while(r = res.getNext()) {
		const Oid &o = r->get_oid();
		if((i = m->getInterfaceById(o[14])) != NULL) {
			r->get_value(val);
			i->vlan = -1;
			i->nativeVlan = val;
		}
	}
}

void get_interfaces_c2900(Materiel *m) {
	Interface *i;
	SNMPResult const *r;
	int portnum, modulenum, rx;

	SNMPResults res(m->multiplesnmpwalk(3, 0,
		&OID_SpanningTreePortFast,
		&OID_PortDot1D,
		&OID_PortInterface));

	while (r = res.getNext()) {
		const Oid& o = r->get_oid();

		portnum = o[15];
		modulenum = o[14];
		r->get_value(rx);

		if (o[13] == 25 && ((i = m->getInterfaceById(rx)) != NULL)) {
			i->portNum = portnum;
			i->moduleNum = modulenum;
		}
		else if ((i = m->getInterfaceByPort(modulenum, portnum)) != NULL) {
			switch(o[13]) {
				case 36: i->spanningTree = 2-rx; break;
				case 35: i->portDot1d = rx; break;
			}
		}
	}
}

bool is_mac_address_correct(const char *mac) {
	regex_t preg;
	int match;

	if((regcomp(&preg, "([0-9a-fA-F]{2}[ :-]){5}[0-9a-fA-F]{2}[ :-]?", REG_NOSUB | REG_EXTENDED)) == 0) {
		match = regexec(&preg, mac, 0, NULL, 0);
		regfree(&preg);
		return match == 0;
	}
	return false;
}

void get_interfaces_port_security(Materiel *m) {
	Interface *i;
	SNMPResult const *r;
	int val;
	unsigned long value;

	SNMPResults res(m->multiplesnmpwalk(7, 0,
		&OID_PortSecurityEnabled,
		&OID_PortSecureStatus,
		&OID_MaximumSecureMacCount,
		&OID_CurrentSecureMacCount,
		&OID_ViolationCount,
		&OID_SecureLastMac,
		&OID_StickyEnabled));

	while(r = res.getNext()) {
		const Oid& o = r->get_oid();
		if((i = m->getInterfaceById(o[14])) != NULL) {
			switch(o[13]) {
				case 1: r->get_value(val); i->portSecEnabled = 2-val; break;
				case 2: r->get_value(val); i->portSecStatus = val; break;
				case 3: r->get_value(val); i->maxMacCount = val; break;
				case 4: r->get_value(val); i->currMacCount = val; break;
				case 9: r->get_value(value); i->violationCount = value; break;
				case 10:
					if(is_mac_address_correct(r->get_printable_value()))
						i->setLastMacAddr(r->get_printable_value());
					break;
				case 15: r->get_value(val); i->stickyEnabled = 2-val; break;
			}
		}
	}
}

// get_interfaces_infos
// Va chercher toutes les informations sur les interfaces
void get_interfaces_infos(Materiel *m) {
	get_interfaces_infos_base(m);
	get_interfaces_description(m);
	get_interfaces_trunk(m);
	get_interfaces_vlan(m);
	get_interfaces_dot1d(m);
	get_interfaces_c2900(m);
	get_interfaces_port_security(m);
}

// ==========================================
// Fonction ARP
// ==========================================

void get_arp_infos(Materiel *m) {
	const SNMPResult *r;
	int len, c;
	const char *oid, *pres;

	SNMPResults res(m->snmpwalk(&OID_ARPCache));
	while(r = res.getNext()) {
		oid = r->get_printable_oid();
		pres = r->get_printable_value();

		len = strlen(oid);
		c = 0;
		while(len > 0 && c < 4) {
			if(oid[--len] == '.')
				c++;
		}
		if(c == 4)
			m->addARPEntry(pres, &oid[len+1]);
	}
}

// ===========================================
// Fonction Forwarding Database
// ===========================================

void get_real_fdb_infos(Materiel *m) {
	const SNMPResult *r;
	int i;
	unsigned short int vlan;
	VlanList vlans;
	ForwardingDatabase *fdb = NULL;
	Interface *I;
	char buffer[8], mac[24];
	char hextable[] = "0123456789ABCDEF";

	for(InterfaceList *ifl=m->getInterfaces(); ifl!=NULL; ifl=ifl->getNext()) {
		if((i = ifl->getInterface()->vlan) > 0)
			vlans.addVlan(i);
	}
	//vlans.addVlan(0);

	while(vlans.getNext(vlan)) {
		SNMPResults res(m->multiplesnmpwalk(2, vlan, &OID_ForwardingInterface, &OID_ForwardingType));

		while(r = res.getNext()) {
			const Oid &o = r->get_oid();

			mac[0] = 0;
			for(i=0; i<6; i++) {
				sprintf(buffer, "%c%c:", hextable[o[11+i]/16], hextable[o[11+i]%16]);
				strcat(mac, buffer);
			}
			mac[17] = 0;

			switch(o[10]) {
				case 3:
					r->get_value(i);
					if(i == 3) {
						if(!fdb) {
							fdb = new ForwardingDatabase(mac, vlan, 0);
						}
						else {
							fdb->addEntry(mac, vlan, 0);
						}
					}
					break;

				case 2:
					r->get_value(i);
					if(i > 0) {
						I = m->getInterfaceByDot1d(i);
						if(!I)
							I = m->getInterfaceByPortDot1d(i);
						if(!I)
							I = m->getInterfaceById(i);
						if(I && fdb->hasMAC(mac, vlan) && (I->vlan != -1 || (I->voiceVlan > 0 && I->voiceVlan < 4096)))
							I->addForwardingDBEntry(mac, vlan, 0);
					}
					break;
			}
		}
	}

	if(fdb)
		delete fdb;
}

void get_secure_addresses_infos(Materiel *m) {
	const SNMPResult *r;
	Interface *I;
	char buffer[8], mac[24];
	char hextable[] = "0123456789ABCDEF";
	int val;

	SNMPResults res(m->snmpwalk(&OID_SecureMacType));
	while(r = res.getNext()) {
		const Oid &o = r->get_oid();

		mac[0] = 0;
		for(unsigned short int i=0; i<6; i++) {
			sprintf(buffer, "%c%c:", hextable[o[15+i]/16], hextable[o[15+i]%16]);
			strcat(mac, buffer);
		}
		mac[17] = 0;

		r->get_value(val);
		I = m->getInterfaceById(o[14]);
		if(I)
			I->addForwardingDBEntry(mac, 0, val);
	}
}

void get_fdb_infos(Materiel *m) {
	get_real_fdb_infos(m);
	get_secure_addresses_infos(m);
}

