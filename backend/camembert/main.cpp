/* =================================

Camembert Project
Alban FERON, 2007

================================== */

#include "camembert.h"

int snmp_answers, snmp_requests, snmp_dups;

SNMP* snmp;
MaterielList* lstMateriel;

Monitor* monitor;

pthread_mutex_t
	mtx_materiels,
	mtx_activity,
	mtx_jobs;
pthread_cond_t allConsumed;
unsigned int jobsrunning = 0;

unsigned int maxIdMateriel;
unsigned int maxIdInterface;

int tehdate;

void addMateriel(Materiel *const mat) {
	if(!lstMateriel)
		lstMateriel = new MaterielList(mat);
	else
		lstMateriel = lstMateriel->addMateriel(mat);
}


// Destruction des mutex et libération de la mémoire
void free_memory() {
	pthread_mutex_destroy(&mtx_materiels);
	pthread_mutex_destroy(&mtx_jobs);
	pthread_mutex_destroy(&mtx_activity);
	pthread_cond_destroy(&allConsumed);

	delete snmp;
	delete lstMateriel;
	delete monitor;
}

void terminate(int code) {
	free_memory();
	DisconnectDB();
	exit(code);
}

void init_globals() {
	// Connection à la base de données.
	if (ConnectDB() == 0) {
		printf( "!! Impossible de se connecter à la base de données\n");
		exit(-1);
	}

	// Création des grosse variables.
	snmp = new SNMP();
	monitor = new Monitor(MAX_JOBS);

	lstMateriel = NULL;

	// Mutexes pour la protection des données avec les threads
	pthread_mutex_init(&mtx_materiels, NULL);
	pthread_mutex_init(&mtx_jobs, NULL);
	pthread_mutex_init(&mtx_activity, NULL);
	pthread_cond_init(&allConsumed, NULL);

	snmp_answers	= 0;
	snmp_requests	= 0;
	snmp_dups	= 0;

	tehdate = time(NULL);
}

void read_db_materiel() {
	void *r, *r2;
	unsigned int i, j, n, m;
	Materiel *mat;
	IPList *ipl;
	char buffer[256];

	// On charge tout le matériel depuis la base de données
	r = QueryDB("SELECT * FROM materiel");
	n = _PQntuples(r);
	for(i=0; i<n; i++) {
		// Avec toutes leurs IPs
		sprintf(buffer, "SELECT ip FROM ip WHERE idmateriel = %d ORDER BY main DESC, datelast DESC", atoi(_PQgetvalue(r, i, 0)));
		r2 = QueryDB(buffer);
		m = _PQntuples(r2);
		ipl = new IPList();
		for(j=0; j<m; j++)
			ipl->addIP(new IP(_PQgetvalue(r2, j, 0)), DBSTATUS_OLD);
		_PQclear(r2);

		// Instanciation du matériel
		mat = new Materiel(atoi(_PQgetvalue(r, i, 0)), snmp, COMMUNITY, ipl);
		mat->setHostName	(_PQgetvalue(r, i, 1));
		mat->setManageable	(atoi(_PQgetvalue(r, i, 2)));
		mat->setVersion		(atoi(_PQgetvalue(r, i, 3)));
		mat->setType		(_PQgetvalue(r, i, 4));
		mat->setOSType		(_PQgetvalue(r, i, 5));
		mat->setCapabilities(atoi(_PQgetvalue(r, i, 6)));
		mat->setDBstatus(DBSTATUS_OLD);
		addMateriel(mat);

		if(mat->getManageableStatus() != STATUS_NOTMANAGEABLE)
			monitor->addJob(mat);
	}
	_PQclear(r);
}

