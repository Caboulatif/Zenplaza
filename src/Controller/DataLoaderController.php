<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Categories;
use App\Entity\Product;
//use c'est savoir d'où ça vient

class DataLoaderController extends AbstractController
{
    /**
     * @Route("/data", name="data_loader")
     */
    public function index( EntityManagerInterface $manager): Response
    {
      //Recuperation de l'adresse du fichier : dirname permet de revenir en arriere deux fois.
      $file_products = dirname(dirname(__DIR__))."/products.json";
      $file_categories = dirname(dirname(__DIR__))."/categories.json";
      //Regarder le repertoire où on est
      //dd(__DIR__);
      // lecture du contenu du fichier. file_get_contents permet d'avoir le contenu du fichier
      //$data_product = file_get_contents($file_product);
      //Convertir(decoder) les données en format php en tableau
      $data_products = json_decode(file_get_contents($file_products))[0]->rows;
      $data_categories = json_decode(file_get_contents($file_categories))[0]->rows;
      //dd($data_categories[0]->rows);

      //Creer un tableau categorie. Faire une boucle for pour parcourir le tableau reçu.
      $categories = [];

      foreach($data_categories as $data_category){
        // creer un objet metier  Categorie : les clés 1 et 3 sont le nom et l'image
        $category = new Categories();
        $category ->setName($data_category[1])
                  ->setImage($data_category[3]);
                  // persister pour garder en memoire cette categorie
                  $manager -> persist($category);
                  $categories[] = $category;
      }
      // Parcourir le $data_products et a chaque element $data_product faire un set du produit $product
      foreach($data_products as $data_Product){
        // creer un objet metier  Categorie : les clés 1 et 3 sont le nom et l'image
        $product = new Product();
        $product->setName($data_Product[1])
                  ->setDescription($data_Product[2])
                  ->setPrice($data_Product[4])
                  ->setIsBestSeller($data_Product[5])
                  ->setIsNewArrival($data_Product[6])
                  ->setIsFeatured($data_Product[7])
                  ->setIsSpecialOffer($data_Product[8])
                  ->setImage($data_Product[9])
                  ->setQuantity($data_Product[10])
                  ->setTags($data_Product[12])
                  ->setSlug($data_Product[13])
                  ->setCreatedAt(new \DateTime());
                  // persister pour garder en memoire ce produit
                  $manager -> persist($product);
                  $products[] = $product;
      }
      // Faire un flush pour envoyer tout en base de données.
      //$manager->flush();


        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/DataLoaderController.php',
        ]);
    }
}
