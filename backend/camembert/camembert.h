#ifndef __CAMEMBERT_H
#define __CAMEMBERT_H

#include <stdio.h>

#include "ip.h"
#include "snmp.h"
#include "snmpobject.h"
#include "pgdb.h"
#include "interface.h"
#include "materiel.h"
#include "monitor.h"
#include "functions.h"
#include "neighbours.h"

extern SNMP* snmp;
extern MaterielList* lstMateriel;
extern pthread_mutex_t mtx_materiels;
extern Monitor* monitor;
extern unsigned int maxIdMateriel;
extern unsigned int maxIdInterface;

#define FIRST_IP "172.17.24.1"
#define COMMUNITY "pacadmins"

#define MAX_JOBS 256
#define MAX_THREADS 8
#define THREADED 1

#endif /* __CAMEMBERT_H */
