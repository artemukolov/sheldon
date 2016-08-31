<?php

    abstract class SheldonPaginator
    {
        public static function paginate($array, $keys, $count){
            $response = [];
            $i = 0;
            $keys = json_decode($keys);
            foreach ($array as $key=>$value){
                if (!in_array($key, $keys)){
                    $response[$key] = $value;
                    $i++;
                }

                if ($i>=$count){break;}
            }

            return $response;
        }
    }