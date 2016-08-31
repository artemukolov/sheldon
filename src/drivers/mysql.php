<?php

    namespace Sheldon\driver;

	class mysql
    {
        private static function parseFilter($table, $f)
        {
            $flt = "";
            if (isset($f['raw'])){
                $flt .= ($flt == "" ? "" : " AND ").$f['raw'];
            } else {
                if (is_array($f['field']) <> is_array($f['value'])){
                    \Sheldon::error("Error to parse where structure!");
                } else{
                    if (is_array($f['field'])){
                        $flt .= self::parseFilter($table, $f['field'])
                                ." ".$f["modificator"]." "
                                .self::parseFilter($table, $f['value']);
                    } else {
                        if ($f['operator'] == 'in'){
                            if (isset($f['value'])) {
                                $arr = "";
                                foreach ($f['value'] as $v) {
                                    $arr .= ($arr == '' ? '' : ', ') . "'" . $v . "'";
                                }
                                $flt .= ($flt == "" ? "" : " ".$f["modificator"]." ") . "" . $table.".".$f['field'] . " IN (" . $arr . ")";
                            } else {
                                return "";
                            }
                        } elseif ($f['operator'] == 'notin'){
                            if (isset($f['value'])) {
                                $arr = "";
                                foreach ($f['value'] as $v) {
                                    $arr .= ($arr == '' ? '' : ', ') . "'" . $v . "'";
                                }
                                $flt .= ($flt == "" ? "" : " ".$f["modificator"]." ") . "" .$table.".". $f['field'] . " NOT IN (" . $arr . ")";
                            } else {
                                return "";
                            }
                        }
                        else {
                            $flt .= ($flt == "" ? "" : " ".$f["modificator"]." ") . "" .$table.".". $f['field'] . " " . $f['operator'] . " " . (trim($f['value']) == ""? "": "'".$f['value']."'") ;
                        }
                    }
                }
            }
            return "(".$flt.")";
        }

        private static function parseFilters($table, $filters, $flt)
        {
            foreach ($filters as $f) {
                $flt .= self::parseFilter($table, $f);
            }
            return $flt;
        }

        private static function getWhereRow($table, $data)
        {
            $flt = "";
            if (isset($data["filters"])){
                $flt = self::parseFilters($table, $data["filters"], $flt);
            }
            if (isset($data["joins"])){
                foreach ($data["joins"] as $jTable => $j){
                    if (is_object($j['object'])){
                        if (isset($j['object']->filters)){
                            $flt = self::parseFilters($jTable, $j['object']->filters, $flt);
                        }
                    }
                }
            }
            $flt = ($flt == ""? "": " WHERE ".$flt);
            return $flt;
        }

        public static function constructInsert($table, $data)
        {
            $keys = "";
            $values = "";
            foreach ($data as $keyN=>$valueN){
                $keys .= ($keys == ""? "":",")."`".$keyN."`";
                $values .= ($values == ""? "":",")."'".$valueN."'";
            }
            $query = "INSERT INTO `".$table."` (".$keys.") VALUES (".$values.")";
            return $query;
        }

        public static function constructUpdate($table, $data, $changeData)
        {
            $changedRow = "";
            foreach ($changeData as $keyN=>$valueN){
                $changedRow .=($changedRow==""? "":",")."`".$keyN."`='".$valueN."'";
            }
            $flt = self::getWhereRow($table, $data);
            $query = "UPDATE `" . $table . "` SET " . $changedRow . $flt;
            return $query;
        }

        public static function constructDelete($table, $data)
        {
            $q = "DELETE FROM ";
            $q .= $table;
            $q .= self::getWhereRow($table, $data);
            return $q;
        }

        public static function constructSelect($table, $data)
        {
            if ($data["rawString"]){
                return $data["rawString"];
            }
            $q = "SELECT ";
            $fields = "";
            if (isset($data["fields"])){
                if (count($data["fields"]) > 0){
                    foreach ($data["fields"] as $f){
                        $mod_ = (array_key_exists($f, $data['modificators'])? $data['modificators'][$f]: false);
                        $fields .= ($fields == ""? "":",").
                            ($mod_? $mod_."(": "").
                            $table.".".$f.
                            ($mod_? ")": "").
                            (!(array_key_exists($f, $data['aliases']) == false) ? " AS ".$data['aliases'][$f]:"");

                    }
                }
            }

            if (isset($data["joins"])){
                foreach ($data["joins"] as $tableJoin=>$j) {
                    foreach ($j["fields"] as $f) {
                        $fields .= ($fields == ""? "":",").$tableJoin.".".$f.(!(array_key_exists($f, $j["aliases"]) == false) ? " AS ".$j['aliases'][$f]:"");
                    }
                }
            }
            if ($fields == ""){$fields = "*";}
            $q = $q.$fields." FROM ".$table;
            $jns = "";
            if (isset($data["joins"])) {
                foreach ($data["joins"] as $tableJoin=>$j) {
                    $jn = "";
                    foreach ($j["comparisons"] as $c) {
                        $jn .= ($jn == ""? " ".$j["mode"]." JOIN ".$tableJoin. " ON " :" AND "). " " .$table.".".$c[0]." ".$c[1]." ".$tableJoin.".".$c[2];
                    }
                    $jns .= $jn;
                }
            }
            $flt = self::getWhereRow($table, $data);
            $ord = "";
            if (isset($data["orders"])){
                foreach ($data["orders"] as $ordrec){
                    $ord = ($ord == "" ? "": ",")." ".$ordrec['field']." ".(isset($ordrec['order']) ? $ordrec['order'] : "");
                }
            }
            $lim = "";
            if (isset($data["limit"])){
                if (count($data["limit"]) > 0){
                    $lim = (isset($data["limit"]['offset'])?$data["limit"]['offset'] : "0").", ".$data["limit"]['limit'];
                }
            }
            if ($jns<>""){$q .= $jns;}
            $q .= $flt;
            if ($ord<>""){$q .= ' ORDER BY '.$ord;}
            if ($lim<>""){$q .= ' LIMIT '.$lim;}
            //echo $q;
            return $q;
        }
    }