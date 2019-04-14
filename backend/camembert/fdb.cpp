#include "fdb.h"

ForwardingDatabase::ForwardingDatabase(const char *macaddr, unsigned short int vlan, unsigned short int type) {
	strcpy(mac, macaddr);
	this->vlan = vlan;
	this->type = type;
	next = NULL;
}

ForwardingDatabase::~ForwardingDatabase() {
	if(next)
		delete next;
}

bool ForwardingDatabase::hasMAC(const char *macaddr, unsigned short int vlan) const {
	const ForwardingDatabase *fdb;
	for(fdb=this; fdb!=NULL; fdb=fdb->next) {
		if(vlan == fdb->vlan && !strcmp(fdb->mac, macaddr))
			return true;
	}
	return false;
}

void ForwardingDatabase::addEntry(const char *macaddr, unsigned short int vlan, unsigned short int type) {
	ForwardingDatabase *fdb = this, *prev = NULL;
	while(fdb) {
		if(!strcmp(macaddr, fdb->mac) && vlan == fdb->vlan)
			return;
		prev = fdb;
		fdb = fdb->next;
	}
	prev->next = new ForwardingDatabase(macaddr, vlan, type);

}

