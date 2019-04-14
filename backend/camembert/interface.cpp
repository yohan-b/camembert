#include "interface.h"

Interface::Interface(unsigned int dbID, unsigned short int ifNum) {
	_dbID = dbID;
	_number = ifNum;

	_description = NULL;
	_name = NULL;
	_address[0] = 0;
	_fdb = NULL;

	oldLinks = NULL;
	nbOldLinks = 0;

	vlan = 0;
	voiceVlan = 0;
	nativeVlan = 0;

	ifType = 0;
	speed = 0;
	adminStatus = 0;
	operStatus = 0;
	ifDot1d = 0;

	moduleNum = 0;
	portNum = 0;
	portDot1d = 0;
	spanningTree = 0;

	_dstDevice = NULL;

	portSecEnabled = 0;
	portSecStatus = 0;
	maxMacCount = 0;
	currMacCount = 0;
	violationCount = 0;
	_lastSecAddr[0] = 0;
	stickyEnabled = 0;

	_actions = NULL;
}

Interface::~Interface() {
	if(_description)
		delete[] _description;
	if(_name)
		delete[] _name;
	if(oldLinks)
		delete[] oldLinks;
	if(_fdb)
		delete _fdb;
	if(_dstDevice) {
		dstDevice_t* dd = _dstDevice;
/*		while(_dstDevice->next) {
			dd = _dstDevice->next;
			while(dd->next)
				dd = dd->next;
			delete dd->dstIfName;
			delete dd;
		}*/
		delete _dstDevice;
	}
	if(_actions)
		delete _actions;
}

void Interface::setName(const char *name) {
	if(_name)
		delete[] _name;
	_name = new char[strlen(name)+1];
	strcpy(_name, name);
}

void Interface::setAddress(const char *addr) {
	strcpy(_address, addr);
}

void Interface::setDescription(const char *descr) {
	if(_description)
		delete[] _description;
	_description = new char[strlen(descr)+1];
	strcpy(_description, descr);

	char *c;
	while(c = strchr(_description, '\''))
		*c = ' ';
}

void Interface::addForwardingDBEntry(const char *mac, unsigned short int vlan, unsigned short int type) {
	if(!_fdb)
		_fdb = new ForwardingDatabase(mac, vlan, type);
	else
		_fdb->addEntry(mac, vlan, type);
}

void Interface::addDistantDevice(const char* ifName, void const* device) {
	if(!_dstDevice) {
		_dstDevice = new dstDevice_t;
		_dstDevice->dstIfName = new char[strlen(ifName)+1];
		strcpy(_dstDevice->dstIfName, ifName);
		_dstDevice->distantDevice = device;
		_dstDevice->next = NULL;
	}
	else {
		dstDevice_t* dd = _dstDevice;
		while(dd->next)
			dd = dd->next;
                dd->next = new dstDevice_t;
                dd->next->dstIfName = new char[strlen(ifName)+1];
                strcpy(dd->next->dstIfName, ifName);
                dd->next->distantDevice = device;
                dd->next->next = NULL;
	}
}

void Interface::setLastMacAddr(const char *addr) {
	strcpy(_lastSecAddr, addr);
}

void Interface::addAction(unsigned short int num, const char *opt) {
	if(!_actions)
		_actions = new Action(num, opt);
	else
		_actions->addAction(num, opt);
}

InterfaceList::InterfaceList(Interface *i, unsigned int dbStatus) {
	_current = i;
	_next = NULL;
	_dbStatus = dbStatus;
}

InterfaceList::~InterfaceList() {
	delete _current;
	if(_next)
		delete _next;
}

void InterfaceList::addInterface(Interface *i, unsigned int dbStatus) {
	InterfaceList *ifl = this;
	// Va jusqu'à la fin de la liste
	while(ifl->_next)
		ifl = ifl->_next;
	// Ajoute l'interface à la fin
	ifl->_next = new InterfaceList(i, dbStatus);
}

Interface *InterfaceList::getInterfaceById(unsigned int id) const {
	const InterfaceList *ifl;
	Interface *i;

	// Parcourt la liste des interfaces
	for(ifl=this; ifl!=NULL; ifl=ifl->getNext()) {
		i = ifl->getInterface();
		// retourne l'interface courante si son ifNum correspond à celui passé
		if(i->getIfNumber() == id)
			return i;
	}

	return NULL;
}

Interface *InterfaceList::getInterfaceByDot1d(unsigned int id) const {
	const InterfaceList *ifl;
	Interface *i;

	// Parcourt la liste des interfaces
	for(ifl=this; ifl!=NULL; ifl=ifl->getNext()) {
		i = ifl->getInterface();
		// retourne l'interface courante si son ifNum correspond à celui passé
		if(i->ifDot1d == id)
			return i;
	}

	return NULL;
}

Interface *InterfaceList::getInterfaceByPort(unsigned int module, unsigned int port) const {
	const InterfaceList *ifl;
	Interface *i;

	// Parcourt la liste des interfaces
	for(ifl=this; ifl!=NULL; ifl=ifl->getNext()) {
		i = ifl->getInterface();
		// retourne l'interface courante si son ifNum correspond à celui passé
		if(i->moduleNum == module && i->portNum == port)
			return i;
	}

	return NULL;
}

Interface *InterfaceList::getInterfaceByPortDot1d(unsigned int id) const {
	const InterfaceList *ifl;
	Interface *i;

	// Parcourt la liste des interfaces
	for(ifl=this; ifl!=NULL; ifl=ifl->getNext()) {
		i = ifl->getInterface();
		// retourne l'interface courante si son ifNum correspond à celui passé
		if(i->portDot1d == id)
			return i;
	}

	return NULL;
}

/*Interface *InterfaceList::getInterfaceByDbId(unsigned int dbId) const {
	const InterfaceList *ifl;
	Interface *i;

	for(ifl=this; ifl!=NULL; ifl=ifl->getNext()) {
		i = ifl->getInterface();
		if(i->getID() == dbId)
			return i;
	}

	return NULL;
} */

void InterfaceList::foundInterface(unsigned int id) {
	InterfaceList *ifl;

	for(ifl=this; ifl!=NULL; ifl=ifl->getNext()) {
		if(ifl->getInterface()->getIfNumber() == id) {
			ifl->setDBstatus(DBSTATUS_UPDATED);
			break;
		}
	}
}