void read_db_interfaces() {
	void *r, *r2;
	unsigned int i, j, n, m, ifDbId;
	Materiel *mat;
	Interface *iface;
	char buffer[256];

	r = QueryDB("SELECT idinterface, idmateriel, ifnumber FROM interface");
	n = _PQntuples(r);
	for(i=0; i<n; i++) {
		ifDbId = atoi(_PQgetvalue(r, i, 0));
		iface = new Interface(ifDbId, atoi(_PQgetvalue(r, i, 2)));

		// On reprend les liens sur l'interface
		sprintf(buffer, "SELECT iddstmateriel FROM link WHERE idinterface = %d", ifDbId);
		r2 = QueryDB(buffer);
		m = _PQntuples(r2);
		iface->nbOldLinks = m;
		if(m > 0) {
			iface->oldLinks = new unsigned int[m];
			for(j=0; j<m; j++)
				iface->oldLinks[j] = atoi(_PQgetvalue(r2, j, 0));
		}
		_PQclear(r2);

		// Actions sur l'interface
		sprintf(buffer, "SELECT numaction, option FROM action WHERE idinterface = %d", ifDbId);
		r2 = QueryDB(buffer);
		m = _PQntuples(r2);
		for(j=0; j<m; j++)
			iface->addAction(atoi(_PQgetvalue(r2, j, 0)), _PQgetvalue(r2, j, 1));
		_PQclear(r2);
		sprintf(buffer, "DELETE FROM action WHERE idinterface = %d", ifDbId);
		QueryDB(buffer);

		// Ajout de l'interface au materiel correspondant
		mat = lstMateriel->getMaterielById(atoi(_PQgetvalue(r, i, 1)));
		if(mat)
			mat->addInterface(iface, DBSTATUS_OLD);
	}
	_PQclear(r);
}

void read_db() {
	void *r;

	read_db_materiel();
	read_db_interfaces();

	r = QueryDB("SELECT MAX(idmateriel) FROM materiel");
	maxIdMateriel = atoi(_PQgetvalue(r, 0, 0));
	_PQclear(r);

	r = QueryDB("SELECT MAX(idinterface) FROM interface");
	maxIdInterface = atoi(_PQgetvalue(r, 0, 0));
	_PQclear(r);
}

void check_materiel() {
	if(!lstMateriel) {
		Materiel *planet = new Materiel(++maxIdMateriel, snmp, COMMUNITY, new IP(FIRST_IP));
		planet->setDBstatus(DBSTATUS_NEW);
		addMateriel(planet);
		monitor->addJob(planet);
	}
}

void update_db_materiel() {
	MaterielList *ml;
	IPList *ipl;
	Materiel *m;
	char *buffer = new char[4*1024];
	int ptr;
	bool bFirst;

	// Parcourt la liste du matériel
	for(ml=lstMateriel; ml!=NULL; ml=ml->getNext()) {
		ptr = sprintf(buffer, "BEGIN;\n");

		m = ml->getCurrentMateriel();
		// Si il est nouveau, on l'insère
		if(m->getDBstatus() == DBSTATUS_NEW) {
			ptr += sprintf(&buffer[ptr], "INSERT INTO materiel VALUES(%d, '%s', %d, %d, '%s', '%s', %d, %d, %d);\n",
				m->getID(), m->getHostName(), m->isManageable(), m->getVersion(), m->getType(), 
				m->getOSType(), m->getCapabilities(), tehdate, tehdate);
		}
		// si il n'est pas nouveau mais qu'il a été trouvé, on l'update
		else if(m->getDBstatus() == DBSTATUS_UPDATED) {
			ptr += sprintf(&buffer[ptr], "UPDATE materiel SET hostname='%s', manageable=%d, snmpversion=%d, type='%s'",
				m->getHostName(), m->isManageable(), m->getVersion(), m->getType());
			ptr += sprintf(&buffer[ptr], ", ostype='%s', capabilities=%d, datelast=%d WHERE idmateriel=%d;\n",
				m->getOSType(), m->getCapabilities(), tehdate, m->getID());
		}

		// On fait le tour des IP pour vérifier qu'une vieille IP ne soit pas déclarée comme principale
		for(ipl=m->getIPList(); ipl!=NULL; ipl=ipl->getNext()) {
			if(ipl->getDBstatus() > DBSTATUS_OLD) {
				m->getIPList()->setFirst(ipl);
				break;
			}
		}

		bFirst = true;
		// Parcourt la liste des IPs du matériel
		for(ipl=m->getIPList(); ipl!=NULL; ipl=ipl->getNext()) {
			if(ipl->getDBstatus() == DBSTATUS_NEW) {
				ptr += sprintf(&buffer[ptr], "INSERT INTO ip VALUES('%s', %d, '%d', %d, %d);\n", ipl->getIP()->getIPstr(),
					m->getID(), bFirst, tehdate, tehdate);
			}
			else if(ipl->getDBstatus() == DBSTATUS_UPDATED) {
				ptr += sprintf(&buffer[ptr], "UPDATE ip SET main='%d', datelast=%d WHERE ip='%s' AND idmateriel=%d;\n",
					bFirst, tehdate, ipl->getIP()->getIPstr(), m->getID());
			}
			else if(ipl->getDBstatus() == DBSTATUS_OLD && !bFirst) {
				ptr += sprintf(&buffer[ptr], "UPDATE ip SET main='f' WHERE ip='%s' AND idmateriel='%d';\n",
					ipl->getIP()->getIPstr(), m->getID());
			}
			bFirst = false;
		}

		ptr += sprintf(&buffer[ptr], "END;\n");
		QueryDB(buffer);
	}

	delete[] buffer;
}

