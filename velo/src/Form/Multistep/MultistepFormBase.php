<?php 
/**
 * @file
 * Contains \Drupal\demo\Form\Multistep\MultistepFormBase.
 */

namespace Drupal\velo\Form\Multistep;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;


abstract class MultistepFormBase extends FormBase {
	

  

//abstract class MultistepFormBase extends ContentEntityForm {

  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;
  
  
  
	protected $cart ;
	protected $cartManager ;
	protected $attributeFieldManager ;  
	protected $my_store;

  /**
   * Constructs a \Drupal\demo\Form\Multistep\MultistepFormBase.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->store = $this->tempStoreFactory->get('multistep_data');
	
  }


  
  
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start a manual session for anonymous users.
    //if ($this->currentUser->isAnonymous() && !isset($_SESSION['multistep_form_holds_session'])) {
	if (\Drupal::currentUser()->isAnonymous() && !isset($_SESSION['multistep_form_holds_session'])) {
		
      $_SESSION['multistep_form_holds_session'] = true;
      $this->sessionManager->start();
    }

    $form = array();
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10,
    );

    return $form;
  }

  /**
   * Saves the data from the multistep form.
   */
  protected function saveData() {
    // Logic for saving data goes here...
    $this->deleteStore();
    drupal_set_message($this->t('The form has been saved.'));

  }

  /**
   * Helper method that removes all the keys from the store collection used for
   * the multistep form.
   */
  protected function deleteStore() {
    $keys = ['name', 'email', 'age', 'location','telephone'];
    foreach ($keys as $key) {
      $this->store->delete($key);
    }
  }
  
	protected function _select_variation ($type_produit, $quantite,$parametre){
		//sélectionner les variations appartenant à type produit
		
		$query = \Drupal::entityQuery('commerce_product')
		->condition('type',array($type_produit),'IN');
		$nids = $query->execute();
		
		$variations = array();
		$variations_list = array();
		foreach ($nids as $key => $value){
			$product = \Drupal\commerce_product\Entity\Product::load($value);
			$variations = array_merge ( $variations,  $product->getVariations());
		}
		if (isset($parametre['parcours_list'])){
			return ( $variations);
		}
		else{
			if (count($variations)>=$quantite){
				$variations_list = array_rand($variations, $quantite);
				//ob_start();
				////var_dump(array_keys($variations_list));
				////var_dump(array_keys($variations));	
				//var_dump($variations);	
				//var_dump(array_keys($variations_list));
				//var_dump($quantite);
				//$dumpy = ob_get_clean();
				//\Drupal::logger('Velo')->notice('Variation Structure 2:'.$dumpy);
				//return ( $variations_list);
				return ( reset($variations));
			}
			else{
				return (null);
			}			
		}
	}  
}


?>