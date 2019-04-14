/* ========================================================

Camembert Project
Alban FERON, 2007

IP et IPNetmask repris de Kindmana, par Aurélien Méré

======================================================== */

#ifndef __CAMEMBERT_IP_H
#define __CAMEMBERT_IP_H

#include <stdio.h>
#include <string.h>
#include <stdlib.h>

#include "pgdb.h"

#define IP_LENGTH	24

class IP {

	private:
		char ip[IP_LENGTH];
		unsigned int ipint;

		void setIP(char const * ip);
		void computestring();

	public:

		// IP Constructor

		IP();
		IP(char const * const ip);
		IP(unsigned int const ip);  
		IP(IP const &ip);
		IP(IP const * const ip);

		// Base informations for getting, setting 
		//	and testing an IP

		char const *getIPstr() const;
		unsigned int const getIPint() const;

		bool const operator == (char const * const ip) const;
		bool const operator == (IP const &ip) const;
		bool const operator == (unsigned int const ip) const;
		bool const operator == (IP const * const ip) const;
		bool const operator != (char const * const ip) const;
		bool const operator != (IP const &ip) const;
		bool const operator != (unsigned int const ip) const;
		bool const operator != (IP const * const ip) const;

		IP &operator = (char const * const ip);
		IP &operator = (unsigned int const ip);	
		IP &operator = (IP const &ip);
		IP &operator = (IP const * const ip);

		unsigned int const operator & (const IP &ip) const;
		unsigned int const operator & (const IP * const ip) const;

		IP &operator &= (const IP &ip);
		IP &operator &= (const IP * const ip);
};

class IPNetmask {

	private:
		IP netmask;
		unsigned int smallmask;

	public:
		IPNetmask();
		IPNetmask(const char * const ip);
		IPNetmask(const unsigned int mask);

		unsigned int const getMask() const ;
		char const *getMaskStr() const ;
		unsigned int const getMaskSmall() const;
};

/**
 * Liste d'IPs
 */
class IPList: public DBObject {
	private:
		IPList *_next;
		IP *_current;

	public:
		/**
		 * Crée une nouvelle liste vide
		 */
		IPList();

		/**
		 * Détruit la liste ainsi que les IPs qu'elle contient
		 */
		~IPList();

		/**
		 * Ajoute une IP à la fin de la liste
		 *
		 * @param ip - IP à ajouter
		 * @param dbStatus - L'IP est sortie de la base où est à créer ?
		 */
		void addIP(IP *const ip, unsigned int dbStatus);

		/**
		 * @return IP courante
		 */
		IP *getIP() const { return _current; }

		/**
		 * @return Sous liste contenant les IP suivantes
		 */
		IPList *getNext() const { return _next; }

		/**
		 * Cherche si une IP donnée existe dans la liste
		 *
		 * @param ip - IP à chercher
		 * @return VRAI si l'ip donnée est dans la liste, FAUX sinon
		 */
		bool isIPinList(const IP *ip) const;

		/**
		 * Spécifie que l'IP donnée a été trouvée, donc qu'elle peut être
		 * gardée dans la base de données
		 *
		 * @param ip - IP à déclarer comme "trouvée"
		 */
		void foundIP(const IP *ip);

		/**
		 * Etablit l'IP passée (ou plutot l'élément de liste contenant l'IP) comme
		 * première de la liste.
		 * Attention à ne passer que des éléments appartenant effectivement bien à la liste.
		 * Aucune vérification n'est faite.
		 *
		 * @param lip - Elément à passer au début de la liste.
		 */
		 void setFirst(IPList *lip);
};

#endif /* __CAMEMBERT_H */
