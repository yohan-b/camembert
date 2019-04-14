/* =================================================

Camembert Project
Alban FERON, 2007

Connexion à la base de données
Entièrement repris de Kindmana, par Aurélien Méré

================================================== */

#ifndef __PGDB_H
#define __PGDB_H

#define DBSTATUS_UNKNOWN	0
#define DBSTATUS_OLD		1
#define DBSTATUS_UPDATED	2
#define DBSTATUS_NEW		3

#define DBNAME		"camembert"
#define DBUSERNAME	"camembert"
#define DBHOST		"127.0.0.1"
#define DBPASSWORD	"CamembertDB@Pacat"

/**
 * Effectue une connexion à la base de données.
 * @return NULL en cas de problème, 1 en cas de succès
 */
int ConnectDB();

/**
 * Déconnecte l'objet de connexion à la base de 
 * données. Il est important d'appeler cette fonction
 * avant toute terminaison du programme.
 */
void DisconnectDB();

/**
 * Effectue une requête dans la base de données. Le
 * programme se termine dans le cas où la requête
 * n'est pas valide.
 */
void *QueryDB(char *query);

char *_PQcmdTuples(void*);
void _PQclear(void *);
int _PQntuples(void *);
char *_PQgetvalue(void *,int, int);
void *_PQgetResult();
int _PQescapeString(char *to, const char *from);

/**
 * Décrit un objet possèdant une entrée dans la base de données
 * Permet de déterminer s'il doit etre crée, mis à jour, etc.
 */
class DBObject {
	protected:
		unsigned int _dbStatus;

	public:

		/**
		 * @return Le statut de l'objet par rapport à son homologue dans la base de données
		 */
		unsigned int getDBstatus() const { return _dbStatus; }
		void setDBstatus(unsigned int status);
};

#endif /* __PGDB_H */

