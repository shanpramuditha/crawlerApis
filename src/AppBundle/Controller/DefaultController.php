<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Goutte\Client;

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

    /**
     * @Route("/getUrls", name="getUrls" )
     */
    public function getUrlAction(Request $request)
    {
        $content = $request->getContent();
        $content = json_decode($content,true);

        $url = $content['url'];
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $cls = $content['class'];
        $pgType = $content['pgType'];
        $minPg = $content['minPg'];
        $maxPg = $content['maxPg'];

        if ($pgType=='p'){

        }

        elseif ($pgType=='q'){

            $qKey = $content['qKey'];

            $linksPgArr = array();
            foreach (range($minPg,$maxPg) as $pageNum){

                parse_str($query, $qs);
                $qs[$qKey] = $pageNum;

                $_url = $scheme.'://'.$host.$path.'?'.http_build_query($qs);

                $linksArr = $this->getLinks($_url, $cls);
                $linksPgArr = array_merge($linksPgArr,$linksArr);
            }
        }

        $linksPgUArr = array_unique($linksPgArr);

        $res =  new JsonResponse($linksPgUArr);
        $res->headers->set('Content-Type', 'application/json');
        return $res;
    }

    private function getLinks($_url, $cls){
        $client = new Client();
        $crawler = $client->request('GET', $_url);
        $links = $crawler->filter('a'.$cls)->links();

        $linksArr = array();
        foreach ($links as $link) {
            $link = $link->getUri();
            if (parse_url($_url, PHP_URL_HOST) == parse_url($link, PHP_URL_HOST)){
                array_push($linksArr, $link);
            }
        }

        return $linksArr;
    }
}
