/* ========================================================

Camembert Project
Alban FERON, 2007

SNMP Base class.
Totalement repris de Kindmana, par Aurélien Méré

======================================================== */

#include "snmp.h"
#include <errno.h>

extern int snmp_answers, snmp_requests, snmp_dups;

SNMPResult::SNMPResult(const Vb &vb):Vb(vb),next(NULL) { }
SNMPResult::SNMPResult(const Vb *vb):Vb(*vb),next(NULL) { }
SNMPResult::SNMPResult(const Vb &vb, const SNMPResult *next):Vb(vb),next(next) { }
SNMPResult::SNMPResult(const Vb *vb, const SNMPResult *next):Vb(*vb),next(next) { }

//inline const SNMPResult *SNMPResult::getNext() const { return next; }


SNMP::SNMP() {
	current_rid = (rand() % (PDU_MAX_RID - PDU_MIN_RID - 1)) + PDU_MIN_RID;
	pthread_mutex_init(&mtx_rid, NULL);
}

const SNMPResult *SNMP::snmpwalk(const unsigned int idconn, 
	const Oid *oid, 
	const unsigned int version, 
	const char *community,
	const unsigned int timeout, 
	const unsigned int retry, 
	const SNMPResult *prev) 
{
	int i;
	Pdu pdu;
	Vb vb(*oid);
	pdu += vb;

	SNMPResult *last = (SNMPResult *)prev;

	++snmp_requests;

	while(1) {
		pdu.set_type(sNMP_PDU_GETBULK);
		//printf("Suite boucle ... \n");
		if ((snmpengine(idconn, &pdu, version, community, timeout, retry)) == 0) {
			if (pdu.get_vb_count()>0) {
				for (i=0; i<pdu.get_vb_count(); ++i) {
					pdu.get_vb(vb, i);
					if ((vb.get_syntax() == SNMP_ENDOFMIBVIEW) || (vb.get_oid().nCompare(oid->len(), *oid) != 0))
						return(last);
					last = new SNMPResult(vb, last);
				}
				pdu.set_vblist(&vb, 1);
			}
			else return prev;
		}
		else return prev;
	}
	printf("Fin snmpwalk 2 +++++++++++++++\n");
}

const unsigned int SNMP::snmpopen(const IP *ip) {

  int sock;
  struct sockaddr_in sa;

  if ((sock = socket(AF_INET, SOCK_DGRAM, 0)) <= 0) {
    fprintf(stderr, "FATAL: Couldn't create socket\n");
    return 0;
  }

  memset(&sa, 0, sizeof(sa));
  sa.sin_family = AF_INET;
  sa.sin_addr.s_addr = htonl(((IP *)ip)->getIPint());
  sa.sin_port = htons(161);

  if (connect(sock, (struct sockaddr *)&sa, (socklen_t)sizeof(sa)) == -1) {
    close(sock);
    return 0;
  }

  return sock;
}

void SNMP::snmpclose(unsigned int idconn) {

  close(idconn);

}

const unsigned int SNMP::snmpengine(const unsigned int idconn,
		Pdu *pdu,
		const unsigned int version,
		const char *community,
		const unsigned int timeout,
		const unsigned int retry) 
{
  fd_set fd;
  int status, bytes;
  char buffer[4096];
  struct timeval tout;
  SnmpMessage snmpmsg;
  SnmpMessage snmpmsg_ret;
  OctetStr community_ret;
  snmp_version version_ret;
  unsigned short pdu_action;
  unsigned int id, tries=0;
  OctetStr comm(community);
  snmp_version ver;
/*
	Vb vb;
	pdu->get_vb(vb, 0);

  printf("snmpengine : %d %s %d %s %d %d\n", idconn, vb.get_printable_oid(), version, community, timeout, retry);
*/
  if (version==1) ver=version1;
  else ver=version2c;
   
  pdu_action = pdu->get_type();
  pdu->set_error_index(0);
   
  pthread_mutex_lock(&mtx_rid);
  if (++current_rid > PDU_MAX_RID) current_rid=PDU_MIN_RID;
  pdu->set_request_id(id = current_rid);
  pthread_mutex_unlock(&mtx_rid);
   
  if (pdu_action == sNMP_PDU_GETBULK) {  
    if (ver == version1) {
      pdu->set_type(sNMP_PDU_GETNEXT);
    } 
    else {
      pdu->set_error_status((int)0);
      pdu->set_error_index((int)20);
    }
  }
   
  if ((status = snmpmsg.load(*pdu, comm, ver)) != SNMP_CLASS_SUCCESS) {
    return 1;
  }
   
  while (tries < retry) {

    bytes = send(idconn, snmpmsg.data(), (size_t)snmpmsg.len(), 0);
    if (bytes != (int)snmpmsg.len()) {
      fprintf(stderr, "FATAL: Send failed\n");
      return 1;
    }

    tout.tv_sec = timeout/1000;
    tout.tv_usec = (timeout%1000)*1000;
    FD_ZERO(&fd);
    FD_SET(idconn, &fd);
    
    if (select(idconn+1, &fd, NULL, NULL, &tout)>0) {
      
      if ((bytes = recv(idconn, buffer, sizeof(buffer), 0))>0) {
	if (snmpmsg_ret.load((unsigned char *)buffer, bytes) == SNMP_CLASS_SUCCESS) {
	  if (snmpmsg_ret.unload(*pdu, community_ret, version_ret) == SNMP_CLASS_SUCCESS) {

	    if (pdu->get_request_id() == id) {
	      ++snmp_answers;
	      return 0;
	    }
	  }
	}
      }
      else fprintf(stderr, "FATAL: Read error\n");
    }
    ++tries;
  }

  return 1;
}

const SNMPResult *SNMP::snmpget(const unsigned int idconn, 
		const Oid *oid,
		const unsigned int version, 
		const char *community, 
		const unsigned int timeout, 
		const unsigned int retry) 
{
  unsigned int result;
  Pdu pdu;
  Vb vb(*oid);

  ++snmp_requests;
  pdu += vb;
  pdu.set_type(sNMP_PDU_GET);

  if ((result = snmpengine(idconn, &pdu, version, community, timeout, retry)) == 0) {
         
    if (pdu.get_vb_count()>0) {	
      pdu.get_vb(vb, 0);
      return(new SNMPResult(vb));
    }
  }

  return NULL;        
}
const SNMPResult *SNMP::snmpset(const unsigned int idconn, 
		const Vb *vb, 
		const unsigned int version, 
		const char * community, 
		const unsigned int timeout, 
		const unsigned int retry) 
{
  Pdu pdu;
  pdu += (Vb &)(*vb);
  Vb vb_res;
  int res; 
  ++snmp_requests;

  pdu.set_type(sNMP_PDU_SET);

  if ((res = snmpengine(idconn, &pdu, version, community, timeout, retry)) == 0) {

    pdu.get_vb(vb_res, 0);
    return(new SNMPResult(vb_res));
  }

  return NULL;
}
