<?php

/**

 * @file

 * Contains \Drupal\demo\Form\Multistep\MultistepTwoForm.

 */



namespace Drupal\velo\Form\Multistep;



use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Url;



class MultistepTwoForm extends MultistepFormBase {



  /**

   * {@inheritdoc}.

   */
   
  private $availability_result = array();

  public function getFormId() {

    return 'multistep_form_two';

  }



  /**

   * {@inheritdoc}.

   */

  public function buildForm(array $form, FormStateInterface $form_state) {

	$form = parent::buildForm($form, $form_state);

	$form['intrested'] = array(
		'#type' => 'radios',
		'#title' => $this->t('Do you wish to take part in a thematic Tour'),
		'#options' => array(
			1 => $this->t('Yes, I\'m interested'),
			2 => $this->t('No, thank you'),
		),
		'#ajax' => [
			'callback'	=> [$this, 'radioCallback'],
			'effect'	=> 'fade',
			'wrapper' 	=> 'edit-choice',
			'progress'=> [
				'type'=> 'throbber',
				'message' => t('Processing entry...'),
			],
		],
	);
	$intrested = $this->store->get('intrested');
	if ($intrested == 1 or $intrested == 2){
		$form['intrested']['#default_value'] = $intrested;	
	}
		
	
	$form = $this->configure_form($form, $form_state);

    $form['#cache'] = ['max-age' => 0];
	$form['actions']['previous'] = array(
		'#type' => 'link',
		'#prefix' => '<div class="multistep_previous">',
		'#suffix' => '</div>',
		'#title' => $this->t('Previous'),
		'#attributes' => array(
			'class' => array('button'),
		),
		'#weight' => 0,
		'#url' => Url::fromRoute('velo.multistep_one'),
	);
	return $form;

  }

