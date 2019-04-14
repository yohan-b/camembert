/* =================================================

Camembert Project
Alban FERON, 2007

Monitor class.
Entièrement repris de Kindmana, par Aurélien Méré

================================================== */

#ifndef __CAMEMBERT_MONITOR_H
#define __CAMEMBERT_MONITOR_H

class Monitor
{
	private:
		pthread_mutex_t mutex;
		pthread_cond_t can_produce;
		pthread_cond_t can_consume;
		unsigned int want_to_consume;
		unsigned int want_to_produce;
		unsigned int firstjob;
		unsigned int nextjob;
		unsigned int maxjobs;
		void **jobs;

	public:
		Monitor(unsigned int const maxjobs);
		~Monitor();
		void addJob(void * const job);
		void * const getJob();
		bool const isJobPending() const;
};

#endif /* __CAMEMBERT_MONITOR_H */
