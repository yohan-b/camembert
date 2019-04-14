#ifndef __CAMEMBERT_ACTION_H
#define __CAMEMBERT_ACTION_H

#include <string.h>

class Action {
	private:
		unsigned short int _numAction;
		char *_option;
		Action *_next;

	public:
		Action(unsigned short int num, const char *opt);
		~Action();

		void addAction(unsigned short int num, const char *opt);
		unsigned short int getNum() const { return _numAction; }
		const char *getOption() const { return _option; }
		Action *getNext() const { return _next; }
};


#endif /* __CAMEMBERT_ACTION_H */

