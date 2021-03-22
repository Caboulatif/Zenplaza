<?php

namespace App\Controller\Cart;

use App\Services\CartServices;
use App\Services\OrderServices;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\CheckoutType;


// Le CheckoutController permet de renvoyer les données consernant le panier au template
class CheckoutController extends AbstractController
{
    private $cartServices;
    private $session;

    public function __construct( CartServices $cartServices , SessionInterface $session)
    {
      // Recuperation du panier
      $this->cartServices = $cartServices;
      $this->session = $session;
    }
    /**
     * @Route("/checkout", name="checkout")
     */
    public function index(Request $request): Response
    {
      //recuperation de l'utilisateur connecté
      $user = $this->getUser();
      //Recuperer le panier et fournir les données du panier à notre template
      $cart = $this -> cartServices->getFullCart();
      //Unitialser le formulaire et proceder à son affichage. Verifier si la clé products est definit
      if(!isset($cart['products'])){
        // s'il n'a rien on se redirige vers la page d'accueil
        return $this -> redirectToRoute("home");
      }
      // verifier si l'utilisateur connecté a dejà defini ses getAddresses. getAddresses recupere une collection. getValue recupere les valeurs
      if(!$user->getAddresses()->getValues()){
        //Ajouter un message Flash
        $this->addFlash('checkout_message', 'Please add an address to your account without continuing !');
        //renvoyer l'utilisateur vers la page d'ajout ou de creation d'une addresse
        return $this -> redirectToRoute("address_new");
      }
      // Regarder si les données sont dejà enregistrées
      if ($this->session->get('checkout_data')) {
        return $this ->redirectToRoute('checkout_confirm');
      }

      // Unitialisation  ou creation du formulaire, null signifie aucun mappge, ensuite renseigner les options: affichage du formulaire
      $form = $this->createForm(CheckoutType::class,null, ['user'=>$user]);

      //Tester si le formulaire est soumis ou renvoyer: Traitement du formulaire. On confier le Traitement à une autre methode (confirm())

        return $this->render('checkout/index.html.twig', [
          //La clé cart contient les données consernant le panier
          'cart'=>$cart,
          'checkout' => $form -> createView()
          ] );
    }

    /**
    * @Route("checkout/confirm", name="checkout_confirm")
    **/
    public function confirm(Request $request, OrderServices $orderServices): Response{
      // Recuperer l'utilisateur conecté et le Panier
      $user = $this->getUser();
      $cart = $this->cartServices->getFullCart();
      // Tester si le panier contient quelque chose
      if(!isset($cart['products'])){
        // s'il n'a rien on se redirige vers la page d'accueil
        return $this -> redirectToRoute("home");
      }
      // verifier si l'utilisateur connecté a dejà defini ses getAddresses. getAddresses recupere une collection. getValue recupere les valeurs
      if(!$user->getAddresses()->getValues()){
        //Ajouter un message Flash
        $this->addFlah('checkout_message', 'Please add an address to your account without continuing !');
        //renvoyer l'utilisateur vers la page d'ajout ou de creation d'une addresse
        return $this -> redirectToRoute("address_new");
      }
      // Unitialisation  ou creation du formulaire, null signifie aucun mappge, ensuite renseigner les options
      $form = $this->createForm(CheckoutType::class,null, ['user'=>$user]);

      //Analyser la requette qui est tappée
      $form->handleRequest($request);

      // Est ce que le formulaire est soumis ou les données sont valides ou bien est ce qu'il y a des données dans la clé qui pour clé checkout_data
      if($form->isSubmitted() && $form->isValid() || $this -> session -> get('checkout_data')){
            // est ce qu'on est entrée dans la boucle parce que la session est definie ? Si oui les données viennent de la session
            if ($this -> session -> get('checkout_data')) {
              $data = $this->session->get('checkout_data');
            }// Mais on vient à partir du formulaire?
            else{
              // Recuperer les données issues du formulaires et les envoyer au template pour les afficher afin que le user puisse confirmer
              $data = $form->getData();
              // Si les données viennent du formulaire il faut les sauvegarder dans la session
              $this->session ->set('checkout_data', $data);
              }

        //Recuperer l'addesse qu'on a dans le resulta
        $address =$data['address'];
        //Recuperer le carrier qu'on a dans le resulta
        $carrier =$data['carrier'];
        //Recuperer les informations qu'on a dans le resulta
        $information =$data['informations'];
        //dd($data);

        //Sauvegarder le Panier
        $cart['checkout'] = $data;
        $reference = $orderServices ->saveCart($cart, $user);
        //dd($cart);dd($reference);


        // rediriger l'utilisateur vers le template confirm si la condition du if est remplie
        return $this->render('checkout/confirm.html.twig', [
          //La clé cart contient les données consernant le panier
          'cart'=>$cart,
          'address'=>$address,
          'carrier'=>$carrier,
          'informations'=>$information,
          'reference' => $reference,
          'checkout' => $form -> createView()
          ]);

      }

      // Sinon rediriger l'utilisateur sur le checkout
      return $this ->redirectToRoute('checkout');
    }

    /**
    *@Route("/checkout/edit", name="checkout_edit")
    **/
    public function checkoutEdit():Response{
      //Supprimer la session puis l'envoyer sur la page checkout
      $this->session->set('checkout_data', []);
      return $this->redirectToRoute("checkout");
    }

}
