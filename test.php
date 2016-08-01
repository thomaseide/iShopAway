<?php
	
	
	$name = "kartik,abc,xyz";	
	$names = array("kartik1","kartik2","kartik3");
	
	$name_array = explode(",",$name);
	print_r($name_array);
	echo $name_array[0];
	
	
	$city_array=array("vadodara","surat","ahmeadabd");
	var_dump($city_array);
	$city = implode("-",$city_array);
	
	echo $city;
	
	echo $name;
	
	

?>