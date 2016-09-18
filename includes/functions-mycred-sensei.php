<?php

function filter_by_value($array, $index, $value){ 
    $newarray = array();
    if(is_array($array) && count($array)>0){ 
        foreach(array_keys($array) as $key){ 
            $temp[$key] = $array[$key][$index]; 
             
            if ($temp[$key] == $value){ 
                $newarray[$key] = $array[$key]; 
            } 
        } 
      } 
  return $newarray; 
} 