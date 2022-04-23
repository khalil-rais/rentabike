<?php
/**
 * @file
 * Contains \Drupal\hello_world\HelloWorldController.
 */
 
namespace Drupal\velo\Controller;
 

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Datetime\DrupalDateTime;

/*use \Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;
use \Drupal\commerce_product\Entity\ProductVariationType;*/

require(drupal_get_path('module', 'velo') . '/fpdf17/pdf.php');

class VeloController extends ContainerBuilder {

	private $user = "";
	private $password = "";


	public function showDogInfo($latitude,$longitude) {
		\Drupal::logger('velo')->notice('Here I am: '.$latitude."___".$longitude);
		$response = new AjaxResponse();
		//$polygon = $this->_get_static_polygon ();
		//$parcours_statique = $polygon ["parcours_statique"];
		//$monuments = $polygon ["monuments"];
		$parcours_statique = array();
		$monuments = array();
		
		date_default_timezone_set('Africa/Tunis');
		$here_time_stamp = date ("H:i");

		$user = \Drupal::currentUser();
		

		//Extraire la dernière commande qui correspond à l'utilisateur actuel
		$today = date ("Y-m-d");
		$query = \Drupal::entityQuery('commerce_order')
		->condition('field_check_in' , $today, '>=')
		->condition('field_check_out', $today, '<=')
		->condition("uid", $user->id());
		$nids = $query->execute();
		
		
		ob_start();
		var_dump ('Requete recue de: ');
		var_dump ($user->id());
		
		
		if (count ($nids)>0){
			//$order_id = $nids [count ($nids) - 1];
			$order_id = end($nids);
			
			$order = \Drupal\commerce_order\Entity\Order::load($order_id);
			var_dump ('Order_id: ');
			var_dump ($order_id);
			
			//Chercher si la commande contient un parcours
			if ($order->getState()->value == "completed"){
				$item_list_ids = $order->getItems();
				foreach ($item_list_ids as $key_it => $value_it){
					if ($value_it->getPurchasedEntity()->bundle() =="tour"){
						$my_tour_id = $value_it->getPurchasedEntity()->id();
						$polygon = $this->_get_static_polygon ($my_tour_id);
						$parcours_statique = $polygon ["parcours_statique"];
						$monuments = $polygon ["monuments"];						
						break;
						var_dump ('Tour id is:');
						var_dump ($my_tour_id);
					}
					else{
						var_dump ('Tour bundle is:');
						var_dump ($value_it->getPurchasedEntity()->bundle());						
					}
				}				
			}

			$dumpy = ob_get_clean();
			\Drupal::logger('velo')->notice('parcours_actuel: '.$dumpy);
		
			$parcours_actuel = $this->get_dynamic_points($order_id);
			
			if ($longitude !=0){			
				$this->ajouter_point($order_id,$latitude, $longitude);
			}
			elseif (count($parcours_actuel) >= 1){
				//Récupérer la dernière position connue
				$longitude = $parcours_actuel [count($parcours_actuel)-1]["long"];
				$latitude = $parcours_actuel [count($parcours_actuel)-1]["lat"];
				$here_time_stamp = date ("H:i",$parcours_actuel [count($parcours_actuel)-1]["timestamp"]);
			}
			
			$here_i_am = array (
				//"long" => 10.927223 , "lat" => 33.790856, "timestamp" => " at ".date ("H:i")
				"long" => $longitude , "lat" => $latitude, "timestamp" => " at ".$here_time_stamp
			);
			
			$my_response = array (
				"parcours_statique" => $parcours_statique, 
				"parcours_actuel" => $parcours_actuel,
				"monuments" => $monuments,
				"here_i_am" => $here_i_am
			);	
		}
		else{
			$my_response = array (
				"parcours_statique" => $parcours_statique, 
				"parcours_actuel" => array(),
				"monuments" => $monuments,
				"here_i_am" => array()
			);				
		}

		
		//$order = \Drupal\commerce_order\Entity\Order::load($order_id);
		//$check_in =$order->get('field_check_in')->getValue();
		//$order_uid =$order->get('uid')->getValue();
		//$order->set("uid", 26);
		//$order->save();		
		//$order_uid[0]["target_id"]
		
		
		//$query = \Drupal::entityQuery('node');
		//$query->condition('field_date', $now->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=');
		//$results = $query->execute();


		//$query = \Drupal::entityQuery('commerce_order')
		//->condition('field_check_in' , $now->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=')
		//->condition('field_check_out', $now->format(DATETIME_DATETIME_STORAGE_FORMAT), '<=')
		//->condition("uid", 26);
		//$nids = $query->execute();


		
		


		
		//foreach ($nids as $key => $value){
		//	$produits_parcourus++;
		//	$product = \Drupal\commerce_product\Entity\Product::load($value);
		//	dpm ("Product will be deleted: ".$product->getTitle());
		//}
			
		
		//ob_start();
		//var_dump("order_uid");
		//var_dump($order_uid);
		//Message 	parcours_actuel: string(9) "order_uid" array(1) {
		//	[0]=> array(1) { ["target_id"]=> string(1) "0" } } array
		
		//var_dump("now->format(DATETIME_DATETIME_STORAGE_FORMAT)");
		//var_dump($now->format(DATETIME_DATETIME_STORAGE_FORMAT));

		//var_dump ("nids");
		//var_dump ($nids);

		//var_dump ("check_in");
		//var_dump ($check_in);
		
		//parcours_actuel: string(8) "check_in" array(1) { [0]=> array(1) { ["value"]=> string(10) "2018-09-10" } } 
		
		//var_dump ("parcours_actuel");
		//var_dump($parcours_actuel);
		//$dumpy = ob_get_clean();
		//\Drupal::logger('velo')->notice('parcours_actuel: '.$dumpy);
		
		//$response->addCommand(new AlertCommand('DOGS ARE GREAT!'));
		$response->setData($my_response);
		
		return $response;
	}
	public function get_dynamic_points ($order_id){
		$parcours_actuel = array ();
		$order = \Drupal\commerce_order\Entity\Order::load($order_id);

		if ($order->getState()->value == "completed"){
			$field_deplacements = array ();
			$field_deplacements_raw=$order->get('field_deplacements')->getValue();
			if (isset ($field_deplacements_raw[0]["value"])){
				$field_deplacements=json_decode($field_deplacements_raw[0]["value"],true);
				foreach ($field_deplacements as $key => $value){
					$parcours_actuel []= array (
						"long" => $value["long"] , 
						"lat" => $value["lat"],
						"timestamp" => $value["timestamp"],
					);
				}
			}
			//$field_deplacements=json_decode($order->get('field_deplacements')->getValue()[0]["value"],true);
			//$field_deplacements[] = array (
			//	"long" => 10.927223 , 
			//	"lat" => 33.790856,
			//	"timestamp" => time(),
			//);
		}
		return ($parcours_actuel );
	}
	public function ajouter_point($order_id,$latitude=0, $longitude=0, $timestamp=0){
		$order = \Drupal\commerce_order\Entity\Order::load($order_id);

		if ($order->getState()->value == "completed"){
			//$field_deplacements=$order->get('field_deplacements')->getValue()[0]["value"];
			$field_deplacements = array ();
			$field_deplacements_raw=$order->get('field_deplacements')->getValue();
			if (isset ($field_deplacements_raw[0]["value"])){
				$field_deplacements=json_decode($field_deplacements_raw[0]["value"],true);
			}
			//$field_deplacements=json_decode($order->get('field_deplacements')->getValue()[0]["value"],true);
			if ($longitude  !=0){
				if ($timestamp ==0 ){
					$field_deplacements[] = array (
						"long" => $longitude , 
						"lat" => $latitude,
						"timestamp" => time(),
					);					
				}
				else{
					$field_deplacements[] = array (
						"long" => $longitude , 
						"lat" => $latitude,
						"timestamp" => date("H:i",$timestamp),
					);										
				}
				
			}

			$field_deplacements= json_encode($field_deplacements);
			//Start from here
			//$field_deplacements .= " "."Senkouh";
			$order->set("field_deplacements", $field_deplacements);
			//$field_deplacements2=$order->get('field_deplacements')->getValue()[0]["value"];

		//ob_start();
		//var_dump("field_deplacements2");
		//var_dump($field_deplacements2);
		//$dumpy = ob_get_clean();
		//\Drupal::logger('velo')->notice('Deplacements: '.$dumpy);
		
			
			$order->save();
		}
		return array(
		  '#type' => 'markup',
		  '#markup' => 	'merci'
		);			
		
	}
	
