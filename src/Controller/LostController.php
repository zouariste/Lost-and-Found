<?php

namespace App\Controller;
use App\Entity\Post;
use App\Form\LostType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
function CompareString($input,$word){
    $lev = levenshtein($input, $word);
    similar_text($input, $word,$per);
    return $per;
}


function DistAB($lat_a,$lat_b,$lon_a,$lon_b)

{
    $delta_lat = $lat_b - $lat_a ;
    $delta_lon = $lon_b - $lon_a ;

    $earth_radius = 6372.795477598;

    $alpha    = $delta_lat/2;
    $beta     = $delta_lon/2;
    $a        = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($lat_a)) * cos(deg2rad($lat_b)) * sin(deg2rad($beta)) * sin(deg2rad($beta)) ;
    $c        = asin(min(1, sqrt($a)));
    $distance = 2*$earth_radius * $c;
    $distance = round($distance, 4);

    return $distance;

}
function LinkA($lat_a,$lat_b,$lon_a,$lon_b){
    $url = "https://www.google.com/maps/dir/";
    $origin = $lat_a . "," . $lon_a."/";
    $destination =  $lat_b . "," . $lon_b;
    $newUrl = $url . $origin .$destination. "/@" .$destination.',13z';
    return $newUrl;
}
class LostController extends AbstractController
{
    /**
     * @Route("/lost", name="lost")
     */
    public function lost(Request $request, \Swift_Mailer $mailer)
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
                      var char='/lost?loc='+char2;
                      document.location.href=char;
                    }
                    
                    function error(err) {
                    }
                    
                    navigator.geolocation.getCurrentPosition(success, error, options);
                    
                    </script>
                    ";};
        $form = $this->createForm(LostType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $lostFormData = $form->getData();
            $entityManager = $this->getDoctrine()->getManager();
            $Post = new Post();
            $Post->setType("L");
            $Post->setNomPrenom($lostFormData['Nom-Prenom']);
            $Post->setItem($lostFormData['Objet-Perdu']);
            $Post->setDescrip($lostFormData['Description']);
            $Post->setDate($lostFormData['Date-de-la-perte']);
            $Post->setReward($lostFormData['Recompense']);
            $Post->setTel($lostFormData['Tel']);
            $Post->setEmail($lostFormData['Email']);
            $lat=0;
            $lon=0;
            $d=null;
            if ( !empty( $_GET['loc'])){
                $var=$_GET['loc'];
                $keywords = preg_split("/[\s,]+/",$var);
                $Post->setLatitude((float) ($keywords[0]));
                $Post->setLongitude((float) ($keywords[1]));
                $lat=(float) ($keywords[0]);
                $lon=(float) ($keywords[1]);
            }

            $entityManager->persist($Post);
            $entityManager->flush();

            $char=$lostFormData['Nom-Prenom']. PHP_EOL
                .' Nous venons de recevoir votre demande Lost'. PHP_EOL
                .' Nous vous tiendrons au courant!!'. PHP_EOL.'Cordialement';
            $message = (new \Swift_Message('Lost and Found Enit!!'))
                ->setFrom($lostFormData['Email'])
                ->setTo($lostFormData['Email'])
                ->setBody(
                    $char, 'text/plain');

            $mailer->send($message);


            $em = $this->getDoctrine()->getManager();
            $x=$lostFormData['Objet-Perdu'];
            $d=$lostFormData['Date-de-la-perte'];
            $RAW_QUERY = "SELECT id,item,latitude,longitude,nom_prenom,tel,email,date FROM post where post.type='F';";
            // where CompareString($x,post.item)>=80
            $statement = $em->getConnection()->prepare($RAW_QUERY);
            $statement->execute();
            $result = $statement->fetchAll();
            foreach ($result as $resul) {
                if ((CompareString($x,$resul['item'])>=70)&&(DistAB($lat,$resul['latitude'],$lon,$resul['longitude'])<0.600)&&($d >= $resul['date'] ))
                {
                    $char=$lostFormData['Nom-Prenom']. PHP_EOL . 'Nous venons de localiser votre '. $lostFormData['Objet-Perdu'].
                        ' trouvé par '. $resul['nom_prenom'].' à '.DistAB($lat,$resul['latitude'],$lon,$resul['longitude']).
                            ' KLM. Le '.$resul['date']. PHP_EOL .'Tel: '.$resul['tel']. PHP_EOL
                        .'Email: '.$resul['email']. PHP_EOL
                        . 'Iteneraire: '.LinkA($lat,$resul['latitude'],$lon,$resul['longitude']). PHP_EOL.'Cordialement';
                    $message = (new \Swift_Message('Objet trouvé!!'))
                        ->setFrom($lostFormData['Email'])
                        ->setTo($lostFormData['Email'])
                        ->setBody(
                            $char, 'text/plain');
                    $mailer->send($message);



                    //Informer celui qui a trouvé
                    $char=$resul['nom_prenom']. PHP_EOL
                        .' Nous venons de trouver une piste sur '. $resul['item'].' perdu.'. PHP_EOL
                        .' Le concerné va vous contacter'. PHP_EOL.'Cordialement';
                    $message = (new \Swift_Message('L objet est trouvé!!'))
                        ->setFrom($resul['email'])
                        ->setTo($resul['email'])
                        ->setBody(
                            $char, 'text/plain');

                    $mailer->send($message);


                    break;

                }
            }



            return $this->redirectToRoute('lost');
        }







        return $this->render('lost/index.html.twig', [
            'our_form' => $form->createView()
        ]);

    }
}
