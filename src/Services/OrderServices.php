<?php
namespace App\Services;


use App\Entity\Cart;
use App\Repository\ProductRepository;
use App\Entity\CartDetails;
use Doctrine\ORM\EntityManagerInterface;

/**
 *
 */
class OrderServices
{
    private $manager;
    private $repoProduct;

  function __construct(EntityManagerInterface $manager, ProductRepository $repoProduct)
  {
    $this->manager = $manager;
    $this->repoProduct = $repoProduct;
  }

  // Methode pour creer une commande
  public function createOrder( $cart){
    //1: Initialiser Order
    $order = new Order();
    $order -> setReference($cart->getReference())
           -> setCarrierName($cart->getCarrierName())
           -> setCarrierPrice($cart->getCarrierPrice())
           -> setFullName($cart->getFullName())
           -> setDeliveryAddress($cart->getDeliveryAddress())
           -> setMoreInformations($cart->getMoreInformations())
           -> setQuantity($cart->getQuantity())
           -> setsubTotalHT($cart->getSubTotalHT())
           -> setTaxe($cart->getTaxe())
           -> setSubtotalTTC($cart->getSubTotalTTC())
           -> setUser($cart->getCreatedAt());
    $this->manager->persist($order);

    //getCartDetails contient les details du panier
    $products = $cart->getCartDetails()->getValues();

    // boucler sur $products et mettre la valeur dans la $cart_product
    foreach ($products as $cart_product) {
      //Initialiser $orderDetails
      $orderDetails = new OrderDetails();
      // Remplir les attributs
      $orderDetails -> setOrders($order)
             -> setProductName($cart_Product->getProductName())
             -> setProductPrice($cart_Product->getCarrierPrice())
             -> setFullName($cart_Product->getFullName())
             -> setQuantity($cart_Product->getQuantity())
             -> setSubTotalHT($cart_Product->getSubTotalHT())
             -> setSubtotalTTC($cart_Product->getSubTotalTTC())
             -> setTaxe($cart_Product->getTaxe());
      // Enregistrer en memoire
      $this->manager->persist($orderDetails);
    }
    // Enregistrer en base de données
    $this->manager->flush();

    return $order;
  }
  // une methode pour sauvegarder un panier et d'autres données en base de données. Cela permet de contacter le user plus tard s'il ne valide pas son paiment
  //fonction qui permet de generer le line_items
  public function getLineItems($cart){
    //Recuperer le detail du panier
    $cartDetails = $cart->getCartDetails();
    /*Structure du tableau data_product:
    [
      'quantity'=>5,
      'products'=>Objet metier product
    ]*/
    $line_items=[];
    foreach ($cartDetails as $details) {
      // Recupererationd du produit
      $product = $this -> $repoProduct->findOneByName($details->getProductName());
      //Chargement des produits
      //Remplir le tableau à chaque iteration
      $line_items[] = [
        'price_data' => [
          'currency' => 'usd',
          'unit_amount' => $product->getPrice(), // Le prix du produit. $product est un objet. unit_amount ne prends pas un nombre avec virgule
          'product_data' => [
            'name' => $product->getName(), // Le nom du produit
            'images' => [$_ENV['YOUR_DOMAIN'].'/uploads/products/'.$product->getImage()], // L'image du produit
          ],
        ],
        'quantity' =>  $details->getQuantity(), // Qauntité du produit cad le nombre de produits qu'il y a dans details
      ];
    }

    // Ajouter les données concernant la Taxe en ajoutant simplement une ligne
    $line_items[] = [
      'price_data' => [
        'currency' => 'usd',
        'unit_amount' => $cart->getTaxe()*100, // Le montant de la taxe. un nombre sans virgule. c'est pourquoi on multiplie par 100
        'product_data' => [
          'name' => 'TVA (20%)' , // Le nom du produit
          //'images' => [$_ENV['YOUR_DOMAIN'].'/uploads/products/'.$product->getImage()], // L'image du produit
          'images' => [$_ENV['YOUR_DOMAIN'].'/uploads/products/'], // L'image du produit

        ],
      ],
      'quantity' =>  1, // Qauntité du produit cad le nombre de produits qu'il y a dans details
    ];
    // Ajouter les données concernant le transport : Carrier
    $line_items[] = [
      'price_data' => [
        'currency' => 'usd',
        'unit_amount' => $cart->getCarrierPrice()*100, // Le prix du livreur. un nombre sans virgule. c'est pourquoi on multiplie par 100
        'product_data' => [
          'name' => $cart->getCarrierName(), // Le nom du transporteur
          //'images' => [$_ENV['YOUR_DOMAIN'].'/uploads/products/'.$product->getImage()], // L'image du produit
          'images' => [$_ENV['YOUR_DOMAIN'].'/uploads/products/'], // L'image du produit

        ],
      ],
      'quantity' =>  1, // Qauntité du produit cad le nombre de produits qu'il y a dans details
    ];
    return $line_items;
  }

