<?php

namespace App\Controller\Stripe;

use Stripe\Stripe;
use App\Entity\Cart;
use App\Services\CartServices;
use App\Services\OrderServices;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeCheckoutSessionController extends AbstractController
{
    /**
     * @Route("/create-checkout-session/{reference}", name="create_checkout_session")
     */
    public function index(?Cart $cart, OrderServices $orderServices): Response // grace au paramconverter ns recuperons  correctement un panier avec la reference sinon retourner nul
    {
      //Verifier si le panier est bien recuperé
      if (!$cart) { // si la reference est incorrecte
        return $this->redirectToRoute('home');
      }
      // Coninuer si le panier est bien recuperer
      Stripe::setApiKey('sk_test_51IWpbQLSI8kiU9SwM8QmwfQKqvk5YP964znQCa7cKmGhgAcSGOa7FZhpvvi1PCt8qr5gfTNWgByuA5yPxZv49ciF00kArMZG0L');

        $checkout_session = Session::create([
          'payment_method_types' => ['card'],
          'line_items' => $orderServices->getLineItems($cart), // getLineItems definit dans OrderServices Utilise le panier pour generer le line_items
          'mode' => 'payment',
          'success_url' => $_ENV['YOUR_DOMAIN'].'/stripe-payment-success',
          'cancel_url' => $_ENV['YOUR_DOMAIN'].'/stripe-payment-cancel',
        ]);


        return $this->json(['id' => $checkout_session->id]);
    }
}
