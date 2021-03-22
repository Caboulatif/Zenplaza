<?php

namespace App\Services;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\RelateProducts;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Session\SessionInterface;



class CartServices{

  private $session;
  private $repoProduct;
  private $tva = 0.2;

  public function __construct(SessionInterface $session, ProductRepository $repoProduct)
  {
    $this->session = $session;
    $this->repoProduct = $repoProduct;
  }

  public function addToCart($id){

    $cart=$this->getCart(); // $cart c'est le panier
    if(isset($cart[$id])){
      //Le produit est déjà dans le panier
      $cart[$id]++;
    }else{
      //Le produit n'est pas encore dans le panier
      $cart[$id]=1;
    }
    $this->updateCart($cart);
  }

  public function deleteFromCart($id){

    $cart = $this->getCart();

    if(isset($cart[$id])){
      //Produit est déjà dans le panier
      if($cart[$id] > 1){
        // Le produit existe plus d'une fois
        $cart[$id]--;
      }else{
        unset($cart[$id]);
      }
      $this->updateCart($cart);
    }

  }
  // Methode pour supprimer tout l'element du panier
  public function deleteAllToCart($id){
    $cart = $this->getCart();
    if(isset($cart[$id])){
      //Produit est déjà dans le panier
        unset($cart[$id]);
        $this->updateCart($cart);
      }
  }

  // Methode pour supprimer le panier d'un coup
  public function deleteCart(){
      $this->updateCart([]);
  }
  // updateCart() est appelé chaque fois qu'il ya un mouvement du panier: ajout, suppression, ... d'un élément du panier
  public function updateCart($cart){
    //cart= clé,$cart le contenu de la session
    $this->session -> set('cart', $cart);
    // creer une deuxieme session pour rendre le panier visible partout contenant tout le panier
      $this->session -> set('cartData', $this->getFullCart());
  }

  public function getCart(){

    return $this->session -> get('cart',[]);
  }

  //Cette methode permet de recuperer les données complets du panier
  public function getFullCart(){
    $cart = $this->getCart();
    //Tableau qui contient tous les panier
    $fullCart = [];
    //tableau qui contient la quantité du produit
    $quantity_cart = 0;
    // montant total du produit
    $subTotal=0;
    //Parcourir le panier
    foreach($cart as $id => $quantity){
      $product = $this->repoProduct ->find($id);
      if($product){
        //produit recuperé avec succès. la clé products contient les produits qui sont dans le panier
        $fullCart['products'][]=
          [
            'quantity' => $quantity,
            'product'=> $product
          ];
          //Incrementer la quantité si le produit est defini avec la quantité du produit cad $quantity
          $quantity_cart += $quantity;
          //Incrementer la subTotal au prix reel du produit * la quantité du produit
          $subTotal += $product->getPrice()/100 * $quantity;
      }else{
        // id incorrecte
        $this->deleteFromCart($id);
      }

    }
    // Ajouter   $quantity_cart, $subTotal et les autres elements av de renvoyer le Parenier  en creant une clé data
    //la 2e clé data contient les informations sur le panier: quantity, subTotal,...
    $fullCart['data'] = [
      'quantity_cart' => $quantity_cart,
      'subTotalHT' => $subTotal,
      'Taxe' => round($subTotal * $this->tva,2),
      'subTotalTTC' => round( ($subTotal + ($subTotal * $this->tva) ), 2)
    ];
    // $fullCart retour le contenu panier cad les produits
    return $fullCart;

  }

}
