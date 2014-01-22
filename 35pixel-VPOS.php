<?php

/**
 * Plugin Name: WooCommerce Payment Gateway - Virtual POS
 * Plugin URI: http://www.35pixel.com/
 * Description: Virtual POS integration as a WooCommerce Payment Gateway by <a href="http://www.35pixel.com" target="_blank">35pixel Digital Media Agency</a>
 * Author: Cansın Çağan Acarer
 * Author URI: http://cansinacarer.com/
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly




if ( !defined('CCA_VPOS_PLUGIN_URL') )
    define('CCA_VPOS_PLUGIN_URL', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__)));




add_action( 'plugins_loaded', 'cca_vpos_init', 0 );

function cca_vpos_init() {


	class cca_vpos extends WC_Payment_Gateway {

/*ÖZEL ADMIN ALANLARININ DEĞİŞKENLERİNİ TANIMLADIK*/
		var $clientId;
		var $name;
		var $password;
		var $storekey;
		var $lang;
		var $testmode;
		var $isbank_3D_address;
		var $isbank_non3D_address;
		var $refreshtime;
		var $firmaadi;
		var $currency;
		var $vadeFarki;

     //constructor
        function __construct() {

		    $this->id = 'sanalpos';
            $this->method_title = 'Sanal Pos';
            $this->icon = CCA_VPOS_PLUGIN_URL . '/images/credit-cards.png';
		    $this->has_fields = true;

//benim değişkenlerimden tanımladıklarım
		    $this->lang = 'tr';
		    $this->isbank_3D_address = 'https://spos.isbank.com.tr/servlet/est3Dgate';
		    $this->isbank_non3D_address = 'https://spos.isbank.com.tr/servlet/cc5ApiServer';
		    $this->refreshtime = '0';
		    $this->currency = '949'; //Türk lirası için
            $this->iframe_3d_redirect       = CCA_VPOS_PLUGIN_URL . '/3dsecure/3DYonlendirme.php';
            $this->iframe_3d_degerlendir    = CCA_VPOS_PLUGIN_URL . '/3dsecure/3DDegerlendirme.php';

			$this->notify_url   	= str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'cca_vpos', home_url( '/' ) ) );

         // Create plugin fields and settings
			$this->init_form_fields();
			$this->init_settings();

/*
INIT SETTINGSTEN SONRA ADMIN AYARLARINI DEĞİŞKENLERE YULEYEBİLİYORUZ
ADMINDE ALDIĞIMIZ AYARLARI ALDIK, DEĞİŞKENLERE YÜKLEDİK,
ŞÖYLE DE ÇEKİLEBİLİR:  $this->title = $this->get_option( 'title' );
SOLDAKİLER BİZİM TANIMLIYOR OLDUĞUMUZ DEĞİŞKENLER, SAĞDAKİLER WC DEN ÇEKME YÖNTEMİ
YANİ SOLDAKİLER ÖZEL DEĞİŞKENLER, SAĞDAKİLER WC NİN KENDİ DEĞİŞKENLERİ VEYA HAZIR GİRDİĞİMİZ VERİLER
*/
			$this->title 			= $this->settings['title'];
			$this->description 		= $this->settings['description'];
			$this->enabled 			= $this->settings['enabled'];
			$this->clientId			= $this->settings['clientId'];
			$this->name				= $this->settings['name'];
			$this->password 		= $this->settings['password'];
			$this->storekey 		= $this->settings['storekey'];
			$this->lang 			= $this->settings['lang'];
			$this->testmode 		= $this->settings['testmode'];
			$this->firmaadi 		= $this->settings['firmaadi'];
			$this->order_prefix 	= $this->settings['order_prefix'];
			$this->vadeFarki 		= $this->settings['vadeFarki'];


            add_action('woocommerce_receipt_sanalpos', array($this, 'TdSecureOdemeSayfasi'));
            add_action('woocommerce_thankyou_sanalpos', array($this, 'TdSecureTesekkurSayfasi'), 10, 1);

        	add_action( 'woocommerce_calculate_totals', array( $this, 'yanit_isle' ), 10, 1 );


			if($this->testmode==yes) {
				$this->clientId = '700200000';
				$this->name 	= 'ISBANK';
				$this->password = 'ISBANK07';
				$this->storekey = '123456';
			    $this->isbank_3D_address = 'https://testsanalpos.est.com.tr/servlet/est3Dgate';
			    $this->isbank_non3D_address = 'https://testsanalpos.est.com.tr/servlet/cc5ApiServer';
//ADRESİ DE TANIMLA
			}

//ayarları kaydetmek için
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); 
        }//__construct()

