<?php
namespace App\Helpers;

use Httpful\Request as Request;


class SabreAPI
{

    const TOKEN_KEY = 'token';
    const API_URL_KEY = "apiUrl";
    const API_TOKEN_URL_KEY = "apiTokenUrl";
    const CLIENT_ID_KEY = "clientId";
    const CLIENT_SECRET_KEY = "clientSecret";

    private $db;
    private $apiUrl;
    private $apiTokenUrl;
    private $secret;

    public function __construct()
    {

        $this->db = FileDB::entity('main');
        $this->apiUrl = $this->db->get(self::API_URL_KEY);
        $this->apiTokenUrl = $this->db->get(self::API_TOKEN_URL_KEY);

        $clientId = $this->db->get(self::CLIENT_ID_KEY);
        $clientSecret = $this->db->get(self::CLIENT_SECRET_KEY);
        $this->secret = base64_encode(base64_encode($clientId) . ":" . base64_encode($clientSecret));
    }

    function get($uri)
    {
        $db = FileDB::entity('main');
        $token = session(self::TOKEN_KEY,"");

        if (empty($token)) {
            $token = $this->updateToken($this->secret);
        }

var_dump($token);
exit;

        $response = Request::get($this->apiUrl . $uri)
            ->addHeader("Authorization", "Bearer " . $token)
            ->send();

        $body = $response->body;
        if ($response->code != 200 && ($body->status == "NotProcessed" && $body->errorCode == "ERR.2SG.SEC.INVALID_CREDENTIALS")) {
            $token = $this->updateToken($this->secret);
            $response = Request::get($this->apiUrl . $uri)
                ->addHeader("Authorization", "Bearer " . $token)
                ->send();
            $body = $response->body;
        }
        return $body;
    }

    function updateToken($secret)
    {
        $token = $this->getToken($secret);
        session()->put(self::TOKEN_KEY, $token);

        return $token;
    }

    function getToken($secret)
    {
        $headers = array("Authorization" => "Basic " . $secret, "Content-Type" => "application/x-www-form-urlencoded");


        $response = Request::post($this->apiTokenUrl . "/auth/token")
            ->addHeaders($headers)
            ->body("grant_type=client_credentials")
            ->send();

        $resArr = json_decode($response);
        if (array_key_exists('access_token', $resArr)) {
            return $resArr->access_token;
        }else{
            return "";
        }

    }

    function getFirstToken(){
        $token = $this->getToken($this->secret);
        $db = FileDB::entity('main');
        $token = $this->updateToken($this->secret);


        return $token;
    }

    function translate($airportCode)
    {
        $airports = FileDB::entity('airports');
        $row = $airports->filterOne('code', $airportCode);
        if (isset($row)) {
            return array('longitude' => $row['lon'], 'latitude' => $row['lat'], 'city' => $row['city']);
        }
    }

    function airports($code)
    {
        $airports = FileDB::entity('airports');
        return $airports->filter('code', $code);
    }

    function formDestinationFinderResponse($input)
    {
        $list = new Collection();
        $data = json_decode($input, true);

        if (isset($data['FareInfo'])) {
            $fares = $data['FareInfo'];
            foreach ($fares as $fare) {

                $fareInfo = new FareInfo();
                $airPortCode = $fare['DestinationLocation'];
                if ($list->keyExists($airPortCode)) {
                    $fareInfo = $list->getItem($airPortCode);
                } else {
                    $geo = $this->translate($airPortCode);
                    $fareInfo->id = $airPortCode;
                    $fareInfo->coords = new Geo($geo['latitude'], $geo['longitude']);
                    $fareInfo->city = $geo['city'];
                    $fareInfo->currencyCode = $fare['CurrencyCode'];
                    if (isset($fare['DestinationRank'])) {
                        $fareInfo->destinationRank = $fare['DestinationRank'];
                    }
                    if (isset($fare['Theme'])) {
                        $fareInfo->theme = $fare['Theme'];
                    }

                    $list->addItem($fareInfo, $airPortCode);
                }

                $fareObj = new Fare();
                $fareObj->lowestFare = $fare['LowestFare'];
				$fareObj->lowestNonStopFare = $fare['LowestNonStopFare'];
                $fareObj->departureDateTime = $fare['DepartureDateTime'];
                $fareObj->returnDateTime = $fare['ReturnDateTime'];
                $fareInfo->fares[] = $fareObj;
            }
        }

        return json_encode(array_values($list->items));

    }

} 
