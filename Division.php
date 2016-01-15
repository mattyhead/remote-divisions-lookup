<?php
/**
 * Quickie class to turn a first address line into the highest rated candidate philly location
 * using the city's findAddressCandidates GIS service
 *
 */
class Division
{
    public static function lookup($address1)
    {
        //get division data
        $url = "http://gis.phila.gov/arcgis/rest/services/ElectionGeocoder/GeocodeServer/findAddressCandidates";
        // candidate fields:
        // shape,score,match_addr,house,side,predir,pretype,streetname,suftype,sufdir,city,state,zip,ref_id,blockid,division,match,addr_type
        $fields = "division";
        $params = "Street=" . urlencode($address1) . "&outFields=" . urlencode($fields) . "&f=pjson";
        if (function_exists(curl_init)) {
            $curl = curl_init($url . "?" . $params);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        } else {
            return array('status' => 'failure', 'data' => array('message' => 'function \'curl_init\' not available.  is curl available?'));
        }
        try {
            $response = curl_exec($curl);
            curl_close($curl);
            $json = json_decode($response);

            $division = $lon = $lat = '';
            switch (sizeof($json->candidates)) {
                case 0:
                    // do nothing
                    break;
                case 1:
                    $division = (string) $json->candidates[0]->attributes->division;
                    $lon = (string) $json->candidates[0]->location->x;
                    $lat = (string) $json->candidates[0]->location->y;
                    break;
                default:
                    // sort our candidates by score -- we want the highest score result
                    uasort($json->candidates, function ($a, $b) {
                        if ($a->score == $b->score) {
                            return 0;
                        }
                        return ($a->score > $b->score) ? -1 : 1;
                    });
                    $division = (string) $json->candidates[0]->attributes->division;
                    $lon = (string) $json->candidates[0]->location->x;
                    $lat = (string) $json->candidates[0]->location->y;
                    break;
            }
        } catch (Exception $e) {
            return array('status' => 'failure', 'data' => array('message' => 'retrieval failure.', 'exception' => $e));
        }
        return array('status' => 'success', 'data' => array('message' => '', 'division' => $division, 'lon' => $lon, 'lat' => $lat, 'response' => $json));
    }
}
