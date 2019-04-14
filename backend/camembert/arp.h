#ifndef __CAMEMBERT_ARP_H
#define __CAMEMBERT_ARP_H

#include "ip.h"

class ARPCache {
	private:
		char mac[24];
		IP *ip;
		ARPCache *next;

	public:
		ARPCache();
		ARPCache(const char *mac, const char *ip);
		~ARPCache();
		void addEntry(const char *mac, const char *ip);

		const char *getMAC() const { return mac; }
		IP *getIP() const { return ip; }
		ARPCache *getNext() const { return next; }
};

#endif /* __CAMEMBERT_H */
