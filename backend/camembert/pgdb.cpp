/* =================================================

Camembert Project
Alban FERON, 2007

Connexion à la base de données
Entièrement repris de Kindmana, par Aurélien Méré

Juste viré ce qui servait à rien

================================================== */

#include <pthread.h>
#include <stdlib.h>
#include <string.h>
#include "pgdb.h"
#include <postgresql/libpq-fe.h>

pthread_mutex_t mtxDB;
PGconn *db;

int ConnectDB() {
#ifdef DBPASSWORD
	db = PQconnectdb("host="DBHOST" user="DBUSERNAME" dbname="DBNAME" password="DBPASSWORD);
#else
	db = PQconnectdb("host="DBHOST" user="DBUSERNAME" dbname="DBNAME);
#endif

	pthread_mutex_init(&mtxDB, NULL);

	if (db == NULL) {
		printf("Failed to connect to PostgreSQL database\n");
		exit(-1);
	}

	return 1;
}

void DisconnectDB() {
	pthread_mutex_destroy(&mtxDB);
	PQfinish(db);   
}

void *QueryDB(char *query) {
	PGresult *r;
	ExecStatusType e;

	pthread_mutex_lock(&mtxDB);   

	if ((r = PQexec(db, query)) == NULL) {      
		printf("POSTGRES FATAL : Query [%s] returned NULL\n", query);
		exit(-1);
	}

	e = PQresultStatus(r);
	if ((e != PGRES_TUPLES_OK) && (e != PGRES_COMMAND_OK)) {      
		printf("POSTGRES FATAL : Query [%s] failed returning error [%s]\n", query, PQresultErrorMessage(r));
		exit(-1);
	}

	pthread_mutex_unlock(&mtxDB);
	return ((void *)r);
}

char *_PQcmdTuples(void* a)				{ return PQcmdTuples((PGresult *)a); }
void _PQclear(void *a)					{ PQclear((PGresult *)a); }
int _PQntuples(void *a)					{ return PQntuples((PGresult *)a); }
char *_PQgetvalue(void *a,int b, int c)	{ return PQgetvalue((PGresult *)a, b, c); }
void *_PQgetResult()					{ return (void *)PQgetResult(db); }
int _PQescapeString(char *to, const char *from)	{ return PQescapeStringConn(db, to, from, strlen(from), NULL); }

/* ======================================================= */

void DBObject::setDBstatus(unsigned int status) {
	if(status > _dbStatus)
		_dbStatus = status;
}
