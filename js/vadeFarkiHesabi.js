 jQuery(document).ready(function($){
     $(document.body).on('change', 'select[name="taksit"]', function() {

		if($('#taksit').val() != "") {
/*
	//sepet ara toplamını al
			cartSubtotal=$(".cart-subtotal .amount").text();
			cartSubtotal = cartSubtotal.replace(/[^0-9.]/ig, '');
			cartSubtotal = parseFloat(cartSubtotal);

	//shipping ücreti varsa shipping e tanımla yoksa boş tanımla
			shipping=$("#shipping_method input[type=radio]:checked + label .amount").text();
			if (shipping==''){shipping = 0} else {
				shipping.replace(/[^0-9.]/ig, '');
				shipping = parseFloat(shipping);
			}

			sepetToplami = cartSubtotal + shipping;*/
			sepetToplami = orderTotal;
			$yuzdeKac = vadeFarki * $('#taksit').val(); // Faiz yüzdesini hesaplıyor yuzde 2 X 3 taksit = toplam fiyata yuzde 6 faiz verir
			$percentage = $yuzdeKac * 0.01; // rakamı kesire çevir - 6yı %6ya çevir


			$yeniFaiz = sepetToplami * $percentage;
			$yeniTotal = sepetToplami + $yeniFaiz;

	// 100 le çarp, round, 100e böl
			$yeniFaiz = Math.round($yeniFaiz*100) / 100;
			$yeniTotal = Math.round($yeniTotal*100) / 100;

// yeni vade farkı
			$vadeFarkiMiktari = $yeniFaiz;
			$('#vadeFarkiBilgisi').show();
			$('#vadeFarkiBilgisi .rakam').text($vadeFarkiMiktari+' TL');


			$('.fee-kredi-karti-vade-farki .amount').text($yeniFaiz +' TL');
			$('.total .amount').text($yeniTotal+' TL');

    	} else {
    		$('.fee-kredi-karti-vade-farki .amount').text('0'+' TL');
			$('.total .amount').text(orderTotal+' TL');
			$('#vadeFarkiBilgisi').hide();
    	}
    });
 });
