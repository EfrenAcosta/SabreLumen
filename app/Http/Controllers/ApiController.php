<?php

namespace App\Http\Controllers;

# Package
use App\Helpers\SabreAPI;
use Laravel\Lumen\Routing\Controller as BaseController;

class ApiController extends BaseController
{
    /**
     *    Get
     */
    public function getDestinations ()
    {
        # get
        $sabre = new SabreAPI();
        $origin = 'ORD'; //"ORD" (Chicago O'Hare International)
        $lookbackweeks = 8;
        $topdestinations = 5;
        $api_uri = '/lists/top/destinations?origin='.$origin
            .'&lookbackweeks='.$lookbackweeks
            .'&topdestinations='.$topdestinations;

        $response = $sabre->get($api_uri);


        return response()->json($response);
    }

    public function getFlights($destination, $departureDate, $returnDate, $passengerCount)
    {
        $sabre = new SabreAPI();
        $origin = 'ORD';
        //$destination = '';
        //$departureDate = '';
        //$returnDate = '';
        $limit = 20; //Numeromaximo de resultados
        //$passengerCount = ''; //Numero de pasajeros
        $outBoundStopDuration = '';

        $api_uri = '/shop/flights?origin='.$origin
            .'&destination='.$destination
            .'&departuredate='.$departureDate
            .'&returndate='.$returnDate
            .'&passengerCount='.$passengerCount
            .'&limit='.$limit
            ;

        $response = $sabre->get($api_uri);


        return response()->json($response);

    }


    public function getAirports ($code)
    {
        $sabre = new SabreAPI();
        $result = $sabre->airports($code);
        return response()->json($result);
    }
}