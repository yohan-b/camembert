/* =================================================

Camembert Project
Alban FERON, 2007

Fonctions pour la recherche des voisins (CDP)

=================================================== */

#ifndef __CAMEMBERT_NEIGHBOURS_H
#define __CAMEMBERT_NEIGHBOURS_H

#include "camembert.h"

/**
 * Cherche les voisins d'un materiel donné
 * Les nouveaux voisins sont ajoutés à la liste globale du matériel
 * et au monitor pour exu aussi être analysés.
 *
 * @param m - Materiel à partir duquel on doit chercher les voisins
 * @return VRAI si au moins un nouveau voisin a été trouvé, FAUX sinon.
 */
bool look_for_neighbours(Materiel *m);

#endif /* __CAMEMBERT_NEIGHBOURS_H */