//ADMINDE ÇIKACAK SEÇENEKLER
	    function init_form_fields() {
	    
	    	$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'İş Bankası Sanal Pos ile ödeme seçeneği müşterilere sunulsun mu?', 'woothemes' ), 
								'label' => __( 'Evet', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => '', 
								'default' => 'no'
							), 
				'title' => array(
								'title' => __( 'Ödeme Yönteminin adı', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Müşterinin sipariş sırasında göreceği ödeme seçeneği ismi.', 'woothemes' ), 
								'default' => __( 'Kredi Kartı veya Bankamatik Kartı', 'woothemes' )
							),
				'description' => array(
								'title' => __( 'Açıklama', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Müşterinin sipariş sırasında göreceği açıklama.', 'woothemes' ), 
								'default' => 'Kredi Kartı veya Banka Kartı ile ödeme'
							), 
				'vadeFarki' => array(
								'title' => __( 'Aylık Vade Farkı %\'si', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( '%1 için sadece 1 yazın. Vade farkı \'Aylık Vade Farkı\' x \'Taksit Sayısı\' yöntemi ile hesaplanır. Örneğin \'Aylık Vade Farkı\' nı 0.83 olarak belirlerseniz 6 taksitte vade farkı 0.83 x 6 ≈ 5\'ten %5 olacaktır.', 'woothemes' ), 
								'default' => '0'
							),
				'order_prefix' => array(
								'title' => __( 'Sipariş Numarası Öneki', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Raporlar arabiriminde siparişleriniz bu önek ile görünecektir.', 'woothemes' ), 
								'default' => ''
							),
				'firmaadi' => array(
								'title' => __( '3D Mağaza adınız', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Müşterinin 3D onay ekranında göreceği mağaza ismi.', 'woothemes' ), 
								'default' => ''
							),
				'lang' => array(
								'title' => __( 'Sanal Pos Dili', 'woothemes' ), 
								'type' => 'select', 
								'description' => __( 'Bankadan hata mesajlarının hangi dilde geleceğini seçin.', 'woothemes' ), 
								'default' => 'tr',
								'options' => array(
										          	'tr' => 'Türkçe',
										          	'en' => 'İngilizce'
										     	)
							),  
				'clientId' => array(
								'title' => __( 'İş Bankası - Mağaza Numarası', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Client ID', 'woothemes' ), 
								'default' => ''
							),
				'name' => array(
								'title' => __( 'İş Bankası - API Kullanıcı Adı', 'woothemes' ), 
								'type' => 'text', 
								'description' => '',
								'default' => ''
							), 
				'password' => array(
								'title' => __( 'İş Bankası - API Şifre', 'woothemes' ), 
								'type' => 'text', 
								'description' => '', 
								'default' => ''
							), 
				'storekey' => array(
								'title' => __( 'İş Bankası - Üye İşyeri Anahtarı', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Storekey - Sadece 3D Secure kullanıcıları için.', 'woothemes' ), 
								'default' => ''
							),
				'testmode' => array(
								'title' => __( 'Test Modu', 'woothemes' ), 
								'label' => __( 'Test Modu Aktif', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Dikkat: Test Modunda yapılan ödemeler gerçek değildir! Sadece test bilgileriyle test sunucusuna işlem gönderilir.', 'woothemes' ), 
								'default' => 'no'
							), 
				);
	    }//init_form_fields
//YUKARIDA TANIMLADIK BURADA SADECE SAYFAYA ÇEKİYORUZ
		function admin_options() {
	    	?>
	    	<style type="text/css">
				#wc_get_started.vPOS {
					background: #5BC0DE <?php echo 'url('; get_site_url(); echo '/wp-content/plugins/35pixel-VPOS/images/35pixel.png)'; ?>no-repeat 15px 18px;
					border: 1px solid #339BB9;
					padding: 15px 15px 15px 210px;
					box-shadow: inset 1px 1px 0 rgba(255, 255, 255, 0.5),inset -1px -1px 0 rgba(255, 255, 255, 0.5);
					-moz-box-shadow: inset 1px 1px 0 rgba(255,255,255,0.5),inset -1px -1px 0 rgba(255,255,255,0.5);
					-webkit-box-shadow: inset 1px 1px 0 rgba(255, 255, 255, 0.5),inset -1px -1px 0 rgba(255, 255, 255, 0.5);
				}
				#wc_get_started.vPOS span.main {
					color: #3968A5;
				}
				#wc_get_started.vPOS span {
			    	color: #FFF;
					text-shadow: none ;
				}
	    	</style>
	    	<h3>35pixel Sanal Pos</h3>
	    	<div id="wc_get_started" class="vPOS">
				<span class="main">35pixel Sanal Pos Ödeme Yöntemi hakkında</span>
				<span><a href="http://www.35pixel.com/" style="color:#fff" target="_blank">35pixel</a> E-Ticaret sistemi sanal pos entegrasyonu hazır olarak gelir. Sadece mağaza bilgilerinizi girerek sitenizden ödeme almaya hemen başlayabilirsiniz.</span>
				<span>İşlemlerinizin bankadaki dökümünü görmek için Raporlar Arabirimine ziyaret edebilirsiniz.</span>
				<p><a href="https://spos.isbank.com.tr/isbank/report/user.login" target="_blank" class="button button-primary">Raporlar Arabirimi</a>
				</p>
			</div>
	    	<table class="form-table">
	    		<?php $this->generate_settings_html(); ?>
			</table>
			<fieldset style="border: #DDD 2px solid; border-radius: 4px; -moz-border-radius: 4px; -khtml-border-radius: 4px; -webkit-border-radius: 4px; padding: 30px; margin: 20px; float: left; color: #8496B1;">
				<legend style="margin: -20px; padding-left: 10px; padding-right: 10px; font-size: 16px; font-weight: bold;">Test işlemleri için gerekli bilgiler</legend>
				<h3>İşbankası Test Kredi Kartı Bilgileri</h3>
				<strong>Kart Numarası:</strong>
				<p style="display: inline;">4508034508034509</p><br>
				<strong>Son Kullanma Tarihi:</strong>
				<p style="display: inline;">12/15</p><br>
				<strong>Güvenlik Kodu:</strong>
				<p style="display: inline;">000</p><br>
				<h3>İşbankası Test Raporlar Arabirimi Giriş Bilgileri</h3>
				<strong>Mağaza Numarası:</strong>
				<p style="display: inline;">700200000</p><br>
				<strong>Kullanıcı Adı:</strong>
				<p style="display: inline;">ISBANK</p><br>
				<strong>Şifre:</strong>
				<p style="display: inline;">ISBANK07</p><br>
				<p><a href="https://testsanalpos.est.com.tr/isbank/report/user.login" target="_blank">Test raporlar arabirimi' ne girmek için tıklayın</a></p>
				<p class="description">Test işlemleri için raporlar arabirimine de aynı giriş bilgileri kullanarak aşağıdaki adresten giriş yapabilirsiniz.</p>
			</fieldset><div style="clear: both;"></div>
	    	<?php
	    }//admin_options


//ÖDEME FORMU
        public function payment_fields() {

            global $woocommerce; 
            
            $checkout = $woocommerce->checkout();
            // create month options
            $month_select = "";
            for ($i=0; $i < 12; $i++){
                $month = sprintf('%02d', $i+1);
                $month_select .= "<option value='" . $month . "' " . $select . ">" . $month . "</option>\n";
            }    
            
            // create options for valid until on years
            $year_now = date('y');
            $from_year_select = "";
            $until_year_select = "";
            for($y = $year_now; $y < $year_now + 7; $y++){
                $year = sprintf('%02d', $y);
                $until_year_select .= "<option value='" . $year . "' " . $select . ">" . $year . "</option>\n";
            }
            
            // billing fullname DEPRECATED :)
            // $fullname = $checkout->get_value( 'billing_first_name' ) . " " . $checkout->get_value( 'billing_last_name' );
                
            ?>           
            <table>
            <tbody>
            <tr>
            <td><label for="pan">Kart Numarası <span class="required">*</span></label></td>
            <td><input onchange="kartNoDegerlendir()" type="text" class="input-text" id="pan" name="pan" size="20" maxlength="16" autocomplete="off" placeholder="Kart Numarası" value=""></td>
            </tr>         
            <tr>
            <td><label for="Ecom_Payment_Card_ExpDate_Month">Son Kullanma Tarihi <span class="required">*</span></label></td>
            <td><select id="Ecom_Payment_Card_ExpDate_Month" name="Ecom_Payment_Card_ExpDate_Month">
				<option value="00" selected="selected">Ay</option>
                <?php echo $month_select; ?>
            </select>&nbsp;<select id="Ecom_Payment_Card_ExpDate_Year" name="Ecom_Payment_Card_ExpDate_Year">
				<option value="00" selected="selected">Yıl</option>
				<?php echo $until_year_select; ?>
            </select>
            </td>
            </tr>   
            <tr>            
            <td><label for="cv2">Güvenlik Kodu <span class="required">*</span></label></td>
            <td><input id="cv2" name="cv2" size="4" maxlength="4" autocomplete="off" type="text" class="input-text" placeholder="CVC" value="">
            <span>Kartınızın arkasındaki numaranın son 3 hanesi.</span>
        	</td>            
            </tr><!--
            <tr>
            <td><label for="isim">Kart Üzerindeki İsim <span class="required">*</span></label></td>
            <td><input id="isim" name="isim" type="text" class="input-text" value="<?php echo $fullname; ?>" placeholder="Kart Üzerindeki İsim"></td>
            </tr>-->
            <tr>
            <td><label for="taksit">Taksit Seçenekleri <span class="required">*</span></label></td>
            <td>
				<div id="vadeFarkiBilgisi" style="display:none; float:right">
					<span class="metin">Vade farkı:</span>
					<span class="rakam"></span>
				</div>
                <select id="taksit" name="taksit">
					<option value="">Peşin</option>
                </select>
            </td>
            </tr>
			
			<tr id="taksitSecenekleri">
				<td>
                	<img src="<?php echo get_site_url() . '/wp-content/plugins/35pixel-VPOS/images/maximum-kart.png'; ?>" style="width: 150px;">
            	</td>
                <td style="">
                	<table cellspacing="0" cellpadding="0" class="">
						<tbody>
							<tr class="card-title">
							   <td>Taksit</td>
							   <td>Taksit Tutarı</td>
							   <td>Toplam Tutar</td>
							</tr>

							<tr class="highlight">               
							 <td>Peşin</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 1 * 0.01)))/1, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 1 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>2</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 2 * 0.01)))/2, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 2 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>3</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 3 * 0.01)))/3, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 3 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>4</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 4 * 0.01)))/4, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 4 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>5</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 5 * 0.01)))/5, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 5 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>6</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 6 * 0.01)))/6, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 6 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>7</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 7 * 0.01)))/7, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 7 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>8</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 8 * 0.01)))/8, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 8 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>9</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 9 * 0.01)))/9, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 9 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>10</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 10 * 0.01)))/10, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 10 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>11</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 11 * 0.01)))/11, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 11 * 0.01)), 2); ?> TL</td>
							</tr>

							<tr class="highlight">               
							 <td>12</td>
							 <td><?php echo round((( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 12 * 0.01)))/12, 2); ?> TL</td>
							 <td><?php echo round(( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) + (( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total )*($this->vadeFarki * 12 * 0.01)), 2); ?> TL</td>
							</tr>

						</tbody></table></td></tr>
            <tr>
            <td><label for="cardType">Kart Tipi <span class="required">*</span></label></td>
            <td>
                <select id="cardType" name="cardType">
					<option value="1">Visa</option>
                	<option value="2">MasterCard</option>
                	<option value="3">American Express</option>
                </select>
            </td>
            </tr>
            <tr id="threeDSenecegi">
            <td><label for="threeD">3D Secure İle Öde <span class="required">*</span></label>
            </td>
            <td>
				<input name="threeD" type="checkbox" value="yes" id="threeD">
				<label for="threeD">3D Secure yöntemi ile ödeme yapmak istiyorum.</label>
				<br>
				<span>(Hesap kartlarından ödeme yapabilmek için zorunludur.)</span>
            </td>
            </tr>
            </tbody>
            </table>