	public function _get_static_polygon($tour_variation_id){
		ob_start();
		//$tour_variation_id = 12;
		$parcours_statique = array ();		
		$monuments =  array ();
		
		$variation = \Drupal\commerce_product\Entity\ProductVariation::load($tour_variation_id );
		if ( $variation->isActive()){//Article Actifs
			//var_dump ($variation->get('field_booking_priority')->getValue()[0]["value"]);
			$monument_list = $variation->get('field_monuments')->getValue();
			$step = 1;
			foreach ($monument_list as $key => $value){
				$monument_info = \Drupal\field_collection\Entity\FieldCollectionItem::load($value ["value"]);
				$parcours_statique [] = array (
					"long" => $monument_info->get('field_longitude')->getValue()[0]["value"] , 
					"lat" => $monument_info->get('field_latitude')->getValue()[0]["value"]
				);
				$field_description = $monument_info->get('field_description')->getValue();
				$field_icon = $monument_info->get('field_icon')->getValue();
				if (isset ($field_description[0]["value"])){
					$monuments [] = array (
						"long" => $monument_info->get('field_longitude')->getValue()[0]["value"] , 
						"lat" => $monument_info->get('field_latitude')->getValue()[0]["value"],
						"step" =>  $step, 
						"activity" =>  substr ( strip_tags($field_description[0]["value"]), 0 , 10 )."...",
						"favColor" => $field_icon[0]["value"],
						"align" => "cm", 
						"comment" => $field_description[0]["value"],
						 
					);
					$step ++;
				}
				
			}
			var_dump ("Parcours statique");
			var_dump ($parcours_statique);
			
			var_dump ("Monuments");
			var_dump ($monuments);			
			//var_dump ($variation->get('field_monuments')->getValue()[0] ["value"]);			 	
		}
		//ob_start();
		//var_dump($item_list);
		//var_dump("item_list apres");
		//var_dump("available_ressources");
		//var_dump($output);
		$dumpy = ob_get_clean();
		\Drupal::logger('velo')->notice('Tour Variation Info:'.$dumpy);
		
		return (
			array (
				"parcours_statique" => $parcours_statique ,
				"monuments" => $monuments,
			)
		);
	}
	public function parcours (){//Dessine le polygone de parcours ainsi que la position courante.
	
		return array(
		  '#type' => 'markup',
		  '#markup' => 
			'<div id="map" class="smallmap"></div>
			<div><p id="ajaxdemo"></p></div>'
		);			
	}  
	public function dbb_parcours (){
		
		return array(
		  '#type' => 'markup',
		  '#markup' => 
			'<div id="map" class="smallmap"></div>
			<div><p id="ajaxdemo"></p></div>'
		);
	}
	