void update_db_interfaces() {
	MaterielList *ml;
	Materiel *m;
	InterfaceList *ifl;
	Interface *i;
	char *buffer = new char[1024*1024];
	int ptr;
	char macaddr[24], descr[256], tmpdescr[256], lastsrc[24];
	unsigned short int n;
	unsigned int idLink;
	bool bOldLink;
	dstDevice_t* dd;

	for(ml=lstMateriel; ml!=NULL; ml=ml->getNext()) {
		m = ml->getCurrentMateriel();
		ifl = m->getInterfaces();
		if(!ifl)
			continue;

		ptr = sprintf(buffer, "BEGIN;\n");
		for(; ifl!=NULL; ifl=ifl->getNext()) {
			i = ifl->getInterface();

			if(strlen(i->getAddress()) == 0)
				strcpy(macaddr, "NULL");
			else
				sprintf(macaddr, "'%s'", i->getAddress());

			if(!i->getDescription())
				strcpy(descr, "NULL");
			else {
				sprintf(descr, "'%s'", i->getDescription());
				//_PQescapeString(tmpdescr, i->getDescription());
				//sprintf(descr, "'%s'", tmpdescr);
			}

			if(strlen(i->getLastMacAddr()) == 0)
				strcpy(lastsrc, "NULL");
			else
				sprintf(lastsrc, "'%s'", i->getLastMacAddr());

			if(ifl->getDBstatus() == DBSTATUS_NEW) {
				ptr += sprintf(&buffer[ptr], "INSERT INTO interface VALUES(%d, %d, %d, '%s', %s, %s, %d, '%d', '%d', %d, %d, %d, %d, %d, %d, %d, '%d', '%d', %d, %d, %d, %d, %s, '%d');\n",
					i->getID(), m->getID(), i->getIfNumber(), i->getName(), descr,
					macaddr, i->speed, i->adminStatus, i->operStatus, i->ifType, i->vlan, i->voiceVlan, i->nativeVlan,
					i->moduleNum, i->portNum, i->portDot1d, i->spanningTree, i->portSecEnabled, i->portSecStatus,
					i->maxMacCount, i->currMacCount, i->violationCount, lastsrc, i->stickyEnabled);
			}
			else if(ifl->getDBstatus() == DBSTATUS_UPDATED) {
				ptr += sprintf(&buffer[ptr], "UPDATE interface SET ifname='%s', ifdescription=%s, ifaddress=%s, ifspeed=%d, ifadminstatus='%d', ",
					i->getName(), descr, macaddr, i->speed, i->adminStatus);
				ptr += sprintf(&buffer[ptr], "ifoperstatus='%d', iftype=%d, ifvlan=%d, ifvoicevlan=%d, ifnativevlan=%d, ifmodule=%d, ifport=%d, ",
					i->operStatus, i->ifType, i->vlan, i->voiceVlan, i->nativeVlan, i->moduleNum, i->portNum);
				ptr += sprintf(&buffer[ptr], "portdot1d=%d, portfast='%d', portsecenable='%d', portsecstatus=%d, portsecmaxmac=%d, ",
					i->portDot1d, i->spanningTree, i->portSecEnabled, i->portSecStatus, i->maxMacCount);
				ptr += sprintf(&buffer[ptr], "portseccurrmac=%d, portsecviolation=%d, portseclastsrcaddr=%s, portsecsticky='%d' WHERE idinterface=%d;\n",
					i->currMacCount, i->violationCount, lastsrc, i->stickyEnabled, i->getID());
			}

			dd = i->getDistantDevice();
			while(dd) {
				bOldLink = false;
				idLink = ((Materiel*)dd->distantDevice)->getID();
				for(n=0; n<i->nbOldLinks; n++) {
					if(idLink == i->oldLinks[n]) {
						ptr += sprintf(&buffer[ptr], "UPDATE link SET dstifname='%s', datelast=%d WHERE idinterface=%d AND iddstmateriel=%d;\n",
							dd->dstIfName, tehdate, i->getID(), idLink);
						bOldLink = true;
						break;
					}
				}
				if(!bOldLink)
					ptr += sprintf(&buffer[ptr], "INSERT INTO link VALUES(%d, %d, '%s', %d, %d);\n", i->getID(),
						idLink, dd->dstIfName, tehdate, tehdate);

				dd = dd->next;
			}
		}

		ptr += sprintf(&buffer[ptr], "END;\n");
		QueryDB(buffer);
	}

	delete[] buffer;
}

