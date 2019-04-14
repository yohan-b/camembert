#ifndef __CAMEMBERT_FDB_H
#define __CAMEMBERT_FDB_H

#include <string.h>
#include <stdlib.h>

// Bon, c'est pas tres beau, mais on utilise aussi cette classe pour
// les secure Mac addresses (port-security)
class ForwardingDatabase {
	private:
		char mac[24];
		unsigned short int vlan, type;
		ForwardingDatabase *next;

	public:
		ForwardingDatabase(const char *macaddr, unsigned short int vlan, unsigned short int type);
		~ForwardingDatabase();

		bool hasMAC(const char *macaddr, unsigned short int vlan) const;
		void addEntry(const char *macaddr, unsigned short int vlan, unsigned short int type);

		const char *getMAC() const { return mac; }
		unsigned short int getVLAN() const { return vlan; }
		unsigned short int getType() const { return type; }
		ForwardingDatabase *getNext() { return next; }
};

#endif /* __CAMEMBERT_H */

