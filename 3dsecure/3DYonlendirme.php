<?php
    session_start();


$clientId = $_SESSION["clientId"];  //Banka tarafindan magazaya verilen isyeri numarasi
$oid = $_SESSION["oid"];            //Siparis numarasi
$amount = $_SESSION["amount"];      //tutar
$okUrl = $_SESSION["failUrl"];      //Islem basariliysa dönülecek isyeri sayfasi  (3D isleminin ve ödeme isleminin sonucu)
$failUrl = $_SESSION["failUrl"];    //Islem basarisizsa dönülecek isyeri sayfasi  (3D isleminin ve ödeme isleminin sonucu)
$islemtipi="Auth";                  //Islem tipi {Auth, PreAuth, PostAuth, Void, Credit
$taksit = $_SESSION["taksit"];      //Taksit sayisi
$rnd = microtime();                 //Tarih ve zaman gibi sürekli degisen bir deger güvenlik amaçli kullaniliyor
$storekey = $_SESSION["storekey"];  //Isyeri anahtari

$hashstr = $clientId . $oid . $amount . $okUrl . $failUrl . $islemtipi . $taksit . $rnd . $storekey; //güvenlik amaçli hashli deger
$hash = base64_encode(pack('H*',sha1($hashstr)));


// BENİM TANIMLADIKLARIM - değerde tr karaket olmuyor
$storetype = "3d_pay";      //"pay_hosting", “3d_pay_hosting”, "3d"
$lang = "tr";                       //storetype
$currency = "949";                  //currency TL için 949
$refreshtime = "0";                 //refreshtime banka onay sayfasında sonuç gösterilecek süre
$firmaadi = "GösterilenFirma Adı";  //Firmanın gösterilen adı




$curl_connection =  curl_init($_SESSION["addressToSend"]);
                    curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
                    curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
                    curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true); //set to true forces cURL not to display the output of the request, but return it as a string.
                    curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false); //option to false, so the request will not trigger an error in case of an invalid, expired or not signed SSL certificate.
/*					curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1); // 1 to instruct cURL to follow “Location: ” redirects found in the headers sent by the remote site.
*/

$post_data['pan'] = $_SESSION["pan"];
$post_data['cv2'] = $_SESSION["cv2"];
$post_data['Ecom_Payment_Card_ExpDate_Year'] = $_SESSION["Ecom_Payment_Card_ExpDate_Year"];
$post_data['Ecom_Payment_Card_ExpDate_Month'] = $_SESSION["Ecom_Payment_Card_ExpDate_Month"];
$post_data['cardType'] = $_SESSION["cardType"];
$post_data['clientid'] = $clientId;
$post_data['amount'] = $amount;
$post_data['oid'] = $oid;
$post_data['okUrl'] = $okUrl;
$post_data['failUrl'] = $failUrl;
$post_data['rnd'] = $rnd;
$post_data['hash'] = $hash;
$post_data['storetype'] = $storetype;
$post_data['lang'] = $lang;
$post_data['currency'] = $currency;
$post_data['islemtipi'] = 'Auth';
$post_data['taksit'] = $taksit;

$post_data['firmaadi'] = $_SESSION["firmaadi"];
$post_data['posDili'] = $_SESSION["posDili"];

$post_data['Fismi'] = $_SESSION["Fismi"];
$post_data['faturaFirma'] = $_SESSION["faturaFirma"];
$post_data['Fadres'] = $_SESSION["Fadres"];
$post_data['Fadres2'] = $_SESSION["Fadres2"];
$post_data['Fil'] = $_SESSION["Fil"];
$post_data['Filce'] = $_SESSION["Filce"];
$post_data['Fpostakodu'] = $_SESSION["Fpostakodu"];
$post_data['tel'] = $_SESSION["tel"];
$post_data['fulkekod'] = $_SESSION["fulkekod"];

$post_data['nakliyeFirma'] = $_SESSION["nakliyeFirma"];
$post_data['tismi'] = $_SESSION["tismi"];
$post_data['tadres'] = $_SESSION["tadres"];
$post_data['tadres2'] = $_SESSION["tadres2"];
$post_data['til'] = $_SESSION["til"];
$post_data['tilce'] = $_SESSION["tilce"];
$post_data['tpostakodu'] = $_SESSION["tpostakodu"];
$post_data['tulkekod'] = $_SESSION["tulkekod"];





foreach ( $post_data as $key => $value) 
{
    $post_items[] = $key . '=' . $value;
}
$post_string = implode ('&', $post_items);

//echo ('<h5>post string:</h5>' . $post_string);

curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);

$result = curl_exec($curl_connection);



//echo ('<br><strong>cURL Bilgileri: </strong><br>');
//echo ( curl_getinfo($curl_connection) );
//echo ('<br><strong>cURL Hata No: </strong><br>');
//echo ( curl_errno($curl_connection) );
//echo ('<br><strong>cURL Hata açıklaması: </strong><br>');
//echo ( curl_error($curl_connection) );


curl_close($curl_connection);


echo ($result);


    
    //remove PAReq
    unset($_SESSION["PAReq"]);
?>