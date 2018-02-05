<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/html", name="htmlApi")
     */
    public function indexAction(Request $request)
    {
        $content = $request->getContent();//get json data
        $content = json_decode($content,true);
        $urls = $content['urls'];
        $response = array();
        $results = array();
        foreach ($urls as $url){
            $data = $this->get_data($url);
            $results[] = array("url"=>$url,"data"=>mb_convert_encoding($data, "UTF-8", "auto"));
        }
        $response["results"] = $results;
        $res =  new JsonResponse($response);
        $res->headers->set('Content-Type', 'application/json');
        return $res;

    }

    function get_data($url) {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}
