/* =================================================

Camembert Project
Alban FERON, 2007

Fonctions pour la recherche des voisins (CDP)

=================================================== */

#include "camembert.h"

Oid OID_NeighboursNames	("1.3.6.1.4.1.9.9.23.1.2.1.1.6");
Oid OID_NeighboursIPs	("1.3.6.1.4.1.9.9.23.1.2.1.1.4");
Oid OID_NeighboursTypes	("1.3.6.1.4.1.9.9.23.1.2.1.1.8");
Oid OID_NeighboursOS	("1.3.6.1.4.1.9.9.23.1.2.1.1.5");
Oid OID_NeighboursCapab	("1.3.6.1.4.1.9.9.23.1.2.1.1.9");
Oid OID_NeighboursIfName("1.3.6.1.4.1.9.9.23.1.2.1.1.7");

Oid OID_ARP("1.3.6.1.2.1.3.1.1.2");

/* Petite liste contenant les infos sur les voisins */
typedef struct neighbourList_s {
	int key[2];			/* Clé CDP du voisin */
	char name[64];		/* Nom du voisin */
	char type[64];		/* Type du voisin */
	char os[512];		/* Description de l'OS du voisin */
	char ifName[64];	/* Nom de l'interface distante */
	unsigned int capa;	/* Masque des capabilities */
	IPList *lip;		/* Liste d'IP du voisin */
	struct neighbourList_s *next; /* Voisin suivant */
} neighbourList;

neighbourList *addNeighbour(neighbourList *nl, const char *name, int key[]) {
	neighbourList *n;

	// Cherche si la clé existe déjà et retourne la structure passée (qui doit correspondre au premier voisin)
	for(n = nl; n!=NULL; n=n->next)
		if(key[0] == n->key[0] && key[1] == n->key[1])
			return nl;

	// Création du nouveau voisin
	n = new neighbourList;
	n->key[0] = key[0];
	n->key[1] = key[1];
	strcpy(n->name, name);
	n->type[0] = 0;
	n->os[0] = 0;
	n->ifName[0] = 0;
	n->capa = 0;
	n->lip = new IPList();
	n->next = nl;
	return n;
}

void addNeighbourType(neighbourList *nl, const char *type, int key[]) {
	neighbourList *n;

	// Cherche le voisin correspondant à la clé
	for(n=nl; n!=NULL; n=n->next) {
		if(key[0] == n->key[0] && key[1] == n->key[1]) {
			// Change son type
			strcpy(n->type, type);
			return;
		}
	}
}

void addNeighbourOS(neighbourList *nl, const char *os, int key[]) {
	neighbourList *n;

	// Cherche le voisin correspondant à la clé
	for(n=nl; n!=NULL; n=n->next) {
		if(key[0] == n->key[0] && key[1] == n->key[1]) {
			// Change son type
			strcpy(n->os, os);
			return;
		}
	}
}

void addNeighbourIfName(neighbourList *nl, const char *ifname, int key[]) {
	neighbourList *n;

	// Cherche le voisin correspondant à la clé
	for(n=nl; n!=NULL; n=n->next) {
		if(key[0] == n->key[0] && key[1] == n->key[1]) {
			// Change son type
			strcpy(n->ifName, ifname);
			return;
		}
	}
}

void addNeighbourIP(neighbourList *nl, unsigned int ipnum, int key[]) {
	neighbourList *n;
	IP *ip;

	// Cherche le voisin correspondant à la clé
	for(n=nl; n!=NULL; n=n->next) {
		if(key[0] == n->key[0] && key[1] == n->key[1]) {
			ip = new IP(ipnum);
			// Si l'IP trouvé n'est pas dans la liste, on l'ajoute
			if(!n->lip->isIPinList(ip)) {
				n->lip->addIP(ip, DBSTATUS_NEW);
			}
			else
				delete ip;
			return;
		}
	}
}

void addNeighbourCapability(neighbourList *nl, unsigned int capa, int key[]) {
	neighbourList *n;

	// Cherche le voisin correspondant à la clé
	for(n=nl; n!=NULL; n=n->next) {
		if(key[0] == n->key[0] && key[1] == n->key[1]) {
			// Met à jour les capabilities
			n->capa = capa;
			return;
		}
	}
}

// C'est censé libérer la mémoire, mais en fait ça fait des erreurs
void clearNeighbours(neighbourList *nl) {
	neighbourList *next;
	while(nl) {
		next = nl->next;
		delete nl;
		nl = next;
	}
}