<style>
.payment_box select {
	display: inline-block !important;
}
#taksitSecimi {
	display: block !important;
}
#taksitSecenekleri td {
	font: normal 11px Tahoma, Arial, Verdana;
	border: 1px solid #DDD;
	vertical-align: middle;
	text-align: center;
	padding: 5px;
}
</style>
<script src="//code.jquery.com/jquery-latest.js"></script>
<script src="//code.jquery.com/ui/1.10.3/jquery-ui.js" type="text/javascript"></script>
<script src="<?php echo get_site_url() ?>/wp-content/plugins/35pixel-VPOS/js/kartNoDegerlendir.js" type="text/javascript"></script>
<script src="<?php echo get_site_url() ?>/wp-content/plugins/35pixel-VPOS/js/numpad.js" type="text/javascript"></script>
<link href="<?php echo get_site_url() ?>/wp-content/plugins/35pixel-VPOS/css/numpad.css" rel="stylesheet" type="text/css">
<script src="<?php echo get_site_url() ?>/wp-content/plugins/35pixel-VPOS/js/vadeFarkiHesabi.js" type="text/javascript"></script>
<script type="text/javascript">
	jQuery(document).ready(function($){
		jQuery(function () {
			jQuery('#pan').keypad({
				showAnim: 'blind',
				layout:['123', '456', '789', jQuery.keypad.BACK + '0' + jQuery.keypad.CLOSE ]
			});
			jQuery('#cv2').keypad({
				showAnim: 'blind',
				layout:['123', '456', '789', jQuery.keypad.BACK + '0' + jQuery.keypad.CLOSE ]
			});

			jQuery('#pan').removeAttr( "readonly" );
			jQuery('#cv2').removeAttr( "readonly" );
		});
	});
	jQuery( "#pan" ).click(function( event ) {
		jQuery(function () {
			jQuery('#pan').keypad({
				showAnim: 'blind',
				layout:['123', '456', '789', jQuery.keypad.BACK + '0' + jQuery.keypad.CLOSE ]
			});
			jQuery('#pan').removeAttr( "readonly" );
		});
	});
	jQuery( "#pan" ).click(function( event ) {
		jQuery(function () {
			jQuery('#cv2').keypad({
				showAnim: 'blind',
				layout:['123', '456', '789', jQuery.keypad.BACK + '0' + jQuery.keypad.CLOSE ]
			});
			jQuery('#cv2').removeAttr( "readonly" );
		});
	});


	var orderTotal = <?php echo ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) ?>;
	var vadeFarki = <?php echo $this->vadeFarki ?>;
