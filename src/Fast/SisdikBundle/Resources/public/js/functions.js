/**
 *
 * @author Ihsan Faisal
 * (c) 2008
 */

var ajax = new Array();

// mempopulasi kode yang diperlukan..
function createElements(nis) {
   var obj = document.getElementById("paket_sms_" + nis);
   eval(ajax[index].response);   // Executing the response from Ajax as Javascript code
}

function setPacket(nis, paket, reqFile) {
	alert ("nis: " + nis + "\npaket: " + paket + "\nreqFile: " + reqFile);

	if(thevalue.length>0){
		var index = ajax.length;
		ajax[index] = new sack();

		ajax[index].requestFile = reqFile + "?nis=" + param + "&paket=" + paket;
		ajax[index].onCompletion = function(){ updateCheckboxValue(nis); };
		ajax[index].runAJAX();
	}
}