void update_db_arp() {
	MaterielList *ml;
	Materiel *m;
	ARPCache *arp;
	char *buffer = new char[2*1024*1024];
	int ptr;

	sprintf(buffer, "CREATE TABLE arp_%d (mac macaddr, ip inet)", tehdate);
	QueryDB(buffer);

	for(ml=lstMateriel; ml!=NULL; ml=ml->getNext()) {
		m = ml->getCurrentMateriel();
		arp = m->getARPCache();

		if(!arp)
			continue;

		ptr = sprintf(buffer, "BEGIN;\n");
		for(; arp!=NULL; arp=arp->getNext())
			ptr += sprintf(&buffer[ptr], "INSERT INTO arp_%d VALUES('%s', '%s');\n", tehdate, arp->getMAC(), arp->getIP()->getIPstr());

		ptr += sprintf(&buffer[ptr], "UPDATE arpcache SET datelast=%d, foundon=%d WHERE (mac, ip) IN (SELECT mac, ip FROM arp_%d);\n",
			tehdate, m->getID(), tehdate);
		ptr += sprintf(&buffer[ptr], "INSERT INTO arpcache SELECT mac, ip, %d, %d, %d FROM arp_%d WHERE (mac, ip) NOT IN (SELECT mac, ip FROM arpcache);\n",
			m->getID(), tehdate, tehdate, tehdate);
		ptr += sprintf(&buffer[ptr], "DELETE FROM arp_%d; END;\n", tehdate);
		QueryDB(buffer);
	}

	sprintf(buffer, "DROP TABLE arp_%d", tehdate);
	QueryDB(buffer);

	delete[] buffer;
}

void update_db_fdb() {
	MaterielList *ml;
	InterfaceList *ifl;
	Interface *i;
	ForwardingDatabase *fdb;
	char *buffer = new char[1024*1024];
	int ptr;

	sprintf(buffer, "CREATE TABLE fdb_%d (idinterface integer, vlan integer, mac macaddr, type integer)", tehdate);
	QueryDB(buffer);

	for(ml=lstMateriel; ml!=NULL; ml=ml->getNext()) {
		ifl = ml->getCurrentMateriel()->getInterfaces();

		ptr = sprintf(buffer, "BEGIN;\n");

		for(; ifl!=NULL; ifl=ifl->getNext()) {
			i = ifl->getInterface();
			fdb = i->getForwardingDB();
			if(!fdb)
				continue;

			for(; fdb!=NULL; fdb=fdb->getNext())
				ptr += sprintf(&buffer[ptr], "INSERT INTO fdb_%d VALUES(%d, %d, '%s', %d);\n", tehdate, i->getID(), fdb->getVLAN(), fdb->getMAC(), fdb->getType());

		}

		ptr += sprintf(&buffer[ptr], "UPDATE fdb SET datelast=%d WHERE (idinterface, vlan, mac, type) IN (SELECT * FROM fdb_%d);\n",
			tehdate, tehdate);
		ptr += sprintf(&buffer[ptr], "INSERT INTO fdb SELECT idinterface, vlan, mac, %d, %d, type FROM fdb_%d WHERE (idinterface, vlan, mac, type) NOT IN (SELECT idinterface, vlan, mac, type FROM fdb);\n",
			tehdate, tehdate, tehdate);

		ptr += sprintf(&buffer[ptr], "DELETE FROM fdb_%d; END;\n", tehdate);
		QueryDB(buffer);
	}

	sprintf(buffer, "DROP TABLE fdb_%d", tehdate);
	QueryDB(buffer);

	delete[] buffer;
}

