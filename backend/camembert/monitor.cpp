/* =================================================

Camembert Project
Alban FERON, 2007

Monitor class.
Entièrement repris de Kindmana, par Aurélien Méré
(avec quelques commentaires en plus)

================================================== */

#include <pthread.h>
#include "monitor.h"
#include <stdio.h>
#include <stdlib.h>

Monitor::Monitor(unsigned int const maxjobs) {
	want_to_consume = 0;
	want_to_produce = 0;
	firstjob = 0;
	nextjob =0;
	this->maxjobs = maxjobs;
	jobs = (void **)malloc(sizeof(void*)*maxjobs);

	pthread_mutex_init(&mutex, NULL);
	pthread_cond_init(&can_produce, NULL);
	pthread_cond_init(&can_consume, NULL);
}

Monitor::~Monitor() {
	pthread_mutex_destroy(&mutex);
	pthread_cond_destroy(&can_produce);
	pthread_cond_destroy(&can_consume);
	free(jobs);
}

void Monitor::addJob(void * const job) {
	pthread_mutex_lock(&mutex);

	// Tant que le monitor est "plein"
	while (((nextjob + 1) % maxjobs) == firstjob) {
		// On dit qu'on veut ajouter un job
		++want_to_produce;
		// Et on attend qu'une place se libère
		pthread_cond_wait(&can_produce, &mutex);
		--want_to_produce;
	}

	// On ajoute le job
	jobs[nextjob++] = job;
	nextjob %= maxjobs;

	// Si un thread attend pour ajouter un job et qu'il y a de la place, 
	// on le signale pour qu'il se réveille.
	if (want_to_produce && (((nextjob + 1) % maxjobs) != firstjob))
		pthread_cond_signal(&can_produce);

	// Si un thread attend pour "lire" un job, comme on vient d'en ajouter un
	// on lui dit qu'il peut lire.
	if (want_to_consume)
		pthread_cond_signal(&can_consume);

	pthread_mutex_unlock(&mutex); 
}

void * const Monitor::getJob() {
	void *res;

	pthread_mutex_lock(&mutex);

	// Tant que ya rien à lire
	while (firstjob == nextjob) {
		// On dit qu'on veut lire
		++want_to_consume;
		// Et on attend qu'il y ait quelque chose à lire
		pthread_cond_wait(&can_consume, &mutex);
		--want_to_consume;
	}

	// On prend le job pour le retourner
	res = jobs[firstjob++];
	firstjob %= maxjobs;

	// Si un thread attend d'ajouter un job, comme on vient d'en "supprimer" un,
	// on lui dit qu'il peut l'ajouter
	if (want_to_produce)
		pthread_cond_signal(&can_produce);

	// Si un thread veut lire et qu'il y a encore des trucs à lire, on lui dit.
	if ((firstjob != nextjob) && want_to_consume)
		pthread_cond_signal(&can_consume);

	pthread_mutex_unlock(&mutex);
	return res;
}

bool const Monitor::isJobPending() const {
	return (firstjob != nextjob);
}

