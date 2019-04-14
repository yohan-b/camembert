#include "vlan.h"

VlanList::VlanList() {
	vlans = NULL;
	curr = NULL;
}

VlanList::~VlanList() {
	curr = vlans;
	while(curr) {
		vlans = vlans->next;
		delete curr;
		curr = vlans;
	}
}

void VlanList::addVlan(unsigned short int vlan) {
	lstVLANs *newptr, *prev, *ptr;

	for(ptr=vlans, prev=NULL; ptr!=NULL; ptr=ptr->next) {
		if (ptr->vlan == vlan) return;
		if (ptr->vlan < vlan) break;
		prev = ptr;
	}

	newptr = new lstVLANs;
	newptr->vlan = vlan;
	if (prev == NULL) {
		newptr->next = vlans;
		vlans = newptr;
		return;
	}

	newptr->next = prev->next;
	prev->next = newptr;
}

bool VlanList::getNext(unsigned short int &vlan) const {
	if(!curr)
		curr = vlans;
	else
		curr = curr->next;

	if(!curr)
		return false;

	vlan = curr->vlan;
	return true;
}