</script>
<?php

        }//payment_fields()


		function validate_fields() {

			global $woocommerce;

            if(empty($_POST['pan']))//Kart numarası girilmemişse
                $woocommerce->add_error('Kart numaranızı girmediniz.');
            if($_POST['Ecom_Payment_Card_ExpDate_Month'] == '00')//SKT ay seçilmemişse
                $woocommerce->add_error('Son kullanma tarihi için ay bilgisini seçmediniz.');
            if($_POST['Ecom_Payment_Card_ExpDate_Year'] == '00')//SKT yıl seçilmemişse
                $woocommerce->add_error('Son kullanma tarihi için yıl bilgisini seçmediniz.');
            if(empty($_POST['cv2']))//CV2 numarası girilmemişse
                $woocommerce->add_error('Kartınızın arkasındaki güvenlik kodunu girmediniz.');

            if(!$woocommerce->error_count()){//BURAYA KADAR hata yoksa
                $this->validated = true;
            }
            else{
                $this->validated = false;
            }// !$woocommerce->error_count()

		}//validate_fields()


		function process_payment( $order_id ) {

			global $woocommerce;

            // exit if validation fails
            if(! $this->validated) return;

			$order = new WC_Order( $order_id );
			$checkout = $woocommerce->checkout(); //checkout bilgilerini çağır:

			// $payment_type
			$threeD = $checkout->get_value( 'threeD' );
			if ($threeD == yes) {$payment_type = 'isbank-3dsecure';} elseif ($threeD == '') {$payment_type = 'isbank-non-3d';}

			
			session_start();
			unset($_SESSION["threeD"]);
			if ($payment_type == 'isbank-3dsecure') {


	           	session_start();

	           	$_SESSION["addressToSend"]						= $this->isbank_3D_address;
	           	$_SESSION["pan"] 								= str_replace( array( ' ', '-' ), '', $checkout->get_value( 'pan' ));
	           	$_SESSION["cv2"] 								= $checkout->get_value( 'cv2' );
	           	$_SESSION["Ecom_Payment_Card_ExpDate_Month"] 	= $checkout->get_value( 'Ecom_Payment_Card_ExpDate_Month' );
	           	$_SESSION["Ecom_Payment_Card_ExpDate_Year"] 	= $checkout->get_value( 'Ecom_Payment_Card_ExpDate_Year' );
	           	$_SESSION["cardType"] 							= $checkout->get_value( 'cardType' );
	           	$_SESSION["clientId"]							= $this->clientId;
	           	$_SESSION["amount"]								= $order->order_total;
	           	$_SESSION["oid"] 								= $this->order_prefix . $order->id;
	           	$_SESSION["storekey"] 							= $this->storekey;
	           	$_SESSION["taksit"] 							= $checkout->get_value( 'taksit' );

            	$_SESSION["okUrl"] 								= add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_thanks_page_id'))));
	           	$_SESSION["failUrl"]							= $this->iframe_3d_degerlendir;

				$_SESSION["checkoutUrl"]						= add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_checkout_page_id'))));
				$_SESSION["returnUrl"]							= get_permalink(get_option('woocommerce_checkout_page_id'));

	           	$_SESSION["wc_oid"] 							= $order->id;
	           	$_SESSION["wc_okey"] 							= $this->order->order_key;

	           	$_SESSION["threeD"]								= 'yes';


	           	//İsteğe bağlı gönderilen bilgiler
	           	$_SESSION["firmaadi"] 							= $this->firmaadi;
	           	$_SESSION["posDili"] 							= $this->lang;

	           	$_SESSION["Fismi"] 								= $checkout->get_value( 'billing_first_name' ) . ' ' . $checkout->get_value( 'billing_last_name' );
	           	$_SESSION["faturaFirma"] 						= $checkout->get_value( 'billing_company' );
	           	$_SESSION["Fadres"] 							= $checkout->get_value( 'billing_address_1' );
	           	$_SESSION["Fadres2"] 							= $checkout->get_value( 'billing_address_2' );
	           	$_SESSION["Fil"] 								= $checkout->get_value( 'billing_state' );
	           	$_SESSION["Filce"] 								= $checkout->get_value( 'billing_city' );
	           	$_SESSION["Fpostakodu"] 						= $checkout->get_value( 'billing_postcode' );
	           	$_SESSION["tel"] 								= $checkout->get_value( 'billing_phone' );
	           	$_SESSION["fulkekod"] 							= $checkout->get_value( 'billing_country' );

	           	$_SESSION["nakliyeFirma"] 						= $checkout->get_value( 'shipping_company' );
	           	$_SESSION["tismi"] 								= $checkout->get_value( 'shipping_first_name' ) . ' ' . $checkout->get_value( 'shipping_last_name' );
	           	$_SESSION["tadres"] 							= $checkout->get_value( 'shipping_address_1' );
	           	$_SESSION["tadres2"] 							= $checkout->get_value( 'shipping_address_2' );
	           	$_SESSION["til"] 								= $checkout->get_value( 'shipping_state' );
	           	$_SESSION["tilce"] 								= $checkout->get_value( 'shipping_city' );
	           	$_SESSION["tpostakodu"] 						= $checkout->get_value( 'shipping_postcode' );
	           	$_SESSION["tulkekod"] 							= $checkout->get_value( 'shipping_country' );



				return array(
					'result' 	=> 'success',
					'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
				);


// 3D seçilmemiş durumda işlemler:
			} elseif ($payment_type == 'isbank-non-3d') {

				// XML request sablonu
				$request = "DATA=<?xml version=\"1.0\" encoding=\"ISO-8859-9\"?>
				<CC5Request>
				<Name>" . $this->name . "</Name>
				<Password>" . $this->password . "</Password>
				<ClientId>" . $this->clientId . "</ClientId>
				<IPAddress>" . $_SERVER['REMOTE_ADDR'] . "</IPAddress>
				<Email>" . $checkout->get_value( 'billing_email' ) . "</Email>
				<Mode>P</Mode>
				<OrderId>" . $this->order_prefix . $order->id . "</OrderId>
				<GroupId></GroupId>
				<TransId></TransId>
				<UserId></UserId>
				<Type>Auth</Type>
				<Number>" . str_replace( array( ' ', '-' ), '', $checkout->get_value( 'pan' )) . "</Number>
				<Expires>" . $checkout->get_value( 'Ecom_Payment_Card_ExpDate_Month' ) . "/" . $checkout->get_value( 'Ecom_Payment_Card_ExpDate_Year' ) . "</Expires>
				<Cvv2Val>" . $checkout->get_value( 'cv2' ) . "</Cvv2Val>
				<Total>" . $order->order_total ."</Total>
				<Currency>949</Currency>
				<Taksit>" . $checkout->get_value( 'taksit' ) . "</Taksit>
				<BillTo>
				<Name>" . $checkout->get_value( 'billing_first_name' ) . ' ' . $checkout->get_value( 'billing_last_name' ) . "</Name>
				<Street1>" . $checkout->get_value( 'billing_address_1' ) . "</Street1>
				<Street2>" . $checkout->get_value( 'billing_address_2' ) . "</Street2>
				<Street3></Street3>
				<City>" . $checkout->get_value( 'billing_city' ) . "</City>
				<StateProv>" . $checkout->get_value( 'billing_state' ) . "</StateProv>
				<PostalCode>" . $checkout->get_value( 'billing_postcode' ) . "</PostalCode>
				<Country>" . $checkout->get_value( 'billing_country' ) . "</Country>
				<Company>" . $checkout->get_value( 'billing_company' ) . "</Company>
				<TelVoice>" . $checkout->get_value( 'billing_phone' ) . "</TelVoice>
				</BillTo>
				<ShipTo>
				<Name>" . $checkout->get_value( 'shipping_first_name' ) . ' ' . $checkout->get_value( 'shipping_last_name' ) . "</Name>
				<Street1>" . $checkout->get_value( 'shipping_address_1' ) . "</Street1>
				<Street2>" . $checkout->get_value( 'shipping_address_2' ) . "</Street2>
				<Street3></Street3>
				<City>" . $checkout->get_value( 'shipping_city' ) . "</City>
				<StateProv>" . $checkout->get_value( 'shipping_state' ) . "</StateProv>
				<PostalCode>" . $checkout->get_value( 'shipping_postcode' ) . "</PostalCode>
				<Country>" . $checkout->get_value( 'shipping_country' ) . "</Country>
				</ShipTo>
				<Extra></Extra>
				</CC5Request>
				";


				$url = $this->isbank_non3D_address;
				$ch = curl_init();    // initialize curl handle
				curl_setopt($ch, CURLOPT_URL,$url); // set url to post to
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
				curl_setopt($ch, CURLOPT_TIMEOUT, 90); // times out after 4s
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $request); // add POST fields

				$result = curl_exec($ch); // run the whole process

				if (curl_errno($ch)) {
					$woocommerce->add_error( 'Sunucu bağlantısında hata oluştu:<br>(' . curl_error($ch) . ')' );
					return null;
				} else {curl_close($ch);}


				$response_tag="Response";
				$posf = strpos (  $result, "<Response>" );
				$posl = strpos (  $result, "</Response>" ) ;
				$Response = strip_tags(substr (  $result, $posf , $posl - $posf   )) ;

				$response_tag="ErrMsg";
				$posf = strpos (  $result, "<" . $response_tag . ">" );
				$posl = strpos (  $result, "</" . $response_tag . ">" ) ;
				$ErrMsg = strip_tags(substr (  $result, $posf , $posl - $posf   )) ;

				$response_tag="HOSTMSG";
				$posf = strpos (  $result, "<" . $response_tag . ">" );
				$posl = strpos (  $result, "</" . $response_tag . ">" ) ;
				$HOSTMSG = strip_tags(substr (  $result, $posf , $posl - $posf   )) ;

				$response_tag="ProcReturnCode";
				$posf = strpos (  $result, "<" . $response_tag . ">" );
				$posl = strpos (  $result, "</" . $response_tag . ">" ) ;
				$ProcReturnCode = strip_tags(substr (  $result, $posf , $posl - $posf   )) ;

				$response_tag="OrderId";
				$posf = strpos (  $result, ("<" . $response_tag . ">") );
				$posl = strpos (  $result, ("</" . $response_tag . ">") ) ;
				$OrderId = strip_tags(substr (  $result, $posf , $posl - $posf   )) ;

				$response_tag="AuthCode";
				$posf = strpos (  $result, "<" . $response_tag . ">" );
				$posl = strpos (  $result, "</" . $response_tag . ">" ) ;
				$AuthCode = strip_tags(substr (  $result, $posf , $posl - $posf   )) ;


				if ( $Response == 'Approved' ) {
					// Success
					$order->add_order_note( 'Ödeme alındı. <br> Sipariş Numarası:<br>' . $OrderId . '<br>Provizyon Numarası:<br>' . $AuthCode );
					$order->payment_complete();

					// Return thank you redirect
					return array (
					  'result'   => 'success',
					  'redirect' => $this->get_return_url( $order ),
					);

					} elseif ( $Response == 'Error' ) {
						
						$woocommerce->add_error( 'Banka sunucusundan hata mesajı geldi:' );
						$woocommerce->add_error( $ErrMsg );
						$woocommerce->add_error( 'Hata Kodu: ' . $ProcReturnCode );
						$woocommerce->add_error( $HOSTMSG );

						$order = new WC_Order( $order_id );
						$order->update_status('failed', 'Ödeme başarısız oldu.');


					} elseif ( $Response == 'Declined' ) {
						
						$woocommerce->add_error( 'Kartınız banka tarafından reddedildi.' );
						$woocommerce->add_error( $ErrMsg );
						$woocommerce->add_error( $HOSTMSG );
						$woocommerce->add_error( 'Hata Kodu: ' . $ProcReturnCode );
						
						$order = new WC_Order( $order_id );
						$order->update_status('failed', 'Ödeme başarısız oldu.');


					} else {
					$order->add_order_note( 'Bankadan gelen yanıt anlaşılamadı. <br>Yanıt:' . $Response );
					$woocommerce->add_error( 'Bilinmeyen bir hata meydana geldi:' . $Response );

				}

			}// elseif ($payment_type == 'isbank-non-3d')

		} //process_payment()



