#include "action.h"

Action::Action(unsigned short int num, const char *opt) {
	this->_numAction = num;
	this->_option = new char[strlen(opt)+1];
	strcpy(this->_option, opt);
	this->_next = NULL;
}

Action::~Action() {
	delete[] _option;
	if(_next)
		delete _next;
}

void Action::addAction(unsigned short int num, const char *opt) {
	if(_next) // flemme de faire propre sans récurrence
		_next->addAction(num, opt);
	else
		_next = new Action(num, opt);
}

