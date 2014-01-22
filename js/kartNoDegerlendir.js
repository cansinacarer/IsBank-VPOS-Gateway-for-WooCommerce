
/*

kartNoDegerlendir() :

1. BIN numarasını ayır, (binNo)
2. Çeşidine göre isim ver, (Kaynak:Isbank_Sanal_POS_Test_Bilgileri.pdf)
3. ışbank Sanal Pos un bildiği çeşitlere taksit seçeneği ver,


Banka kartlarını 3D' ye zorlamak için BIN numaralarını bekliyorum.


*/


function kartNoDegerlendir() {
	var binCesidi;
	taksitKartNo = document.getElementById('pan').value;
	binNo = taksitKartNo.substr(0,6);
//BIN çeşidini tanımla
	if ( binNo == '450803' || 
		 binNo == '454318' || 
		 binNo == '454358' || 
		 binNo == '454359' || 
		 binNo == '454360' || 
		 binNo == '418342' || 
		 binNo == '418343' || 
		 binNo == '418344' || 
		 binNo == '418345'
	 	) {
	 	binCesidi = 'Isbank_Visa_Credit';
		//alert (binCesidi);
	} else if (
		 binNo == '540667' || 
		 binNo == '540668' || 
		 binNo == '543771' || 
		 binNo == '552096' || 
		 binNo == '553058' || 
		 binNo == '510152'
		) {
	 	binCesidi = 'Isbank_MasterCard_Credit';
		//alert (binCesidi);		
	} else if (
		 binNo == '454314' || 
		 binNo == '441075' || 
		 binNo == '441076' || 
		 binNo == '441077'
		) {
	 	binCesidi = 'Isbank_Visa_Debit';
		//alert (binCesidi);
	} else if (
		 binNo == '589283' || 
		 binNo == '603125'
		) {
	 	binCesidi = 'Isbank_MasterCard_Debit';
		//alert (binCesidi);
	} else if (
		 binNo == '444676' || 
		 binNo == '444677' || 
		 binNo == '444678' || 
		 binNo == '469894'
		) {
	 	binCesidi = 'ZiraatMax_Visa_Credit';
		//alert (binCesidi);
	} else if (
		 binNo == '534981'
	 ) {
	 	binCesidi = 'ZiraatMax_MasterCard_Credit';
		//alert (binCesidi);
	} else {
	 	binCesidi = 'no';
		//alert ('binCesidi bilinmiyor');
	}

//Taksit ekle-çıkar
	if (binCesidi == 'Isbank_Visa_Credit' || 
		binCesidi == 'Isbank_MasterCard_Credit' || 
		binCesidi == 'Isbank_Visa_Debit' || 
		binCesidi == 'Isbank_MasterCard_Debit' || 
		binCesidi == 'ZiraatMax_Visa_Credit' || 
		binCesidi == 'ZiraatMax_MasterCard_Credit'
		) {
		//alert ('Taksit eklendi');
		var select = document.getElementById("taksit");
		var secili = $("#taksit").val();
		select.options.length = 1;
		select.options[select.options.length] = new Option('2 taksit', '2');
		select.options[select.options.length] = new Option('3 taksit', '3');
		select.options[select.options.length] = new Option('4 taksit', '4');
		select.options[select.options.length] = new Option('5 taksit', '5');
		select.options[select.options.length] = new Option('6 taksit', '6');
		select.options[select.options.length] = new Option('7 taksit', '7');
		select.options[select.options.length] = new Option('8 taksit', '8');
		select.options[select.options.length] = new Option('9 taksit', '9');
		select.options[select.options.length] = new Option('10 taksit', '10');
		select.options[select.options.length] = new Option('11 taksit', '11');
		select.options[select.options.length] = new Option('12 taksit', '12');
		$("#taksit").val(secili);
	} else {
		//alert ('Taksitler gitti');
		var select = document.getElementById("taksit");
		select.options.length = 1;
	}

	if (select.options.length == 1) {

		//taksitler gittiyse vade farkını da 0 la
		$('.fee-kredi-karti-vade-farki .amount').text('0'+' TL');
		$('.total .amount').text(orderTotal+' TL');
	}


}//kartNoDegerlendir()