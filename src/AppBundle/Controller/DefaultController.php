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
     * @Route("/getHtml", name="getHtml")
     */
    public function indexAction(Request $request)
    {
        // API to get html contents from a given Url list

        // Input Json should be like that ...
        // {"urls":["http://www.wemall.com/products/mykronoz-smartwatch-zefit2-2726126690391.html", "http://www.wemall.com/products/golife-by-papago-heart-black-2735662599781.html"]}

        // sent POST request to http://your_host_name/getHtml

        $content = $request->getContent();
        $content = json_decode($content,true);

        $urls = $content['urls'];

        $results = array();

        foreach ($urls as $url){
            $data = $this->get_data($url);
            $results[] = array("url"=>$url,"data"=>mb_convert_encoding($data, "UTF-8", "auto"));
        }

        $res =  new JsonResponse($results);
        $res->headers->set('Content-Type', 'application/json');

        return $res;

    }

    function get_data($url) {

        // getting html data from a given url
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
        // API to get Urls which consist of a given website

        // In the input,
        // have to set pagination key '$$' to the given url
        // choose 2nd or after pages of the pagination to do that cause 1st page has not pagination query in the url like _pgn=3
        // set 'class' to filter specific Urls in the website (if any)
        // set 'from' with start page of the pagination and 'to' for the end

        // Input Json should be like that ...
        // {"url":"https://www.ebay.com/sch/Moisturizers/21205/i.html?_pgn=$$&_skc=100", "class":".vip", "from":"1", "to":"3"}

        // sent POST request to http://your_host_name/getUrls

        $content = $request->getContent();
        $content = json_decode($content,true);

        $url = $content['url'];
        $cls = $content['class'];
        $minPg = $content['from'];
        $maxPg = $content['to'];

        $linksPgArr = array();

        foreach (range($minPg,$maxPg) as $pageNum) {

            // iterate through given pages

            $_url = str_replace('$$',$pageNum,$url);

            $linksArr = $this->getLinks($_url, $cls);
            $linksPgArr = array_merge($linksPgArr, $linksArr);
        }

//        $linksPgUArr = array_unique($linksPgArr);

        $res =  new JsonResponse($linksPgArr);
        $res->headers->set('Content-Type', 'application/json');

        return $res;
    }

    private function getLinks($_url, $cls){

        $client = new Client();
        $crawler = $client->request('GET', $_url);

        // find Urls for a given class
        $links = $crawler->filter('a'.$cls)->links();

        $linksArr = array();

        foreach ($links as $link) {
            $link = $link->getUri();

            // checks if Urls are in the same host
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
        // API to get links consist of a given XML file

        // Input Json should be like that ...
        // {"url":"http://www.wemall.com/products.xml"}

        // sent POST request to http://your_host_name/getLinks

        $content = $request->getContent();
        $content = json_decode($content,true);

        $url = $content['url'];

        $linksArr = $this->getXMLLinks($url);

//        $linksPgUArr = array_unique($linksPgArr);

        $res =  new JsonResponse($linksArr);
        $res->headers->set('Content-Type', 'application/json');

        return $res;
    }

    private function getXMLLinks($url){

        $linksArr = array();

        $xml = simplexml_load_file($url);

        // find links by XML tags
        foreach ($xml->children() as $loc){
            array_push($linksArr, (string) $loc->loc);
        }

        return $linksArr;
    }
}
