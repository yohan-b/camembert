#include "arp.h"

ARPCache::ARPCache() {
	mac[0] = 0;
	ip = NULL;
	next = NULL;
}

ARPCache::ARPCache(const char *macaddr, const char *i) {
	strcpy(mac, macaddr);
	ip = new IP(i);
	next = NULL;
}

ARPCache::~ARPCache() {
	if(ip)
		delete ip;
	if(next)
		delete next;
}

void ARPCache::addEntry(const char *mac, const char *ip) {
	ARPCache *arp = this, *prev = NULL;
	while(arp) {
		if(!strcmp(mac, arp->mac) && !strcmp(ip, arp->ip->getIPstr()))
			return;
		prev = arp;
		arp = arp->next;
	}
	prev->next = new ARPCache(mac, ip);
}