  /**

   * {@inheritdoc}

   */

public function submitForm(array &$form, FormStateInterface $form_state) {
	
	\Drupal::logger('Velo')->notice('Step 2000');	
	$tour_list = array(7);//7 est le product id des tours.
	//ob_start();
	//var_dump ("variation_list");
	//var_dump ($tour_list);
	//var_dump($this->availability_result);
	//
	//$dumpy = ob_get_clean();
	//\Drupal::logger('Velo')->notice('Step 200:'.$dumpy);	
	


	///////Chargement Panier/////////////////////
	$store_id = 1;
	$this->my_store = \Drupal\commerce_store\Entity\Store::load($store_id);
	$this->cart = \Drupal::service('commerce_cart.cart_provider')->getCart('default',$this->my_store,\Drupal::currentUser());

	if(!$this->cart){
		$this->cart = \Drupal::service('commerce_cart.cart_provider')->createCart('default', $this->my_store);

	}
	
	/////////////Copier les informations client vers le billing Profile.
	$profile = \Drupal\profile\Entity\Profile::create([
		'type' => 'customer',
		'address' => [
			'country_code' => $this->store->get('country'),
			//'postal_code' => '1006',
			//'locality' => 'Tumbuktoo',
			//'address_line1' => 'Rue Addouha',
			//'given_name' => 'Salah',
			//'family_name' => 'Bouthelja',
		],
	]);
	$profile->save();
	$this->cart->setBillingProfile($profile);
	$this->cart->setEmail($this->store->get( 'email'));	
	$this->cart->save();	
	
	
	
	$this->cartManager = \Drupal::service('commerce_cart.cart_manager');
	///////////////////////////////////////
	
	$product_list = array('velo',	'guide',	'insurance',	'telephone');
	$variation_list = array ();
	
	$datetime1 = date_create($this->store->get('check-in'));
	$datetime2 = date_create($this->store->get('check-out'));
	$interval = date_diff($datetime1, $datetime2);	
	$duration = $interval->format('%a')+1;

	//foreach ($product_list as $key => $value){
	ob_start();	
	var_dump ("this->availability_result");	
	var_dump ($this->availability_result);	
	foreach ($this->availability_result as $key => $value){	
		/*$variation_qte = $duration;
		if ($key == 'velo' or $key == 'telephone' or $key == 'insurance'){
			$variation_qte = $duration * $this->store->get('bike_qte');
		}*/

		/*if (
			$key == 'velo' or 
			( $key == 'telephone' and $this->store->get('telephone')) or 
			($key == 'guide' and $this->store->get('guide')) or 
			($key == 'insurance' and $this->store->get('insurance')) ){
				$variation_list [] = $this->_select_variation ($key, $variation_qte,array());
		}*/
		//$identifiant_variation = array_keys ($value["output"]);
		//$identifiant_variation = $identifiant_variation [0];
		foreach ($value["output"] as $key_output => $value_output){//Liste des ressources sélectionnées			
			$identifiant_variation = $key_output;
			$variation_list [] = \Drupal\commerce_product\Entity\ProductVariation::load($identifiant_variation);
		}
	}
	
	
	foreach ($variation_list as $key_v => $value_v){
		//var_dump ("value_v->getTitle()");
		//var_dump ($value_v->getTitle());
		//var_dump ("value_v->bundle()");
		//var_dump ($value_v->bundle());
		
		
		//Start from here
		//if ($key_v == 'velo' or $key_v == 'telephone'  or $key_v == 'insurance'){
		$variation_type = $value_v->bundle();
		if ($variation_type == 'insurance'){			
			$variation_qte = $duration * $this->store->get('bike_qte');
			//var_dump ("Variation_qte");
			//var_dump ($variation_qte);
		}
		elseif ($variation_type == 'guide' or $variation_type == 'telephone' or $variation_type == 'velo'){
			$variation_qte = $duration;
		}
		else{//tour
			$variation_qte = 1;
			//var_dump ("Key");
			//var_dump ($key_v);
		}
		//var_dump ("Added Ressource");
		//var_dump ($value_v);
		//$this->cartManager->addEntity($this->cart, $value_v, $variation_qte);
		
		$order_item = \Drupal\commerce_order\Entity\OrderItem::create([
			'type' => 'default',
			'quantity' => $variation_qte,
			'purchased_entity' => $value_v->id(),
		]);
		
		//$unit_price = new \Drupal\commerce_price\Price('70.00', 'TND');
		$order_item->save();
		
		//var_dump ("country");
		//var_dump ($this->store->get( 'country'));
		$country = $this->store->get( 'country');
		if ($country =="TN"){
			$unit_price = $value_v->get("field_price_tnd")->getValue();
			$unit_price = new \Drupal\commerce_price\Price($unit_price[0]["number"], $unit_price[0]["currency_code"]);

		}
		elseif ($country =="RU"){
			$unit_price = $value_v->get("field_price_rub")->getValue();
			$unit_price = new \Drupal\commerce_price\Price($unit_price[0]["number"], $unit_price[0]["currency_code"]);

		}
		elseif (in_array ($country , array ("DE","AU","BE","ES","FI","FR","PF","WF","IE","IT","LU","NL","CW","MF","PT","GR","SK","CY","MT","SK","EE","LV","LT"))){
			$unit_price = new \Drupal\commerce_price\Price($value_v->getPrice()->getNumber(), "EUR");
		}
		else{
			$unit_price = $value_v->get("field_price_usd")->getValue();
			$unit_price = new \Drupal\commerce_price\Price($unit_price[0]["number"], $unit_price[0]["currency_code"]);			
		}

		$order_item->setUnitPrice($unit_price,TRUE);
		$order_item->save();					
		
		
		
			//var_dump ("unit_price");
			//var_dump ($unit_price[0]["number"]);		
		//Start from here
		
		//$unit_price = new \Drupal\commerce_price\Price('70.00', $unit_price[0]["currency_code"]);
		//$unit_price = new \Drupal\commerce_price\Price('70.00', 'TND');
		//$order_item->setUnitPrice($unit_price,TRUE);

		//$unit_price = new \Drupal\commerce_price\Price('70.00', 'TND');
		
		
		
		
		//$order_item->setTitle("Majs");
		//$order_item->save();
		//$order_item = $this->reloadEntity($order_item);	
		//$this->cartManager->addItem($order_item);
		$this->cartManager->addOrderItem($this->cart, $order_item);
	}
	//////////////////////////////////
	

	
	
	$this->cart->set("field_check_in", $this->store->get('check-in'));
	$this->cart->set("field_check_out", $this->store->get('check-out'));
	$this->cart->set("field_place_withdrawal", $this->store->get('place-withdrawal'));
	$this->cart->set("field_telephone", $this->store->get('mobile'));
	
	$this->cart->save();
	//////////////////////////////
	if ($form_state->getValue('intrested')==1){//Ajout du parcours sélectionné.
    
		$variation_tour_list = array ();
    
		foreach ($tour_list as $key => $value){    
			$product = \Drupal\commerce_product\Entity\Product::load($value);
			$variation_tour_list = array_merge ( $variation_tour_list,  $product->getVariations());    
		}
    		
    
		//Sélection de la ressource mentionnée dans le formulaire.    
		foreach ($variation_tour_list as $keyvar => $valuevar){        
			if ($form_state->getValue('tour_choice') == $valuevar->get("attribute_tour_id")->getValue()[0]['target_id']){        
				$slected_variation = $valuevar;        
			}        
		}
        
		$variation_tour_list = array($slected_variation);
        
		foreach ($variation_tour_list as $key_v => $value_v){

			$order_item = \Drupal\commerce_order\Entity\OrderItem::create([
				'type' => 'default',
				//'quantity' => $variation_qte,
				'quantity' => 1,
				'purchased_entity' => $value_v->id(),
			]);
			
			//$order_item->save();
			//$unit_price = $value_v->get("field_price_tnd")->getValue();
			//$unit_price = new \Drupal\commerce_price\Price($unit_price[0]["number"], $unit_price[0]["currency_code"]);

		$country = $this->store->get( 'country');
		if ($country =="TN"){
			$unit_price = $value_v->get("field_price_tnd")->getValue();
			$unit_price = new \Drupal\commerce_price\Price($unit_price[0]["number"], $unit_price[0]["currency_code"]);

		}
		elseif ($country =="RU"){
			$unit_price = $value_v->get("field_price_rub")->getValue();
			$unit_price = new \Drupal\commerce_price\Price($unit_price[0]["number"], $unit_price[0]["currency_code"]);

		}
		elseif (in_array ($country , array ("DE","AU","BE","ES","FI","FR","PF","WF","IE","IT","LU","NL","CW","MF","PT","GR","SK","CY","MT","SK","EE","LV","LT"))){
			$unit_price = new \Drupal\commerce_price\Price($value_v->getPrice()->getNumber(), "EUR");
		}
		else{
			$unit_price = $value_v->get("field_price_usd")->getValue();
			$unit_price = new \Drupal\commerce_price\Price($unit_price[0]["number"], $unit_price[0]["currency_code"]);			
		}

			$order_item->setUnitPrice($unit_price,TRUE);			
			$order_item->save();
			$this->cartManager->addOrderItem($this->cart, $order_item);
			
		}
    
		$this->cart->save();
		
		//Adapter les prix en d'autres devises
		//Start from here
		//foreach ($this->cart->getItems() as $order_item) {
		//	$purchased_entity = $order_item->getPurchasedEntity();
		//	
		//	var_dump ("order_item");
		//	var_dump (get_class($order_item));
		//	if ($purchased_entity) {
		//		$order_item->setTitle($purchased_entity->getOrderItemTitle());
		//		var_dump ("Pre-Conversion Dinars");
		//		if (!$order_item->isUnitPriceOverridden()) {
		//			$unit_price = new \Drupal\commerce_price\Price('70.00', 'TND');
		//			$order_item->setUnitPrice($unit_price,TRUE);
		//			//$order_item->save();
		//			var_dump ("Conversion Dinars");					
		//		}
		//	}
		//}
		$this->cart->save();
	}
	/////////////////////////////

	$dumpy = ob_get_clean();
	\Drupal::logger('Velo')->notice('Check me in:'.$dumpy);	

	
	$this->store->set('intrested', $form_state->getValue('intrested'));
    $this->store->set('tour_choice', $form_state->getValue('tour_choice'));



    // Save the data
    parent::saveData();
	
	$form_state->setRedirect('commerce_checkout.form',array('commerce_order' => $this->cart->id(),'step' =>'order_information'));
}





