#ifndef __CAMEMBERT_VLAN_H
#define __CAMEMBERT_VLAN_H

#include <stdlib.h>

typedef struct lstVLANs_s 
{
	unsigned short int vlan;
	struct lstVLANs_s *next;
} lstVLANs;

class VlanList {
	private:
		lstVLANs *vlans;
		mutable lstVLANs *curr;

	public:
		VlanList();
		~VlanList();

		void addVlan(unsigned short int vlan);
		bool getNext(unsigned short int &vlan) const;
};

#endif /* __CAMEMBERT_VLAN_H */