// checkout sayfasına 3d hatasıyla dönülmüşse hatayı göster
public function yanit_isle() {

	global $woocommerce;
    
	session_start();
	if($_SESSION["yanit"] == 'basarisiz') {
		echo '<p style="color:red; margin-bottom: 30px;" class="woocommerce-error">3D Secure Ödeme işleminiz tamamlanamadı: ' . $_SESSION["hataMesaji"] . '</p>';
	}
}// yanit_isle()



function TdSecureTesekkurSayfasi($order_id) {
	error_reporting(0);
	session_start();
	//echo 'gelen: ' . $_SESSION["yanit"]; // basarili mi basarisiz mi geldi, ya da geldi mi görmek için
	if($_SESSION["yanit"] == 'basarili') {
		$order = new WC_Order( $order_id );
		$order->add_order_note( '3D Secure ile ödeme alındı. <br> Sipariş Numarası:<br>' . $_SESSION["donenOid"] . '<br>Provizyon Numarası:<br>' . $_SESSION["provizyonNo"] );
		$order->payment_complete();

		// Return thank you redirect
		/*return array (
		  'result'   => 'success',
		  'redirect' => $this->get_return_url( $order ),
		);*/


	} else {//3D ödeme yapılırken basarili yanıtı almadan sipariş tamamla sayfasına girilirse
		if ($_SESSION["threeD"]	== 'yes') {
			echo '<h2 style="color:red" class="hata">Hata! 3D Secure ödemeniz tamamlanmadan bu sayfaya ulaştınız, siparişiniz tamamlanmadı!</h2><br>' . $_SESSION["yanit"] . '
				<style>.entry-content p:first-child {display:none}</style>
			';
			//echo "<br>3d: " . $_SESSION["threeD"];
			$order = new WC_Order( $order_id );
			$order->update_status('failed', __( '3D Secure ödeme tamamlanmadığı için bekleniyor.', 'woocommerce' ));
			exit();
		}
	}
	unset($_SESSION["yanit"]);
	// bir kere başarılı ödeme yapanın hileyle bir dahaki sefere code inject yapıp siparişi tamamlamaması için basarılı da olsa sonucu kaldırıyoruz.
} // TdSecureTesekkurSayfasi


