/*
	File: xajax_legacy.js
	
	Provides support for legacy scripts that have not been updated to the
	latest syntax.
	
	Title: xajax legacy support module
	
	Please see <copyright.inc.php> for a detailed description, copyright
	and license information.
*/

/*
	@package xajax
	@version $Id$
	@copyright Copyright (c) 2005-2007 by Jared White & J. Max Wilson
	@copyright Copyright (c) 2008-2009 by Joseph Woolley, Steffen Konerow, Jared White  & J. Max Wilson
	@license http://www.xajaxproject.org/bsd_license.txt BSD License
*/

try
{
	/*
		Class: xajax.legacy
	*/
	if ('undefined' == typeof xajax)
		throw { name: 'SequenceError', message: 'Error: xajax core was not detected, legacy module disabled.' }

	if ('undefined' == typeof xajax.legacy)
		xajax.legacy = {}

	/*
		Function: xajax.legacy.call
		
		Convert call parameters from the 0.2.x syntax to the new *improved*
		call format.
		
		Parameters: 
			sFunction - (string): Registered PHP Functionname to be called
			objParametes - (object): Paramter object for the request.
		
		This is a wrapper function around the standard <xajax.call> function.
	*/
	xajax.legacy.call = xajax.call;
	xajax.call = function(sFunction, objParameters) {
		var oOpt = {}
		oOpt.parameters = objParameters;
		if (undefined != xajax.loadingFunction) {
			if (undefined == oOpt.callback)
				oOpt.callback = {}
			oOpt.callback.onResponseDelay = xajax.loadingFunction;
		}
		if (undefined != xajax.doneLoadingFunction) {
			if (undefined == oOpt.callback)
				oOpt.callback = {}
			oOpt.callback.onComplete = xajax.doneLoadingFunction;
		}
		return xajax.legacy.call(sFunction, oOpt);
	}

	/*
		Boolean: isLoaded
		
		true - Indicates that the <xajax.legacy> module is loaded.
	*/
	xajax.legacy.isLoaded = true;
} catch (e) {
	alert(e.name + ': ' + e.message);
}
