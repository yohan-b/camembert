#ifndef __CAMEMBERT_INTERFACE_H
#define __CAMEMBERT_INTERFACE_H

#include <string.h>
#include "pgdb.h"
#include "fdb.h"
#include "action.h"

typedef struct dstDevice_s {
	char* dstIfName;
	void const* distantDevice;
	struct dstDevice_s* next;
} dstDevice_t;

class Interface {
	private:
		unsigned int _dbID;
		unsigned short int _number;
		char *_name;
		char _address[24];
		char *_description;

		ForwardingDatabase *_fdb;
		char _lastSecAddr[24];

		dstDevice_t* _dstDevice;

		Action *_actions;

	public:
		unsigned int ifType;
		unsigned int speed;
		unsigned short int adminStatus;
		unsigned short int operStatus;
		unsigned int ifDot1d;
		unsigned short int spanningTree;

		int vlan, voiceVlan, nativeVlan;

		unsigned int moduleNum, portNum, portDot1d;

		unsigned int *oldLinks;
		unsigned short int nbOldLinks;

		unsigned short int portSecEnabled;
		unsigned short int portSecStatus;
		unsigned short int maxMacCount;
		unsigned short int currMacCount;
		unsigned short int violationCount;
		unsigned short int stickyEnabled;

		Interface(unsigned int dbID, unsigned short int ifNum);
		~Interface();

		unsigned int getID() const { return _dbID; }
		unsigned short int getIfNumber() const { return _number; }
		const char *getName() const { return _name; }
		const char *getAddress() const { return _address; }
		const char *getDescription() const { return _description; }
		ForwardingDatabase *getForwardingDB() const { return _fdb; }
		dstDevice_t* getDistantDevice() const { return _dstDevice; }
		const char *getLastMacAddr() const { return _lastSecAddr; }
		Action *getActions() const { return _actions; }

		void setName(const char *name);
		void setAddress(const char *addr);
		void setDescription(const char *descr);
		void addForwardingDBEntry(const char *mac, unsigned short int vlan, unsigned short int type);
		void addDistantDevice(const char* ifName, void const* device);
		void setLastMacAddr(const char *addr);
		void addAction(unsigned short int num, const char *opt);
};

class InterfaceList: public DBObject {
	private:
		InterfaceList *_next;
		Interface *_current;

	public:
		InterfaceList(Interface *i, unsigned int dbStatus);
		~InterfaceList();

		void addInterface(Interface *i, unsigned int dbStatus);
		Interface *getInterface() const { return _current; }
		InterfaceList *getNext() const { return _next; }

		Interface *getInterfaceById(unsigned int id) const;
		Interface *getInterfaceByDot1d(unsigned int id) const;
		Interface *getInterfaceByPort(unsigned int module, unsigned int port) const;
		Interface *getInterfaceByPortDot1d(unsigned int id) const;
		//Interface *getInterfaceByDbId(unsigned int dbId) const;

		void foundInterface(unsigned int id);
};

#endif /* __CAMEMBERT_INTERFACE_H */

