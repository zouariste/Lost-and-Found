<?php

namespace App\Controller;
use App\Entity\Post;
use Symfony\Component\HttpFoundation\Request;
use App\Form\FoundType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class FoundController extends AbstractController
{
    /**
     * @Route("/found", name="found")
     */
    public function found(Request $request, \Swift_Mailer $mailer)
    {
        if ( empty( $_GET['loc'])) {
            echo"<script>
                    var options = {
                      enableHighAccuracy: true,
                      timeout: 5000,
                      maximumAge: 0
                    };
                    
                    function success(pos) {
                      var crd = pos.coords;
                    
                      var char2=crd.latitude+' '+crd.longitude;
                      var char='/found?loc='+char2;
                      document.location.href=char;
                    }
                    
                    function error(err) {
                    }
                    
                    navigator.geolocation.getCurrentPosition(success, error, options);
                    
                    </script>
                    ";};
        $form = $this->createForm(FoundType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $foundFormData = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $Post = new Post();
            $Post->setType("F");
            $Post->setNomPrenom($foundFormData['Nom-Prenom']);
            $Post->setItem($foundFormData['Objet-Perdu']);
            $Post->setDescrip($foundFormData['Description']);
            $Post->setDate($foundFormData['Date-de-la-perte']);
            $Post->setReward(NULL);
            $Post->setTel($foundFormData['Tel']);
            $Post->setEmail($foundFormData['Email']);
            if ( !empty( $_GET['loc'])){
                $var=$_GET['loc'];
                $keywords = preg_split("/[\s,]+/",$var);
                $Post->setLatitude((float) ($keywords[0]));
                $Post->setLongitude((float) ($keywords[1]));
            }
            $entityManager->persist($Post);
            $entityManager->flush();

            $char=$foundFormData['Nom-Prenom']. PHP_EOL
                .' Nous venons de recevoir votre demande Lost'. PHP_EOL
                .' Nous vous tiendrons au courant!!'. PHP_EOL.'Cordialement';
            $message = (new \Swift_Message('Lost and Found Enit!!'))
                ->setFrom($foundFormData['Email'])
                ->setTo($foundFormData['Email'])
                ->setBody(
                    $char, 'text/plain');

            $mailer->send($message);
            return $this->redirectToRoute('found');
        }
        return $this->render('found/index.html.twig', [
            'our_form2' => $form->createView()
    ]);
    }
}
