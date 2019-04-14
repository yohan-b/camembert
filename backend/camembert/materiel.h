/* =========================================

Camembert Project
Alban FERON, 2007

Représentation d'un équipement matériel et
 liste de matériel.

========================================== */

#ifndef __CAMEMBERT_MATERIEL_H
#define __CAMEMBERT_MATERIEL_H

#include "snmpobject.h"
#include "pgdb.h"
#include "arp.h"
#include "interface.h"

/* See http://www.cisco.com/univercd/cc/td/doc/product/lan/trsrb/frames.htm#18843
   for more informations */
#define CAPABILITY_LVL3_ROUTING			0x01
#define CAPABILITY_LVL2_TRANSPARENT_BRIDGE	0x02
#define CAPABILITY_LVL2_SRC_ROUTE_BRIDGING	0x04
#define CAPABILITY_LVL2_SWITCHING		0x08
#define CAPABILITY_SEND_RECIEVE_PACKETS		0x10
#define CAPABILITY_IGMP_NONFORWARDING		0x20
#define CAPABILITY_LVL1_FUNCTIONALITY		0x40
#define CAPABILITY_PHONE			0x80

#define MAT_NOT_MANAGEABLE	0
#define MAT_IS_MANAGEABLE	1
#define MAT_WAS_MANAGEABLE	2

/**
 * Représentation d'un équipement matériel
 * Permet d'avoir accès au nom, type et capabilities du matériel
 */
class Materiel: public SNMPObject, public DBObject {
	private:
		unsigned int _id;
		char *_name; /* Hostname du matos */
		char *_type; /* Type */
		char *_osType;
		unsigned int _capabilities; /* Capabilities récupérées via CDP */
		bool bTreated; /* Si on a déjà fait l'analyse à partir de ce materiel */
		unsigned int _manageable;
		InterfaceList *_ifs;
		ARPCache *_arp;

	public:
		/**
		 * Constructeur
		 *
		 * @param id - ID du matos dans la base de données
		 * @param snmp - Pointeur vers une instance de class SNMP pour effectuer les requêtes
		 * @param community - Communauté SNMP à utiliser lors des connexions
		 * @param ip - IP principale du matériel
		 */
		Materiel(unsigned int id, SNMP *snmp, const char* community, IP* ip);

		/**
		 * Constructeur
		 *
		 * @param id - ID du matos dans la base de données
		 * @param snmp - Pointeur vers une instance de class SNMP pour effectuer les requêtes
		 * @param community - Communauté SNMP à utiliser lors des connexions
		 * @param lip - Pointeur sur une liste d'IP du matériel
		 */
		Materiel(unsigned int id, SNMP *snmp, const char* community, IPList* lip);

		/**
		 * Destructeur
		 * Fait les libérations de mémoire nécéssaires
		 */
		~Materiel();

		/**
		 * Récupère les infos de base (tel que le nom) si elles ne sont pas initialisées
		 */
		void retrieveInfos();

		void setManageable(unsigned int manageable) { _manageable = manageable; }
		unsigned int isManageable() const { return _manageable; }
		void setVersion(int v) { version = v; }

		/**
		 * Change le type du matériel
		 */
		void setType(const char *type);

		/**
		 * Change le nom du matériel
		 */
		void setHostName(const char *name);

		/**
		 * Change la description de l'OS
		 */
		void setOSType(const char *os);

		/**
		 * Change les capabilities
		 *
		 * @param capabilities - Masque des capabilities à affecter
		 * @note Si les capabilities spécifient que c'est un téléphone, le matériel est déclaré
		 *       non manageable
		 * @see http://www.cisco.com/univercd/cc/td/doc/product/lan/trsrb/frames.htm#18843
		 */
		void setCapabilities(const unsigned int capabilities);

		/**
		 * Définit si le matériel a été analysé ou non
		 */
		void setTreated(const bool treated) { bTreated = treated; }

		void addInterface(Interface *i, unsigned int dbStatus);

		unsigned int getID() const { return _id; }

