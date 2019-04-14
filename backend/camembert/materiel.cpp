/* =========================================

Camembert Project
Alban FERON, 2007

Représentation d'un équipement matériel et
 liste de matériel.

========================================== */

#include "materiel.h"

Oid OID_Hostname("1.3.6.1.2.1.1.5.0");
Oid OID_OSType	("1.3.6.1.2.1.1.1.0");

// ---------------------------
// Materiel::Constructeurs
// ---------------------------

Materiel::Materiel(unsigned int id, SNMP *snmp, const char* community, IP* ip):
	SNMPObject(snmp, community, ip)
{
	_id = id;
	_type = NULL;
	_name = NULL;
	_osType = NULL;
	_capabilities = 0;
	bTreated = false;
	_dbStatus = DBSTATUS_UNKNOWN;
	_manageable = 0;
	_ifs = NULL;
	_arp = NULL;
}

Materiel::Materiel(unsigned int id, SNMP *snmp, const char* community, IPList* lip):
	SNMPObject(snmp, community, lip)
{
	_id = id;
	_type = NULL;
	_name = NULL;
	_osType = NULL;
	_capabilities = 0;
	bTreated = false;
	_dbStatus = DBSTATUS_UNKNOWN;
	_manageable = 0;
	_ifs = NULL;
	_arp = NULL;
}

// ----------------------------
// Materiel::Destructeur
// ----------------------------

Materiel::~Materiel() {
	if(_name)
		delete[] _name;
	if(_type)
		delete[] _type;
	if(_osType)
		delete[] _osType;
	if(_ifs)
		delete _ifs;
	if(_arp)
		delete _arp;
}

// --------------------------------
// Materiel::Setters
// --------------------------------

void Materiel::setHostName(const char* name) {
	// Si il a déjà un nom, on le vire
	if(_name)
		delete[] _name;
	// On affecte le nom
	_name = new char[strlen(name)+1];
	strcpy(_name, name);
}

void Materiel::setType(const char* type) {
	if(_type)
		delete[] _type;
	_type = new char[strlen(type)+1];
	strcpy(_type, type);
}

void Materiel::setOSType(const char* os) {
	if(_osType)
		delete[] _osType;
	_osType = new char[strlen(os)+1];
	strcpy(_osType, os);

	char *c;
	// Remplace les apostrophes par des espaces sinon ça va bugger lors des modifs de la BDD
	while(c = strchr(_osType, '\''))
		*c = ' ';
}

void Materiel::setCapabilities(unsigned int capabilities) {
	_capabilities = capabilities;

	// Si c'est un téléphone, on dit qu'il a été traité car
	// de toutes façons il est pas manageable
	if(capabilities & CAPABILITY_PHONE) {
		status = STATUS_NOTMANAGEABLE;
		_manageable = MAT_NOT_MANAGEABLE;
		setTreated(true);
	}
}

void Materiel::addInterface(Interface *i, unsigned int dbStatus) {
	if(!_ifs)
		_ifs = new InterfaceList(i, dbStatus);
	else
		_ifs->addInterface(i, dbStatus);
}

Interface *Materiel::getInterfaceById(unsigned int id) const {
	if(!_ifs)
		return NULL;
	return _ifs->getInterfaceById(id);
}

Interface *Materiel::getInterfaceByDot1d(unsigned int id) const {
	if(!_ifs)
		return NULL;
	return _ifs->getInterfaceByDot1d(id);
}

Interface *Materiel::getInterfaceByPort(unsigned int module, unsigned int port) const {
	if(!_ifs)
		return NULL;
	return _ifs->getInterfaceByPort(module, port);
}

Interface *Materiel::getInterfaceByPortDot1d(unsigned int id) const {
	if(!_ifs)
		return NULL;
	return _ifs->getInterfaceByPortDot1d(id);
}

/*Interface *Materiel::getInterafceByDBId(unsigned int dbId) const {
	if(!_ifs)
		return NULL;
	return _ifs->getInterfaceByDBId(dbId);
} */

void Materiel::retrieveInfos() {
	const SNMPResult *r;

	// Si il a pas de nom, on va le récupérer
	if(!_name) {
		r = this->snmpget(&OID_Hostname);
		if(r) {
			this->setHostName(r->get_printable_value());
			delete r;
		}
	}
}

void Materiel::addARPEntry(const char *mac, const char *ip) {
	if(!_arp)
		_arp = new ARPCache(mac, ip);
	else
		_arp->addEntry(mac, ip);
}

// -----------------------------
// MaterielList::Constructeurs
// -----------------------------

MaterielList::MaterielList(Materiel *const mat) {
	_mat = mat;
	_next = NULL;
}

MaterielList::MaterielList(Materiel *const mat, MaterielList *next) {
	_mat = mat;
	_next = next;
}

// ------------------------------
// MaterielList::Destructeur
// ------------------------------

MaterielList::~MaterielList() {
	if(_mat)
		delete _mat;
	if(_next)
		delete _next;
}

MaterielList *MaterielList::addMateriel(Materiel *const mat) {
	return new MaterielList(mat, this);
}

Materiel *MaterielList::getMaterielById(unsigned int id) const {
	const MaterielList *l;
	Materiel *m;

	for(l=this; l!=NULL; l=l->getNext()) {
		m = l->getCurrentMateriel();
		if(m->getID() == id)
			return m;
	}

	return NULL;
}

Materiel *MaterielList::getMaterielByHostname(const char *hostname, const char *type, const char *ostype) const {
	const MaterielList *l;
	Materiel *m;
	char *c;

	// Parcourt la liste du matos
	for(l=this; l!=NULL; l=l->getNext()) {
		m = l->getCurrentMateriel();
		// Si il a le même nom...
		if(!strcmp(m->getHostName(), hostname)) {
			// Si le nom commence par SEP, on retourne le matériel, pas besoin de faire d'autres vérifications
			// vu que pour les SEP, le nom est unique.
			if((c = strstr((char *)m->getHostName(), "SEP")) && (c - m->getHostName() == 0))
				return m;
			// Sinon on vérifie le type et l'OS (s'ils sont définis)
			if((type[0] == 0 || !m->getType() || !strcmp(m->getType(), type)) &&
			(ostype[0] == 0 || !m->getOSType() || !strcmp(m->getOSType(), ostype)))
				return m;
		}
	}
	return NULL;
}

Materiel *MaterielList::getMaterielByIP(const IP* ip, const char *type, const char *ostype) const {
	const MaterielList *l;
	Materiel *m;

	// Parcourt la liste du matos
	for(l=this; l!=NULL; l=l->getNext()) {
		m = l->getCurrentMateriel();
		// Si le matos a l'IP passée et que le type et l'os correspondent, on retourne le matos.
		if(m->getIPList()->isIPinList(ip) &&
				(type[0] == 0 || !m->getType() || !strcmp(m->getType(), type)) &&
				(ostype[0] == 0 || !m->getOSType() || !strcmp(m->getOSType(), ostype)))
			return m;
	}

	return NULL;
}

Materiel *MaterielList::getMaterielByHostnameAndIP(const char *hostname, const IP *ip) const {
	const MaterielList *l;
	Materiel *m;

	for(l=this; l!=NULL; l=l->getNext()) {
		m = l->getCurrentMateriel();
		if(!strcmp(m->getHostName(), hostname) && m->getIPList()->isIPinList(ip))
			return m;
	}
	return NULL;
}