	public function radioCallback(array&$form, FormStateInterface $form_state) {		



		//return $form['users_wrapper']['tour_choice'];

		return $form['tour_choice'];

		//return $form['users_wrapper'];

		//return $form;

		

	}  

	

	public function configure_form ($form, $form_state){

		\Drupal::logger('Velo')->notice('Ajax Form.');

		$display_style = 'khabbiha';

		//$display_style = 'warriha';

		if ($form_state->getValue('intrested')==1){

			$display_style = 'warriha';

			\Drupal::logger('Velo')->notice('Cart Class:warriha');

		}

		$variations_list = $this->_select_variation ('tour', 0,array ('parcours_list' => 1));

		if (isset ($form['tour_choice']['#attributes']['class'])){
			foreach ($form['tour_choice']['#attributes']['class'] as $keych => $valuech){
				if ($valuech == 'khabbiha' or $valuech == 'warriha' ){
					unset ($form['tour_choice']['#attributes']['class'][$keych]);
					\Drupal::logger('Velo')->notice('Class detected:'.$valuech);
				}				
			}			
		}

			

		$form['tour_choice'] = array(
			'#prefix' => '<div id="edit-choice">',
			'#suffix' => '</div>',
			'#type' => 'radios',
			'#title' => $this->t('Suggested Tours'),
			'#attributes' => [
				'class' => [$display_style],
			],
			'#default_value' => $this->store->get('tour_choice') ?$this->store->get('tour_choice'):'',
			'#ajax' => [
				'callback'	=> [$this, 'radioCallback'],
				'effect'	=> 'fade',
				'wrapper' 	=> 'edit-choice',		
				'progress'=> [
					'type'=> 'throbber',
					'message' => t('Processing entry...'),
				],				
			],
		);		

		foreach ($variations_list as $key_var => $value_var){

			$tour_id = $value_var->get("attribute_tour_id")->getValue()[0]['target_id'];

			$tour_title = $value_var->getTitle();

			$tour_description = $value_var->get("field_description")->getValue();

			$tour_description = isset($tour_description[0]["value"])?$tour_description[0]["value"]:'';

			if ($form_state->getValue('tour_choice')==$tour_id){

				$form['tour_choice']['#options'][$tour_id] = $tour_title.' '.'<div class="tour_description">'.$tour_description.'</div>';

			}
			else{

				$form['tour_choice']['#options'][$tour_id] = $tour_title;
			}
		}
		return ($form);
	}

	
	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {
		//if (strlen($form_state->getValue('phone_number')) < 3) {
		//$this->my_store = \Drupal\commerce_store\Entity\Store::load($store_id);
		
		$velo_controller = new \Drupal\velo\Controller\VeloController;
		
		$this->availability_result ['velo']= $velo_controller->check_availability (
			'velo', 
			$this->store->get('check-in'),
			$this->store->get('check-out'),
			$this->store->get('bike_qte'),
			array ('ressource_category' => $this->store->get('bike_type'))
		);
		
		$other_services = array ('guide','telephone','insurance');
		
		foreach ($other_services as $key_oth => $val_oth){
			if ($this->store->get($val_oth)){
				$this->availability_result  [$val_oth]= $velo_controller->check_availability (
					$val_oth, 
					$this->store->get('check-in'),
					$this->store->get('check-out'),
					1,
					array ()
				);
				//if ($availability_result[$val_oth]["error"] != "") {
				//	$form_state->setErrorByName('check-out');
				//	$form_state->setErrorByName('check-in', $availability_result[$val_oth]["error"]);
				//}				
			}
		}
	}
	
	

}

?>