	public function dbb_debug (){
		return array(
		  '#type' => 'markup',
		  '#markup' => 
			"Action is Done"
		);					
		//return array(
		//  '#type' => 'markup',
		//  '#markup' => 
		//	'<div id="map" class="smallmap"></div>
		//	<div><p id="ajaxdemo"></p></div>'
		//);
	}
	
	public function chache1 (){
	
		global $user;		
		$product_id = 1;
		// Create the new order in checkout; you might also check first to
		// see if your user already has an order to use instead of a new one.
			
		$product = \Drupal\commerce_product\Entity\Product::load('4');
		$variations = $product->getVariations();
		$variation_red_medium = reset($variations );
		
		$order_item = \Drupal\commerce_order\Entity\OrderItem::create([
			'type' => 'default',
			'purchased_entity' => $variation_red_medium,
			'quantity' => 3,
			'unit_price' => $variation_red_medium->getPrice(),
		]);
		$order_item->save();
	
		// You can set the quantity with setQuantity.
		//$order_item->setQuantity('1');
//		$order_item->save();
	
		// You can also set the price with setUnitPrice.
		//$unit_price = new \Drupal\commerce_price\Price('9.99', 'USD');
		//$order_item->setUnitPrice($unit_price);
		//$order_item->save();		

		//$profile = \Drupal\profile\Entity\Profile::create([
		//	'type' => 'customer',
		//	'uid' => 1,
		//]);
		//$profile->save();
		$user = \Drupal::currentUser();
	
		// Next, we create the order.
		$order = \Drupal\commerce_order\Entity\Order::create([
			'type' => 'default',
			'state' => 'draft',
			'mail' => 'user@example.com',
			//'uid' => 1,
			'uid' => $user->id(),
			'ip_address' => '127.0.0.1',
			'order_number' => '8',
			//'billing_profile' => $profile,
			'store_id' => '1',			
			'order_items' => [$order_item],
			'placed' => time(),
		]);
		$order->save();
		
	}
	