function TdSecureOdemeSayfasi( $order_id ) {

	session_start();
	//unset($_SESSION["yanit"]);
	echo '<iframe id="3dSecureOdeme" frameborder="0" style="width:100%; height:390px;" src="' . $this->iframe_3d_redirect . '"></iframe>';


}// TdSecureOdemeSayfasi





	}//class cca_vpos



//Add the gateway class to woocommerce
	function add_cca_vpos_gateway( $methods ) {
		$methods[] = 'cca_vpos';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'add_cca_vpos_gateway' );


}// cca_vpos_init()






//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	END OF PAYMENT GATEWAY 		//////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	BEGINNING: ADD CHARGE TO GATEWAYS		//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


class WC_PaymentGateway_Add_Charges{
    public function __construct(){
        $this -> current_gateway_title = '';
        $this -> current_gateway_extra_charges = '';
        add_action('admin_head', array($this, 'add_form_fields'));
        add_action( 'woocommerce_calculate_totals', array( $this, 'calculate_totals' ), 10, 1 );
        wp_enqueue_script( 'wc-add-extra-charges', CCA_VPOS_PLUGIN_URL . '/js/updateCheckout.js', array('wc-checkout'), false, true );
    } // __construct

    function add_form_fields(){
        global $woocommerce;
         // Get current tab/section
        $current_tab        = ( empty( $_GET['tab'] ) ) ? '' : sanitize_text_field( urldecode( $_GET['tab'] ) );
        $current_section    = ( empty( $_REQUEST['section'] ) ) ? '' : sanitize_text_field( urldecode( $_REQUEST['section'] ) );
        if($current_tab == 'payment_gateways' && $current_section!=''){
            $gateways = $woocommerce->payment_gateways->payment_gateways();
            foreach($gateways as $gateway){
                if(get_class($gateway)==$current_section){
                    $current_gateway = $gateway -> id;
                    $extra_charges_id = 'woocommerce_'.$current_gateway.'_extra_charges';
                    $extra_charges_type = $extra_charges_id.'_type';
                    if(isset($_REQUEST['save'])){
                        update_option( $extra_charges_id, $_REQUEST[$extra_charges_id] );
                        update_option( $extra_charges_type, $_REQUEST[$extra_charges_type] );
                    }
                    $extra_charges = get_option( $extra_charges_id);
                    $extra_charges_type_value = get_option($extra_charges_type);
                }
            }
            /*
?>
            <script>
	            jQuery(document).ready(function($){
	                $data = '<h4>Bu ödeme yöntemine ekstra ücret ekle/çıkar</h4><table class="form-table">';
	                $data += '<tr valign="top">';
	                $data += '<th scope="row" class="titledesc">Ekstra Ücret<br><em>İndirim için eksi değer giriniz.</em></th>';
	                $data += '<td class="forminp">';
	                $data += '<fieldset>';
	                $data += '<input style="" name="<?php echo $extra_charges_id?>" id="<?php echo $extra_charges_id?>" type="text" value="<?php echo $extra_charges?>"/>';
	                $data += '<br /></fieldset></td></tr>';
	                $data += '<tr valign="top">';
	                $data += '<th scope="row" class="titledesc">Ekstra Ücret Tipi</th>';
	                $data += '<td class="forminp">';
	                $data += '<fieldset>';
	                $data += '<select name="<?php echo $extra_charges_type?>"><option <?php if($extra_charges_type_value=="add") echo "selected=selected"?> value="add">Total Add</option>';
	                $data += '<option <?php if($extra_charges_type_value=="percentage") echo "selected=selected"?> value="percentage">Total % Add</option>';
	                $data += '<br /></fieldset></td></tr></table>';
	                $('.form-table:last').after($data);
	            });
			</script>
<?php */
		}
	}// add_form_fields








