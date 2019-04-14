/*_############################################################################
  _## 
  _##  config_snmp_pp.h  
  _##
  _##  SNMP++v3.2.9c
  _##  -----------------------------------------------
  _##  Copyright (c) 2001-2003 Jochen Katz, Frank Fock
  _##
  _##  This software is based on SNMP++2.6 from Hewlett Packard:
  _##  
  _##    Copyright (c) 1996
  _##    Hewlett-Packard Company
  _##  
  _##  ATTENTION: USE OF THIS SOFTWARE IS SUBJECT TO THE FOLLOWING TERMS.
  _##  Permission to use, copy, modify, distribute and/or sell this software 
  _##  and/or its documentation is hereby granted without fee. User agrees 
  _##  to display the above copyright notice and this license notice in all 
  _##  copies of the software and any documentation of the software. User 
  _##  agrees to assume all liability for the use of the software; 
  _##  Hewlett-Packard and Jochen Katz make no representations about the 
  _##  suitability of this software for any purpose. It is provided 
  _##  "AS-IS" without warranty of any kind, either express or implied. User 
  _##  hereby grants a royalty-free license to any and all derivatives based
  _##  upon this software code base. 
  _##  
  _##  Stuttgart, Germany, Tue Dec  2 01:31:09 CET 2003 
  _##  
  _##########################################################################*/

// $Id: config_snmp_pp.h,v 1.1.1.1 2005/03/14 10:55:29 kindman Exp $

#ifndef _CONFIG_SNMP_PP_H_
#define _CONFIG_SNMP_PP_H_

//! This is the amount of variable bindings, a snmp++ Pdu object can contain.
#define PDU_MAX_VBS 50

//! The maximum size of a message that can be sent or received.
#define MAX_SNMP_PACKET 4096

#ifndef DLLOPT
#if defined (WIN32) && defined (SNMP_PP_DLL)
#ifdef SNMP_PP_EXPORTS
#define DLLOPT __declspec(dllexport)
#else
#define DLLOPT __declspec(dllimport)	
#endif
#else
#define DLLOPT
#endif
#endif

// define SNMP_PP_IPv6 if you want to use IPv6
#ifndef WIN32
#define SNMP_PP_IPv6
#endif

// define _NO_SNMPv3 here or in the Makefile if you do not want to use SNMPv3
// (default is to use SNMPv3)
#define _NO_SNMPv3

// If you have not disabled SNMPv3, snmp++ will use libdes
// (separate package) as default.
// define _USE_LIBTOMCRYPT if you want to use libtomcrypt instead
// Note that _USE_OPENSSL will override libtomcrypt for SHA1, MD5 and DES.
// #define _USE_LIBTOMCRYPT

// If you define _USE_OPENSSL, snmp++ will use OpenSSL for SHA1,
// MD5 and DES. 
//#define _USE_OPENSSL

// If you do not use SNMP++ for commercial purposes or if you
// have licensed IDEA (read README.v3) you may define the following
// to enable IDEA support.
// #define _USE_IDEA

// define _NO_THREADS here or in the Makefile if you do not want thread support
// (default is to include thread support)
// #define _NO_THREADS

// define _IPX_ADDRESS and/or _MAC_ADDRESS if you want to use the
// classess IpxAddress/IpxSockAddress and/or MacAddress
#define _IPX_ADDRESS
#define _MAC_ADDRESS

// define this if you want to send out broadcasts
#define SNMP_BROADCAST


// Some older(?) compilers need a special declaration of
// template classes
// #define _OLD_TEMPLATE_COLLECTION

// We have inet_aton() function if not compiling with VC++
#ifndef _MSC_VER
#define HAVE_INET_ATON
#endif

// If IPv6 is enabled assume that inet_pton() is available
// If IPv6 and gcc then assume gethostbyname2() is available
#ifdef SNMP_PP_IPv6
#define HAVE_INET_PTON
#ifdef __GNUC__
#define HAVE_GETHOSTBYNAME2
#endif
#endif

///////////////////////////////////////////////////////////////////////
// Changes below this line should not be necessary
///////////////////////////////////////////////////////////////////////

#ifndef _NO_SNMPv3
#ifndef _SNMPv3
#define _SNMPv3
#endif
#endif

#ifndef _NO_THREADS
#ifdef WIN32

#ifndef _THREADS
#define _WIN32THREADS
#define VC_EXTRALEAN
#define _THREADS
#endif

#else  // !WIN32

#ifndef _THREADS
#define _THREADS
#endif

#ifndef POSIX_THREADS
#ifdef __unix
#define POSIX_THREADS
#endif
#endif

#endif // WIN32
#endif // !_NO_THREADS

#endif // _CONFIG_SNMP_PP_H_
