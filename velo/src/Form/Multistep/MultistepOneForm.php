<?php

/**
 * @file
 * Contains \Drupal\demo\Form\Multistep\MultistepOneForm.
 */

namespace Drupal\velo\Form\Multistep;

use Drupal\Core\Form\FormStateInterface;

class MultistepOneForm extends MultistepFormBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The order type resolver.
   *
   * @var \Drupal\commerce_order\Resolver\OrderTypeResolverInterface
   */
  protected $orderTypeResolver;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The chain base price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The form ID.
   *
   * @var string
   */
  protected $formId;
  
  /**
   * {@inheritdoc}
   */
  public function setFormId($form_id) {
    $this->formId = $form_id;
    return $this;
  }  
  
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'multistep_form_one';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

	$country_list = [
			"TN" => $this->t("Tunisia"),
			"AF" => $this->t("Afghanistan"),
			"AX" => $this->t("Åland Islands"),
			"AL" => $this->t("Albania"),
			"DZ" => $this->t("Algeria"),
			"AS" => $this->t("American Samoa"),
			"AD" => $this->t("Andorra"),
			"AO" => $this->t("Angola"),
			"AI" => $this->t("Anguilla"),
			"AQ" => $this->t("Antarctica"),
			"AG" => $this->t("Antigua and Barbuda"),
			"AR" => $this->t("Argentina"),
			"AM" => $this->t("Armenia"),
			"AW" => $this->t("Aruba"),
			"AU" => $this->t("Australia"),
			"AT" => $this->t("Austria"),
			"AZ" => $this->t("Azerbaijan"),
			"BS" => $this->t("Bahamas"),
			"BH" => $this->t("Bahrain"),
			"BD" => $this->t("Bangladesh"),
			"BB" => $this->t("Barbados"),
			"BY" => $this->t("Belarus"),
			"BE" => $this->t("Belgium"),
			"BZ" => $this->t("Belize"),
			"BJ" => $this->t("Benin"),
			"BM" => $this->t("Bermuda"),
			"BT" => $this->t("Bhutan"),
			"BO" => $this->t("Bolivia, Plurinational State of"),
			"BQ" => $this->t("Bonaire, Sint Eustatius and Saba"),
			"BA" => $this->t("Bosnia and Herzegovina"),
			"BW" => $this->t("Botswana"),
			"BV" => $this->t("Bouvet Island"),
			"BR" => $this->t("Brazil"),
			"IO" => $this->t("British Indian Ocean Territory"),
			"BN" => $this->t("Brunei Darussalam"),
			"BG" => $this->t("Bulgaria"),
			"BF" => $this->t("Burkina Faso"),
			"BI" => $this->t("Burundi"),
			"KH" => $this->t("Cambodia"),
			"CM" => $this->t("Cameroon"),
			"CA" => $this->t("Canada"),
			"CV" => $this->t("Cape Verde"),
			"KY" => $this->t("Cayman Islands"),
			"CF" => $this->t("Central African Republic"),
			"TD" => $this->t("Chad"),
			"CL" => $this->t("Chile"),
			"CN" => $this->t("China"),
			"CX" => $this->t("Christmas Island"),
			"CC" => $this->t("Cocos (Keeling) Islands"),
			"CO" => $this->t("Colombia"),
			"KM" => $this->t("Comoros"),
			"CG" => $this->t("Congo"),
			"CD" => $this->t("Congo, the Democratic Republic of the"),
			"CK" => $this->t("Cook Islands"),
			"CR" => $this->t("Costa Rica"),
			"CI" => $this->t("Côte d'Ivoire"),
			"HR" => $this->t("Croatia"),
			"CU" => $this->t("Cuba"),
			"CW" => $this->t("Curaçao"),
			"CY" => $this->t("Cyprus"),
			"CZ" => $this->t("Czech Republic"),
			"DK" => $this->t("Denmark"),
			"DJ" => $this->t("Djibouti"),
			"DM" => $this->t("Dominica"),
			"DO" => $this->t("Dominican Republic"),
			"EC" => $this->t("Ecuador"),
			"EG" => $this->t("Egypt"),
			"SV" => $this->t("El Salvador"),
			"GQ" => $this->t("Equatorial Guinea"),
			"ER" => $this->t("Eritrea"),
			"EE" => $this->t("Estonia"),
			"ET" => $this->t("Ethiopia"),
			"FK" => $this->t("Falkland Islands (Malvinas)"),
			"FO" => $this->t("Faroe Islands"),
			"FJ" => $this->t("Fiji"),
			"FI" => $this->t("Finland"),
			"FR" => $this->t("France"),
			"GF" => $this->t("French Guiana"),
			"PF" => $this->t("French Polynesia"),
			"TF" => $this->t("French Southern Territories"),
			"GA" => $this->t("Gabon"),
			"GM" => $this->t("Gambia"),
			"GE" => $this->t("Georgia"),
			"DE" => $this->t("Germany"),
			"GH" => $this->t("Ghana"),
			"GI" => $this->t("Gibraltar"),
			"GR" => $this->t("Greece"),
			"GL" => $this->t("Greenland"),
			"GD" => $this->t("Grenada"),
			"GP" => $this->t("Guadeloupe"),
			"GU" => $this->t("Guam"),
			"GT" => $this->t("Guatemala"),
			"GG" => $this->t("Guernsey"),
			"GN" => $this->t("Guinea"),
			"GW" => $this->t("Guinea-Bissau"),
			"GY" => $this->t("Guyana"),
			"HT" => $this->t("Haiti"),
			"HM" => $this->t("Heard Island and McDonald Islands"),
			"VA" => $this->t("Holy See (Vatican City State)"),
			"HN" => $this->t("Honduras"),
			"HK" => $this->t("Hong Kong"),
			"HU" => $this->t("Hungary"),
			"IS" => $this->t("Iceland"),
			"IN" => $this->t("India"),
			"ID" => $this->t("Indonesia"),
			"IR" => $this->t("Iran, Islamic Republic of"),
			"IQ" => $this->t("Iraq"),
			"IE" => $this->t("Ireland"),
			"IM" => $this->t("Isle of Man"),
			"IL" => $this->t("Israel"),
			"IT" => $this->t("Italy"),
			"JM" => $this->t("Jamaica"),
			"JP" => $this->t("Japan"),
			"JE" => $this->t("Jersey"),
			"JO" => $this->t("Jordan"),
			"KZ" => $this->t("Kazakhstan"),
			"KE" => $this->t("Kenya"),
			"KI" => $this->t("Kiribati"),
			"KP" => $this->t("Korea, Democratic People's Republic of"),
			"KR" => $this->t("Korea, Republic of"),
			"KW" => $this->t("Kuwait"),
			"KG" => $this->t("Kyrgyzstan"),
			"LA" => $this->t("Lao People's Democratic Republic"),
			"LV" => $this->t("Latvia"),
			"LB" => $this->t("Lebanon"),
			"LS" => $this->t("Lesotho"),
			"LR" => $this->t("Liberia"),
			"LY" => $this->t("Libya"),
			"LI" => $this->t("Liechtenstein"),
			"LT" => $this->t("Lithuania"),
			"LU" => $this->t("Luxembourg"),
			"MO" => $this->t("Macao"),
			"MK" => $this->t("Macedonia, the former Yugoslav Republic of"),
			"MG" => $this->t("Madagascar"),
			"MW" => $this->t("Malawi"),
			"MY" => $this->t("Malaysia"),
			"MV" => $this->t("Maldives"),
			"ML" => $this->t("Mali"),
			"MT" => $this->t("Malta"),
			"MH" => $this->t("Marshall Islands"),
			"MQ" => $this->t("Martinique"),
			"MR" => $this->t("Mauritania"),
			"MU" => $this->t("Mauritius"),
			"YT" => $this->t("Mayotte"),
			"MX" => $this->t("Mexico"),
			"FM" => $this->t("Micronesia, Federated States of"),
			"MD" => $this->t("Moldova, Republic of"),
			"MC" => $this->t("Monaco"),
			"MN" => $this->t("Mongolia"),
			"ME" => $this->t("Montenegro"),
			"MS" => $this->t("Montserrat"),
			"MA" => $this->t("Morocco"),
			"MZ" => $this->t("Mozambique"),
			"MM" => $this->t("Myanmar"),
			"NA" => $this->t("Namibia"),
			"NR" => $this->t("Nauru"),
			"NP" => $this->t("Nepal"),
			"NL" => $this->t("Netherlands"),
			"NC" => $this->t("New Caledonia"),
			"NZ" => $this->t("New Zealand"),
			"NI" => $this->t("Nicaragua"),
			"NE" => $this->t("Niger"),
			"NG" => $this->t("Nigeria"),
			"NU" => $this->t("Niue"),
			"NF" => $this->t("Norfolk Island"),
			"MP" => $this->t("Northern Mariana Islands"),
			"NO" => $this->t("Norway"),
			"OM" => $this->t("Oman"),
			"PK" => $this->t("Pakistan"),
			"PW" => $this->t("Palau"),
			"PS" => $this->t("Palestinian Territory, Occupied"),
			"PA" => $this->t("Panama"),
			"PG" => $this->t("Papua New Guinea"),
			"PY" => $this->t("Paraguay"),
			"PE" => $this->t("Peru"),
			"PH" => $this->t("Philippines"),
			"PN" => $this->t("Pitcairn"),
			"PL" => $this->t("Poland"),
			"PT" => $this->t("Portugal"),
			"PR" => $this->t("Puerto Rico"),
			"QA" => $this->t("Qatar"),
			"RE" => $this->t("Réunion"),
			"RO" => $this->t("Romania"),
			"RU" => $this->t("Russian Federation"),
			"RW" => $this->t("Rwanda"),
			"BL" => $this->t("Saint Barthélemy"),
			"SH" => $this->t("Saint Helena, Ascension and Tristan da Cunha"),
			"KN" => $this->t("Saint Kitts and Nevis"),
			"LC" => $this->t("Saint Lucia"),
			"MF" => $this->t("Saint Martin (French part)"),
			"PM" => $this->t("Saint Pierre and Miquelon"),
			"VC" => $this->t("Saint Vincent and the Grenadines"),
			"WS" => $this->t("Samoa"),
			"SM" => $this->t("San Marino"),
			"ST" => $this->t("Sao Tome and Principe"),
			"SA" => $this->t("Saudi Arabia"),
			"SN" => $this->t("Senegal"),
			"RS" => $this->t("Serbia"),
			"SC" => $this->t("Seychelles"),
			"SL" => $this->t("Sierra Leone"),
			"SG" => $this->t("Singapore"),
			"SX" => $this->t("Sint Maarten (Dutch part)"),
			"SK" => $this->t("Slovakia"),
			"SI" => $this->t("Slovenia"),
			"SB" => $this->t("Solomon Islands"),
			"SO" => $this->t("Somalia"),
			"ZA" => $this->t("South Africa"),
			"GS" => $this->t("South Georgia and the South Sandwich Islands"),
			"SS" => $this->t("South Sudan"),
			"ES" => $this->t("Spain"),
			"LK" => $this->t("Sri Lanka"),
			"SD" => $this->t("Sudan"),
			"SR" => $this->t("Suriname"),
			"SJ" => $this->t("Svalbard and Jan Mayen"),
			"SZ" => $this->t("Swaziland"),
			"SE" => $this->t("Sweden"),
			"CH" => $this->t("Switzerland"),
			"SY" => $this->t("Syrian Arab Republic"),
			"TW" => $this->t("Taiwan, Province of China"),
			"TJ" => $this->t("Tajikistan"),
			"TZ" => $this->t("Tanzania, United Republic of"),
			"TH" => $this->t("Thailand"),
			"TL" => $this->t("Timor-Leste"),
			"TG" => $this->t("Togo"),
			"TK" => $this->t("Tokelau"),
			"TO" => $this->t("Tonga"),
			"TT" => $this->t("Trinidad and Tobago"),
			"TR" => $this->t("Turkey"),
			"TM" => $this->t("Turkmenistan"),
			"TC" => $this->t("Turks and Caicos Islands"),
			"TV" => $this->t("Tuvalu"),
			"UG" => $this->t("Uganda"),
			"UA" => $this->t("Ukraine"),
			"AE" => $this->t("United Arab Emirates"),
			"GB" => $this->t("United Kingdom"),
			"US" => $this->t("United States"),
			"UM" => $this->t("United States Minor Outlying Islands"),
			"UY" => $this->t("Uruguay"),
			"UZ" => $this->t("Uzbekistan"),
			"VU" => $this->t("Vanuatu"),
			"VE" => $this->t("Venezuela, Bolivarian Republic of"),
			"VN" => $this->t("Viet Nam"),
			"VG" => $this->t("Virgin Islands, British"),
			"VI" => $this->t("Virgin Islands, U.S."),
			"WF" => $this->t("Wallis and Futuna"),
			"EH" => $this->t("Western Sahara"),
			"YE" => $this->t("Yemen"),
			"ZM" => $this->t("Zambia"),
			"ZW" => $this->t("Zimbabwe"),
		]	
	;  
  
  
	///////Si le visiteur dispose déjà d'un panier, on le vide/////////////////////
	$store_id = 1;
	$this->my_store = \Drupal\commerce_store\Entity\Store::load($store_id);
	$this->cart = \Drupal::service('commerce_cart.cart_provider')->getCart('default',$this->my_store,\Drupal::currentUser());

	if($this->cart){
		//$this->cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->my_store);
		//$this->cart->emptyCart ();

		foreach ($this->cart->getItems() as $order_item) {
			 $this->cart->removeItem($order_item);
		}
		$this->cart->save();		

	}
    $form = parent::buildForm($form, $form_state);
		
	
	$form['bike_type'] = [
		'#type' => 'select',
		'#title' => $this->t('Bike Type'),
		'#empty_option' => $this->t('Select a Bike Type'),
		'#default_value' => $this->store->get('bike_type') ? $this->store->get('bike_type'):'2',
		'#options' =>  \Drupal\field\Entity\FieldConfig::load('commerce_product_variation.velo.field_category')->getFieldStorageDefinition()->getSettings()['allowed_values'],
	];
	
	$form['bike_qte'] = array(
	  '#type' => 'number',
	  '#title' => $this->t('Bikes Quantity'),
	  '#min' => 1,
	  '#max' => 5,
	  '#default_value' => $this->store->get('bike_qte') ? $this->store->get('bike_qte'):1,
	);
		
	$form['check-in'] = array(
		'#type' => 'date',
		'#title' => $this->t('Check-in').' *',
		'#description' => $this->t('starting from 8h'),
		'#default_value' => $this->store->get('check-in') ? $this->store->get('check-in'):'',
		'#required' => true,		
	);
	
	$form['check-out'] = array(
		'#type' => 'date',
		'#title' => $this->t('Check-out').' *',
		'#description' => $this->t('return bike before 17h'),
		'#default_value' => $this->store->get('check-out') ? $this->store->get('check-out'):'',
		'#required' => true,
	);	
	
	$form['place-withdrawal'] = [
		'#type' => 'select',
		'#title' => $this->t('Place of Withdrawal'),
		'#empty_option' => $this->t('Where would you pick-up your Bike'),
		'#default_value' => $this->store->get('place-withdrawal') ?$this->store->get('place-withdrawal'):'',
		'#options' =>  \Drupal\field\Entity\FieldConfig::load('commerce_order.default.field_place_withdrawal')->getFieldStorageDefinition()->getSettings()['allowed_values'],
	];	
	
	
	$form['constomer-infos'] = array(
	  '#type' => 'fieldset',
	  '#title' => $this->t('Customer Infos'),
	);
		
	$form['constomer-infos']['email'] = array(
	  '#type' => 'email',
	  '#title' => $this->t('E-Mail'),
	  '#default_value' => $this->store->get( 'email') ? $this->store->get( 'email'):'',
	);
	
	$form['constomer-infos']['mobile'] = array(
	  '#type' => 'textfield',
	  '#title' => $this->t('Telephone'),
	  '#default_value' => $this->store->get('mobile') ? $this->store->get('mobile'):'',
	);	
	
	$ip = $_SERVER['REMOTE_ADDR'];
	$country_code = ip2country_get_country($ip);  

	
		//ob_start();
		//var_dump("ip");
		//var_dump($ip);
		//var_dump("country_code");
		//var_dump($country_code);
		//var_dump("SERVER");
		//var_dump($_SERVER);		
		//
		//$dumpy = ob_get_clean();
		//\Drupal::logger('velo')->notice('ip2country:'.$dumpy);
		

		
	$form['constomer-infos']['country'] = array(
		'#type' => 'select',
		'#title' => $this->t('Country'),
		'#empty_option' => $this->t('Select a Country'),
		'#default_value' => $this->store->get('country') ? $this->store->get( 'country'):$country_code,
		'#options' => $country_list,
		'#prefix' => '<div class="warriha">',
		'#suffix' => '</div>',		
	);	


	if ($country_code == 'TN'){
		$form['constomer-infos']['country']['#prefix'] = '<div class="khabbiha">';
		$form['constomer-infos']['country']['#suffix'] = '</div>';		
	}
	
	$form['extras'] = array(
	  '#type' => 'fieldset',
	  '#title' => $this->t('As Options'),
	);

	$form['extras']['guide'] = array(
	  '#type' => 'checkbox',
	  //'#title' => $this->t('With a personal Guide (€@amount per day)', array ('@amount' => 20)),
	  '#title' => $this->t('With a personal Guide'),
	  '#default_value' => $this->store->get( 'guide') ? $this->store->get( 'guide'):'',
	);	
	
	$form['extras']['telephone'] = array(
	  '#type' => 'checkbox',
	  //'#title' => $this->t('With a local mobile phone to get geolocalized (€@amount per day)', array ('@amount' => 10)),	  
	  '#title' => $this->t('With a local mobile phone to get geolocalized'),
	  '#default_value' => $this->store->get('telephone') ? $this->store->get('telephone'):'',
	);	

	$form['extras']['insurance'] = array(
	  '#type' => 'checkbox',
	  //'#title' => $this->t('Insurance against accidents (€@amount per day)', array ('@amount' => 5)),
	  '#title' => $this->t('Insurance against accidents'),
	  '#default_value' => $this->store->get( 'insurance') ?  $this->store->get( 'insurance'):'',
	);		

    $form['actions']['submit']['#value'] = $this->t('Next');
	$form['#cache'] = ['max-age' => 0];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

	//Submit sur le premier formulaire: ajout dans la carte d'un vélo, guide téléphone et assurance.
	//$product_list = array(5,4,3,1);
	////////////////////////////
	//$store_id = 1;
	//$this->my_store = \Drupal\commerce_store\Entity\Store::load($store_id);
    //
	//$this->cart = \Drupal::service('commerce_cart.cart_provider')->getCart('default',$this->my_store,\Drupal::currentUser());
	//if(!$this->cart){
	//	$this->cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->my_store);
	//}
    
	///////////////////////////
	

	/////////////Copier les informations client vers le billing Profile.
	//$profile = \Drupal\profile\Entity\Profile::create([
	//	'type' => 'customer',
	//	'address' => [
	//		'country_code' => $form_state->getValue('country'),
	//	],
	//]);
	//$profile->save();
	//$this->cart->setBillingProfile($profile);
	//$this->cart->save();	
	
	/////////////////////////////////////////
    $this->store->set('email', $form_state->getValue('email'));
	$this->store->set('country', $form_state->getValue('country'));
	$this->store->set('mobile', $form_state->getValue('mobile'));
	$this->store->set('check-in', $form_state->getValue('check-in'));
	$this->store->set('check-out', $form_state->getValue('check-out'));
	$this->store->set('bike_type', $form_state->getValue('bike_type'));
	$this->store->set('bike_qte', $form_state->getValue('bike_qte'));
	$this->store->set('place-withdrawal', $form_state->getValue('place-withdrawal'));
	$this->store->set('guide', $form_state->getValue('guide'));
	$this->store->set('telephone', $form_state->getValue('telephone'));
	$this->store->set('insurance', $form_state->getValue('insurance'));	

	//$dumpy = ob_get_clean();
	//\Drupal::logger('Velo')->notice('Houna Friga :'.$dumpy);		
    $form_state->setRedirect('velo.multistep_two');
  }
  
	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		//if (strlen($form_state->getValue('phone_number')) < 3) {
		//$this->my_store = \Drupal\commerce_store\Entity\Store::load($store_id);
		
		$velo_controller = new \Drupal\velo\Controller\VeloController;
		$availability_result = array();
		//$availability_result ['velo']= $velo_controller->check_availability (
		//	'velo', 
		//	$form_state->getValue('check-in'),
		//	$form_state->getValue('check-out'),
		//	$form_state->getValue('bike_qte'),
		//	array ('ressource_category' => $form_state->getValue('bike_type'))
		//);
		
		$other_services = array ('guide','telephone','insurance','velo');
		
		foreach ($other_services as $key_oth => $val_oth){
			if ($val_oth == 'velo'){
				$qte = $form_state->getValue('bike_qte');
				$parameter = array ('ressource_category' => $form_state->getValue('bike_type'));
			}
			else{
				$qte = 1;
				$parameter = array ();
			}
			if ($form_state->getValue($val_oth) or $val_oth =='velo'){
				if ($form_state->getValue('check-in') > $form_state->getValue('check-out')){
					$form_state->setErrorByName('check-out');
					$form_state->setErrorByName('check-in', $this->t("Check-in date must come before Check-out date"));
					
				}
				$availability_result  [$val_oth]= $velo_controller->check_availability (
					$val_oth, 
					$form_state->getValue('check-in'),
					$form_state->getValue('check-out'),
					$qte,
					$parameter
				);
				if ($availability_result[$val_oth]["error"] != "") {
					$form_state->setErrorByName('check-out');
					$form_state->setErrorByName('check-in', $availability_result[$val_oth]["error"]);
					//Supprimer la sauvegarde des données pour les champs erronés
					$this->store->delete($val_oth);
				}				
			}
		}		
		ob_start();
		var_dump($availability_result);
		$dumpy = ob_get_clean();
		\Drupal::logger('velo')->notice('Availability_result:'.$dumpy);
	}
}

?>