		/**
		 * Récupère le nom
		 *
		 * @return Le nom du matériel
		 */
		const char *getHostName() const { return _name; }

		/**
		 * Récupère le type
		 *
		 * @return Le type du matériel
		 */
		const char *getType() const { return _type; }

		/**
		 * Récupère l'OS
		 */
		const char *getOSType() const { return _osType; }

		/**
		 * Récupère les capabilities
		 *
		 * @return Le masque de capabilities du matériel
		 * @see http://www.cisco.com/univercd/cc/td/doc/product/lan/trsrb/frames.htm#18843
		 */
		const unsigned int getCapabilities() const { return _capabilities; }

		/**
		 * @return VRAI si le matériel a été traité.
		 */
		const bool isTreated() const { return bTreated; }

		InterfaceList *getInterfaces() const { return _ifs; }
		Interface *getInterfaceById(unsigned int id) const;
		Interface *getInterfaceByDot1d(unsigned int id) const;
		Interface *getInterfaceByPort(unsigned int module, unsigned int port) const;
		Interface *getInterfaceByPortDot1d(unsigned int id) const;
		//Interface *getInterfaceByDBId(unsigned int dbId) const;

		void addARPEntry(const char *mac, const char *ip);
		ARPCache *getARPCache() const { return _arp; }
};

/**
 * Liste de matériel
 */
class MaterielList {
	private:
		MaterielList *_next;	/* Sous liste permettant d'acceder au matériels suivants */
		Materiel *_mat;			/* Matériel courant */

	public:
		/**
		 * Crée une liste avec un unique matériel
		 *
		 * @param mat - Pointeur sur le matériel à mettre dans la liste
		 */
		MaterielList(Materiel *const mat);

		/**
		 * Crée une liste avec un ancienne liste et un matériel à lui ajouter
		 *
		 * @param mat - Materiel à ajouter
		 * @param next - Ancienne liste utilisée pour déterminer les matériels suivants
		 */
		MaterielList(Materiel *const mat, MaterielList *next);

		/**
		 * Libère la mémoire
		 *
		 * @note Détruit aussi le matériel pointé
		 */
		~MaterielList();

		/**
		 * Ajoute un nouveau matériel au début de la liste
		 *
		 * @param mat - Materiel à ajouter
		 * @return une nouvelle liste contenant le nouveau matériel et ceux présent
		 *         dans la liste actuelle
		 * @note Ne modifie pas la liste actuelle
		 */
		MaterielList *addMateriel(Materiel *const mat);

		/**
		 * @return Sous liste pour avoir les matériels suivant
		 */
		MaterielList *getNext() const { return _next; }

		/**
		 * @return Matériel actuellement pointé par la liste
		 */
		Materiel *getCurrentMateriel() const { return _mat; }

		Materiel *getMaterielById(unsigned int id) const;

		/**
		 * Cherche un matériel dans la liste à partir d'un nom donné
		 *
		 * @param hostname - Nom du matériel à chercher
		 * @param type -
		 * @param ostype -
		 * @return Le matériel dont le nom correspond à 'hostname'
		 */
		Materiel *getMaterielByHostname(const char *hostname, const char *type, const char *ostype) const;

		/**
		 * Cherche un matériel dans la liste à partir d'une IP donnée
		 *
		 * @param ip - L'IP du matériel à chercher
		 * @param type -
		 * @param ostype -
		 * @return Le matériel ayant l'IP 'ip'
		 */
		Materiel *getMaterielByIP(const IP* ip, const char *type, const char *ostype) const;

		/**
		 * Cherche un materiel dans la liste à partir d'un nom et d'une IP donnés
		 *
		 * @param hostname - Nom du matériel à chercher
		 * @param ip - IP du matériel à chercher
		 * @return Le matériel ayant le nom 'hostname' et l'IP 'ip'
		 */
		Materiel *getMaterielByHostnameAndIP(const char *hostname, const IP *ip) const;
};

#endif /* __CAMEMBERT_MATERIEL_H */
