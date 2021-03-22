<?php

namespace App\Controller\Cart;

use App\Services\CartServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{

    private $cartServices;

    public function __construct( CartServices $cartServices)
    {
      $this->cartServices = $cartServices;
    }

    // Cette page permet d'afficher le panier. Cett
    /**
     * @Route("/cart", name="cart")
     */
    public function index(): Response
    {
        /*$session->set("cart", ["name"=>"session"]);
        $cart = $session->get("cart");
         On cree un service dans /src/services et l'injecter dans ce controller au lieu d'utiliser les sessions*/

         //Recuperation des données du panier
        $cart = $this->cartServices->getFullCart();
        if(!isset($cart['products'])){
            return $this->redirectToRoute('home');
        }
        //Fournir les donneés à notre templete
        return $this->render('cart/index.html.twig', [
            'cart'=> $cart
        ]);
    }
      /**
      *@Route("/cart/add/{id}", name="cart_add")
      */
    public function addToCart($id): Response{
      //Ajouter un element dans le panier
      $this->cartServices->addToCart($id);
      return $this->redirectToRoute("cart");
    }

    /**
    *@Route("/cart/delete/{id}", name="cart_delete")
    */
  public function deleteFromCart($id): Response{
    //Supprimer le panier avant
    $this->cartServices->deleteFromCart($id);
    return $this->redirectToRoute("cart");
  }

  // Supprimer le produit du Panier
  /**
  *@Route("/cart/delete-all/{id}", name="cart_delete_all")
  */
  public function deleteAllToCart($id): Response{
    //Supprimer un element du en appler le methode deleteAllToCart du service CartServices
    $this->cartServices->deleteAllToCart($id);
    return $this->redirectToRoute("cart");
  }

}