  public function saveCart($data , $user){
      // Structure de la methode
    /*[
      'products' => [],// la clé contient les produits du panier
      'data' => [], // la clé data contient les informations consernant le panier: prix TTC, prix HT, ...
      'checkout' => [  // // la clé contient les données saisies par le user sur la page checkout
                    'address' => address,  // contient un objet address
                    'carrier' => carrier   // contient un objet carrier cad les informations retireées du formulaire
      ]
    ]*/

    // 1: Creer l'objet metier $cart
    $cart = new Cart();
    $reference = $this->generateUuid();
    $address = $data['checkout']['address'];
    $carrier = $data['checkout']['carrier'];
    $informations =$data['checkout']['informations'];

    //remplir les attributs de $cart
    $cart ->setReference($reference) // Modifier sa reference en lui affectant la refence que nous avons recuperer
          ->setCarrierName($carrier->getName())
          -> setCarrierPrice($carrier->getPrice()/100)
          -> setFullName($address->getFullName())
          -> setDeliveryAdress($address)
          -> setMoreInformations($informations)
          -> setQuantity($data['data']['quantity_cart']) // la qauntité totale d'élément que ns avons ds notre panier. Cela est disponible dans la clé data
          -> setSubTotalHT($data['data']['subTotalHT'])
          -> setTaxe($data['data']['Taxe']) // unitle d'arrondir ici comme c'est deja arrondi ds CartServices
          -> setSubtotalTTC(round(($data['data']['subTotalTTC'] + $carrier -> getPrice()/100),2))
          -> setUser($user)
          -> setCreatedAT(new \DateTime());
    $this -> manager -> persist($cart);

    // 2: Creer l'objet metier $cart_details_array pour stocker les données
    $cart_details_array = [];
    foreach ($data['products'] as $products) { // chaque element est un produit en particulier. $products est tb consernant un produit en particulier
      //
      $cartDetails = new CartDetails();

      /* Structure de $products: A l'interieur de $products nous avons la quantité,
      [
        'quantity' => 5 /ex
        'product' => objet
      ]*/
      $subtotal = $products['quantity'] * $products['product']->getPrice()/100;

      $cartDetails -> setCarts($cart)
                   -> setProductName($products['product']->getName())
                   -> setProductPrice($products['product']->getPrice()/100)
                   -> setQuantity($products['quantity'])
                   -> setSubTotalHT($subtotal)
                   -> setSubtotalTTC($subtotal*1.2)
                   -> setTaxe($subtotal*0.2);
     $this->manager->persist($cartDetails);
     $cart_details_array[] = $cartDetails;
    }
    $this->manager->flush();

    // retour vers la reference de l'element que ns avons creé. c'est grace à cette reference on pourra remonter pour retrouvé le panier en question qui sauvegardé et le contenu egalement
    return $reference;

  }
  // methode pour garder une clé unique de la commande pour sauvegarder la reference.
  //le Role de cette methode est de renvoyer un id unique qu'on utilisera quand veut sauvegarder un panier
  public function generateUuid(){
    //Initialise le générateur de nombre aleatoires Mersenne Twister
    mt_srand((double)microtime()*100000);

    // strtoupper: Renvoie une chaine en majuscules
    //uniqid : Génère un identifiant unique
    $charid = strtoupper(md5(uniqid(rand(), true)));

    // Générer une chaine d'un octet à partir d'un nombre
    $hyphen = chr(45);

    //substr : Retourne un segment de chaïne
    $uuid = ""
    .substr($charid, 0, 8).$hyphen
    .substr($charid, 8, 4).$hyphen
    .substr($charid, 12, 4).$hyphen
    .substr($charid, 16, 4).$hyphen
    .substr($charid, 20, 12).$hyphen;
    return $uuid;
  }

  public function __toString()
  {
    return $this->name;
  }

}
