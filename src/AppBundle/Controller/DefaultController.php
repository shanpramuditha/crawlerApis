<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Goutte\Client;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/",name="homepage")
     */
    public function homepageAction(Request $request){
       return $this->render('index.html.twig');
    }

    public function indexAction($urls,$fileName)
    {
        $results = array();

        foreach ($urls as $url){
            $data = $this->get_data($url);
            $results[] = array("url"=>$url,"data"=>mb_convert_encoding($data, "UTF-8", "auto"));
        }

        header('Content-disposition: attachment; filename='.$fileName.'.json');
        header('Content-type: application/json');
        echo json_encode($results);

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
        $url = $request->get('url');
        $cls = $request->get('cls');
        $minPg = (int)$request->get('from');
        $maxPg = (int)$request->get('to');
        $fileName = $request->get('filename');
        $fileName = $fileName.'-'.(string)$minPg.'-'.(string)$maxPg;

        $linksPgArr = array();

        foreach (range($minPg,$maxPg) as $pageNum) {

            $_url = str_replace('$$',$pageNum,$url);

            $linksArr = $this->getLinks($_url, $cls);
            $linksPgArr = array_merge($linksPgArr, $linksArr);
        }

//        $linksPgUArr = array_unique($linksPgArr);

        header('Content-disposition: attachment; filename='.$fileName.'.json');
        header('Content-type: application/json');
        echo json_encode($linksPgArr);

        return new Response('done');
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

    /**
     * @Route("/getLinks", name="getLinks" )
     */
    public function getLinksAction(Request $request)
    {
        $url = $request->get('url');
        $filename = $request->get('filename');
        $from = (int)$request->get('from');
        $to = (int)$request->get('to');
        $linksArr = $this->getXMLLinks($url);
        $linksArr = array_slice($linksArr,$from,($to-$from));

        $this->indexAction($linksArr,$filename.' | '.(string)$from.'-'.(string)$to);

        return new Response('done');
    }

    private function getXMLLinks($url){

        $linksArr = array();

        $xml = simplexml_load_file($url);

        foreach ($xml->sitemap as $map) {
            $loc = $map->loc;
            $loc = json_decode(json_encode($loc),true);
            array_push($linksArr, $loc[0]);
        }

        return $linksArr;
    }

    /**
     * @Route("/getXmlCount", name="get_xml_count")
     */
    public function getXmlCount(Request $request){
        $url = $request->get('url');
        $linksArr = array();

        $xml = simplexml_load_file($url);

        foreach ($xml->sitemap as $map) {
            $loc = $map->loc;
            $loc = json_decode(json_encode($loc),true);
            array_push($linksArr, $loc[0]);
        }

        return new Response(count($linksArr));
    }

    /**
     * @Route("/getUrlCount", name="get_url_count")
     */
    public function getUrlCount(Request $request){

        $url = $request->get('url');
        $cls = $request->get('cls');
        $minPg = (int)$request->get('from');
        $maxPg = (int)$request->get('to');

        $linksPgArr = array();

        foreach (range($minPg,$maxPg) as $pageNum) {

            $_url = str_replace('$$',$pageNum,$url);

            $linksArr = $this->getLinks($_url, $cls);
            $linksPgArr = array_merge($linksPgArr, $linksArr);
        }

        return new Response(count($linksPgArr));
    }
}