void update_db() {
	update_db_materiel();
	update_db_interfaces();
	update_db_arp();
	update_db_fdb();
}

void analyseMateriel(Materiel *mat) {
	// Si on l'a déjà analysé, on zappe.
	if(mat->isTreated())
		return;

	mat->setTreated(true);

	// Manageable ?
	if(mat->snmpcheck()) {
		mat->setManageable(MAT_IS_MANAGEABLE);
		mat->setDBstatus(DBSTATUS_UPDATED);

		// Ouverture de connexion
		mat->snmpopen();

		mat->retrieveInfos();
		apply_actions(mat);
		get_interfaces_infos(mat);
		get_arp_infos(mat);
		get_fdb_infos(mat);
		look_for_neighbours(mat);

		// Fermeture de la connexion
		mat->snmpclose();
	}
	else if(mat->isManageable() != MAT_NOT_MANAGEABLE)
		mat->setManageable(MAT_WAS_MANAGEABLE);
	else
		mat->setManageable(MAT_NOT_MANAGEABLE);
}

// Thread d'analyse du réseau
void* camembert_thread(void*) {
	Materiel *m;

	while(true) {
		m = (Materiel *)monitor->getJob();

		pthread_mutex_lock(&mtx_jobs); 
		++jobsrunning;
		pthread_mutex_unlock(&mtx_jobs);

		if (!m->isTreated())
			analyseMateriel(m);

		pthread_mutex_lock(&mtx_jobs);
		if (!(--jobsrunning) && !monitor->isJobPending())
			pthread_cond_signal(&allConsumed);

		pthread_mutex_unlock(&mtx_jobs);
	}

	return NULL;
}

// Attend que le monitor soit vide, donc qu'il n'y ait plus de matériel à analyser
void wait_end_of_jobs() {
#if THREADED
	pthread_mutex_lock(&mtx_activity);
	pthread_cond_wait(&allConsumed, &mtx_activity);
	pthread_mutex_unlock(&mtx_activity);
#endif
}

// Crée les threads d'analyse du réseau.
void create_threads() {
#if THREADED
	pthread_t p;

	for(unsigned int i=0; i<MAX_THREADS; i++) {
		if(pthread_create(&p, NULL, camembert_thread, NULL) != 0) {
			fprintf(stderr, "Erreur de création des threads. (%d)\n", i);
			terminate(1);
		}
	}
#else
	Materiel *m;
	while(monitor->isJobPending()) {
		m = (Materiel *)monitor->getJob();
		if(!m->isTreated())
			analyseMateriel(m);
	}
#endif
}

// Parcourt la liste du matériel à la recherche du matériel qui n'a pas été analysé
// Et l'analyse le cas échéant (peu probable que ça se produise anyway vu comment mon appli est construite)
void check_untreated() {
	unsigned int untreated = 0;
	Materiel *m;

	for(MaterielList *lst=lstMateriel; lst!=NULL; lst=lst->getNext()) {
		m = lst->getCurrentMateriel();
		if(!m->isTreated()) {
			printf("%s not treated\n", m->getHostName());
			monitor->addJob((void*)m);
			untreated++;
		}
	}

	if(untreated)
		wait_end_of_jobs();
}

// Programme principal
int main(int argc, char* argv[]) {
	init_globals();
	read_db();
	check_materiel();
	create_threads();
	wait_end_of_jobs();
	check_untreated();
	update_db();
	terminate(0);
}