bool look_for_neighbours(Materiel *m) {
	neighbourList *nl = NULL;
	const SNMPResult *r;
	int key[2];
	unsigned char buffer[16];
	unsigned int num;
	unsigned long int size;

	SNMPResults res(m->multiplesnmpwalk(6, 0,
		&OID_NeighboursCapab,
		&OID_NeighboursIfName,
		&OID_NeighboursOS,
		&OID_NeighboursTypes,
		&OID_NeighboursIPs,
		&OID_NeighboursNames));
	// Recherche des voisins
	while(r = res.getNext()) {
		const Oid &o = r->get_oid();
		key[0] = o[14];
		key[1] = o[15];

		switch (o[13]) {
		case 6:
			nl = addNeighbour(nl, r->get_printable_value(), key);
			break;

		case 4:
			r->get_value(buffer, size);
			if(buffer[0] != 0) {
				num = buffer[0]*16777216 + buffer[1]*65536 + buffer[2]*256 + buffer[3];
				addNeighbourIP(nl, num, key);
			}
			break;

		case 8:
			addNeighbourType(nl, r->get_printable_value(), key);
			break;

		case 5:
			addNeighbourOS(nl, r->get_printable_value(), key);
			break;

		case 7:
			addNeighbourIfName(nl, r->get_printable_value(), key);
			break;

		case 9:
			r->get_value(buffer, size);
			num = buffer[0]*16777216 + buffer[1]*65536 + buffer[2]*256 + buffer[3];
			addNeighbourCapability(nl, num, key);
			break;
		}
	}

	neighbourList *n;
	Materiel *mat = NULL;
	IPList *lip;
	bool bNew = false;
	Interface *i;

	// Pour chaque voisin trouvé...
	for(n=nl; n!=NULL; n=n->next) {

		// On bloque l'acces au matériel pour éviter qu'un autre thread modifie en même
		// temps la liste du matériel
		pthread_mutex_lock(&mtx_materiels);

		// Commence par chercher si un materiel porte le même nom et a la même IP que le voisin
		lip = n->lip;
		while(lip!=NULL) {
			mat = lstMateriel->getMaterielByHostnameAndIP(n->name, lip->getIP());
			if(mat)
				lip = NULL;
			else
				lip = lip->getNext();
		}
		// S'il y en a aucun, on cherche juste sur le nom (en vérifiant que le type et l'OS soient les mêmes)
		if(!mat)
			mat = lstMateriel->getMaterielByHostname(n->name, n->type, n->os);
		// S'il y en a toujours aucun, on cherche sur les IPs (en vérifiant que le type et l'OS soient les mêmes)
		if(!mat) {
			lip = n->lip;
			while(lip!=NULL) {
				mat = lstMateriel->getMaterielByIP(lip->getIP(), n->type, n->os);
				if(mat)
					lip = NULL;
				else
					lip = lip->getNext();
			}
		}

		if(!mat) {
			// Si aucun ne correspond aux critères, on en crée un nouveau et on l'ajoute à la liste
			mat = new Materiel(++maxIdMateriel, snmp, COMMUNITY, n->lip);
			mat->setDBstatus(DBSTATUS_NEW);
			lstMateriel = lstMateriel->addMateriel(mat);
			bNew = true;
		}
		else {
			// Sinon on parcourt la liste des IPs du voisin et on ajoute celles qui
			// ne sont pas dans la liste d'IP du matériel correspondant
			for(lip=n->lip; lip!=NULL; lip=lip->getNext()) {
				if(!mat->getIPList()->isIPinList(lip->getIP())) {
					mat->addIP(lip->getIP(), DBSTATUS_NEW);
				}
				else
					mat->getIPList()->foundIP(lip->getIP());
			}
		}

		i = m->getInterfaceById(n->key[0]);
		/*if(i) {
			i->distantDevice = mat;
			i->setDistantIfName(n->ifName);
		}*/
		if(i)
			i->addDistantDevice(n->ifName, mat);

		if(!mat->getHostName() || strcmp(mat->getHostName(), n->name))
			mat->setHostName(n->name);
		// Si il a pas de type on le met à jour
		if(!mat->getType() || strcmp(mat->getType(), n->type))
			mat->setType(n->type);
		if(!mat->getOSType() || strcmp(mat->getOSType(), n->os))
			mat->setOSType(n->os);
		// et pareil pour les capabilities
		if(!mat->getCapabilities() || n->capa != mat->getCapabilities())
			mat->setCapabilities(n->capa);
		mat->setDBstatus(DBSTATUS_UPDATED);

		// On débloque l'acces au matériel
		pthread_mutex_unlock(&mtx_materiels);

		if(!mat->isTreated())
			monitor->addJob(mat);
	}

	//clearNeighbours(nl);
	return bNew;
}