	public function check_availability ($varation_bundle, $date_debut,$date_fin,$quantity,$parameter){
				
		//$date_debut = "2018-07-08";
		//$date_fin = "2018-07-10";
		//$quantity = 1;
		$error_list = array();
		if (isset ($parameter['ressource_category'])){
			$ressource_category = $parameter['ressource_category'];
		}
		else{
			$ressource_category = -1;
		}
		
		

		$order_dates_list = array ();
		$item_list = array();
		$item_list_ids = array();
		$error = "";
				
		$varaition_entity = 'commerce_product_variation';
		//$varation_bundle = 'guide';//velo	guide	telephone
		$output =  array();
		
		ob_start();

		$query = \Drupal::entityQuery($varaition_entity)
		//->condition('type', $varation_bundle);
		->condition('type', $varation_bundle);
		
		$vid = $query->execute();
		//Rechercher les articles
		$product_list = array();
		var_dump ("Count raw results");
		var_dump (count ($vid));
		foreach ($vid as $key => $value){
			$variation = \Drupal\commerce_product\Entity\ProductVariation::load($value);
			var_dump ("Confg");
			//var_dump ($ressource_category);
			//var_dump ($variation->get('field_category')->getValue()[0]["value"]);
			var_dump ($varation_bundle );
			
			if ( $variation->isActive()//Article Actifs
				and (
					(	$varation_bundle == 'velo' and 
						$variation->get('field_category')->getValue()[0]["value"] == $ressource_category
					)					
					or
					($varation_bundle == 'telephone')
					or 
					($varation_bundle == 'guide')
					or 
					($varation_bundle == 'insurance')
				)
			)
			{
				$unavailabilities = $variation->get('field_unavailability')->getValue();
				if (!isset($item_list [$variation->id()])){//Construction de l'article
					//$item_list [$variation->getSku()] = array (
					$item_list [$variation->id()] = array (					
						'unavailabilities'=> array(),
						'field_booking_priority'=> $variation->get('field_booking_priority')->getValue()[0]["value"],
					);
				}
						
				foreach ($unavailabilities as $key_unv => $value_unv){
					//$item_list [$variation->getSku()]['unavailabilities'][] = 
					$item_list [$variation->id()]['unavailabilities'][] = 
					array(
						'value' => $value_unv["value"], 
						'end_value' => $value_unv["end_value"]
					);
				}
			}
		}	
		var_dump ("item_list avant");
		var_dump ($item_list);

		$entity_type = 'commerce_order';
		$entity_bundle = 'default';
		
		$query = \Drupal::entityQuery($entity_type)
			->condition('type', $entity_bundle);
			
		$vid = $query->execute();
		
		foreach ($vid as $key => $value){
			$order = \Drupal\commerce_order\Entity\Order::load($value);

			if ($order->getState()->value == "completed"){
				$item_list_ids = $order->getItems();
				foreach ($item_list_ids as $key_it => $value_it){
					if ($value_it->getPurchasedEntity()->bundle() ==$varation_bundle and 
					isset($item_list [$value_it->getPurchasedEntity()->id()])){
						//if (!isset($item_list [$value_it->getPurchasedEntity()->getSku()])){
						//	$item_list [$value_it->getPurchasedEntity()->getSku()] = array (
						//		'unavailabilities'=> array(),
						//		'field_booking_priority'=> $value_it->getPurchasedEntity()->get('field_booking_priority')->getValue()[0]["value"]
						//	);
						//}
						$item_list [$value_it->getPurchasedEntity()->id()]['unavailabilities'][]=						
							array(
								'value' => $order->get('field_check_in')->getValue()[0]["value"], 
								'end_value' => $order->get('field_check_out')->getValue()[0]["value"]
							);
						break;			
					}
				}
			}
		}
		
		
		//Comparaison des dates
		$available_ressources = array ();
		foreach ($item_list as $key_list => $value_list){
			$ressource_availability = true;
			foreach ($value_list ['unavailabilities']as $key_l => $value_l){
				if ($varation_bundle !="insurance" and  !($date_fin < $value_l['value'] or $date_debut > $value_l['end_value'])){
					$ressource_availability = false;
					$error .= '('.$value_l['value'].' to '.$value_l['end_value'].')';
					break;
				}
			}
			if ($ressource_availability){
				$available_ressources [$key_list] = $value_list ['field_booking_priority'];
			}
		}
		
		
		//Trier les ressources suivant la priorité
		asort ($available_ressources);
		$available_ressources =  array_reverse ( $available_ressources, true);		
		if (count ($available_ressources) >= $quantity){
			$i=0;
			foreach ($available_ressources as $key_ress => $value_ress){
				$output [$key_ress] = $value_ress;
				$i++;
				if ($i == $quantity){
					$error = "";
					break;
				}
			}
		}
		else{
			$error = "No available ".$varation_bundle." found for that period :".$error; 
			$error .= implode  (',',$available_ressources).'|'.$quantity;
			$error_list [] = $error;
			var_dump ("error");
			var_dump ($error);
		}
			
		//var_dump("item_list_ids");
		//var_dump($item_list_ids);
		var_dump("item_list apres");
		var_dump($item_list);
		var_dump("error_list");
		var_dump($error_list);		
		var_dump("available_ressources");
		var_dump($output);
		var_dump("Dates");
		var_dump($date_debut);
		var_dump($date_fin);
		
		//var_dump($order_dates_list);		
		$dumpy = ob_get_clean();
		\Drupal::logger('velo')->notice('Booking Purchased Items:'.$dumpy);
		
		return (array ('output' => $output, 'error' => implode (' ', $error_list)));

		//return array(
		//  '#type' => 'markup',
		//  '#markup' => 
		//	"Action is Done"
		//);			
	}	
	function _generation_pdf($order) {
		/*$order = array();
		$order ['id']= '180430-001';
		$order ['order_time']= '30-04-2018';
		$order['lines'] = array("00014|Parfum CK|5|30|150", "00125|Parfum Hugo Boss|5|20|100", "08226|Parfum Coco Channel|4|15|60");
		
		$order ['nom_client']= 'Mme/Mr Dupont';
		$order ['adresse_client']= 'Rue des Jasmins';

		$order ['Total HT'] = '5,00';
		$order ['Frais de Port'] = '5,00';
		$order ['Total TTC'] = '5,00';

		$order ['message']= "Nous procèderons à l'envoi du colis dès la réception de votre virement sur notre compte. Vous pouvez consulter nos conditions générales de vente sur cette adresse http://parfumstreet.fr/cgv Pour toute demande ou réclamation, nous sommes à votre disposition au 06 17 87 76 80 ou par e-mail contact@parfumstreet.fr";*/

		date_default_timezone_set('Africa/Tunis');
		

		//$date = mktime(12, 0, 0, $date['month'], $date['day'], $date['year']);
		//Drupal\parfum\Controller\PDF
		//$pdf = new PDF();
		$pdf = new PDF\PDF;
		//$pdf = new PDF('L');
		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetLineWidth(0.05);
		
		$pdf->Image('sites/default/files/logo_transparent.png', 0, 0, 200);
		$pdf->Image('sites/default/files/logo_pdf_171_84.png', 5, 5);
		
		//logo_pdf.png	300px × 84px
		$pdf->SetFont('arial', 'B', 12);
		//$pdf->SetTextColor(163, 2, 7);
		$pdf->SetTextColor(0, 153, 204);
		$pdf->Text(130, 15, utf8_decode($order ['nature'].' n°: '. $order ['id']));
		$pdf->SetTextColor(0, 0, 0);
		
		$pdf->Text(155, 28, utf8_decode('Djerba le '.date('d/m/Y',strtotime($order ['order_time'])).',' ));
		 
		$pdf->SetFont('arial', '', 10);
		//start from here http://www.fpdf.org/en/script/script92.php		
		//$pdf->Text(23, 50, utf8_decode('Rapport de Visite Chaufferie et Station - Relevé du '.date('d/m/Y', $node->created) ));	
		$pdf->Text(5, 35, utf8_decode('Émetteur'));	
		$pdf->SetFillColor(230,230,230);
		//$pdf->Rect(5,37,82,45,'FD');//45 + 37 = 82
		$pdf->Rect(5,37,82,53,'FD');//
		$pdf->SetFont('arial', 'B', 12);
		//$pdf->SetXY(5, 39);
		$pdf->Text(7, 42, utf8_decode('Société Djerba by Bike'));	
		$pdf->SetFont('arial', '', 12);
		$pdf->Text(7, 48, utf8_decode('Midoun'));	
		$pdf->Text(7, 54, utf8_decode('Djerba'));	
		$pdf->SetFont('arial', 'B', 12);
		$pdf->Text(7, 60, utf8_decode('Téléphone :'));
		$pdf->Text(7, 66, utf8_decode('Matricule Fiscale :'));	   
		$pdf->SetFont('arial', '', 12);
		$pdf->Text(32, 60, utf8_decode('+216 29 19 29 54'));	
		$pdf->Text(9, 72, utf8_decode(' 100200300  A.B.C. Djerba'));
		
		//100
		//171/4=42
		$pdf->Image('sites/default/files/logo_fb_42.png', 7, 76);
		$pdf->Text(15, 80, utf8_decode('djerbabybike'));
		$pdf->Image('sites/default/files/logo_instgrm_42.png', 7, 82);
		$pdf->Text(15, 87, utf8_decode('djerbabybike'));
		
										

		$pdf->SetFillColor(255,255,255);
		
		
		$pdf->SetFont('arial', '', 10);
		$pdf->Text(112, 35, utf8_decode('Adressée à'));	
		$pdf->Rect(112,37,85,53);
		$pdf->SetFont('arial', 'B', 12);
		$pdf->Text(114, 42, utf8_decode('Client:' ));
		$pdf->Text(114, 48, utf8_decode('Forme Juridique:' ));
		$pdf->Text(114, 54, utf8_decode('Téléphone:' ));		
		$pdf->Text(114, 60, utf8_decode('Adresse:' ));
		

		$pdf->SetFont('arial', '', 12);
		$pdf->Text(129, 42, utf8_decode($order ['nom_client']));
		$pdf->Text(150, 48, utf8_decode('Client Particulier'));	
		$pdf->Text(150, 54, utf8_decode($order ['telephone']));	
		
		
		$initial_address_line =  66;
		foreach ($order ['adresse_client'] as $key_a => $value_a){
			$pdf->Text(114, $initial_address_line, utf8_decode(' '.$value_a));
			$initial_address_line += 6;
		}
		
				
		
		$pdf->SetFont('arial', '', 10);
		$pdf->Text(159, 100, utf8_decode('Montants exprimés en '.$order['currency']));
		$pdf->SetFont('arial', '', 12);
		
		
		$pdf->SetXY(5, 102);
		
		//$pdf->MultiCell(170, 10, utf8_decode("Suite à notre visite à votre usine le ").date ('d/m/Y').utf8_decode(", nous avons effectué des analyses d'eau concernant la chaufferie.Vous trouverez ci-dessous les valeurs mesurées le jour de notre visite ainsi que nos recommandations."),0);
		
		/*$pdf->SetFont('arial', 'BU', 12);
		$pdf->Ln(2);
		$pdf->MultiCell(170, 10, utf8_decode("Relevé des mesures prises:"),0);	*/
		
		// Largeurs des colonnes
		$pdf->SetFillColor(230,230,230);
		//$pdf->SetDrawColor(30,30,30);
		$w = array(35, 100, 25, 19,21);
			
		$header = array(utf8_decode("Code"),utf8_decode("Désignation"), utf8_decode("P.U./Jour"), utf8_decode("Jours"),utf8_decode("Total TTC"));
		// En-tête
		for($i=0;$i<count($header);$i++){
			$pdf->Cell($w[$i],7,$header[$i],1,0,'C',true);
		}
		$pdf->Ln();
		
		
		//Code|Designation|PUHT|Qte|TotalHT
		
		
		 
		//$pdf->SetXY(5, 122);
		


		//if (isset ($node->field_presentation_pdf['und'])){//We will draw the taken paramters.
		if (isset ($order['lines'])){//We will draw the taken paramters.
			foreach ($order['lines'] as $key_l=> $value_l){
				$parameter_list = explode ('|',$value_l);
				$pdf->SetX(5);
				/*if (count ($parameter_list)==2){//we have here a section
					$pdf->SetFont('arial', 'B', 12);
					$pdf->Cell(10,7,$parameter_list[0],1,0,'C',false);
					$pdf->Cell(175,7,utf8_decode($parameter_list[1]),1,0,'L',false);
					$pdf->Ln();
				}*/
				//elseif (count ($parameter_list)==5){
				if (count ($parameter_list)==5){
								/*ob_start();
			var_dump(count ($parameter_list));
			$dumpy = ob_get_clean();
			\Drupal::logger('parfum')->notice('Le nombre de paramètres est :'.$dumpy);*/

			
					//$parameter_data =  array("1","Température (T)", "50 °C", "<60","Valeur conforme.");
					$pdf->SetFont('arial', '', 12);
					
					for($i=0;$i<count($parameter_list);$i++){
						if ($i==2 or $i==3 or $i==4 ){
							$pdf->Cell($w[$i],7,utf8_decode($parameter_list[$i]),0,0,'R',false);
						}
						else{
							$pdf->Cell($w[$i],7,utf8_decode('  '.$parameter_list[$i]),0,0,'L',false);
						}
					}
					$pdf->Ln();
				}
			}
			
			//$w_totaux = array ('Sous Total H.T','Frais de Port H.T', 'TOTAL H.T');
			$w_totaux = array ('Net TTC');
			$w_fillcolor = array (255,248,230);
			
			//for ($j=0;$j<3;$j++){
			for ($j=0;$j<1;$j++){
				
				//if ($j==2){//Juste avant le total ht on mettra la mention.
				//	$pdf->SetFillColor(255,255,255);
				//	$pdf->SetX(60);
				//	$pdf->Cell(75,7,utf8_decode("TVA non applicable,article 293 B du CGI."),0,0,'L','F');
				//}
				/*else{
					$pdf->SetX(140);
				}*/
				$pdf->SetX(140);
				$pdf->SetFillColor($w_fillcolor[$j],$w_fillcolor[$j],$w_fillcolor[$j]);
				/*$pdf->Cell(30,7,utf8_decode($w_totaux[$j]),0,0,'L','F');
				$pdf->Cell(35,7,utf8_decode($order [$w_totaux[$j]]),0,0,'R','F');*/
				$pdf->Cell(35,7,utf8_decode($w_totaux[$j]),0,0,'L','F');
				$pdf->Cell(30,7,utf8_decode($order [$w_totaux[$j]]),0,0,'R','F');
				$pdf->Ln();				

			}
			$table_height = 7 * count($order['lines']);
			
			$pdf->Rect(5,109,200,$table_height);
			$starting_position = 5;
			for($i=0;$i<count($header);$i++){
				$pdf->Rect($starting_position,109,$w[$i],$table_height);
				$starting_position += $w[$i];
			}
			/*$w = array(35, 100, 25, 20,20);
			$pdf->Rect(5,109,200,$table_height);*/
		}
		
		/*if (isset($order['sum'])){
			foreach ($order['sum'] as $key_l=> $value_l){
				$parameter_list = explode ('|',$value_l);
				if (count ($parameter_list)==2){
					
				}
				$pdf->SetFont('arial', 'B', 12);
				$pdf->Cell(10,7,$parameter_list[0],1,0,'C',false);
				$pdf->Cell(175,7,utf8_decode($parameter_list[1]),1,0,'L',false);
				$pdf->Ln();				
			}
		}*/
		
		$pdf->Ln();
		$pdf->SetFont('arial', '', 12);

		//$pdf->MultiCell(190, 5, utf8_decode($order ['message']),0);
		//$pdf->SetXY(5,160);
		$pdf->SetXY(5,$pdf->GetY() + 10);
		$pdf->WriteHTML(utf8_decode($order ['message']));
		//$pdf->WriteHTML($order ['message']);
		$pdf->Ln();
		
		//$pdf->WriteHTML(utf8_decode($node->body['und'][0]['value']));
		
		$pdf->Ln();
		$pdf->Ln();
		
		
		

		//$file_name = "AE_" . ucfirst($societe_user->name).'_'.date ('d-m-Y', strtotime($node->field_period['und'][0]['value'])) . ".pdf";
		$file_name = $order ['file_name'];
		
		
		
		$file_path = 'sites/default/files/private/'.$order ['subdirectory'].'/'.$file_name;
		//$file_path = $file_name;
		
		$pdf->Output($file_path, "F");
			
		$file = file_save_data(file_get_contents($file_path), 'private://'.$order ['subdirectory'].'/'.$file_name,FILE_EXISTS_REPLACE );
		 
		
		$order_ui = \Drupal\commerce_order\Entity\Order::load($order ['uid']);
		//$order->get('field_facture')->setValue(set('target_id',$file->id());
		//$order_ui->get('field_facture')->set('target_id',$file->id());
		//$order->get('field_facture')->setValue('target_id');
		//$order->set("field_facture", new \Drupal\commerce_price\Price(strval ($value['PVR']), 'EUR'));
		//$order->set("field_facture", $file->id());
		//$order_ui->get('field_facture')->setValue('target_id');
		$order_ui->get('field_'.$order ['subdirectory'])->setValue($file->id());
		$order_ui->save();
		
		//$file->display = 1;
		//$file->uid = $node->uid;		
		//$node->field_rapport['und'][0] = (array)$file;
		
		return array(
		  '#type' => 'markup',
		  '#markup' => 'La facture est générée',
		);
		
	}

}