	public function calculate_totals( $totals ) {
	    global $woocommerce;
	    $available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
	    $current_gateway = '';
	    if ( ! empty( $available_gateways ) ) {
	           // Chosen Method
	        if ( isset( $woocommerce->session->chosen_payment_method ) && isset( $available_gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
	            $current_gateway = $available_gateways[ $woocommerce->session->chosen_payment_method ];
	        } elseif ( isset( $available_gateways[ get_option( 'woocommerce_default_gateway' ) ] ) ) {
	            $current_gateway = $available_gateways[ get_option( 'woocommerce_default_gateway' ) ];
	        } else {
	            $current_gateway =  current( $available_gateways );
	        }
	    }
			$checkout = $woocommerce->checkout(); //checkout bilgilerini çağır:
	    	$taksit = $woocommerce->checkout->get_value( 'taksit' );
		$yuzdeKac =  $current_gateway->vadeFarki * $taksit; // Faiz yüzdesini hesaplıyor yuzde 2 X 3 taksit = toplam fiyata yuzde 6 faiz verir

	    if($current_gateway!=''){
	        $current_gateway_id = $current_gateway -> id;
	        $extra_charges_id = 'woocommerce_'.$current_gateway_id.'_extra_charges';
	        $extra_charges_type = $extra_charges_id.'_type';
	        $extra_charges = (float)$yuzdeKac;
	        $extra_charges_type_value = get_option( $extra_charges_type); 
	        if($extra_charges){
	            $this -> current_gateway_extra_charges = (round($woocommerce->cart->shipping_total*$extra_charges)/100) + (round($totals -> cart_contents_total*$extra_charges)/100);
	        	//Toplam ücret üzerinden vade farkı hesapla ve ekle
	            $totals -> cart_contents_total = $totals -> cart_contents_total + (round($totals -> cart_contents_total*$extra_charges)/100);
	        	//Kargo ücreti üzerinden vade farkı hesapla ve ekle NEDEN KENDİSİNİ EKLEMEDİĞİMİZDE NET DEĞİLİM
	            $woocommerce->cart->shipping_total += (round($woocommerce->cart->shipping_total*$extra_charges)/100);
	            $this -> current_gateway_title = $current_gateway -> title;
	            $this -> current_gateway_extra_charges_type_value = $extra_charges_type_value;
	            add_action( 'woocommerce_review_order_before_order_total',  array( $this, 'add_payment_gateway_extra_charges_row'));

	        }

	    }
	    return $totals;
	}// calculate_totals



	function add_payment_gateway_extra_charges_row(){
	?>
	    <tr class="payment-extra-charge">
	        <th>Kredi kartı vade farkı</th>
	        <td><?php echo woocommerce_price($this -> current_gateway_extra_charges); ?></td>
	 	</tr>
	<?php
	} // add_payment_gateway_extra_charges_row





} // WC_PaymentGateway_Add_Charges

new Wc_PaymentGateway_Add_Charges();


