<?php

namespace App\Controller\Account;

use App\Entity\Address;
use App\Services\CartServices;
use App\Form\AddressType;
use App\Repository\AddressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/address")
 */
class AddressController extends AbstractController
{

  private $session;

  public function __construct( SessionInterface $session )
  {
    $this->session = $session;
  }

    /**
     * @Route("/", name="address_index", methods={"GET"})
     */
    public function index(AddressRepository $addressRepository): Response
    {
        return $this->render('address/index.html.twig', [
            'addresses' => $addressRepository->findAll(),
        ]);
    }
    // page d'ajout ou creation d'une nouvelle adresse
    /**
     * @Route("/new", name="address_new", methods={"GET","POST"})
     */
    public function new(Request $request, CartServices $cartServices): Response
    {
        $address = new Address();
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
          $user = $this->getUser();
          $address-> setUser($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($address);
            $entityManager->flush();
            //verifier si le panier du user contient des produits. Si oui renvoyer le user le checkout.
            if ($cartServices -> getFullCart()){
              return $this->redirectToRoute('checkout');
            }
            //Si le panier du user n'a rien on envoie le user dans son compte.
            $this->addFlash('address_message','Your address has been saved');
            return $this->redirectToRoute('account');
        }

        return $this->render('address/new.html.twig', [
            'address' => $address,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="address_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Address $address): Response
    {
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            // Faire un teste apres recuperation et enregistrement des données pour voir clé checkout_data est defini si oui on vient alors de la page confirm
            if ($this->session->get('checkout_data')) {
              // Recuperer er Mettre à jour les données de la Session
              $data = $this->session->get('checkout_data');
              // Modifier l'adresse qu'on a dans la Session. Mettre les nouvelles valeurs de l'adresse saisies par l'utilisateur
              $data['adress'] = $address;
              // Mettre à jour la session et y affecter $data
              $this->session -> set('checkout_data', $data);
              //Il faut retourner le user vers la page confirmer
              return $this->redirectToRoute('checkout_confirm');
            }

            $this->addFlash('address_message','Your address has been edited');
            return $this->redirectToRoute('account');
        }

        return $this->render('address/edit.html.twig', [
            'address' => $address,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="address_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Address $address): Response
    {
        if ($this->isCsrfTokenValid('delete'.$address->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($address);
            $entityManager->flush();
            $this->addFlash('address_message','Your address has been deleted');

        }

        return $this->redirectToRoute('account');
    }
}
