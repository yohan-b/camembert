/* ========================================================

Camembert Project
Alban FERON, 2007

IP et IPNetmask repris de Kindmana, par Aurélien Méré

======================================================== */

#include "ip.h"

// --------------------------------------
// Class IP
// --------------------------------------

IP::IP() {
	strcpy(ip, "0.0.0.0");
	ipint = 0;
}


IP::IP(char const * const ip) { this->setIP(ip); }
IP::IP(unsigned int const ip) { this->ipint = ip; this->computestring(); }
IP::IP(IP const &ip) { this->ipint = ip.getIPint(); this->computestring(); }
IP::IP(IP const * const ip) { this->ipint = ip->getIPint(); this->computestring(); }

void IP::computestring() {
	sprintf(this->ip, "%d.%d.%d.%d", ((ipint/16777216)%256), (ipint/65536)%256, (ipint/256)%256, ipint%256);
}

IP &IP::operator = (char const * const ip) {
	this->setIP(ip);
	return(*this);  
}

IP &IP::operator = (unsigned int const ip) {
	this->ipint = ip;
	this->computestring();
	return(*this);
}

IP &IP::operator = (IP const &ip) {
	this->ipint = ip.getIPint();
	strcpy(this->ip, ip.getIPstr());
	return(*this);
}

char const *		IP::getIPstr() const						{ return(ip); }
unsigned int const	IP::getIPint() const						{ return (ipint); }
bool const			IP::operator==(IP const &ip) const			{ return (ip.getIPint() == this->ipint); }
bool const			IP::operator==(char const * const ip) const	{ return (!strcmp(this->ip, ip)); }
bool const			IP::operator==(unsigned int const ip) const	{ return (ip == this->ipint); }
bool const			IP::operator==(IP const * const ip) const	{ return (ip->getIPint() == this->ipint); }
bool const			IP::operator!=(IP const &ip) const			{ return (ip.getIPint()!=this->ipint); }
bool const			IP::operator!=(char const * const ip) const	{ return (strcmp(this->ip, ip)); }
bool const			IP::operator!=(unsigned int const ip) const	{ return (ip != this->ipint); }
bool const			IP::operator!=(IP const * const ip) const	{ return (ip->getIPint() != this->ipint); }
unsigned int const	IP::operator &(IP const &ip) const			{ return (this->ipint & ip.getIPint()); }
unsigned int const	IP::operator &(IP const * const ip) const	{ return (this->ipint & ip->getIPint()); }

IP &IP::operator &= (const IP &ip) {
	this->ipint &= ip.getIPint();
	this->computestring();
	return(*this);
}

IP &IP::operator &= (const IP *ip) {
	this->ipint &= ip->getIPint();
	this->computestring();
	return(*this);
}

void IP::setIP(char const * ip) {
	unsigned int ipi[4];
	int i=0;
	const char *ip2 = ip;

	this->ipint = 0;
	this->ip[0] = 0;

	while (i<4 && ip!=NULL) {
		if (*ip != 0) {
			if ((ipi[i]=atoi(ip))>255) 
				return;
			if ((ip=strchr(ip, '.')) != NULL) {
				ip+=1;
				i++;
			}
		}
	}

	if ((strlen(ip2) < IP_LENGTH) && (i==3)) {
		strcpy(this->ip, ip2);
		this->ipint = ipi[0]*16777216+ipi[1]*65536+ipi[2]*256+ipi[3];
	}

	return;
}

// ----------------------------------------
// Class IPNetmask
// ----------------------------------------
IPNetmask::IPNetmask() {
	smallmask = 0; 
}

IPNetmask::IPNetmask(char const * const ip) {
	int i=31;
	unsigned int mask;

	netmask = ip;
	mask = netmask.getIPint();

	smallmask=0;

	while (i>=0) {
		if ((mask & (1 << i)) != 0) {
			smallmask++;
			i--;
		} else
			i=-1;
	}
}

IPNetmask::IPNetmask(unsigned int const mask) {
	unsigned int newmask = 0, i;

	for (i=0; i<mask; i++)
		newmask += (1 << (31-i));
	netmask = newmask;
	smallmask = mask;
}

unsigned int const IPNetmask::getMask() const {
	return netmask.getIPint();
}

char const * IPNetmask::getMaskStr() const {
	return netmask.getIPstr();
}

unsigned int const IPNetmask::getMaskSmall() const {
	return smallmask;
}

// -------------------------------------------
// Class IPList
// -------------------------------------------

IPList::IPList() {
	_current = NULL;
	_next = NULL;
	_dbStatus = DBSTATUS_UNKNOWN;
}

void IPList::addIP(IP *const ip, unsigned int dbStatus) {
	if(!ip)
		return;

	// Si le pointeur sur l'IP courante est nulle, on met l'IP dedans
	if(!_current) {
		_current = ip;
		setDBstatus(dbStatus);
		return;
	}

	// Sinon on rajoute l'IP à la suite
	IPList *lst = this;
	while(lst->_next)
		lst = lst->_next;
	lst->_next = new IPList();
	lst->_next->_current = ip;
	lst->_next->setDBstatus(dbStatus);
}

bool IPList::isIPinList(const IP *ip) const {
	// L'IP passée est nulle ou il n'y a pas d'IP courante, pas la peine d'aller plus loin,
	// on considère que d'IP passée n'est pas dans la liste
	if(!ip || !_current)
		return false;

	// On parcourt la liste à la recherche d'une même IP et on retourne VRAI si on trouve.
	for(const IPList *lst=this; lst!=NULL; lst=lst->getNext()) {
		if(lst->getIP()->getIPint() == ip->getIPint())
			return true;
	}

	return false;
}

void IPList::foundIP(const IP *ip) {
	if(!ip || !_current)
		return;

	for(IPList *lst=this; lst!=NULL; lst=lst->getNext()) {
		if(lst->getIP()->getIPint() == ip->getIPint()) {
			lst->setDBstatus(DBSTATUS_UPDATED);
			return;
		}
	}
}

void IPList::setFirst(IPList *lip) {
	IP *tmpI = lip->_current;
	unsigned int tmpD = lip->_dbStatus;

	lip->_current = this->_current;
	lip->_dbStatus = this->_dbStatus;

	this->_current = tmpI;
	this->_dbStatus = tmpD;
}

IPList::~IPList() {
	if(_current)
		delete _current;
	if(_next)
		delete _next;
}
