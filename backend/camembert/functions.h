#ifndef __CAMEMBERT_FUNCTIONS_H
#define __CAMEMBERT_FUNCTIONS_H

#include "camembert.h"

void get_interfaces_infos(Materiel *m);
void get_arp_infos(Materiel *m);
void get_fdb_infos(Materiel *m);
void apply_actions(Materiel *m);

#endif /* __CAMEMBERT_FUNCTIONS_H */

